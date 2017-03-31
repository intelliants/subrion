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

    private $_rootId;


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

        $rootEntry = [
            self::COL_PARENT_ID => self::ROOT_PARENT_ID,
            self::COL_PARENTS => '1',
            self::COL_CHILDREN => '1',
            self::COL_LEVEL => 0,

            'id' => 1,
            'title_' . $this->iaView->language => 'ROOT'
        ];

        $this->iaDb->insert($rootEntry, null, self::getTable());

        if ($this->_flatStructureEnabled) {
            $sql = <<<SQL
CREATE TABLE IF NOT EXISTS `:prefixcoupons_categories_flat`(
  `parent_id` int(8) unsigned NOT NULL,
  `category_id` int(8) unsigned NOT NULL,
  UNIQUE KEY `UNIQUE` (`parent_id`,`category_id`)
) :options;
SQL;
            $this->iaDb->query(iaDb::printf($sql, ['prefix' => $this->iaDb->prefix, 'options' => $this->iaDb->tableOptions]));
        }
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

    /*
     * Rebuild categories relations.
     * Fields to be updated: parents, child, level, title_alias
     */
    protected function _rebuildAll()
    {
        $table_flat = $this->iaDb->prefix . 'coupons_categories_flat';
        $table = self::getTable(true);

        $insert_second = 'INSERT INTO ' . $table_flat . ' (`parent_id`, `category_id`) SELECT t.`parent_id`, t.`id` FROM ' . $table . ' t WHERE t.`parent_id` != 0';
        $insert_first = 'INSERT INTO ' . $table_flat . ' (`parent_id`, `category_id`) SELECT t.`id`, t.`id` FROM ' . $table . ' t WHERE t.`parent_id` != 0';
        $update_level = 'UPDATE ' . $table . ' s SET `level` = (SELECT COUNT(`category_id`)-1 FROM ' . $table_flat . ' f WHERE f.`category_id` = s.`id`) WHERE s.`parent_id` != 0;';
        $update_child = 'UPDATE ' . $table . ' s SET `child` = (SELECT GROUP_CONCAT(`category_id`) FROM ' . $table_flat . ' f WHERE f.`parent_id` = s.`id`);';
        $update_parent = 'UPDATE ' . $table . ' s SET `parents` = (SELECT GROUP_CONCAT(`parent_id`) FROM ' . $table_flat . ' f WHERE f.`category_id` = s.`id`);';

        $num = 1;
        $count = 0;

        $iaDb = &$this->iaDb;
        $iaDb->truncate($table_flat);
        $iaDb->query($insert_first);
        $iaDb->query($insert_second);

        while($num > 0 && $count < 10)
        {
            $count++;
            $num = 0;
            $sql = 'INSERT INTO ' . $table_flat . ' (`parent_id`, `category_id`) '
                . 'SELECT DISTINCT t . `id`, h' . $count . ' . `id` FROM ' . $table . ' t, ' . $table . ' h0 ';
            $where = ' WHERE h0 . `parent_id` = t . `id` ';

            for ($i = 1; $i <= $count; $i++)
            {
                $sql .= 'LEFT JOIN ' . $table . ' h' . $i . ' ON (h' . $i . '.`parent_id` = h' . ($i - 1) . '.`id`) ';
                $where .= ' AND h' . $i . '.`id` is not null';
            }

            if ($iaDb->query($sql . $where))
            {
                $num = $iaDb->getAffected();
            }
        }

        $iaDb->query($update_level);
        $iaDb->query($update_child);
        $iaDb->query($update_parent);

        $iaDb->query('UPDATE ' . $table . ' SET `order` = 1 WHERE `order` = 0');
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
                $parent = $this->iaDb->row(['id', 'parent_id', 'title'], "`id` = '{$pid}'");

                $cache[$pid] = $parent;
            }

            $path = $this->_getPathForRebuild($parent['title'], $parent['parent_id'], $path);
        }

        return $path;
    }

    public function rebuildAliases($id)
    {
        $this->iaDb->setTable(self::getTable());

        $category = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
        $path = $this->_getPathForRebuild($category['title'], $category['parent_id']);
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