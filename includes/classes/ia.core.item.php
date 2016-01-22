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

class iaItem extends abstractCore
{
	const TYPE_PACKAGE = 'package';
	const TYPE_PLUGIN = 'plugin';

	protected static $_table = 'items';
	protected static $_favoritesTable = 'favorites';
	protected static $_extrasTable = 'extras';

	private $_itemTools;


	public static function getFavoritesTable()
	{
		return self::$_favoritesTable;
	}

	public static function getExtrasTable()
	{
		return self::$_extrasTable;
	}

	public function getFavoritesByMemberId($memberId)
	{
		$stmt = "`item` IN (':items') AND `member_id` = :user";
		$stmt = iaDb::printf($stmt, array('items' => implode("','", $this->getItems()), 'user' => (int)$memberId));

		$result = array();

		if ($rows = $this->iaDb->all(array('item', 'id'), $stmt, null, null, self::getFavoritesTable()))
		{
			foreach ($rows as $row)
			{
				$key = $row['item'];
				isset($result[$key]) || $result[$key] = array();

				$result[$key][] = $row['id'];
			}
		}

		return $result;
	}

	/**
	* Returns array with keys of available items and values - packages titles
	*
	* @param bool $payableOnly - flag to return items, that can be paid
	*
	* @return array
	*/
	public function getPackageItems($payableOnly = false)
	{
		$result = array();

		$itemsInfo = $this->getItemsInfo($payableOnly);
		foreach ($itemsInfo as $itemInfo)
		{
			$result[$itemInfo['item']] = $itemInfo['package'];
		}

		return $result;
	}

	/**
	 * Returns items list
	 *
	 * @param bool $payableOnly - flag to return items, that can be paid
	 *
	 * @return array
	 */
	public function getItemsInfo($payableOnly = false)
	{
		static $itemsInfo;

		if (is_null($itemsInfo))
		{
			$itemsInfo = $this->iaDb->all('`item`, `package`, IF(`table_name` != \'\', `table_name`, `item`) `table_name`', $payableOnly ? '`payable` = 1' : '', null, null, self::getTable());
			$itemsInfo = is_array($itemsInfo) ? $itemsInfo : array();

			// get active packages
			$packages = $this->iaDb->onefield('name', "`type` = 'package' AND `status` = 'active'", null, null, self::getExtrasTable());
			foreach($itemsInfo as $key => $itemInfo)
			{
				if ('core' != $itemInfo['package'] && !in_array($itemInfo['package'], $packages))
				{
					unset($itemsInfo[$key]);
				}
			}

		}

		return $itemsInfo;
	}

	/**
	 * Returns list of items
	 *
	 * @param bool $payableOnly - flag to return items, that can be paid
	 *
	 * @return array
	 */
	public function getItems($payableOnly = false)
	{
		return array_keys($this->getPackageItems($payableOnly));
	}

	protected function _searchItems($search, $type = 'item')
	{
		$items = $this->getPackageItems();
		$result = array();

		foreach ($items as $item => $package)
		{
			if ($search == $$type)
			{
				if ($type == 'item')
				{
					return $package;
				}
				else
				{
					$result[] = $item;
				}
			}
		}

		return ($type == 'item') ? false : $result;
	}

	/**
	 * Returns list of items by package name
	 * @alias _searchItems
	 * @param string $packageName
	 * @return array
	 */
	public function getItemsByPackage($packageName)
	{
		return $this->_searchItems($packageName, 'package');
	}

	/**
	 * Returns package name by item name
	 * @alias _searchItems
	 * @param $search
	 * @return string|bool
	 */
	public function getPackageByItem($search)
	{
		return $this->_searchItems($search, 'item');
	}

	/**
	 * Returns item table name
	 *
	 * @param $item item name
	 *
	 * @return string
	 */
	public function getItemTable($item)
	{
		$result = $this->iaDb->one_bind('table_name', '`item` = :item', array('item' => $item), self::getTable());
		$result || $result = $item;

		return $result;
	}

	/**
	 * Returns an array of enabled items for specified plugin
	 * @param $plugin
	 * @return array
	 */
	public function getEnabledItemsForPlugin($plugin)
	{
		$result = array();
		if ($plugin)
		{
			$items = $this->iaCore->get($plugin . '_items_enabled');
			if ($items)
			{
				$result = explode(',', $items);
			}
		}

		return $result;
	}

	/**
	 * Set items for specified plugin
	 *
	 * @param string $plugin plugin name
	 * @param array $items items list
	 */
	public function setEnabledItemsForPlugin($plugin, $items)
	{
		if ($plugin)
		{
			$this->iaView->set($plugin . '_items_enabled', implode(',', $items), true);
		}
	}

	/**
	 * Return list of items with favorites field
	 *
	 * @param array $listings listings to be processed
	 * @param $itemName item name
	 *
	 * @return mixed
	 */
	public function updateItemsFavorites($listings, $itemName)
	{
		if (empty($itemName))
		{
			return $listings;
		}

		if (!iaUsers::hasIdentity())
		{
			if (isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items']))
			{
				$itemsFavorites = array_keys($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items']);
			}
		}
		else
		{
			$itemsList = array();
			foreach ($listings as $entry)
			{
				if (
					('members' == $itemName && $entry['id'] != iaUsers::getIdentity()->id) ||
					(isset($entry['member_id']) && $entry['member_id'] != iaUsers::getIdentity()->id)
				)
				{
					$itemsList[] = $entry['id'];
				}
			}

			if (empty($itemsList))
			{
				return $listings;
			}

			// get favorites
			$itemsFavorites = $this->iaDb->onefield('`id`', "`id` IN ('" . implode("','", $itemsList) . "') && `item` = '{$itemName}' && `member_id` = " . iaUsers::getIdentity()->id, 0, null, $this->getFavoritesTable());
		}

		if (empty($itemsFavorites))
		{
			return $listings;
		}

		// process listing and set flag is in favorites array
		foreach ($listings as &$listing)
		{
			$listing['favorite'] = (int)in_array($listing['id'], $itemsFavorites);
		}

		return $listings;
	}

	public function isExtrasExist($extrasName, $type = null)
	{
		$stmt = iaDb::printf("`name` = ':name' AND `status` = ':status'", array(
			'name' => $extrasName,
			'status' => iaCore::STATUS_ACTIVE
		));

		if ($type)
		{
			$stmt .= iaDb::printf(" AND `type` = ':type'", array('type' => $type));
		}

		return (bool)$this->iaDb->exists($stmt, null, self::getExtrasTable());
	}

	public function setItemTools($params = null)
	{
		if (is_null($params))
		{
			return $this->_itemTools;
		}

		if (isset($params['id']) && $params['id'])
		{
			$this->_itemTools[$params['id']] = $params;
		}
		else
		{
			$this->_itemTools[] = $params;
		}
	}
}