<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
 *
 * This file is part of Subrion.
 *
 * Subrion is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Subrion is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Subrion. If not, see <http://www.gnu.org/licenses/>.
 *
 *
 * @link https://subrion.org/
 *
 ******************************************************************************/

require_once IA_INCLUDES . 'helpers/ia.category.interface.php';

abstract class iaAbstractHelperCategoryFlat extends abstractModuleAdmin implements iaAbstractHelperCategoryInterface
{
    const ROOT_PARENT_ID = 0;

    const COL_PARENT_ID = 'parent_id';
    const COL_LEVEL = 'level';

    protected static $_tableFlat;

    private $_root;


    public static function getTableFlat($prefix = false)
    {
        if (is_null(self::$_tableFlat)) {
            self::$_tableFlat = self::getTable() . '_flat';
        }

        return ($prefix ? iaCore::instance()->iaDb->prefix : '')
            . self::$_tableFlat;
    }

    protected static function _cols($sql)
    {
        return str_replace(
            [':col_pid', ':col_level', ':root_pid'],
            [self::COL_PARENT_ID, self::COL_LEVEL, self::ROOT_PARENT_ID],
            $sql
        );
    }

    public function setupDbStructure()
    {
        $queries = [
            'ALTER TABLE `:table_data` ADD `:col_pid` mediumint(8) unsigned NOT NULL default 0',
            'ALTER TABLE `:table_data` ADD `:col_level` tinyint(3) unsigned NOT NULL default 0',
            'ALTER TABLE `:table_data` ADD INDEX `PARENT` (`:col_pid`)',
            'CREATE TABLE IF NOT EXISTS `:table_flat` ('
                . '`parent_id` int(8) unsigned NOT NULL, '
                . '`category_id` int(8) unsigned NOT NULL, '
                . '`level` tinyint(1) unsigned NOT NULL, '
                . 'UNIQUE KEY `UNIQUE` (`parent_id`,`category_id`)'
            . ') :options'
        ];

        foreach ($queries as $query) {
            $sql = iaDb::printf($query, [
                'table_data' => self::getTable(true),
                'table_flat' => self::getTableFlat(true),
                'options' => $this->iaDb->tableOptions
            ]);

            $this->iaDb->query(self::_cols($sql));
        }

        $this->_insertRoot();
    }

    public function resetDbStructure()
    {
        $iaDbControl = $this->iaCore->factory('dbcontrol', iaCore::ADMIN);

        $iaDbControl->truncate(self::getTable());
        $iaDbControl->truncate(self::getTableFlat());

        $this->_insertRoot();
    }

    protected function _insertRoot()
    {
        $row = [
            'id' => 1,
            self::COL_PARENT_ID => self::ROOT_PARENT_ID,
            self::COL_LEVEL => 0
        ];

        foreach ($this->iaCore->languages as $iso => $language)
            $row['title_' . $iso] = 'ROOT';

        $this->iaDb->insert($row, null, self::getTable());

        $row = ['parent_id' => 1, 'category_id' => 1, 'level' => 0];

        $this->iaDb->insert($row, null, self::getTableFlat());
    }

    public function getRoot()
    {
        if (is_null($this->_root)) {
            $this->_root = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds(self::ROOT_PARENT_ID, self::COL_PARENT_ID), self::getTable());
            $this->_processValues($this->_root, true);
        }

