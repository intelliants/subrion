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

class iaBackendController extends iaAbstractControllerBackend
{
	const PATTERN_TITLE = 'plan_title_';
	const PATTERN_DESCRIPTION = 'plan_description_';

	protected $_name = 'plans';

	protected $_tooltipsEnabled = true;

	protected $_gridColumns = array('item', 'cost', 'duration', 'recurring', 'cycles', 'unit', 'order', 'status');

	protected $_phraseAddSuccess = 'plan_added';
	protected $_phraseGridEntryDeleted = 'plan_deleted';

	private $_fields;
	private $_items;
	private $_languages;


	public function __construct()
	{
		parent::__construct();

		$this->setHelper($this->_iaCore->factory('plan'));
		$this->setTable(iaPlan::getTable());

		$this->_fields = $this->_getFieldsList();
		$this->_items = $this->_iaCore->factory('item')->getItems(true);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry['item'] = in_array($data['item'], $this->_items) ? $data['item'] : null;

		if (!$entry['item'])
		{
			$this->addMessage('incorrect_item');
		}

		if ($entry['item'] == iaUsers::getItemName())
		{
			if (isset($data['usergroup']))
			{
				$entry['usergroup'] = (int)$data['usergroup'];
			}
		}

		if (isset($this->_fields[$entry['item']]))
		{
			$entry['data'] = array();
			if (!empty($data['fields']) && !$this->getMessages())
			{
				$f = $this->_fields[$entry['item']];
				$array = array();
				foreach ($data['fields'] as $field)
				{
					if (in_array($field, $f[0]))
					{
						$entry['data']['fields'][] = $field;
						$array[] = $field;
					}
					elseif (in_array($field, $f[1]))
					{
						$entry['data']['fields'][] = $field;
					}
				}
				if ($array)
				{
					$this->_iaDb->update(array('for_plan' => 1), "`name` IN ('" . implode("','", $entry['data']['fields']) . "')", null, iaField::getTable());
				}
			}
			$entry['data'] = serialize($entry['data']);
		}

		$this->_iaCore->startHook('phpAdminAddPlanValidation');

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		$lang = array(
			'title' => $data['title'],
			'description' => $data['description']
		);

		foreach ($this->_iaCore->languages as $code => $language)
		{
			if (isset($lang['title'][$code]))
			{
				if (empty($lang['title'][$code]))
				{
					$this->addMessage(iaLanguage::getf('error_lang_title', array('lang' => $language['title'])), false);
				}
				elseif (!utf8_is_valid($lang['title'][$code]))
				{
					$lang['title'][$code] = utf8_bad_replace($lang['title'][$code]);
				}
			}

			if (isset($lang['description'][$code]))
			{
				if (empty($lang['description'][$code]))
				{
					$this->addMessage(iaLanguage::getf('error_lang_description', array('lang' => $language['title'])), false);
				}
				elseif (!utf8_is_valid($lang['description'][$code]))
				{
					$lang['description'][$code] = utf8_bad_replace($lang['description'][$code]);
				}
			}
		}

		$this->_languages = $lang;

		$entry['duration'] = isset($data['duration']) ? $data['duration'] : 0;
		if (!is_numeric($entry['duration']))
		{
			$this->addMessage('error_plan_duration');
		}

		$entry['cost'] = (float)$data['cost'];
		$entry['cycles'] = (int)$data['cycles'];
		$entry['unit'] = $data['unit'];
		$entry['status'] = $data['status'];
		$entry['recurring'] = (int)$data['recurring'];
		$entry['expiration_status'] = $data['expiration_status'];
		$entry['type'] = $data['type'];
		$entry['listings_limit'] = $data['listings_limit'];

		$this->_iaCore->startHook('phpAdminPlanCommonFieldFilled', array('item' => &$entry));

		$entry['cost'] || $this->_phraseAddSuccess = 'free_plan_added';

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		foreach ($this->_iaCore->languages as $code => $language)
		{
			iaLanguage::addPhrase(self::PATTERN_TITLE . $this->getEntryId(), iaSanitize::tags($this->_languages['title'][$code]), $code);
			iaLanguage::addPhrase(self::PATTERN_DESCRIPTION . $this->getEntryId(), $this->_languages['description'][$code], $code);
		}

		// save plan options
		$optionItems = $this->_iaDb->keyvalue(array('id', 'item'), null, iaPlan::getTableOptions());

		$this->_iaDb->setTable(iaPlan::getTableOptionValues());

		foreach ($data['options'] as $optionId => $values)
		{
			if (!isset($optionItems[$optionId]) || $optionItems[$optionId] != $entry['item']) continue;

			$where = sprintf('`plan_id` = %d AND `option_id` = %d', $this->getEntryId(), $optionId);
			$values = array(
				'plan_id' => $this->getEntryId(),
				'option_id' => (int)$optionId,
				'price' => isset($values['price']) ? $values['price'] : 0,
				'value' => $values['value']
			);

			$this->_iaDb->exists($where)
				? $this->_iaDb->update($values, $where)
				: $this->_iaDb->insert($values);
		}

		$this->_iaDb->resetTable();
	}

