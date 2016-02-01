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

abstract class iaAbstractControllerPackageBackend extends iaAbstractControllerBackend
{
	protected $_packageName;
	protected $_helperName;

	protected $_itemName;

	protected $_activityLog;

	protected $_iaField;

	protected $_setQuickSearch = true;


	public function __construct()
	{
		parent::__construct();

		$this->_iaField = $this->_iaCore->factory('field');

		$this->_packageName = IA_CURRENT_PACKAGE;
		$this->_path = IA_ADMIN_URL . $this->getPackageName() . IA_URL_DELIMITER . $this->getName() . IA_URL_DELIMITER;
		$this->_template = 'form-' . $this->getName();

		if ($this->_activityLog)
		{
			is_array($this->_activityLog) || $this->_activityLog = array();

			$this->_activityLog['path'] = $this->getPackageName() . IA_URL_DELIMITER . $this->getName();
			isset($this->_activityLog['item']) || $this->_activityLog['item'] = substr($this->getItemName(), 0, -1);
		}

		if ($this->_helperName)
		{
			$helperClass = $this->_iaCore->factoryPackage($this->_helperName, $this->getPackageName(), iaCore::ADMIN);
			$this->setHelper($helperClass);

			$this->getItemName() || $this->_setItemName($helperClass->getItemName());
			$this->setTable($helperClass::getTable());
		}

		$this->init();
	}

	public function init()
	{

	}

	public function getPackageName()
	{
		return $this->_packageName;
	}

	public function getItemName()
	{
		return $this->_itemName;
	}

	protected function _setItemName($itemName)
	{
		$this->_itemName = $itemName;
	}

	protected function _gridRead($params)
	{
		$action = empty($this->_iaCore->requestPath[0]) ? null : $this->_iaCore->requestPath[0];

		if (!is_null($action))
		{
			$methodName = '_getJson' . ucfirst($action);
			if (is_callable(array($this, $methodName)))
			{
				return call_user_func(array($this, $methodName), $params);
			}
		}

		return parent::_gridRead($params);
	}

	protected function _indexPage(&$iaView)
	{
		$this->_setQuickSearchActiveItem();

		$iaView->grid('_IA_URL_packages/' . $this->getPackageName() . '/js/admin/' . $this->getName());
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		$this->_setQuickSearchActiveItem();
		$this->_setSystemDefaults($entryData);

		$entryData['item'] = $this->getItemName();

		$sections = $this->_iaField->getGroups($this->getItemName());

		$iaView->assign('item_sections', $sections);
		$iaView->assign('plans', $this->_getPlans());
	}

	protected function _insert(array $entryData)
	{
		return parent::_entryAdd($entryData);
	}

	protected function _update(array $entryData, $entryId)
	{
		return parent::_entryUpdate($entryData, $entryId);
	}

	protected function _delete(array $entryId)
	{
		return parent::_entryDelete($entryId);
	}

	protected function _entryAdd(array $entryData)
	{
		$entryId = $this->_insert($entryData);

		if ($entryId)
		{
			$this->_writeLog(iaCore::ACTION_ADD, $entryData, $entryId);
			$this->updateCounters($entryId, $entryData, iaCore::ACTION_ADD);

			$this->_iaCore->startHook('phpListingAdded', array(
				'itemId' => $entryId,
				'itemName' => $this->getItemName(),
				'itemData' => $entryData
			));
		}

		return $entryId;
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		if (empty($entryId))
		{
			return false;
		}

		$currentData = $this->getById($entryId);

		if (empty($currentData))
		{
			return false;
		}

		$result = $this->_update($entryData, $entryId);

		if ($result)
		{
			$this->_writeLog(iaCore::ACTION_EDIT, $entryData, $entryId);
			$this->updateCounters($entryId, $entryData, iaCore::ACTION_EDIT, $currentData);

			$this->_iaCore->startHook('phpListingUpdated', array(
				'itemId' => $entryId,
				'itemName' => $this->getItemName(),
				'itemData' => $entryData,
				'previousData' => $currentData
			));
		}

		return $result;
	}

