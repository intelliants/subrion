<?php
//##copyright##

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
			foreach ($data as &$days)
			{
				$i = null;
				for ($i = 1; $i < 8; $i++)
				{
					isset($days[$i]) || $days[$i] = 0;
					$max = max($max, $days[$i]);
				}
				ksort($days, SORT_NUMERIC);
				$days = implode(',', $days);
			}
		}

		return array_merge(array(
			'_format' => 'package',
			'data' => $defaultProcessing
				? array('array' => implode('|', $data), 'max' => $max)
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
		}

		return $itemId;
	}

	public function update(array $itemData, $id)
	{
		if (empty($id))
		{
			return false;
		}

		$result = (bool)$this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

		if ($result)
		{
			$this->_writeLog(iaCore::ACTION_EDIT, $itemData, $id);
			$this->updateCounters($id, $itemData, iaCore::ACTION_EDIT);
		}

		return $result;
	}

	public function delete($entryId)
	{
		$result = false;

		if ($entryData = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($entryId), self::getTable()))
		{
			$result = (bool)$this->iaDb->delete(iaDb::convertIds($entryId), self::getTable());

			if ($result)
			{
				$this->_writeLog(iaCore::ACTION_DELETE, $entryData, $entryId);

				// we have to check for uploaded images of this listing
				if ($imageFields = $this->iaCore->factory('field')->getImageFields($this->getPackageName()))
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

				$this->updateCounters($entryId, $entryData, iaCore::ACTION_DELETE);
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

	public function updateCounters($itemId, array $itemData, $action)
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