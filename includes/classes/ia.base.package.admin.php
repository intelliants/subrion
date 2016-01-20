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

abstract class abstractPackageAdmin extends iaGrid
{
	protected $_activityLog;

	protected $_itemName;

	protected $_moduleUrl = '';

	protected $_packageName;

	protected $_statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE);

	public $dashboardStatistics;


	public function init()
	{
		if (empty($this->_moduleUrl))
		{
			$this->_moduleUrl = $this->getPackageName() . IA_URL_DELIMITER . $this->getItemName() . IA_URL_DELIMITER;
		}

		if ($this->_activityLog)
		{
			is_array($this->_activityLog) || $this->_activityLog = array();

			$this->_activityLog['path'] = trim($this->getModuleUrl(), IA_URL_DELIMITER);

			$itemName = $this->getItemName();

			if (!isset($this->_activityLog['icon']))
			{
				$this->_activityLog['icon'] = $itemName;
			}
			if (!isset($this->_activityLog['item']))
			{
				$itemName = substr($itemName, 0, -1);

				$this->_activityLog['item'] = $itemName;
			}
		}

		if ($this->dashboardStatistics)
		{
			is_array($this->dashboardStatistics) || $this->dashboardStatistics = array();

			$this->dashboardStatistics['url'] = $this->getModuleUrl();
			if (!isset($this->dashboardStatistics['icon']))
			{
				$this->dashboardStatistics['icon'] = $this->getItemName();
			}
		}

		parent::init();
	}

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

	public function getModuleUrl()
	{
		return $this->_moduleUrl;
	}

	public function getInfo($key)
	{
		static $cachedData;

		if (is_null($cachedData))
		{
			$cachedData = $this->iaDb->row_bind(array('id', 'type', 'title', 'url', 'version'), '`name` = :name', array('name' => $this->getPackageName()), 'extras');

			$cachedData['url'] = IA_URL . (IA_URL_DELIMITER == $cachedData['url'] ? '' : $cachedData['url']);
		}

		return isset($cachedData[$key]) ? $cachedData[$key] : null;
	}

	public function getById($id)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());
	}

	public function getDashboardStatistics($defaultProcessing = true)
	{
		$statuses = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 = 1 GROUP BY `status`', self::getTable());
		$total = 0;

		foreach ($this->getStatuses() as $status)
		{
			isset($statuses[$status]) || $statuses[$status] = 0;
			$total += $statuses[$status];
		}

		if ($defaultProcessing)
		{
			$data = array();
			$max = 0;
			$weekDay = getdate();
			$weekDay = $weekDay['wday'];
			$rows = $this->iaDb->all('DAYOFWEEK(DATE(`date_added`)) `day`, `status`, `date_added`', 'DATE(`date_added`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW())', null, null, self::getTable());

			foreach ($this->getStatuses() as $status) $data[$status] = array();
			foreach ($rows as $row)
			{
				isset($data[$row['status']][$row['day']]) || $data[$row['status']][$row['day']] = 0;
				$data[$row['status']][$row['day']]++;
			}
			foreach ($data as $key => &$days)
			{
				$i = null;
				for ($i = 1; $i < 8; $i++)
				{
					isset($days[$i]) || $days[$i] = 0;
					$max = max($max, $days[$i]);
				}
				ksort($days, SORT_NUMERIC);
				$days = implode(',', $days);
				$stArray[] = $key;
			}
		}

		return array_merge(array(
			'_format' => 'package',
			'data' => $defaultProcessing
				? array('array' => implode('|', $data), 'max' => $max, 'statuses' => implode('|', $stArray))
				: implode(',', $statuses),
			'rows' => $statuses,
			'item' => $this->getItemName(),
			'total' => number_format($total)
		), $this->dashboardStatistics);
	}

	public function insert(array $itemData)
	{
		$itemId = $this->iaDb->insert($itemData, null, self::getTable());

		if ($itemId)
		{
			$this->_writeLog(iaCore::ACTION_ADD, $itemData, $itemId);

			$this->updateCounters($itemId, $itemData, iaCore::ACTION_ADD);

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

		$currentData = $this->getById($id);

		if (empty($currentData))
		{
			return false;
		}

		$result = $this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

		if ($result)
		{
			$this->_writeLog(iaCore::ACTION_EDIT, $itemData, $id);

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
				if ($storageFields = $iaField->getStorageFields($this->getPackageName()))
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

				$this->_writeLog(iaCore::ACTION_DELETE, $entryData, $itemId);

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

	public function gridUpdate($params)
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		$params || $params = array();

		if (isset($params['id']) && is_array($params['id']) && count($params) > 1)
		{
			$ids = $params['id'];
			unset($params['id']);

			$total = count($ids);
			$affected = 0;

			foreach ($ids as $id)
			{
				if ($this->update($params, $id))
				{
					$affected++;
				}
			}

			if ($affected)
			{
				$result['result'] = true;
				$result['message'] = ($affected == $total)
					? iaLanguage::get('saved')
					: iaLanguage::getf('items_updated_of', array('num' => $affected, 'total' => $total));
			}
			else
			{
				$result['message'] = iaLanguage::get('db_error');
			}
		}

		return $result;
	}

	public function gridDelete($params, $languagePhraseKey = 'deleted')
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		if (isset($params['id']) && is_array($params['id']) && $params['id'])
		{
			$total = count($params['id']);
			$affected = 0;

			foreach ($params['id'] as $id)
			{
				if ($this->delete($id))
				{
					$affected++;
				}
			}

			if ($affected)
			{
				$result['result'] = true;
				if (1 == $total)
				{
					$result['message'] = iaLanguage::get($languagePhraseKey);
				}
				else
				{
					$result['message'] = ($affected == $total)
						? iaLanguage::getf('items_deleted', array('num' => $affected))
						: iaLanguage::getf('items_deleted_of', array('num' => $affected, 'total' => $total));
				}
			}
			else
			{
				$result['message'] = iaLanguage::get('db_error');
			}
		}

		return $result;
	}

	public function updateCounters($itemId, array $itemData, $action, $previousData = null)
	{
		// within final class, the counters update routines should be placed here
	}

	public function getSitemapEntries()
	{
		// should return URLs array to be used in sitemap creation
		return array();
	}

	protected function _writeLog($action, array $itemData, $itemId)
	{
		if ($this->_activityLog)
		{
			$iaLog = $this->iaCore->factory('log');

			$actionsMap = array(
				iaCore::ACTION_ADD => iaLog::ACTION_CREATE,
				iaCore::ACTION_EDIT => iaLog::ACTION_UPDATE,
				iaCore::ACTION_DELETE => iaLog::ACTION_DELETE
			);

			$title = (isset($itemData['title']) && $itemData['title'])
				? $itemData['title']
				: $this->iaDb->one('title', iaDb::convertIds($itemId), self::getTable());
			$params = array_merge($this->_activityLog, array('name' => $title, 'id' => $itemId));

			$iaLog->write($actionsMap[$action], $params);
		}
	}
}