	protected function _entryDelete($entryId)
	{
		$result = false;

		if ($entryData = $this->getById($entryId))
		{
			$result = $this->_delete($entryId);

			if ($result)
			{
				$iaField = $this->_iaCore->factory('field');

				// we have to check for uploaded images of this listing
				if ($imageFields = $iaField->getImageFields($this->getItemName()))
				{
					$iaPicture = $this->_iaCore->factory('picture');

					foreach ($imageFields as $imageFieldName)
					{
						if (!empty($entryData[$imageFieldName]))
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

				$this->_writeLog(iaCore::ACTION_DELETE, $entryData, $entryId);

				$this->updateCounters($entryId, $entryData, iaCore::ACTION_DELETE);

				$this->_iaCore->startHook('phpListingRemoved', array(
					'itemId' => $entryId,
					'itemName' => $this->getItemName(),
					'itemData' => $entryData
				));
			}
		}

		return $result;
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		if ($this->getItemName())
		{
			$this->_iaCore->startHook('phpItemSaved', array(
				'action' => $action,
				'itemId' => $this->getEntryId(),
				'itemData' => $entry,
				'itemName' => $this->getItemName()
			));
		}
	}

	protected function _writeLog($action, array $entryData, $entryId)
	{
		if ($this->_activityLog)
		{
			$iaLog = $this->_iaCore->factory('log');

			$actionsMap = array(
				iaCore::ACTION_ADD => iaLog::ACTION_CREATE,
				iaCore::ACTION_EDIT => iaLog::ACTION_UPDATE,
				iaCore::ACTION_DELETE => iaLog::ACTION_DELETE
			);

			$title = (isset($entryData['title']) && $entryData['title'])
				? $entryData['title']
				: $this->_iaDb->one('title', iaDb::convertIds($entryId), self::getTable());
			$params = array_merge($this->_activityLog, array('name' => $title, 'id' => $entryId));

			$iaLog->write($actionsMap[$action], $params);
		}
	}

	public function updateCounters($entryId, array $entryData, $action, $previousData = null)
	{
		// within final class, the counters update routines should be placed here
	}

	protected function _setQuickSearchActiveItem()
	{
		if ($this->_setQuickSearch)
		{
			$this->_iaCore->iaView->assign('quickSearchItem', $this->getItemName());
		}
	}

	protected function _setSystemDefaults(&$entryData)
	{
		if (isset($entryData['featured']) && $entryData['featured'])
		{
			$entryData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($entryData['featured_end']));
		}
		else
		{
			$date = getdate();
			$date = mktime($date['hours'], $date['minutes'] + 1, 0, $date['mon'] + 1,$date['mday'], $date['year']);
			$entryData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, $date);
		}

		if (isset($entryData['sponsored']) && $entryData['sponsored'])
		{
			$entryData['sponsored_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($entryData['sponsored_end']));
		}

		if (isset($entryData['member_id']))
		{
			$entryData['owner'] = '';
			if ($entryData['member_id'] > 0)
			{
				$iaUsers = $this->_iaCore->factory('users');
				if ($ownerInfo = $iaUsers->getInfo((int)$entryData['member_id']))
				{
					$entryData['owner'] = $ownerInfo['fullname'] . ' (' . $ownerInfo['email'] . ')';
				}
			}
		}
	}


	protected function _getJsonTree(array $data)
	{
		$output = array();

		$rowsCount = $this->_iaDb->one(iaDb::STMT_COUNT_ROWS);
		$dynamicLoadMode = ($rowsCount > 500);

		$clause = $dynamicLoadMode ? sprintf('`parent_id` = %d', (int)$data['id']) : '1';
		$clause.= ' ORDER BY `title`';

		$rows = $this->_iaDb->all(array('id', 'title', 'parent_id', 'child'), $clause);

		foreach ($rows as $row)
		{
			$entry = array('id' => $row['id'], 'text' => $row['title']);

			$dynamicLoadMode
				? $entry['children'] = $row['child'] && $row['child'] != $row['id']
				: $entry['parent'] = (0 == $row['parent_id']) ? '#' : $row['parent_id'];

			$output[] = $entry;
		}

		return $output;
	}

	protected function _getPlans()
	{
		$iaPlan = $this->_iaCore->factory('plan');

		if ($plans = $iaPlan->getPlans($this->getItemName()))
		{
			foreach ($plans as &$plan)
			{
				list(, $plan['defaultEndDate']) = $iaPlan->calculateDates($plan['duration'], $plan['unit']);
			}
		}

		return $plans;
	}
}