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

class iaPage extends abstractCore
{
    protected static $_table = 'pages';


    public function getUrlByName($pageName, $appendScriptPath = true)
    {
        static $pagesToUrlMap;

        if (is_null($pagesToUrlMap)) {
            $pagesToUrlMap = $this->iaDb->keyvalue(['name', 'alias'], null, self::getTable());
        }

        return isset($pagesToUrlMap[$pageName])
            ? ($appendScriptPath ? IA_URL : '') . $pagesToUrlMap[$pageName]
            : null;
    }

    public function getByName($name, $status = iaCore::STATUS_ACTIVE)
    {
        $row = $this->iaDb->row_bind(
            iaDb::ALL_COLUMNS_SELECTION,
            '`name` = :name AND `status` = :status AND `service` != 1',
            ['name' => $name, 'status' => $status],
            self::getTable()
        );

        if ($row) {
            foreach (['meta_description', 'meta_keywords', 'meta_title'] as $key) {
                $phraseKey = sprintf('page_%s_%s', $key, $row['name']);
                $row[$key] = iaLanguage::exists($phraseKey) ? iaLanguage::get($phraseKey) : null;
            }
        }

        return $row;
    }

    protected function _getInfoByName($name)
    {
        $pageParams = $this->getByName($name);

        return [
            'parent' => $pageParams['parent'],
            'title' => iaLanguage::get(sprintf('page_title_%s', $pageParams['name'])),
            'url' => $pageParams['alias'] ? $this->getUrlByName($pageParams['name']) : $pageParams['name'] . IA_URL_DELIMITER
        ];
    }

    public function getParents($parentPageName, array &$chain)
    {
        if ($parentPageName) {
            $chain[] = $parent = $this->_getInfoByName($parentPageName);
            $this->getParents($parent['parent'], $chain);
        } else {
            $chain = array_reverse($chain);
        }
    }
}