	protected function _modifyGridResult(array &$entries)
	{
		foreach ($entries as $key => &$entry)
		{
			$entry['title'] = iaLanguage::get(self::PATTERN_TITLE . $entry['id']);
			$entry['description'] = iaSanitize::tags(iaLanguage::get(self::PATTERN_DESCRIPTION . $entry['id']));
			$entry['item'] = iaLanguage::get($entry['item']);

			$entry['duration'].= ' ' . iaLanguage::get($entry['unit'] . ($entry['duration'] > 1 ? 's' : ''));
			if ($entry['recurring'] && $entry['cycles'] != -1)
			{
				$entry['duration'].= ' (' . $entry['cycles'] . ' ' . iaLanguage::get('cycles') . ')';
			}
			$entry['duration'] = strtolower($entry['duration']);

			unset($entries[$key]['unit'], $entries[$key]['cycles']);
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'item' => '',
			'cost' => '0.00',
			'duration' => 30,
			'unit' => iaPlan::UNIT_DAY,
			'status' => iaCore::STATUS_ACTIVE,
			'usergroup' => 0,
			'recurring' => false,
			'cycles' => 0,
			'type' => iaPlan::TYPE_SUBSCRIPTION,
			'listings_limit' => 0
		);
	}

	protected function _entryAdd(array $entryData)
	{
		$order = $this->_iaDb->getMaxOrder() + 1;
		$entryData['order'] = $order ? $order : 1;

		return $this->_iaDb->insert($entryData);
	}

	protected function _entryDelete($entryId)
	{
		$this->_iaCore->startHook('phpAdminBeforePlanDelete', array('entryId' => $entryId));

		$result = parent::_entryDelete($entryId);

		if ($result)
		{
			// here we should drop the "for_plan" column of fields
			// if there are no more plans exist
			if (0 === (int)$this->_iaDb->one(iaDb::STMT_COUNT_ROWS))
			{
				$this->_iaDb->update(array('for_plan' => 0), iaDb::convertIds(1, 'for_plan'), null, iaField::getTable());
			}

			iaLanguage::delete(self::PATTERN_TITLE . $entryId);
			iaLanguage::delete(self::PATTERN_DESCRIPTION . $entryId);
		}

		return $result;
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		if (isset($entryData['data']))
		{
			$entryData['data'] = unserialize($entryData['data']);
			empty($entryData['data']['fields']) || $entryData['data']['fields'] = array_reverse($entryData['data']['fields']);
		}
		else
		{
			$entryData['data'] = array();
		}

		// populating titles & descriptions
		if (empty($_POST['title']))
		{
			$this->_iaDb->setTable(iaLanguage::getTable());

			$stmt = "`key` = 'plan_title_" . $this->getEntryId() . "'";
			$entryData['title'] = $this->_iaDb->keyvalue(array('code', 'value'), $stmt);

			$stmt = "`key` = 'plan_description_" . $this->getEntryId() . "'";
			$entryData['description'] = $this->_iaDb->keyvalue(array('code', 'value'), $stmt);

			$this->_iaDb->resetTable();
		}
		else
		{
			list($entryData['title'], $entryData['description']) = array($_POST['title'], $_POST['description']);
		}
		//

		$units = $this->_iaDb->getEnumValues($this->getTable(), 'unit');
		$units = $units ? array_values($units['values']) : array();

		$usergroups = $this->_iaCore->factory('users')->getUsergroups();
		unset($usergroups[iaUsers::MEMBERSHIP_ADMINISTRATOR], $usergroups[iaUsers::MEMBERSHIP_GUEST]);

		$iaView->assign('options', $this->_getOptions());
		$iaView->assign('usergroups', $usergroups);
		$iaView->assign('fields', $this->_fields);
		$iaView->assign('items', $this->_items);
		$iaView->assign('expiration_statuses', $this->_getItemsStatuses());
		$iaView->assign('units', $units);
	}

	private function _getFieldsList()
	{
		$this->_iaCore->factory('field');

		$fields = array();
		$rows = $this->_iaDb->all(array('name', 'item', 'for_plan', 'required'), ' 1=1 ORDER BY `for_plan` DESC', null, null, iaField::getTable());
		foreach ($rows as $row)
		{
			$type = $row['for_plan'];
			if ($row['required'] == 1)
			{
				$type = 2; // required
			}
			if (!isset($fields[$row['item']]))
			{
				$fields[$row['item']] = array(2 => array(), 1 => array(), 0 => array());
			}

			$fields[$row['item']][$type][] = $row['name'];
		}

		return $fields;
	}

	private function _getItemsStatuses()
	{
		$result = array();

		$iaItem = $this->_iaCore->factory('item');

		foreach ($this->_items as $itemName)
		{
			$statuses = array();

			$className = ucfirst(substr($itemName, 0, -1));
			$itemClassInstance = (iaUsers::getItemName() == $itemName)
				? $this->_iaCore->factory('users')
				: $this->_iaCore->factoryPackage($className, $iaItem->getPackageByItem($itemName));
			if ($itemClassInstance && method_exists($itemClassInstance, 'getStatuses'))
			{
				$statuses = $itemClassInstance->getStatuses();
			}

			$result[$itemName] = implode(',', $statuses);
		}

		return $result;
	}

	protected function _getOptions()
	{
		$result = array();

		$values = $this->_iaDb->assoc(array('option_id', 'price', 'value'), iaDb::convertIds($this->getEntryId(), 'plan_id'), iaPlan::getTableOptionValues());
		$options = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, iaPlan::getTableOptions());

		foreach ($options as $option)
		{
			$option['values'] = isset($values[$option['id']])
				? $values[$option['id']]
				: array('price' => 0, 'value' => $option['default_value']);
			$result[$option['item']][] = $option;
		}

		return $result;
	}
}