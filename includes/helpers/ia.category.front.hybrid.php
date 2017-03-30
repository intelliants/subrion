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

abstract class iaAbstractFrontHelperCategoryHybrid extends abstractModuleFront
{
    const ROOT_PARENT_ID = 0;

    const COL_PARENT_ID = '_pid';
    const COL_PARENTS = '_parents';
    const COL_CHILDREN = '_children';
    const COL_LEVEL = 'level';

    protected $_flatStructureEnabled = false;

    private $_rootId;


    public function getRootId()
    {
        if (is_null($this->_rootId)) {
            $this->_rootId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds(self::ROOT_PARENT_ID, self::COL_PARENT_ID), self::getTable());
        }

        return $this->_rootId;
    }

    public function fetch($where, $columns = null, $start = null, $limit = null)
    {
        is_null($columns) && $columns = iaDb::ALL_COLUMNS_SELECTION;

        $rows = $this->iaDb->all($columns, $where, (int)$start, (int)$limit, self::getTable());

        $this->_processValues($rows);

        return $rows;
    }

    public function getJsonTree(array $data)
    {
        $output = [];

        $categoryId = isset($data['id']) ? (int)$data['id'] : 1;
        $rows = $this->fetch(iaDb::convertIds($categoryId, self::COL_PARENT_ID), ['id', 'title' => 'title_' . $this->iaView->language, self::COL_CHILDREN]);

        foreach ($rows as $entry) {
            $output[] = [
                'id' => $entry['id'],
                'text' => $entry['title'],
                'children' => ($entry[self::COL_CHILDREN] && $entry[self::COL_CHILDREN] != $entry['id']) || (-1 == $categoryId)
            ];
        }

        return $output;
    }
}