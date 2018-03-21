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

class iaLanguage
{
    const CATEGORY_ADMIN = 'admin';
    const CATEGORY_COMMON = 'common';
    const CATEGORY_FRONTEND = 'frontend';
    const CATEGORY_PAGE = 'page';
    const CATEGORY_TOOLTIP = 'tooltip';
    const CATEGORY_API = 'api';

    protected static $_table = 'language';
    protected static $_languagesTable = 'languages';

    protected static $_phrases = [];

    protected static $_columns = ['code', 'id', 'title', 'locale', 'date_format', 'time_format', 'direction', 'master', 'default', 'flagicon', 'iso' => 'code', 'status'];

    protected static $_validCategories = [self::CATEGORY_ADMIN, self::CATEGORY_COMMON, self::CATEGORY_FRONTEND, self::CATEGORY_PAGE, self::CATEGORY_TOOLTIP, self::CATEGORY_API];


    public function __construct()
    {
    }
    public function __clone()
    {
    }

    public function init()
    {
        $iaCore = iaCore::instance();

        // set list of available languages
        $iaCore->languages = $iaCore->iaDb->assoc(
            self::$_columns,
            iaDb::EMPTY_CONDITION . ' ORDER BY `order` ASC',
            self::$_languagesTable
        );
    }

    public static function get($key, $default = null)
    {
        if (empty($key)) { // false, empty string values
            return false;
        }

        if (self::exists($key)) {
            return self::$_phrases[$key];
        } else {
            if (INTELLI_DEBUG && is_null($default)) {
                $iaCache = iaCore::instance()->iaCache;

                $cache = $iaCache->get('nonexistent_phrases', 0, true);
                $cache || $cache = [];

                if (!in_array($key, $cache)) {
                    $cache[] = $key;
                    $iaCache->write('nonexistent_phrases', serialize($cache));
                }

                iaDebug::debug($key, 'Phrases do not exist', 'error');
            }

            return is_null($default)
                ? '{' . $key . '}'
                : $default;
        }
    }

    public static function getf($key, array $replaces)
    {
        $phrase = self::get($key);

        if (empty($phrase)) {
            return $phrase;
        }

        $search = [];
        foreach (array_keys($replaces) as $item) {
            array_push($search, ':' . $item);
        }

        return str_replace($search, array_values($replaces), $phrase);
    }

    public static function set($key, $value)
    {
        self::$_phrases[$key] = $value;
    }

    public static function exists($key)
    {
        return isset(self::$_phrases[$key]);
    }

    public static function load($languageCode)
    {
        $iaCore = iaCore::instance();

        $where = (iaCore::ACCESS_FRONT == $iaCore->getAccessType())
            ? "`code` = '%s' AND `category` NOT IN('tooltip', 'admin') ORDER BY `module`"
            : "`code` = '%s' AND `category` NOT IN('tooltip', 'frontend', 'page')";
        $where = sprintf($where, $languageCode);

        self::$_phrases = $iaCore->iaDb->keyvalue(['key', 'value'], $where, self::getTable());
    }

    public static function getPhrases()
    {
        return self::$_phrases;
    }

    public static function getMasterLanguage()
    {
        static $row;

        is_null($row) && $row = iaCore::instance()->iaDb->row(self::$_columns,
            iaDb::convertIds(1, 'master'), self::getLanguagesTable());

        return (object)$row;
    }

    public static function getTooltips()
    {
        $iaCore = iaCore::instance();

        $stmt = '`category` = :category AND `code` = :language';
        $iaCore->iaDb->bind($stmt, ['category' => self::CATEGORY_TOOLTIP, 'language' => $iaCore->iaView->language], 1);

        $rows = $iaCore->iaDb->keyvalue(['key', 'value'], $stmt, self::getTable());

        return is_array($rows) ? $rows : [];
    }

    public static function getTable()
    {
        return self::$_table;
    }

    public static function addPhrase($key, $value, $languageCode = null, $module = '', $category = self::CATEGORY_COMMON, $forceReplacement = true, $usedInApi = false)
    {
        if (!in_array($category, self::$_validCategories)) {
            return false;
        }

        if (is_null($languageCode)) {
            $result = [];
            foreach (iaCore::instance()->languages as $code => $language)
                $result[] = self::addPhrase($key, $value, $code, $module, $category, $forceReplacement);

            return !in_array(false, $result, true);
        }

        $iaDb = iaCore::instance()->iaDb;
        $iaDb->setTable(self::getTable());

        $languageCode = empty($languageCode) ? iaCore::instance()->iaView->language : $languageCode;

        $stmt = '`key` = :key AND `code` = :language AND `category` = :category AND `module` = :module';
        $iaDb->bind($stmt, [
            'key' => $key,
            'language' => $languageCode,
            'category' => $category,
            'module' => $module
        ]);

        $phrase = $iaDb->row(['original', 'value'], $stmt);

        if (empty($phrase)) {
            $result = $iaDb->insert([
                'key' => $key,
                'original' => $value,
                'value' => $value,
                'code' => $languageCode,
                'category' => $category,
                'module' => $module,
                'api' => $usedInApi
            ]);
        } else {
            $result = ($forceReplacement || ($phrase['value'] == $phrase['original']))
                ? $iaDb->update(['value' => $value], $stmt)
                : false;
        }

        $iaDb->resetTable();

        return (bool)$result;
    }

    public static function delete($key)
    {
        $iaDb = iaCore::instance()->iaDb;

        return (bool)$iaDb->delete(iaDb::convertIds($key, 'key'), self::getTable());
    }

    public static function getLanguagesTable()
    {
        return self::$_languagesTable;
    }
}
