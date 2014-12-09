<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

class iaPage extends abstractPlugin
{
	protected static $_table = 'pages';

	protected static $_adminTable = 'admin_pages';
	protected static $_adminGroupsTable = 'admin_pages_groups';

	public $extendedExtensions = array('htm', 'html', 'php');


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
		$sql =
			"SELECT DISTINCTROW p.*, IF(t.`value` IS NULL, p.`name`, t.`value`) `title`
			FROM `" . self::getTable(true) . "` p
				LEFT JOIN `" . $this->iaDb->prefix . iaLanguage::getTable() . "` t
					ON `key` = CONCAT('page_title_', p.`name`) AND t.`code` = '" . $this->iaView->language . "'
			WHERE p.`status` = 'active'
				AND p.`service` = 0 " . ($exclude ? "AND !FIND_IN_SET(p.`name`, '" . implode(',', $exclude) . "') " : ' ') .
			'ORDER BY t.`value`';

		return $this->iaDb->getAll($sql);
	}

	public function getGroups(array $exclusions = array())
	{
		$stmt = '`status` = :status AND `service` = 0';
		if ($exclusions)
		{
			$stmt.= " AND `name` NOT IN ('" . implode("','", array_map(array('iaSanitize', 'sql'), $exclusions)) . "')";
		}
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE));

		$pages = array();
		$result = array();

		$rows = $this->iaDb->all(array('id', 'name', 'group'), $stmt, null, null, self::getTable());
		foreach ($rows as $page)
		{
			$page['group'] || $page['group'] = 1;
			$pages[$page['group']][$page['id']] = iaLanguage::get('page_title_' . $page['name']);
		}

		$rows = $this->iaDb->all(array('title', 'id', 'name'), null, null, null, self::getAdminGroupsTable());
		foreach ($rows as $row)
		{
			if (isset($pages[$row['id']]))
			{
				$result[$row['id']] = array(
					'title' => $row['title'],
					'children' => $pages[$row['id']]
				);
			}
		}

		return $result;
	}

	public function getUrlByName($pageName)
	{
		static $pagesToUrlMap;

		if (is_null($pagesToUrlMap))
		{
			$pagesToUrlMap = $this->iaDb->keyvalue(array('name', 'alias'), null, self::getAdminTable());
		}

		if (isset($pagesToUrlMap[$pageName]))
		{
			return $pagesToUrlMap[$pageName] ? $pagesToUrlMap[$pageName] : $pageName;
		}

		return null;
	}

	public function getByName($pageName, $lookupThroughBackend = true)
	{
		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $pageName), $lookupThroughBackend ? self::getAdminTable() : self::getTable());
	}
}