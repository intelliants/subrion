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

abstract class iaAbstractHelperCategoryHybrid extends abstractModuleAdmin implements iaAbstractHelperCategoryInterface
{
    const ROOT_PARENT_ID = 0;

    const COL_PARENT_ID = '_pid';
    const COL_PARENTS = '_parents';
    const COL_CHILDREN = '_children';
    const COL_LEVEL = 'level';

    protected $_flatStructureEnabled = false;

    protected $_tableFlat;

    private $_rootId;


    public function init()
    {
        parent::init();

        if ($this->_flatStructureEnabled) {
            $this->_tableFlat = self::getTable(true) . '_flat';
        }
    }

    public function setupDbStructure()
    {
        $queries = [
            'ALTER TABLE `:table` ADD `_pid` mediumint(8) unsigned NOT NULL default 0',
            'ALTER TABLE `:table` ADD `_parents` tinytext NOT NULL',
            'ALTER TABLE `:table` ADD `_children` text NOT NULL',
            'ALTER TABLE `:table` ADD `level` tinyint(3) unsigned NOT NULL default 0',
            'ALTER TABLE `:table` ADD INDEX `PARENT` (`_pid`)'
        ];

        foreach ($queries as $query) {
            $sql = iaDb::printf($query, ['table' => self::getTable(true)]);
            $this->iaDb->query($sql);
        }

        $this->_insertRoot();

        if ($this->_flatStructureEnabled) {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `:table_name`(
  `parent_id` int(8) unsigned NOT NULL,
  `category_id` int(8) unsigned NOT NULL,
  UNIQUE KEY `UNIQUE` (`parent_id`,`category_id`)
) :options;
SQL;
            $this->iaDb->query(iaDb::printf($sql, ['table_name' => $this->_tableFlat, 'options' => $this->iaDb->tableOptions]));
        }
    }

    public function resetDbStructure()
    {
        $iaDbControl = $this->iaCore->factory('dbcontrol', iaCore::ADMIN);

        $iaDbControl->truncate(self::getTable());
        $this->_flatStructureEnabled && $iaDbControl->truncate($this->_tableFlat);

        $this->_insertRoot();
    }

    protected function _insertRoot()
    {
        $rootEntry = [
            self::COL_PARENT_ID => self::ROOT_PARENT_ID,
            self::COL_PARENTS => '1',
            self::COL_CHILDREN => '1',
            self::COL_LEVEL => 0,

            'id' => 1
        ];

        foreach ($this->iaCore->languages as $iso => $language)
            $rootEntry['title_' . $iso] = 'ROOT';

        $this->iaDb->insert($rootEntry, null, self::getTable());
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
            ? sprintf('`%s` = %d', self::COL_PARENT_ID, $parentId)
            : ($noRootMode ? '`id` != ' . $rootId : iaDb::EMPTY_CONDITION);

        // TODO: better solution should be found here. this code will break jstree composition in case if
        // category to be excluded from the list has children of 2 and more levels deeper
        if (!empty($data['cid'])) {
            $where .= sprintf(' AND `id` != %d AND `%s` != %d', $data['cid'], self::COL_PARENT_ID, $data['cid']);
        }

        $where .= ' ORDER BY `title`';

        $rows = $this->iaDb->all(['id', 'title' => 'title_' . $this->iaCore->language['iso'], self::COL_PARENT_ID, self::COL_CHILDREN], $where);

        foreach ($rows as $row) {
            $entry = ['id' => $row['id'], 'text' => $row['title']];

            if ($dynamicLoadMode) {
                $entry['children'] = ($row[self::COL_CHILDREN] && $row[self::COL_CHILDREN] != $row['id']) || 0 === (int)$row['id'];
            } else {
                $entry['state'] = [];
                $entry['parent'] = $noRootMode
                    ? ($rootId == $row[self::COL_PARENT_ID] ? '#' : $row[self::COL_PARENT_ID])
                    : (1 == $row['id'] ? '#' : $row[self::COL_PARENT_ID]);
            }

            $output[] = $entry;
        }

        return $output;
    }

    public function getRootId()
    {
        if (is_null($this->_rootId)) {
            $this->_rootId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds(self::ROOT_PARENT_ID, self::COL_PARENT_ID), self::getTable());
        }

