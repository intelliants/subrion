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

class iaApiEntityPhrase extends iaApiEntityAbstract
{
    protected static $_table = 'language';

    protected $hiddenFields = ['id', 'api'];


    public function apiGet($id)
    {
        $where = '`key` = :key AND `api` = 1';
        $this->iaDb->bind($where, ['key' => $id]);

        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $where, $this->getTable());

        $this->_filterHiddenFields($row, true);

        return $row;
    }

    public function apiList($start, $limit, $where, $order)
    {
        $where .= ' AND `api` = 1';

        $result = [];

        if ($rows = parent::apiList($start, $limit, $where, $order)) {
            foreach ($rows as $row) {
                $result[$row['code']][$row['key']] = $row['value'];
            }
        }

        return $result;
    }

    public function apiUpdate($data, $id, array $params)
    {
        throw new Exception('Method not allowed', iaApiResponse::NOT_ALLOWED);
    }

    public function apiDelete($id, array $params)
    {
        throw new Exception('Method not allowed', iaApiResponse::NOT_ALLOWED);
    }
}
