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

abstract class iaAbstractFrontHelperCategoryFlat extends abstractModuleFront
{
    const ROOT_PARENT_ID = 0;

    const COL_PARENT_ID = 'parent_id';
    const COL_LEVEL = 'level';

    protected $_tableFlat;

    private $_root;


    public function init()
    {
        parent::init();

        $this->_tableFlat = self::getTable() . '_flat';
    }

    public function getTableFlat($prefix = false)
    {
        return ($prefix ? $this->iaDb->prefix : '') . $this->_tableFlat;
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

    public function getParents($entryId)
    {
        $where = sprintf('`id` IN (SELECT `parent_id` FROM `%s` WHERE `category_id` = %d)',
            $this->iaDb->prefix . $this->_tableFlat, $entryId);

        return $this->fetch($where);
    }

    public function fetch($where, $order = null, $start = null, $limit = null)
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . ' ' . $order, (int)$start, (int)$limit, self::getTable());

        $this->_processValues($rows);

        return $rows;
    }

    public function getTreeVars($id, $title)
    {
        $nodes = [];
        foreach ($this->getParents($id) as $category) {
            $nodes[] = $category['id'];
        }

        $result = [
            'url' => $this->getInfo('url') . 'add/tree.json',
            'id' => $id,
            'nodes' => implode(',', $nodes),
            'title' => $title
        ];

        return $result;
    }

    public function getJsonTree(array $data)
    {
        $output = [];

        $this->iaDb->setTable(self::getTable());

        $dynamicLoadMode = ((int)$this->iaDb->one(iaDb::STMT_COUNT_ROWS) > 150);

        $rootId = $this->getRootId();
        $parentId = isset($data['id']) && is_numeric($data['id']) ? (int)$data['id'] : $rootId;

        $where = $dynamicLoadMode
            ? iaDb::convertIds($parentId, self::COL_PARENT_ID)
            : iaDb::convertIds($rootId, 'id', false);

        $where .= ' ORDER BY `title`';

        $fields = '`id`, `title_' . $this->iaCore->language['iso'] . '` `title`, `parent_id`, '
            . '(SELECT COUNT(*) FROM `' . $this->getTableFlat(true) . '` f WHERE f.`parent_id` = `id`) `children_count`';

        foreach ($this->iaDb->all($fields, $where) as $row) {
            $entry = ['id' => $row['id'], 'text' => $row['title']];

            if ($dynamicLoadMode) {
                $entry['children'] = $row['children_count'] > 1;
            } else {
                $entry['state'] = [];
                $entry['parent'] = $rootId == $row[self::COL_PARENT_ID] ? '#' : $row[self::COL_PARENT_ID];
            }

            $output[] = $entry;
        }

        return $output;
    }
}
