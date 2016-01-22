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

abstract class abstractPackageFront extends abstractCore
{
	protected $_itemName;

	protected $_packageName;

	protected $_statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE);

	public $coreSearchEnabled = false;
	public $coreSearchOptions = array();


	public function getPackageName()
	{
		return $this->_packageName;
	}

	public function getItemName()
	{
		return $this->_itemName;
	}

	public function getStatuses()
	{
		return $this->_statuses;
	}

	public function url($type, $data)
	{
		return isset($data['url']) ? $this->getInfo('url') . $data['url'] : '';
	}

	public function getInfo($key)
	{
		$values = &$this->iaCore->packagesData[$this->getPackageName()];

		return isset($values[$key]) ? $values[$key] : null;
	}

	public function accountActions($params)
	{
		return array('', '');
	}

	public function getById($id)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());
	}

	public function insert(array $itemData)
	{
		$itemId = $this->iaDb->insert($itemData, null, self::getTable());

		if ($itemId)
		{
			$this->updateCounters($itemId, $itemData, iaCore::ACTION_ADD);

			// finally, notify plugins
			$this->iaCore->startHook('phpListingAdded', array(
				'itemId' => $itemId,
				'itemName' => $this->getItemName(),
				'itemData' => $itemData
			));
		}

		return $itemId;
	}

	public function update(array $itemData, $id)
	{
		if (empty($id))
		{
			return false;
		}

		$currentData = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());
		$result = (bool)$this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

		if ($result)
		{
			$this->updateCounters($id, $itemData, iaCore::ACTION_EDIT, $currentData);

			$this->iaCore->startHook('phpListingUpdated', array(
				'itemId' => $id,
				'itemName' => $this->getItemName(),
				'itemData' => $itemData,
				'previousData' => $currentData
			));
		}

		return $result;
	}

	public function delete($itemId)
	{
		$result = false;

		if ($entryData = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($itemId), self::getTable()))
		{
			$result = (bool)$this->iaDb->delete(iaDb::convertIds($itemId), self::getTable());

			if ($result)
			{
				$iaField = $this->iaCore->factory('field');

				// delete images field values
				if ($imageFields = $iaField->getImageFields($this->getItemName()))
				{
					$iaPicture = $this->iaCore->factory('picture');

					foreach ($imageFields as $imageFieldName)
					{
						if (isset($entryData[$imageFieldName]) && $entryData[$imageFieldName])
						{
							$iaPicture->delete($entryData[$imageFieldName]);
						}
					}
				}

				// delete storage field values
				if ($storageFields = $iaField->getStorageFields($this->getItemName()))
				{
					foreach ($storageFields as $storageFieldName)
					{
						if (isset($entryData[$storageFieldName]) && $entryData[$storageFieldName])
						{
							if (':' == $entryData[$storageFieldName][1])
							{
								$unpackedData = unserialize($entryData[$storageFieldName]);
								if (is_array($unpackedData) && $unpackedData)
								{
									foreach ($unpackedData as $oneFile)
									{
										iaUtil::deleteFile(IA_UPLOADS . $oneFile['path']);
									}
								}
							}
						}
					}
				}

				$this->updateCounters($itemId, $entryData, iaCore::ACTION_DELETE);

				$this->iaCore->startHook('phpListingRemoved', array(
					'itemId' => $itemId,
					'itemName' => $this->getItemName(),
					'itemData' => $entryData
				));
			}
		}

		return $result;
	}

	public function updateCounters($itemId, array $itemData, $action, $previousData = null)
	{
		// within final class, the counters update routines should be placed here
	}

	/**
	 * Increments the number of views for a specified item
	 *
	 * Application should ensure if an item is in active status
	 * and provide appropriate DB column name if differs from "views_num"
	 */
	public function incrementViewsCounter($itemId, $columnName = 'views_num')
	{
		$viewsTable = 'views_log';

		$itemName = $this->getItemName();
		$ipAddress = $this->iaCore->util()->getIp();
		$date = date(iaDb::DATE_FORMAT);

		if ($this->iaDb->exists('`item` = :item AND `item_id` = :id AND `ip` = :ip AND `date` = :date', array('item' => $itemName, 'id' => $itemId, 'ip' => $ipAddress, 'date' => $date), $viewsTable))
		{
			return false;
		}

		$this->iaDb->insert(array('item' => $itemName, 'item_id' => $itemId, 'ip' => $ipAddress, 'date' => $date), null, $viewsTable);
		$result = $this->iaDb->update(null, iaDb::convertIds($itemId), array($columnName => '`' . $columnName . '` + 1'), self::getTable());

		return (bool)$result;
	}

	public function coreSearch($stmt, $start, $limit, $order)
	{
		$order = empty($order) ? '' : ' ORDER BY ' . $order;

		$rows = $this->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' ' . iaDb::ALL_COLUMNS_SELECTION, $stmt . $order, $start, $limit, self::getTable());
		$count = $this->iaDb->foundRows();

		return array($count, $rows);
	}

	public function coreSearchTranslateColumn($column, $value)
	{
		return null;
	}
}