        return $this->_rootId;
    }

    public function getRoot()
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($this->getRootId()), self::getTable());

        $this->_processValues($row, true);

        return $row;
    }

    public function update(array $itemData, $id)
    {
        // makes impossible to change the alias for the root
        if ($this->getRootId() == $itemData[self::COL_PARENT_ID] && isset($itemData['title_alias'])) {
            unset($itemData['title_alias']);
        }

        return parent::update($itemData, $id);
    }

    public function delete($itemId)
    {
        if ($itemId == $this->getRootId()) {
            return false;
        }

        return parent::delete($itemId);
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        $this->rebuildRelations($itemId);
    }

    public function rebuildRelations($id = null)
    {
        return is_null($id)
            ? $this->_rebuildAll()
            : $this->_rebuildEntry($id);
    }

    protected function _rebuildEntry($id)
    {
        $this->iaDb->setTable(self::getTable());

        $category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

        // update parents
        $parents = [$category['id']];
        $parents = $this->_getParents($category['id'], $parents);
        $parents = array_reverse($parents);

        $children = [$category['id']];
        $children = $this->_getChildren($category['id'], $children);
        $children = array_reverse($children);

        $entry = [
            self::COL_PARENTS => implode(',', $parents),
            self::COL_CHILDREN => implode(',', $children),
            self::COL_LEVEL => count($parents) - 1
        ];

        $this->iaDb->update($entry, iaDb::convertIds($category['id']));

        $this->iaDb->resetTable();
    }

    protected static function _cols($sql)
    {
        return str_replace(
            [':col_pid', ':col_parents', ':col_children', ':col_level', ':root_pid'],
            [self::COL_PARENT_ID, self::COL_PARENTS, self::COL_CHILDREN, self::COL_LEVEL, self::ROOT_PARENT_ID],
            $sql
        );
    }

    /**
     * Rebuild categories relations.
     * Fields to be updated: parents, child, level, title_alias
     */
    protected function _rebuildAll()
    {
        $table = self::getTable(true);

        $iaDb = &$this->iaDb;

        if ($this->_flatStructureEnabled) {
            $sql1 = 'INSERT INTO ' . $this->_tableFlat . ' SELECT t.`id`, t.`id` FROM ' . $table . ' t WHERE t.`:col_pid` != :root_pid';
            $sql2 = 'INSERT INTO ' . $this->_tableFlat . ' SELECT t.`:col_pid`, t.`id` FROM ' . $table . ' t WHERE t.`:col_pid` != :root_pid';

            $iaDb->truncate($this->_tableFlat);

            $iaDb->query(self::_cols($sql1));
            $iaDb->query(self::_cols($sql2));

            $num = 1;
            $count = 0;

            while ($num > 0 && $count < 10) {
                $count++;
                $num = 0;
                $sql = 'INSERT INTO ' . $this->_tableFlat . ' '
                    . 'SELECT DISTINCT t.`id`, h' . $count . '.`id` FROM ' . $table . ' t, ' . $table . ' h0 ';
                $where = ' WHERE h0.`:col_pid` = t.`id` ';

                for ($i = 1; $i <= $count; $i++) {
                    $sql .= 'LEFT JOIN ' . $table . ' h' . $i . ' ON (h' . $i . '.`:col_pid` = h' . ($i - 1) . '.`id`) ';
                    $where .= ' AND h' . $i . '.`id` IS NOT NULL';
                }

                if ($iaDb->query(self::_cols($sql . $where))) {
                    $num = $iaDb->getAffected();
                }
            }
        }

        $sqlLevel = 'UPDATE ' . $table . ' s SET `:col_level` = (SELECT COUNT(`category_id`)-1 FROM ' . $this->_tableFlat . ' f WHERE f.`category_id` = s.`id`) WHERE s.`:col_pid` != :root_pid';
        $sqlChildren = 'UPDATE ' . $table . ' s SET `:col_children` = (SELECT GROUP_CONCAT(`category_id`) FROM ' . $this->_tableFlat . ' f WHERE f.`parent_id` = s.`id`)';
        $sqlParent = 'UPDATE ' . $table . ' s SET `:col_parents` = (SELECT GROUP_CONCAT(`parent_id`) FROM ' . $this->_tableFlat . ' f WHERE f.`category_id` = s.`id`)';

        $iaDb->query(self::_cols($sqlLevel));
        $iaDb->query(self::_cols($sqlChildren));
        $iaDb->query(self::_cols($sqlParent));

        $iaDb->update(['order' => 1], iaDb::convertIds(0, 'order'), null, self::getTable());
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

    public function rebuildAliases($id)
    {
        $this->iaDb->setTable(self::getTable());

        $category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
        $path = $this->_getPathForRebuild($category['title'], $category[self::COL_PARENT_ID]);
        $this->iaDb->update(['title_alias' => $path], iaDb::convertIds($category['id']));

        $this->iaDb->resetTable();
    }

    protected function _getParents($cId, $parents = [], $update = true)
    {
        $parentId = $this->iaDb->one(self::COL_PARENT_ID, iaDb::convertIds($cId));

        if ($parentId != 0) {
            $parents[] = $parentId;

            if ($update) {
                $childrenIds = $this->iaDb->one(self::COL_CHILDREN, iaDb::convertIds($parentId));
                $childrenIds = $childrenIds ? explode(',', $childrenIds) : [];

                if (!in_array($cId, $childrenIds)) {
                    $childrenIds[] = $cId;
                }

                foreach ($parents as $pid) {
                    if (!in_array($pid, $childrenIds)) {
                        $childrenIds[] = $pid;
                    }
                }

                $this->iaDb->update([self::COL_CHILDREN => implode(',', $childrenIds)], iaDb::convertIds($parentId));
            }

            $parents = $this->_getParents($parentId, $parents, $update);
        }

        return $parents;
    }

    protected function _getChildren($cId, $children = [], $update = false)
    {
        if ($childrenIds = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($cId, 'parent_id'), null, null, self::getTable())) {
            foreach ($childrenIds as $childId) {
                $children[] = $childId;

                if ($update) {
                    $parentIds = $this->iaDb->one(self::COL_PARENTS, iaDb::convertIds($cId), self::getTable());
                    $parentIds = $parentIds ? explode(',', $parentIds) : [];

                    $parentIds[] = $childId;

                    $this->iaDb->update([self::COL_PARENTS => implode(',', $parentIds)], iaDb::convertIds($childId), null, self::getTable());
                }

                $children = $this->_getChildren($childId, $children, $update);
            }
        }

        return $children;
    }

    public function dropRelations()
    {
        $this->iaDb->update(['child' => '', 'parents' => ''], iaDb::EMPTY_CONDITION, self::getTable());
    }

    public function clearArticlesNum()
    {
        $this->iaDb->update(['num_articles' => 0, 'num_all_articles' => 0], iaDb::EMPTY_CONDITION, self::getTable());
    }

    public function getCount()
    {
        return $this->iaDb->one(iaDb::STMT_COUNT_ROWS, null, self::getTable());
    }

    public function assignTreeVars()
    {
        $result = [

        ];

        return $result;
    }
}
