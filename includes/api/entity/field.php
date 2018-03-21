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

class iaApiEntityField extends iaApiEntityAbstract
{
    protected static $_table = 'fields';

    public $apiFilters = ['name', 'item', 'type', 'module', 'searchable', 'status'];


    public function apiList($start, $limit, $where, $order)
    {
        $rows = $this->iaField->fetch($where, $order, $start, $limit);

        $this->_filterHiddenFields($rows);

        return $rows;
    }

    public function apiGet($id)
    {
        $row = $this->iaField->fetch(iaDb::convertIds($id));
        $row && $row = $row[0];

        $this->_filterHiddenFields($row, true);

        return $row;
    }

    public function apiUpdate($data, $id, array $params)
    {
        $resource = $this->apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (!$this->iaCore->factory('acl')->checkAccess('admin_page:edit', 'fields')) {
            throw new Exception(iaLanguage::get(iaView::ERROR_FORBIDDEN), iaApiResponse::FORBIDDEN);
        }

        $this->iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

        return (0 == $this->iaDb->getErrorNumber());
    }

    public function apiDelete($id, array $params)
    {
        throw new Exception('Field removal via API is restricted', iaApiResponse::NOT_ALLOWED);
    }
}
