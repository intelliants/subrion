<?php
//##copyright##

class iaItem extends abstractCore
{
	const TYPE_PACKAGE = 'package';
	const TYPE_PLUGIN = 'plugin';

	protected static $_table = 'extras';
	protected static $_favoritesTable = 'favorites';
	protected static $_itemsTable = 'items';

	private $_itemTools;


	public static function getFavoritesTable()
	{
		return self::$_favoritesTable;
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
	* @return array
	*/
	public function getPackageItems()
	{
		$result = array();

		$itemsInfo = $this->getItemsInfo();
		foreach ($itemsInfo as $itemInfo)
		{
			$result[$itemInfo['item']] = $itemInfo['package'];
		}

		return $result;
	}

	/**
	 * Returns items parameters
	 * @return array
	 */
	public function getItemsInfo($payableOnly = false)
	{
		static $itemsInfo;

		if (is_null($itemsInfo))
		{
			$itemsInfo = $this->iaDb->all('`item`, `package`, IF(`table_name` != \'\', `table_name`, `item`) `table_name`', $payableOnly ? ' AND `payable` = 1' : '', null, null, self::$_itemsTable);
			$itemsInfo = is_array($itemsInfo) ? $itemsInfo : array();
		}

		return $itemsInfo;
	}

	/**
	 * Returns list of items
	 * @return array
	 */
	public function getItems()
	{
		return array_keys($this->getPackageItems());
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
		$result = $this->iaDb->one_bind('table_name', '`item` = :item', array('item' => $item), self::$_itemsTable);
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
	 * @param $plugin
	 * @param $items
	 * @return void
	 */
	public function setEnabledItemsForPlugin($plugin, $items)
	{
		if ($plugin)
		{
			$this->iaView->set($plugin.'_items_enabled', implode(',', $items), true);
		}
	}

	/**
	 * Return list of items with favorites field
	 * @param $listings
	 * @param $aItem
	 * @return array
	 */
	public function updateItemsFavorites($listings, $itemName)
	{
		if (!iaUsers::hasIdentity() || empty($itemName))
		{
			return $listings;
		}

		$itemsList = array();

		foreach ($listings as $entry)
		{
			if ( ('members' == $itemName && $entry['id'] != iaUsers::getIdentity()->id)
				|| (isset($entry['member_id']) && $entry['member_id'] != iaUsers::getIdentity()->id))
			{
				$itemsList[] = $entry['id'];
			}
		}

		if (empty($itemsList))
		{
			return $listings;
		}

		$itemsFavorites = $this->iaDb->onefield('`id`', "`id` IN ('".implode("','", $itemsList)."') AND `item` = '{$itemName}' AND `member_id` = " . iaUsers::getIdentity()->id, 0, null, $this->getFavoritesTable());

		if (empty($itemsFavorites))
		{
			return $listings;
		}

		foreach ($listings as $key => $value)
		{
			if (('members' == $itemName && $value['id'] != iaUsers::getIdentity()->id && in_array($value['id'], $itemsFavorites))
			   	|| (isset($value['member_id']) && $value['member_id'] != iaUsers::getIdentity()->id && in_array($value['id'], $itemsFavorites))
			)
			{
				$listings[$key]['favorite'] = 1;
			}
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

		return (bool)$this->iaDb->exists($stmt, null, self::getTable());
	}

	public function setItemTools($params = null)
	{
		if (is_null($params))
		{
			return $this->_itemTools;
		}

		$this->_itemTools[] = $params;
	}
}