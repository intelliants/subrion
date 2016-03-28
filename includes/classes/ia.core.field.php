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

class iaField extends abstractCore
{
	const CHECKBOX = 'checkbox';
	const COMBO = 'combo';
	const DATE = 'date';
	const IMAGE = 'image';
	const NUMBER = 'number';
	const PICTURES = 'pictures';
	const RADIO = 'radio';
	const STORAGE = 'storage';
	const TEXT = 'text';
	const TEXTAREA = 'textarea';
	const URL = 'url';
	const TREE = 'tree';

	const RELATION_DEPENDENT = 'dependent';
	const RELATION_PARENT = 'parent';
	const RELATION_REGULAR = 'regular';

	const DEFAULT_LENGTH = 100;

	protected static $_table = 'fields';
	protected static $_tableGroups = 'fields_groups';
	protected static $_tablePages = 'fields_pages';
	protected static $_tableRelations = 'fields_relations';


	public static function getTableGroups()
	{
		return self::$_tableGroups;
	}

	public static function getTablePages()
	{
		return self::$_tablePages;
	}

	public static function getTableRelations()
	{
		return self::$_tableRelations;
	}

	public function getByItemName($itemName)
	{
		$fields = array();

		$stmt = '`status` = :status AND `item` = :item';
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'item' => $itemName));

		if ($rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt . ' ORDER BY `order`', null, null, self::getTable()))
		{
			$fieldsList = array();

			foreach ($rows as $row)
			{
				if (in_array($row['type'], array(self::CHECKBOX, self::COMBO, self::RADIO)))
				{
					if (self::CHECKBOX == $row['type'])
					{
						$row['default'] = explode(',', $row['default']);
					}

					$values = explode(',', $row['values']);

					$row['values'] = array();
					foreach ($values as $v)
					{
						$row['values'][$v] = iaLanguage::get('field_' . $row['name'] . '_' . $v);
					}
				}

				$fieldsList[] = $row['name'];
				$fields[] = $row;
			}

			self::_handleRelations($itemName, $fieldsList, $fields);
		}

		return $fields;
	}

	public function filterByGroup(&$items, $item = false, $params = array())
	{
		foreach (array('page', 'where', 'not_empty') as $key)
		{
			isset($params[$key]) || $params[$key] = false;
		}

		$sections = $this->_getFieldgroups($params['page'], $item, $params['where'], $items, $params);

		if ($params['not_empty'])
		{
			if ($sections)
			{
				foreach ($sections as $section)
				{
					if (isset($section['fields']) && $section['fields'] && is_array($section['fields']))
					{
						foreach ($section['fields'] as $field)
						{
							if (isset($items[$field['name']]) && $items[$field['name']])
							{
								return $sections;
							}
						}
					}
				}
			}

			return false;
		}

		return $sections;
	}

	public function filter(&$items, $itemName, $params = array())
	{
		foreach (array('page', 'where', 'filter') as $key)
		{
			isset($params[$key]) || $params[$key] = false;
		}

		if ($params['page'] === false && iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType())
		{
			$params['page'] = 'admin';
		}

		isset($params['info']) || $params['info'] = true;
		if ($params['filter'] !== false && !is_array($params['filter']))
		{
			$params['filter'] = explode(',', $params['filter']);
		}

		$fieldsList = self::getAcoFieldsList($params['page'], $itemName, $params['where'], $params['info'], $items, $params);

		if (!is_array($items))
		{
			return $fieldsList;
		}

		if (iaCore::ADMIN == $params['page'])
		{
			return $fieldsList;
		}

		$type = 'simple';
		if (is_array(current($items)))
		{
			$type = 'group';
		}

		$forPlans = array();
		$fields = array();
		$empty = array();
		foreach ($fieldsList as $key => $field)
		{
			$empty[$field['name']] = $field['empty_field'];
			if (!$field['for_plan'] || $field['required'])
			{
				$fields[] = $field['name'];
			}
			else
			{
				$forPlans[] = $field['name'];
			}
			if ($params['filter'] && in_array($field['name'], $params['filter']))
			{
				unset($fieldsList[$key]);
			}
		}

		if ('simple' == $type)
		{
			$items = $this->_checkItem($items, $itemName, $fields, $forPlans, $empty);
		}
		else
		{
			foreach ($items as $key => $value)
			{
				$items[$key] = $this->_checkItem($value, $itemName, $fields, $forPlans, $empty);
			}
		}

		return $fieldsList;
	}

	protected function _checkItem($items, $itemName, $fields, $forPlans, $empty)
	{
		if ($forPlans)
		{
			$iaPlan = $this->iaCore->factory('plan');

			$plans = $iaPlan->getPlans($itemName);

			if (isset($items[iaPlan::SPONSORED_PLAN_ID]) && $items[iaPlan::SPONSORED_PLAN_ID] != 0 && isset($plans[$items[iaPlan::SPONSORED_PLAN_ID]]))
			{
				if (isset($plans[$items[iaPlan::SPONSORED_PLAN_ID]]['data']['fields']))
				{
					$planFields = $plans[$items[iaPlan::SPONSORED_PLAN_ID]]['data']['fields'];
					foreach ($forPlans as $field)
					{
						if (in_array($field, $planFields))
						{
							$fields[] = $field;
						}
					}
				}
			}
		}

		foreach ($items as $field => $value)
		{
			if (!in_array($field, $fields))
			{
				if (isset($empty[$field]))
				{
					$items[$field] = $empty[$field];
				}
			}
		}

		return $items;
	}

	/**
	 * getAcoFieldsList
	 *
	 * @obsolete should not be used
	 */
	public static function getAcoFieldsList($pageName = null, $itemName = null, $aWhere = '', $aAllFieldInfo = false, $aItemData = false, $params = array())
	{
		$iaCore = iaCore::instance();
		$iaView = &$iaCore->iaView;
		$iaAcl = $iaCore->factory('acl');

		$pageName = $pageName ? $pageName : $iaView->name();
		$itemName = $itemName ? $itemName : $iaView->get('extras');

		$selection = 'f.' . ($aAllFieldInfo || $pageName == 'admin' ? iaDb::ALL_COLUMNS_SELECTION : '`name`');
		if (isset($params['selection']) && $params['selection'])
		{
			$selection = $params['selection'];
		}

		$sql = "SELECT $selection ";

		if (iaCore::ADMIN == $pageName)
		{
			$aAllFieldInfo = true;
			$sql .= "FROM `" . self::getTable(true) . "` f " .
				"WHERE f.`status` = 'active' AND f.`item` = '{$itemName}' "
				. ($aWhere ? ' AND ' . $aWhere : '');
		}
		elseif ('all' == $pageName)
		{
			$sql .= "FROM `" . self::getTable(true) . "` f " .
				"WHERE " .
					"f.`status` = 'active' AND " .
					"f.`item` = '{$itemName}' AND " .
					"f.`adminonly` = 0 "
				. ($aWhere ? ' AND ' . $aWhere : '');
			$sql .= $aItemData['sponsored_plan_id'] && (!$aItemData || $aItemData['sponsored']) ? " AND (`plans`='' OR FIND_IN_SET('{$aItemData['sponsored_plan_id']}', `plans`)) " : " AND `plans`='' ";
		}
		else
		{
			$sql .= 'FROM `' . $iaCore->iaDb->prefix . self::getTablePages() . '` fp ' .
					'LEFT JOIN `' . $iaCore->iaDb->prefix . self::getTable() . '` f ON (fp.`field_id` = f.`id`) ' .
					"WHERE fp.`page_name` = '{$pageName}' AND f.`status` = 'active' AND f.`item` = '{$itemName}' AND f.`adminonly` = 0 "
						. ($aWhere ? ' AND ' . $aWhere : '');
			$sql .= !empty($aItemData['sponsored_plan_id']) && (!$aItemData || $aItemData['sponsored']) ? " AND (`plans`='' OR FIND_IN_SET('{$aItemData['sponsored_plan_id']}', `plans`)) " : " AND `plans`='' ";
		}

		$sql .= 'ORDER BY ' . (empty($params['order']) ? 'f.`order`' : $params['order']);

		$rows = $iaCore->iaDb->getAll($sql);
		$fieldNames = array();

		foreach ($rows as $key => $entry)
		{
			if (isset($entry['name']) && $entry['name'])
			{
				if ($iaAcl->checkAccess('field', $itemName . '_' . $entry['name']))
				{
					$fieldNames[$entry['id']] = $entry['name'];
					continue;
				}
			}

			unset($rows[$key]);
		}

		self::_handleRelations($itemName, $fieldNames, $rows);

		if ($aAllFieldInfo)
		{
			return $rows;
		}

		$fields = array();
		if ($rows)
		{
			foreach ($rows as $row)
			{
				$fields[] = $row['name'];
			}
		}

		return $fields;
	}

	protected static function _handleRelations($itemName, array $fieldsList, array &$fields)
	{
		$iaDb = iaCore::instance()->iaDb;

		$stmt = sprintf("`field` IN('%s') AND `item` = '%s'", implode("','", $fieldsList), $itemName);
		$relations = $iaDb->all(array('field', 'element', 'child'), $stmt, null, null, self::getTableRelations());

		$relationsMap = array();
		foreach ($relations as $entry)
		{
			$relationsMap[$entry['field']][$entry['child']][] = $entry['element'];
		}

		foreach ($fields as &$entry)
		{
			$entry['children'] = isset($relationsMap[$entry['name']]) ? $relationsMap[$entry['name']] : array();
		}
	}

	private function _getFieldgroups($aco = null, $itemName = null, $aWhere = '', &$itemData, $params = array())
	{
		$aco = $aco ? $aco : (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType() ? 'admin' : $this->iaView->name());
		$itemName = $itemName ? $itemName : $this->iaView->get('extras');

		$_params = array('page' => $aco, 'where' => $aWhere, 'filter' => '');
		foreach ($_params as $key => $value)
		{
			isset($params[$key]) || $params[$key] = $value;
		}

		$fields = $this->filter($itemData, $itemName, $params);
		if (empty($fields))
		{
			return array();
		}

		// get all available groups for item
		$groups = $this->iaDb->assoc(array('id', 'name', 'order', 'collapsible', 'collapsed', 'tabview', 'tabcontainer'), "`item` = '{$itemName}' ORDER BY `order`", self::getTableGroups());

		foreach ($fields as $fieldInfo)
		{
			if (self::PICTURES == $fieldInfo['type'])
			{
				$fieldInfo['values'] = empty($fieldInfo['values']) ? array() : explode(',', $fieldInfo['values']);
			}

			if (in_array($fieldInfo['type'], array(self::CHECKBOX, self::COMBO, self::RADIO)))
			{
				if ($fieldInfo['type'] == self::CHECKBOX)
				{
					$fieldInfo['default'] = explode(',', $fieldInfo['default']);
				}

				$values = explode(',', $fieldInfo['values']);

				$fieldInfo['values'] = array();
				if ($values)
				{
					foreach ($values as $v)
					{
						$k = 'field_' . $fieldInfo['name'] . '_' . $v;
						$fieldInfo['values'][$v] = iaLanguage::get($k);
					}
				}
			}

			isset($fieldInfo['class']) || $fieldInfo['class'] = 'fieldzone';

			if ($fieldInfo['plans'])
			{
				foreach (explode(',', $fieldInfo['plans']) as $p)
				{
					$fieldInfo['class'] .= sprintf(' plan_%d ', $p);
				}
			}

			if (empty($fieldInfo['fieldgroup_id']) || empty($groups[$fieldInfo['fieldgroup_id']]))
			{
				$fieldInfo['fieldgroup_id'] = '___empty___';

				// emulate tab to avoid isset checks
				$groups[$fieldInfo['fieldgroup_id']]['name'] = $fieldInfo['fieldgroup_id'];
				$groups[$fieldInfo['fieldgroup_id']]['tabview'] = '';
				$groups[$fieldInfo['fieldgroup_id']]['tabcontainer'] = '';
				$groups[$fieldInfo['fieldgroup_id']]['collapsible'] = false;
				$groups[$fieldInfo['fieldgroup_id']]['collapsed'] = false;
			}

			$groups[$fieldInfo['fieldgroup_id']]['fields'][$fieldInfo['id']] = $fieldInfo;
		}

		$iaAcl = $this->iaCore->factory('acl');

		// clear groups that don't have any fields
		foreach ($groups as $key => $group)
		{
			if (!isset($group['fields']) || !$iaAcl->checkAccess('fieldgroup', $group['name']))
			{
				unset($groups[$key]);
			}
			else
			{
				$groups[$key]['description'] = iaLanguage::get('fieldgroup_description_' . $itemName . '_' . $group['name'], '');
			}
		}

		return $groups;
	}

	public function getValues($field, $item)
	{
		if ($values = $this->iaDb->one_bind(array('values'), '`name` = :field AND `item` = :item', array('field' => $field, 'item' => $item), self::getTable()))
		{
			$result = array();
			foreach (explode(',', $values) as $key)
			{
				$result[$key] = iaLanguage::get('field_' . $field . '_' . $key, $key);
			}

			return $result;
		}

		return false;
	}

	public function getGroups($itemName)
	{
		$groups = $this->iaDb->assoc(array('id', 'name', 'order', 'collapsed'), iaDb::EMPTY_CONDITION . ' ORDER BY `order`', self::getTableGroups());
		$fields = $this->getByItemName($itemName);

		if (empty($fields))
		{
			return $groups;
		}

		foreach ($fields as $value)
		{
			if (empty($value['fieldgroup_id']) || empty($groups[$value['fieldgroup_id']]))
			{
				$value['fieldgroup_id'] = '___empty___';

				// emulate tab to avoid isset checks
				$groups[$value['fieldgroup_id']]['name'] = $value['fieldgroup_id'];
				$groups[$value['fieldgroup_id']]['tabview'] = '';
				$groups[$value['fieldgroup_id']]['tabcontainer'] = '';
				$groups[$value['fieldgroup_id']]['collapsible'] = false;
				$groups[$value['fieldgroup_id']]['collapsed'] = false;
			}

			$groups[$value['fieldgroup_id']]['fields'][] = $value;
		}

		return $groups;
	}

	public function parsePost(array $fields, $previousValues = null)
	{
		$iaCore = &$this->iaCore;

		$error = false;
		$messages = array();
		$invalidFields = array();

		$item = array();
		$data = &$_POST; // access to the data source by link

		if (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType())
		{
			if (isset($data['sponsored']))
			{
				$item['sponsored'] = (int)$data['sponsored'];
				$item['sponsored_plan_id'] = $item['sponsored'] ? (int)$data['plan_id'] : 0;
				if ($item['sponsored'])
				{
					if (!(isset($previousValues['sponsored_start']) && $previousValues['sponsored_start']))
					{
						$item['sponsored_start'] = date(iaDb::DATETIME_SHORT_FORMAT);
					}
				}
				else
				{
					$item['sponsored_start'] = null;
				}

				$item['sponsored_end'] = null;
				if ($item['sponsored'] && !empty($data['sponsored_end']))
				{
					$item['sponsored_end'] = $data['sponsored_end'];
				}
			}

			if (isset($data['featured']))
			{
				$item['featured'] = (int)$data['featured'];
				if ($item['featured'])
				{
					if (isset($data['featured_end']) && $data['featured_end'])
					{
						$item['featured_start'] = date(iaDb::DATETIME_SHORT_FORMAT);
						$item['featured_end'] = iaSanitize::html($data['featured_end']);
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('featured_status_finished_date_is_empty');
						$invalidFields[] = 'featured_end';
					}
				}
				else
				{
					$item['featured_start'] = null;
					$item['featured_end'] = null;
				}
			}

			if (isset($data['status']))
			{
				$item['status'] = iaSanitize::html($data['status']);
			}

			if (isset($data['date_added']))
			{
				$time = strtotime($data['date_added']);
				if (!$time)
				{
					$error = true;
					$messages[] = iaLanguage::get('added_date_is_incorrect');
				}
				elseif ($time > time())
				{
					$error = true;
					$messages[] = iaLanguage::get('future_date_specified_for_added_date');
				}
				else
				{
					$item['date_added'] = date(iaDb::DATETIME_SHORT_FORMAT, $time);
				}
			}

			if (isset($data['owner']))
			{
				if (trim($data['owner']) && isset($data['member_id']) && $data['member_id'] &&
					$memberId = $iaCore->iaDb->one('id', iaDb::convertIds((int)$data['member_id']), iaUsers::getTable()))
				{
					$item['member_id'] = $memberId;
				}
				else
				{
					$item['member_id'] = 0;
				}
			}

			if (isset($data['locked']))
			{
				$item['locked'] = (int)$data['locked'];
			}
		}

		// the code block below filters fields based on parent/dependent structure
		$activeFields = array();
		$parentFields = array();

		foreach ($fields as $field)
		{
			$activeFields[$field['name']] = $field;
			if (iaField::RELATION_PARENT == $field['relation'])
			{
				$parentFields[$field['name']] = $field['children'];
			}
		}

		foreach ($parentFields as $fieldName => $dependencies)
		{
			if (isset($data[$fieldName]))
			{
				$value = $data[$fieldName];
				foreach ($dependencies as $dependentFieldName => $values)
				{
					if (!in_array($value, $values))
					{
						unset($activeFields[$dependentFieldName]);
					}
				}
			}
		}
		//

		$iaCore->factory('util');
		iaUtil::loadUTF8Functions('validation', 'bad');

		foreach ($activeFields as $fieldName => $field)
		{
			isset($data[$fieldName]) || $data[$fieldName] = '';

			// Check the UTF-8 is well formed
			if (!is_array($data[$fieldName]) && !utf8_is_valid($data[$fieldName]))
			{
				$data[$fieldName] = utf8_bad_replace($data[$fieldName]);
			}

			if ($field['extra_actions'])
			{
				if (false === eval($field['extra_actions']))
				{
					continue; // make possible to stop further processing of this field by returning FALSE
				}
			}

			if (in_array($field['type'], array(self::TEXT, self::TEXTAREA, self::NUMBER, self::RADIO, self::CHECKBOX, self::COMBO)))
			{
				if ($field['required'])
				{
					if ($field['required_checks'])
					{
						eval($field['required_checks']);
					}

					if (empty($data[$fieldName]))
					{
						$error = true;

						$messages[] = in_array($field['type'], array(self::RADIO, self::CHECKBOX, self::COMBO))
							? iaLanguage::getf('field_is_not_selected', array('field' => iaLanguage::get('field_' . $fieldName)))
							: iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));

						$invalidFields[] = $fieldName;
					}
				}

				switch ($field['type'])
				{
					case self::NUMBER:
						$item[$fieldName] = (float)str_replace(' ', '', $data[$fieldName]);
						break;

					case self::TEXT:
						$item[$fieldName] = iaSanitize::tags($data[$fieldName]);
						break;

					case self::TEXTAREA:
						$item[$fieldName] = $field['use_editor'] ? iaUtil::safeHTML($data[$fieldName]) : iaSanitize::tags($data[$fieldName]);
						break;

					default:
						$item[$fieldName] = is_array($data[$fieldName]) ? implode(',', $data[$fieldName]) : $data[$fieldName];
						if (in_array($field['type'], array(self::RADIO, self::COMBO)))
						{
							$item[$fieldName] = empty($data[$fieldName]) ? 'NULL' : $data[$fieldName];
						}
				}
			}
			elseif (self::DATE == $field['type'])
			{
				if ($field['required'] && $field['required_checks'])
				{
					eval($field['required_checks']);
				}
				elseif ($field['required'] && empty($data[$fieldName]))
				{
					$error = true;
					$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
					$invalidFields[] = $fieldName;
				}

				$data[$fieldName] = trim($data[$fieldName]);

				if (empty($data[$fieldName]))
				{
					$item[$fieldName] = $field['allow_null'] ? null : '';
				}
				else
				{
					if (strpos($data[$fieldName], ' ') === false)
					{
						$date = $data[$fieldName];
						$time = false;
					}
					else
					{
						list($date, $time) = explode(' ', $data[$fieldName]);
					}

					// FIXME: fucking shit
					$array = explode('-', $date);

					$year = (int)$array[0];
					$month = max(1, (int)$array[1]);
					$day = max(1, (int)$array[2]);

					$year = (strlen($year) == 4) ? $year : 2000;
					$month = (strlen($month) < 2) ? '0' . $month : $month;
					$day = (strlen($day) < 2) ? '0' . $day : $day;

					$item[$fieldName] = $year . '-' . $month . '-' . $day;

					if ($field['timepicker'] && $time)
					{
						$time = explode(':', $time);

						$hour = max(1, (int)$time[0]);
						$minute = max(1, (int)$time[1]);
						$seconds = max(1, (int)$time[2]);

						$hour = (strlen($hour) < 2) ? '0' . $hour : $hour;
						$minute = (strlen($minute) < 2) ? '0' . $minute : $minute;
						$seconds = (strlen($seconds) < 2) ? '0' . $seconds : $seconds;

						$item[$fieldName] .= ' ' . $hour . ':' . $minute . ':' . $seconds;
					}
				}
			}
			elseif (self::URL == $field['type'])
			{
				$validProtocols = array('http://', 'https://');
				$item[$fieldName] = '';

				$req_error = false;
				if ($field['required'])
				{
					if ($field['required_checks'])
					{
						eval($field['required_checks']);
					}
					elseif (empty($data[$fieldName]['url']) || in_array($data[$fieldName]['url'], $validProtocols))
					{
						$error = $req_error = true;
						$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
						$invalidFields[] = $fieldName;
					}
				}

				if (!$req_error && !empty($data[$fieldName]['url']) && !in_array($data[$fieldName]['url'], $validProtocols))
				{
					if (false === stripos($data[$fieldName]['url'], 'http://')
						&& false === stripos($data[$fieldName]['url'], 'https://'))
					{
						$data[$fieldName]['url'] = 'http://' . $data[$fieldName]['url'];
					}

					if (iaValidate::isUrl($data[$fieldName]['url']))
					{
						$item[$fieldName] = array();
						$item[$fieldName]['url'] = iaSanitize::tags($data[$fieldName]['url']);
						$item[$fieldName]['title'] = empty($data[$fieldName]['title'])
							? str_replace($validProtocols, '', $data[$fieldName]['url'])
							: $data[$fieldName]['title'];
						$item[$fieldName] = implode('|', $item[$fieldName]);
					}
					else
					{
						$error = true;
						$messages[] = iaLanguage::get('field_' . $fieldName) . ': ' . iaLanguage::get('error_url');
						$invalidFields[] = $fieldName;
					}
				}
			}
			elseif (in_array($field['type'], array(self::IMAGE, self::STORAGE, self::PICTURES)))
			{
				if (!is_writable(IA_UPLOADS))
				{
					$error = true;
					$messages[] = iaLanguage::get('error_directory_readonly');
				}
				else
				{
					// run required field checks
					if ($field['required'] && $field['required_checks'])
					{
						eval($field['required_checks']);
					}
					elseif ($field['required'] && !in_array(UPLOAD_ERR_OK, $_FILES[$fieldName]['error']))
					{
						$error = true;
						$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('field_' . $fieldName)));
						$invalidFields[] = $fieldName;
					}

					// custom folder for uploaded images
					if (!empty($field['folder_name']))
					{
						if (!is_dir(IA_UPLOADS . $field['folder_name']))
						{
							mkdir(IA_UPLOADS . $field['folder_name']);
						}
						$path = $field['folder_name'] . IA_DS;
					}
					else
					{
						$path = iaUtil::getAccountDir();
					}

					$item[$fieldName] = isset($data[$fieldName]) && $data[$fieldName] ? $data[$fieldName] : array();

					// initialize class to work with images
					$methodName = self::STORAGE == $field['type'] ? '_processFileField' : '_processImageField';

					// process uploaded files
					foreach ($_FILES[$fieldName]['tmp_name'] as $id => $tmp_name)
					{
						if ($_FILES[$fieldName]['error'][$id])
						{
							continue;
						}

						// files limit exceeded or rewrite image value
						if (self::IMAGE != $field['type'] && count($item[$fieldName]) >= $field['length'])
						{
							break;
						}

						$file = array();
						foreach ($_FILES[$fieldName] as $key => $value)
						{
							$file[$key] = $_FILES[$fieldName][$key][$id];
						}

						$processing = self::$methodName($field, $file, $path);
						// 0 - filename, 1 - error, 2 - textual error description
						if (!$processing[1]) // went smoothly
						{
							$fieldValue = array(
								'title' => (isset($data[$fieldName . '_title'][$id]) ? substr(trim($data[$fieldName . '_title'][$id]), 0, 100) : ''),
								'path' => $processing[0]
							);

							if (self::IMAGE == $field['type'])
							{
								$item[$fieldName] = $fieldValue;
							}
							else
							{
								$item[$fieldName][] = $fieldValue;
							}
						}
						else
						{
							$error = true;
							$messages[] = $processing[2];
						}
					}
				}

				// If already has images, append them.
				$item[$fieldName] = empty($item[$fieldName]) ? '' : serialize(array_merge($item[$fieldName])); // array_merge is used to reset numeric keys
			}
			elseif (self::TREE == $field['type'])
			{
				$item[$fieldName] = str_replace(' ', '', iaSanitize::tags($data[$fieldName]));
			}

			if (isset($item[$fieldName]))
			{
				// process hook if field value exists
				$iaCore->startHook('phpParsePostAfterCheckField', array(
					'field_name' => $fieldName,
					'item' => &$item[$fieldName],
					'value' => $field,
					'error' => &$error,
					'error_fields' => &$invalidFields,
					'msg' => &$messages
				));
			}
		}

		return array($item, $error, $messages, implode(',', $invalidFields));
	}

	protected static function _generateFileName($filename = '', $prefix = '', $glue = true)
	{
		if (empty($filename))
		{
			return $prefix . (iaUtil::generateToken());
		}

		$extension = '';
		if (false !== strpos($filename, '.'))
		{
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$filename = $prefix . pathinfo($filename, PATHINFO_FILENAME);

			if (false !== strpos($filename, '.'))
			{
				$filename = str_replace(array('.', '~'), '-', $filename);
			}
		}
		$filename = iaSanitize::alias($filename) . '_'. iaUtil::generateToken(5);

		return $glue ? $filename . '.' . $extension : array($filename, $extension);
	}

	protected static function _processFileField(array $field, array $file, $path)
	{
		$error = false;
		$message = null;

		list($filename, $extension) = self::_generateFileName($file['name'], $field['file_prefix'], false);
		$filename = $path . $filename . '.' . $extension;

		// get available extensions
		$allowedExtensions = empty($field['file_types']) ? false : explode(',', str_replace(' ', '', $field['file_types']));

		if ($extension && $allowedExtensions && in_array($extension, $allowedExtensions))
		{
			move_uploaded_file($file['tmp_name'], IA_UPLOADS . $filename);
			chmod(IA_UPLOADS . $filename, 0644);
		}
		else
		{
			$error = true;
			$message = iaLanguage::getf('file_type_error', array('extension' => $field['file_types']));
		}

		return array($filename, $error, $message);
	}

	protected static function _processImageField(array $field, array $file, $path)
	{
		$error = false;
		$message = null;

		$iaCore = iaCore::instance();
		$iaPicture = $iaCore->factory('picture');

		list($filename, ) = self::_generateFileName($file['name'], $field['file_prefix'], false);

		$imageName = $iaPicture->processImage($file, $path, $filename, $field);

		if ($imageName)
		{
			$imageName = str_replace(IA_DS, '/', $imageName);
		}
		else
		{
			$error = true;
			$message = $iaPicture->getMessage();
		}

		return array($imageName, $error, $message);
	}

	/**
	 * Sets elements of array according to provided fields structure
	 *
	 * @param array $itemData resulting array
	 * @param array $fields standard fields structure returned by methods of this class
	 * @param array $extraValues values that will be merged to $itemData
	 * @param array $data source data (POST values are used if nothing specified)
	 *
	 * @return void
	 */
	public static function keepValues(array &$itemData, array $fields, array $extraValues = array(), $data = null)
	{
		if (is_null($data))
		{
			$data = $_POST;
		}
		if (empty($data))
		{
			return;
		}

		foreach ($fields as $field)
		{
			if ($field['type'] != self::PICTURES && $field['type'] != self::IMAGE)
			{
				$fieldName = $field['name'];
				if (isset($data[$fieldName]) && $data[$fieldName])
				{
					$itemData[$fieldName] = self::CHECKBOX == $field['type']
						? implode(',', $data[$fieldName])
						: $data[$fieldName];
				}
			}
		}

		if (iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType())
		{
			if (isset($data['featured']))
			{
				$itemData['featured'] = $data['featured'];
				$itemData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($data['featured_end']));
			}

			if (isset($data['sponsored']))
			{
				$itemData['sponsored'] = $data['sponsored'];
				if (isset($data['sponsored_end']))
				{
					$itemData['sponsored_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($data['sponsored_end']));
				}
			}

			empty($data['date_added']) || $itemData['date_added'] = iaSanitize::html($data['date_added']);
			empty($data['status']) || $itemData['status'] = iaSanitize::html($data['status']);
			empty($data['owner']) || $itemData['owner'] = iaSanitize::html($data['owner']);
		}

		if ($extraValues)
		{
			$itemData = array_merge($itemData, $extraValues);
		}
	}

	public function generateTabs(array $fieldgroups)
	{
		$tabs = $groups = array();

		foreach ($fieldgroups as $key => $group)
		{
			if ($group['tabview'])
			{
				$tabs['fieldgroup_' . $group['name']][$key] = $group;
			}
			elseif ($group['tabcontainer'])
			{
				$tabs['fieldgroup_' . $group['tabcontainer']][$key] = $group;
			}
			else
			{
				$groups[$key] = $group;
			}
		}

		return array($tabs, $groups);
	}

	public function getImageFields($itemFilter = null)
	{
		$conditions = array("`type` IN ('image','pictures')");
		empty($itemFilter) || $conditions[] = "`item` = '" . iaSanitize::sql($itemFilter) . "'";
		$conditions = implode(' AND ', $conditions);

		return $this->iaDb->onefield('name', $conditions, null, null, self::getTable());
	}

	public function getStorageFields($itemFilter = null)
	{
		$conditions = array("`type` = 'storage'");
		empty($itemFilter) || $conditions[] = "`item` = '" . iaSanitize::sql($itemFilter) . "'";
		$conditions = implode(' AND ', $conditions);

		return $this->iaDb->onefield('name', $conditions, null, null, self::getTable());
	}

	public function getTreeNodes($condition = '')
	{
		$rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $condition, null, null, 'fields_tree_nodes');

		if ($rows)
		{
			foreach ($rows as &$node)
			{
				$node['title'] = iaLanguage::get('field_' . $node['item'] . '_' . $node['field'] . '_' . $node['node_id']);
			}
		}

		return $rows;
	}

	public function getTreeNode($condition)
	{
		$result = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $condition, 'fields_tree_nodes');
		if ($result)
		{
			$result['title'] = iaLanguage::get('field_' . $result['item'] . '_' . $result['field'] . '_' . $result['node_id']);
		}

		return $result;
	}
}