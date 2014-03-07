<?php
//##copyright##

abstract class abstractPackageFront extends abstractCore
{
	protected $_itemName;

	protected $_packageName;

	protected $_statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE);


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

		empty($itemId) || $this->updateCounters($itemId, $itemData, iaCore::ACTION_ADD);

		return $itemId;
	}

	public function update(array $itemData, $id)
	{
		$result = (bool)$this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

		empty($result) || $this->updateCounters($id, $itemData, iaCore::ACTION_EDIT);

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

				$this->updateCounters($itemId, $entryData, iaCore::ACTION_DELETE);
			}
		}

		return $result;
	}

	public function updateCounters($itemId, array $itemData, $action)
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
}