        return $this->_root;
    }

    public function getRootId()
    {
        return $this->getRoot()['id'];
    }


    public function insert(array $itemData)
    {
        $this->_assignStructureData($itemData);

        return parent::insert($itemData);
    }

    public function update(array $itemData, $id)
    {
        $this->_assignStructureData($itemData);

        return parent::update($itemData, $id);
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        //if (iaCore::ACTION_ADD == $action ||
            //(iaCore::ACTION_EDIT == $action && isset($itemData[self::COL_PARENT_ID]) && $previousData[self::COL_PARENT_ID] != $itemData[self::COL_PARENT_ID])) {
            $this->_updateFlatStructure($itemId);
        //}
    }


    /**
     * Rebuild categories relations.
     * Fields to be updated: parents, child, level, title_alias
     */
    public function rebuildRelations()
    {
        $table = self::getTable(true);
        $tableFlat = self::getTableFlat(true);

        $iaDb = &$this->iaDb;

        $sql1 = 'INSERT INTO ' . $tableFlat . ' SELECT t.`id`, t.`id`, t.`:col_level` FROM ' . $table . ' t WHERE t.`:col_pid` != :root_pid ORDER BY t.`:col_level`';
        $sql2 = 'INSERT INTO ' . $tableFlat . ' SELECT t.`:col_pid`, t.`id`, t.`:col_level` FROM ' . $table . ' t WHERE t.`:col_pid` != :root_pid ORDER BY t.`:col_level`';

        $iaDb->truncate($tableFlat);

        $iaDb->query(self::_cols($sql1));
        $iaDb->query(self::_cols($sql2));

        $num = 1;
        $count = 0;

        while ($num > 0 && $count < 10) {
            $count++;
            $num = 0;
            $sql = 'INSERT INTO ' . $tableFlat . ' '
                . 'SELECT DISTINCT t.`id`, h' . $count . '.`id`, h' . $count . '.`:col_level` '
                . 'FROM ' . $table . ' t, ' . $table . ' h0 ';
            $where = ' WHERE h0.`:col_pid` = t.`id` ';

            for ($i = 1; $i <= $count; $i++) {
                $sql .= 'LEFT JOIN ' . $table . ' h' . $i . ' ON (h' . $i . '.`:col_pid` = h' . ($i - 1) . '.`id`) ';
                $where .= ' AND h' . $i . '.`id` IS NOT NULL';
            }

            if ($iaDb->query(self::_cols($sql . $where))) {
                $num = $iaDb->getAffected();
            }
        }

        $sqlLevel = 'UPDATE ' . $table . ' s SET `:col_level` = (SELECT COUNT(`category_id`)-1 FROM ' . $tableFlat . ' f WHERE f.`category_id` = s.`id`) WHERE s.`:col_pid` != :root_pid';

        $iaDb->query(self::_cols($sqlLevel));

        $iaDb->update(['order' => 1], iaDb::convertIds(0, 'order'), null, self::getTable());
    }

    public function rebuildAliases($id)
    {
        $this->iaDb->setTable(self::getTable());

        $category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
        $path = $this->_getPathForRebuild($category['title'], $category[self::COL_PARENT_ID]);
        $this->iaDb->update(['title_alias' => $path], iaDb::convertIds($category['id']));

        $this->iaDb->resetTable();
    }

    protected function _getPathForRebuild($title, $pid, $path = '')
    {
        static $cache;

        $str = preg_replace('#[^a-z0-9_-]+#i', '-', $title);
        $str = trim($str, '-');
        $str = str_replace("'", '', $str);

        $path = $path ? $str . '/' . $path : $str . '/';

        if ($pid != 1) {
            if (isset($cache[$pid])) {
                $parent = $cache[$pid];
            } else {
                $parent = $this->iaDb->row(['id', self::COL_PARENT_ID, 'title' => 'title_' . iaLanguage::getMasterLanguage()->code], iaDb::convertIds($pid));

                $cache[$pid] = $parent;
            }

            $path = $this->_getPathForRebuild($parent['title'], $parent[self::COL_PARENT_ID], $path);
        }

        return $path;
    }

    public function getCount()
    {
        return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable());
    }

    public function getTreeVars($id, array $entryData, $url)
    {
        $parent = empty($entryData[self::COL_PARENT_ID])
            ? $this->getRoot()
            : $this->getById($entryData[self::COL_PARENT_ID]);

        $url .= 'tree.json';
        if (iaCore::ACTION_EDIT == $this->iaView->get('action')) {
            $url .= '?cid=' . $id;
        }

        $nodes = [];
        if ($parents = $this->getParents($id)) {
            foreach ($parents as $entry)
                $nodes[] = $entry['id'];
        }

        return [
            'url' => $url,
            'nodes' => implode(',', $nodes),
            'id' => $parent['id'],
            'title' => $parent['title']
        ];
    }

    public function getJsonTree(array $data)
    {
        $output = [];

        $this->iaDb->setTable(self::getTable());

        $dynamicLoadMode = ((int)$this->iaDb->one(iaDb::STMT_COUNT_ROWS) > 150);
        $noRootMode = isset($data['noroot']) && '' == $data['noroot'];

        $rootId = $noRootMode ? 1 : 0; // TODO: hardcoded '1' should be reviewed
        $parentId = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : $rootId;

        $where = $dynamicLoadMode
            ? '`:col_pid` = ' . $parentId
            : ($noRootMode ? '`id` != ' . $rootId : iaDb::EMPTY_CONDITION);

        // TODO: better solution should be found here. this code will break jstree composition in case if
        // category to be excluded from the list has children of 2 and more levels deeper
        if (!empty($data['cid'])) {
            $where .= sprintf(' AND `id` != %d AND `:col_pid` != %d', $data['cid'], $data['cid']);
        }

        $where .= ' ORDER BY `title`';

        $rows = $this->iaDb->all(['id', 'title' => 'title_' . $this->iaCore->language['iso'], self::COL_PARENT_ID], $where);

        foreach ($rows as $row) {
            $entry = ['id' => $row['id'], 'text' => $row['title']];

            if ($dynamicLoadMode) {
                $entry['children'] = true;//($row[self::COL_CHILDREN] && $row[self::COL_CHILDREN] != $row['id']) || 0 === (int)$row['id'];
            } else {
                $entry['state'] = [];
                $entry['parent'] = $noRootMode
                    ? ($rootId == $row[self::COL_PARENT_ID] ? '#' : $row[self::COL_PARENT_ID])
                    : (self::ROOT_PARENT_ID == $row[self::COL_PARENT_ID] ? '#' : $row[self::COL_PARENT_ID]);
            }

            $output[] = $entry;
        }

        return $output;
    }

    // tree utulity methods
    public function getParents($entryId)
    {
        $where = sprintf('`id` IN (SELECT `parent_id` FROM `%s` WHERE `category_id` = %d)',
            self::getTableFlat(true), $entryId);

        return $this->_get($where);
    }

    public function getChildren($entryId)
    {
        $where = sprintf('`id` IN (SELECT `category_id` FROM `%s` WHERE `parent_id` = %d)',
            self::getTableFlat(true), $entryId);

        return $this->_get($where);
    }

    // utility methods
    protected function _get($where, $order = null, $start = null, $limit = null)
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . $order, $start, $limit, self::getTable());

        $this->_processValues($rows);

        return $rows;
    }

    protected function _assignStructureData(array &$entryData)
    {
        if (isset($entryData[self::COL_PARENT_ID])) {
            $parent = $this->getById($entryData[self::COL_PARENT_ID], false);

            if ($parent) {
                $entryData[self::COL_LEVEL] = $parent[self::COL_LEVEL] + 1;
            }
        }
    }

    // flat structure
    protected function _updateFlatStructure($entryId)
    {
        $this->iaDb->setTable(self::getTableFlat());

        $this->iaDb->delete(iaDb::convertIds($entryId, 'category_id'));
        $this->iaDb->delete(iaDb::convertIds($entryId, 'parent_id'));

        if ($entry = $this->getById($entryId, false)) {
            $this->iaDb->insert(['parent_id' => $entryId, 'category_id' => $entryId, 'level' => $entry[self::COL_LEVEL]]);

            // handle parents first
            $entries = [];
            $this->_recursiveCollectParents($entry, $entries);

            foreach ($entries as $row) {
                $this->iaDb->insert(['parent_id' => $row['id'], 'category_id' => $entryId, 'level' => $entry[self::COL_LEVEL]]);
            }

            // then, collect children
            $entries = [];
            $this->_recursiveCollectChildren($entry, $entries);

            foreach ($entries as $row) {
                $this->iaDb->insert(['parent_id' => $entryId, 'category_id' => $row['id'], 'level' => $row[self::COL_LEVEL]]);
            }
        }

        $this->iaDb->resetTable();
    }

    private function _recursiveCollectParents($entry, array &$collection)
    {
        if ($entry) {
            $collection[] = $entry;
            $parent = $this->iaDb->row(['id', self::COL_PARENT_ID, self::COL_LEVEL], iaDb::convertIds($entry[self::COL_PARENT_ID]), self::getTable());

            if ($parent && $parent[self::COL_PARENT_ID] != self::ROOT_PARENT_ID) {
                $this->_recursiveCollectParents($parent, $collection);
            }
        }
    }

    private function _recursiveCollectChildren($entry, array &$collection)
    {
        if ($entry) {
            $collection[] = $entry;
            $children = $this->iaDb->all(['id', self::COL_PARENT_ID, self::COL_LEVEL], iaDb::convertIds($entry['id'], self::COL_PARENT_ID), null, null, self::getTable());

            if ($children) {
                foreach ($children as $child) {
                    $this->_recursiveCollectChildren($child, $collection);
                }
            }
        }
    }
}
