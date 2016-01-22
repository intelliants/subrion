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

class iaPage extends abstractCore
{
	protected static $_table = 'pages';


	public function getUrlByName($pageName, $appendScriptPath = true)
	{
		static $pagesToUrlMap;

		if (is_null($pagesToUrlMap))
		{
			$pagesToUrlMap = $this->iaDb->keyvalue(array('name', 'alias'), null, self::getTable());
		}

		return isset($pagesToUrlMap[$pageName])
			? ($appendScriptPath ? IA_URL : '') . $pagesToUrlMap[$pageName]
			: null;
	}

	public function getByName($name, $status = iaCore::STATUS_ACTIVE)
	{
		return $this->iaDb->row_bind(
			iaDb::ALL_COLUMNS_SELECTION,
			'`name` = :name AND `status` = :status AND `service` != 1',
			array('name' => $name, 'status' => $status),
			self::getTable()
		);
	}

	protected function _getInfoByName($name)
	{
		$pageParams = $this->getByName($name);

		return array(
			'parent' => $pageParams['parent'],
			'title' => iaLanguage::get(sprintf('page_title_%s', $pageParams['name'])),
			'url' => $pageParams['alias'] ? $this->getUrlByName($pageParams['name']) : $pageParams['name'] . IA_URL_DELIMITER
		);
	}

	public function getParents($parentPageName, array &$chain)
	{
		if ($parentPageName)
		{
			$chain[] = $parent = $this->_getInfoByName($parentPageName);
			$this->getParents($parent['parent'], $chain);
		}
		else
		{
			$chain = array_reverse($chain);
		}
	}
}