<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

class iaLanguage
{
	const CATEGORY_ADMIN = 'admin';
	const CATEGORY_COMMON = 'common';
	const CATEGORY_FRONTEND = 'frontend';
	const CATEGORY_PAGE = 'page';
	const CATEGORY_TOOLTIP = 'tooltip';

	protected static $_table = 'language';
	protected static $_languagesTable = 'languages';

	protected static $_phrases = array();

	protected static $_validCategories = array(self::CATEGORY_ADMIN, self::CATEGORY_COMMON, self::CATEGORY_FRONTEND, self::CATEGORY_PAGE, self::CATEGORY_TOOLTIP);


	public function __construct(){}
	public function __clone(){}

	public function init()
	{
		$iaCore = iaCore::instance();

		// set list of available languages
		$iaCore->languages = $iaCore->iaDb->assoc(
			array('code', 'id', 'title', 'locale', 'date_format', 'direction', 'master', 'default', 'flagicon', 'iso' => 'code', 'status'),
			iaDb::EMPTY_CONDITION . ' ORDER BY `order` ASC',
			self::$_languagesTable
		);
	}

	public static function get($key, $default = null)
	{
		if (empty($key)) // false, empty string values
		{
			return false;
		}

		if (self::exists($key))
		{
			return self::$_phrases[$key];
		}
		else
		{
			if (INTELLI_DEBUG && is_null($default))
			{
				$iaCore = iaCore::instance();
				$cache = $iaCore->iaCache->get('nonexistent_phrases', 0, true);

				if (empty($cache))
				{
					$cache = array();
				}
				if (!in_array($key, $cache))
				{
					$cache[] = $key;
					$iaCore->iaCache->write('nonexistent_phrases', serialize($cache));
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

		if (empty($phrase))
		{
			return $phrase;
		}

		$search = array();
		foreach (array_keys($replaces) as $item)
		{
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
			? "`code` = '%s' AND `category` NOT IN('tooltip', 'admin') ORDER BY `extras`"
			: "`code` = '%s' AND `category` NOT IN('tooltip', 'frontend', 'page')";
		$where = sprintf($where, $languageCode);

		self::$_phrases = $iaCore->iaDb->keyvalue(array('key', 'value'), $where, self::getTable());
	}

	public static function getPhrases()
	{
		return self::$_phrases;
	}

	public static function getTooltips()
	{
		$iaCore = &iaCore::instance();

		$stmt = '`category` = :category AND `code` = :language';
		$iaCore->iaDb->bind($stmt, array('category' => self::CATEGORY_TOOLTIP, 'language' => $iaCore->iaView->language),1);

		$rows = $iaCore->iaDb->keyvalue(array('key', 'value'), $stmt, self::getTable());

		return is_array($rows) ? $rows : array();
	}

	public static function getTable()
	{
		return self::$_table;
	}

	public static function addPhrase($key, $value, $languageCode = '', $plugin = '', $category = self::CATEGORY_COMMON, $forceReplacement = true)
	{
		if (!in_array($category, self::$_validCategories))
		{
			return false;
		}

		$iaDb = iaCore::instance()->iaDb;
		$iaDb->setTable(self::getTable());

		$languageCode = empty($languageCode) ? iaCore::instance()->iaView->language : $languageCode;

		$stmt = '`key` = :key AND `code` = :language AND `category` = :category AND `extras` = :plugin';
		$iaDb->bind($stmt, array(
			'key' => $key,
			'language' => $languageCode,
			'category' => $category,
			'plugin' => $plugin
		));

		$phrase = $iaDb->row(array('original', 'value'), $stmt);

		if (empty($phrase))
		{
			$result = $iaDb->insert(array(
				'key' => $key,
				'original' => $value,
				'value' => $value,
				'code' => $languageCode,
				'category' => $category,
				'extras' => $plugin
			));
		}
		else
		{
			$result = ($forceReplacement || ($phrase['value'] == $phrase['original']))
				? $iaDb->update(array('value' => $value), $stmt)
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