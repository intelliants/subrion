<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

    const COL_ORDER = 'order';

    protected $_tableFlat;

    protected $_recountEnabled = true;
    protected $_recountOptions = []; // this to be extended by ancestor

    protected $_slugColumnName = 'slug';

    private $_defaultRecountOptions = [
        'listingsTable' => null,
        'activeStatus' => iaCore::STATUS_ACTIVE,
        'columnCounter' => 'num_listings',
        'columnTotalCounter' => 'num_all_listings'
    ];

    private $_root;


    public function init()
    {
        parent::init();

        $this->_tableFlat = self::getTable() . '_flat';
        $this->_recountOptions = array_merge($this->_defaultRecountOptions, $this->_recountOptions);
    }

    public function getTableFlat($prefix = false)
    {
        return ($prefix ? iaCore::instance()->iaDb->prefix : '')
            . $this->_tableFlat;
    }

    protected function _cols($sql)
    {
        return str_replace(
            [':col_pid', ':col_level', ':col_counter', ':col_total_counter', ':root_pid'],
            [self::COL_PARENT_ID, self::COL_LEVEL, $this->_recountOptions['columnCounter'], $this->_recountOptions['columnTotalCounter'], self::ROOT_PARENT_ID],
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
                . '`child_id` int(8) unsigned NOT NULL, '
                . 'UNIQUE KEY `UNIQUE` (`parent_id`,`child_id`)'
            . ') :options'
        ];

        if ($this->_recountEnabled) {
            $queries[] = 'ALTER TABLE `:table_data` ADD `:col_counter` mediumint(8) unsigned NOT NULL default 0';
            $queries[] = 'ALTER TABLE `:table_data` ADD `:col_total_counter` mediumint(8) unsigned NOT NULL default 0';
        }

        foreach ($queries as $query) {
            $sql = iaDb::printf($query, [
                'table_data' => self::getTable(true),
                'table_flat' => $this->getTableFlat(true),
                'options' => $this->iaDb->tableOptions
            ]);

            $this->iaDb->query($this->_cols($sql));
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
    }

    public function getRoot()
    {
        if (is_null($this->_root)) {
            $this->_root = $this->getOne(iaDb::convertIds(self::ROOT_PARENT_ID, self::COL_PARENT_ID));
        }

        return $this->_root;
    }

    public function getRootId()
    {
        return $this->getRoot()['id'];
    }


    public function insert(array $itemData)
    {
        $this->_assignStructureData($itemData, iaCore::ACTION_ADD);

        return parent::insert($itemData);
    }

    public function update(array $itemData, $id)
    {
        // track slug changes
        if (isset($itemData[$this->_slugColumnName])) {
            $row = $this->getById($id);
        }

        $this->_assignStructureData($itemData, iaCore::ACTION_EDIT);

        $result = parent::update($itemData, $id);

        if ($result && isset($row)) {
            $this->validateSlug($id, $row[$this->_slugColumnName], $itemData[$this->_slugColumnName]);
        }

        return $result;
    }

    public function delete($itemId)
    {
        if ($itemId == $this->getRootId()) {
            return false;
        }

        $result = parent::delete($itemId);

        if ($result) {
            // remove subcategories as well
            foreach ($this->getChildren($itemId) as $item) {
                $this->delete($item['id']);
            }
        }

        return $result;
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        if (iaCore::ACTION_DELETE != $action) {
            $this->_updateFlatStructure($itemId);
        }
    }

    public function validateSlug($entryId, $slug, $newSlug)
    {
        if ($slug == $newSlug) {
            return;
        }

        $stmtWhere = iaDb::printf('`id` != :id && `id` IN (SELECT DISTINCT `child_id` FROM `:table_flat` WHERE `parent_id` = :id)',
            [
                'table_flat' => $this->getTableFlat(true),
                'id' => (int)$entryId
            ]);

        $stmtUpdate = iaDb::printf("REPLACE(`:column`, ':slug', ':new_slug')", [
            'column' => $this->_slugColumnName,
            'slug' => $slug,
            'new_slug' => $newSlug
        ]);

        $this->iaDb->update(null, $stmtWhere, [$this->_slugColumnName => $stmtUpdate], self::getTable());
    }

    /**
     * Rebuild relations
     */
    public function rebuild()
    {
        $table = self::getTable(true);
        $tableFlat = self::getTableFlat(true);

        $iaDb = &$this->iaDb;

        $sql1 = 'INSERT INTO ' . $tableFlat . ' SELECT d.`id`, d.`id` FROM ' . $table . ' d WHERE d.`:col_pid` != :root_pid ORDER BY d.`:col_level`';
        $sql2 = 'INSERT INTO ' . $tableFlat . ' SELECT d.`:col_pid`, d.`id` FROM ' . $table . ' d WHERE d.`:col_pid` != :root_pid ORDER BY d.`:col_level`';

        $iaDb->truncate($tableFlat);

        $iaDb->query($this->_cols($sql1));
        $iaDb->query($this->_cols($sql2));

        $num = 1;
        $count = 0;

        while ($num > 0 && $count < 10) {
            $count++;
            $num = 0;
            $sql = "INSERT INTO `{$tableFlat}` "
                . "SELECT DISTINCT t.`id`, h{$count}.`id` "
                . "FROM `{$table}` t, `{$table}` h0 ";
            $where = ' WHERE h0.`:col_pid` = t.`id`';

            for ($i = 1; $i <= $count; $i++) {
                $sql .= "LEFT JOIN `{$table}` h{$i} ON (h{$i}.`:col_pid` = h" . ($i - 1) . ".`id`) ";
                $where .= " AND h{$i}.`id` IS NOT NULL";
            }

            if ($iaDb->query($this->_cols($sql . $where))) {
                $num = $iaDb->getAffected();
            }
        }

        $this->iaDb->delete(iaDb::convertIds($this->getRootId(), 'parent_id'), self::getTableFlat());

        $sqlLevel = 'UPDATE ' . $table . ' d SET `:col_level` = (SELECT COUNT(`parent_id`) FROM ' . $tableFlat . ' f WHERE f.`child_id` = d.`id`) WHERE d.`:col_pid` != :root_pid';

        $iaDb->query($this->_cols($sqlLevel));

        $iaDb->update(['order' => 1], iaDb::convertIds(0, 'order'), null, self::getTable());
    }

    public function recountById($id, $factor = 1)
    {
        if (!$this->_recountEnabled) {
            return;
        }

        $sql = <<<SQL
UPDATE `:table_data` 
SET `:col_counter` = IF(`id` = :id, `:col_counter` + :factor, `:col_counter`),
	`:col_total_counter` = `:col_total_counter` + :factor 
WHERE `id` IN (SELECT `parent_id` FROM `:table_flat` WHERE `child_id` = :id)
SQL;

        $sql = iaDb::printf($sql, [
            'table_data' => self::getTable(true),
            'table_flat' => self::getTableFlat(true),
            'id' => (int)$id,
            'factor' => (int)$factor
        ]);

        $this->iaDb->query($this->_cols($sql));
    }

    public function recount($start, $limit)
    {
        if (!$this->_recountEnabled) {
            return;
        }

        if (empty($this->_recountOptions['listingsTable'])) {
            throw new Exception('Recount options not defined.');
        }

        $where = '`status` = :status ORDER BY `id`';
        $this->iaDb->bind($where, ['status' => iaCore::STATUS_ACTIVE]);

        $rows = $this->iaDb->all(['category_id'], $where, (int)$start, (int)$limit, $this->_recountOptions['listingsTable']);

        if ($rows) {
            foreach ($rows as $row) {
                $this->recountById($row['category_id']);
            }
        }
    }
/*
    public function recount($start, $limit)
    {
        if (!$this->_recountEnabled) {
            return;
        }

        if (empty($this->_recountOptions['listingsTable'])) {
            throw new Exception('Recount options not defined.');
        }

        $this->iaDb->setTable(self::getTable());

        $where = iaDb::EMPTY_CONDITION . ' ORDER BY `' . self::COL_LEVEL . '` DESC';

        if ($rows = $this->iaDb->all(['id', self::COL_PARENT_ID], $where, (int)$start, (int)$limit)) {
            foreach ($rows as $row) {
                if (self::ROOT_PARENT_ID == $row[self::COL_PARENT_ID]) {
                    continue;
                }

                $sql = <<<SQL
SELECT COUNT(l.`id`) `num`
FROM `:table_listings` l 
LEFT JOIN `:table_members` m ON (l.`member_id` = m.`id`)
WHERE l.`category_id` = :category AND l.`status` = ":active_status" AND (m.`status` IS NULL OR m.`status` = ":users_status")
SQL;
                $sql = iaDb::printf($sql, [
                    'table_listings' => $this->iaDb->prefix . $this->_recountOptions['listingsTable'],
                    'table_members' => iaUsers::getTable(true),
                    'active_status' => $this->_recountOptions['activeStatus'],
                    'users_status' => iaCore::STATUS_ACTIVE,
                    'category' => $row['id']
                ]);

                $counter = (int)$this->iaDb->getOne($sql);
                $counterTotal = $counter + (int)$this->iaDb->one('SUM(`' . $this->_recountOptions['columnCounter'] . '`)',
                        '`id` IN (SELECT `category_id` FROM `' . self::getTableFlat(true) . '` WHERE `parent_id` = ' . $row['id'] . ')');

                $this->iaDb->update([
                    $this->_recountOptions['columnCounter'] => $counter,
                    $this->_recountOptions['columnTotalCounter'] => $counterTotal
                ], iaDb::convertIds($row['id']));
            }
        }

        $this->iaDb->resetTable();
    }
*/
    public function resetCounters()
    {
        $this->iaDb->update([$this->_recountOptions['columnCounter'] => 0,
            $this->_recountOptions['columnTotalCounter'] => 0], iaDb::EMPTY_CONDITION, self::getTable());
    }

    public function rebuildAliases($id)
    {
        $this->iaDb->setTable(self::getTable());

        $category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
        $path = $this->_getPathForRebuild($category['title_' . iaLanguage::getMasterLanguage()->code], $category[self::COL_PARENT_ID]);
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

    public function getTopLevel()
    {
        return $this->getByLevel(1);
    }

    public function getByLevel($level)
    {
        return $this->getAll(iaDb::convertIds($level, 'level'));
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

        $nodes = array_merge([$this->getRootId()], $this->getParents($id, true));

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

        $rootId = $noRootMode ? $this->getRootId() : 0;
        $parentId = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : $rootId;

        $where = $dynamicLoadMode
            ? iaDb::convertIds($parentId, self::COL_PARENT_ID)
            : ($noRootMode ? iaDb::convertIds($rootId, 'id', false) : iaDb::EMPTY_CONDITION);

        // TODO: better solution should be found here. this code will break jstree composition in case if
        // category to be excluded from the list has children of 2 and more levels deeper
        if (!empty($data['cid'])) {
            $where .= sprintf(' AND `id` != %d AND `parent_id` != %d', $data['cid'], $data['cid']);
        }

        $where .= ' ORDER BY `title`';

        $fields = '`id`, `title_' . $this->iaCore->language['iso'] . '` `title`, `parent_id`, '
            . '(SELECT COUNT(*) FROM `' . self::getTableFlat(true) . '` f WHERE f.`parent_id` = `id`) `children_count`';

        foreach ($this->iaDb->all($fields, $where) as $row) {
            $entry = [
                'id' => $row['id'],
                'text' => iaSanitize::html($row['title'])
            ];

            if ($dynamicLoadMode) {
                $entry['children'] = $row['id'] == $this->getRootId()
                    ? $this->getCount() > 1
                    : $row['children_count'] > 1;
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

    // tree utility methods
    public function getParents($entryId, $idsOnly = false)
    {
        if ($idsOnly) {
            return $this->iaDb->onefield(self::COL_PARENT_ID, iaDb::convertIds($entryId, 'child_id'),
                null, null, self::getTableFlat());
        } else {
            $subQuery = sprintf('SELECT `parent_id` FROM `%s` WHERE `child_id` = %d',
                self::getTableFlat(true), $entryId);

            return $this->getAll('`id` IN (' . $subQuery . ') ORDER BY `level`');
        }
    }

    public function getChildren($entryId)
    {
        $where = sprintf('`id` IN (SELECT `child_id` FROM `%s` WHERE `parent_id` = %d)',
            self::getTableFlat(true), $entryId);

        return $this->getAll($where);
    }

    protected function _assignStructureData(array &$entryData, $action)
    {
        if (isset($entryData[self::COL_PARENT_ID])) {
            if ($parent = $this->getById($entryData[self::COL_PARENT_ID], false)) {
                $entryData[self::COL_LEVEL] = $parent[self::COL_LEVEL] + 1;
            }
        }

        // if module uses 'order' column
        if (iaCore::ACTION_ADD == $action
            && isset($entryData[self::COL_LEVEL])
            && isset($this->getRoot()['order'])) {
            $entryData[self::COL_ORDER] = (int)$this->iaDb->getMaxOrder(self::getTable(),
                ['level', $entryData[self::COL_LEVEL]]) + 1;
        }
    }

    // flat structure
    protected function _updateFlatStructure($entryId)
    {
        $this->iaDb->setTable(self::getTableFlat());

        $this->iaDb->delete(iaDb::convertIds($entryId, 'child_id'));
        $this->iaDb->delete(iaDb::convertIds($entryId, 'parent_id'));

        if ($entry = $this->getById($entryId, false)) {
            $this->iaDb->insert(['parent_id' => $entryId, 'child_id' => $entryId]);

            // handle parents first
            $entries = [];
            $this->_recursiveCollectParents($entry, $entries);

            foreach ($entries as $row) {
                $this->iaDb->insert(['parent_id' => $row['id'], 'child_id' => $entryId]);
            }

            // then, collect children
            $entries = [];
            $this->_recursiveCollectChildren($entry, $entries);

            foreach ($entries as $row) {
                if ($row['id'] != $entryId) {
                    $this->iaDb->insert(['parent_id' => $entryId, 'child_id' => $row['id']]);
                }
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
