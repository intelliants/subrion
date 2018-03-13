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

    protected static $_adminTable = 'admin_pages';
    protected static $_adminGroupsTable = 'admin_pages_groups';

    public $extendedExtensions = ['htm', 'html', 'php'];

    protected static $_pageTitles = [];


    public static function getAdminTable()
    {
        return self::$_adminTable;
    }

    public static function getAdminGroupsTable()
    {
        return self::$_adminGroupsTable;
    }

    public function getNonServicePages(array $exclude)
    {
        $sql = <<<SQL
SELECT DISTINCTROW p.*, l.`value`, IF(l.`value` IS NULL, p.`name`, l.`value`) `title` 
	FROM `:table_pages` p 
LEFT JOIN `:table_phrases` l ON (`key` = CONCAT('page_title_', p.`name`) AND l.`code` = ':lang') 
WHERE p.`status` = ':status' AND p.`service` = 0 :extra_where
ORDER BY l.`value`
SQL;
        $sql = iaDb::printf($sql, [
            'table_pages' => self::getTable(true),
            'table_phrases' => $this->iaDb->prefix . iaLanguage::getTable(),
            'lang' => $this->iaCore->language['iso'],
            'status' => iaCore::STATUS_ACTIVE,
            'extra_where' => $exclude ? "AND !FIND_IN_SET(p.`name`, '" . implode(',', $exclude) . "') " : ''
        ]);

        return $this->iaDb->getAll($sql);
    }

    public function getTitles($side = iaCore::FRONT)
    {
        if (!isset(self::$_pageTitles[$side])) {
            $category = iaCore::FRONT == $side ? iaLanguage::CATEGORY_PAGE : iaLanguage::CATEGORY_ADMIN;

            $where = '`key` LIKE :key AND `category` = :category AND `code` = :code';
            $this->iaDb->bind($where, ['key' => 'page_title_%', 'category' => $category, 'code' => $this->iaView->language]);

            self::$_pageTitles[$side] = $this->iaDb->keyvalue("REPLACE(`key`, 'page_title_', '') `key`, `value`", $where, iaLanguage::getTable());
        }

        return self::$_pageTitles[$side];
    }

    public function getPageTitle($pageName, $default = null)
    {
        $this->getTitles();

        if (!isset(self::$_pageTitles[iaCore::FRONT][$pageName])) {
            return is_null($default) ? $pageName : $default;
        }

        return self::$_pageTitles[iaCore::FRONT][$pageName];
    }

    public function getGroups(array $exclusions = [])
    {
        $stmt = '`status` = :status AND `service` = 0';
        if ($exclusions) {
            $stmt.= " AND `name` NOT IN ('" . implode("','", array_map(['iaSanitize', 'sql'], $exclusions)) . "')";
        }
        $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE]);

        $pages = [];
        $result = [];

        $rows = $this->iaDb->all(['id', 'name', 'group'], $stmt, null, null, self::getTable());
        $titles = $this->getTitles();
        foreach ($rows as $page) {
            $page['group'] || $page['group'] = 1;
            $pages[$page['group']][$page['id']] = isset($titles[$page['name']]) ? $titles[$page['name']] : $page['name'];
        }

        $rows = $this->iaDb->all(['id', 'name'], null, null, null, self::getAdminGroupsTable());
        foreach ($rows as $row) {
            if (isset($pages[$row['id']])) {
                $result[$row['id']] = [
                    'title' => iaLanguage::get('pages_group_' . $row['name']),
                    'children' => $pages[$row['id']]
                ];
            }
        }

        return $result;
    }

    public function getUrlByName($pageName)
    {
        static $pagesToUrlMap;

        if (is_null($pagesToUrlMap)) {
            $pagesToUrlMap = $this->iaDb->keyvalue(['name', 'alias'], null, self::getAdminTable());
        }

        if (isset($pagesToUrlMap[$pageName])) {
            return $pagesToUrlMap[$pageName] ? $pagesToUrlMap[$pageName] : $pageName;
        }

        return null;
    }

    public function getByName($pageName, $lookupThroughBackend = true)
    {
        $result = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', ['name' => $pageName],
            $lookupThroughBackend ? self::getAdminTable() : self::getTable());

        if ($result) {
            $result['title'] = $this->iaDb->one_bind(['value'], '`key` = :key AND `category` = :category AND `code` = :lang',
                ['key' => 'page_title_' . $pageName, 'category' => $lookupThroughBackend ? iaLanguage::CATEGORY_ADMIN : iaLanguage::CATEGORY_PAGE,
                    'lang' => $this->iaView->language], iaLanguage::getTable());
        }

        return $result;
    }
}
