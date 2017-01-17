<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
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

	const FIELD_TITLE_PHRASE_KEY = 'field_%s_%s';
	const FIELD_VALUE_PHRASE_KEY = 'field_%s_%s+%s';
	const FIELD_TOOLTIP_PHRASE_KEY = 'field_tooltip_%s_%s';

	const FIELDGROUP_TITLE_PHRASE_KEY = 'fieldgroup_%s_%s';
	const FIELDGROUP_DESCRIPTION_PHRASE_KEY = 'fieldgroup_description_%s_%s';

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


	public static function getLanguageValue($itemName, $fieldName, $value)
	{
		return iaLanguage::get(sprintf(self::FIELD_VALUE_PHRASE_KEY, $itemName, $fieldName, $value));
	}

	public static function getFieldTitle($itemName, $fieldName)
	{
		return iaLanguage::get(sprintf(self::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName));
	}

	public static function getFieldTooltip($itemName, $fieldName)
	{
		return iaLanguage::get(sprintf(self::FIELD_TOOLTIP_PHRASE_KEY, $itemName, $fieldName));
	}

	public static function getFieldValue($itemName, $fieldName, $key)
	{
		return iaLanguage::get(sprintf(self::FIELD_VALUE_PHRASE_KEY, $itemName, $fieldName, $key), $key);
	}

	public static function getFieldgroupTitle($itemName, $fieldName)
	{
		return iaLanguage::get(sprintf(self::FIELDGROUP_TITLE_PHRASE_KEY, $itemName, $fieldName));
	}

	/**
	 * Returns fields by item name
	 *
	 * @param $itemName string Item name
	 *
	 * @return array
	 */
	public function get($itemName)
	{
		$fields = array();

		$where = '`status` = :status && `item` = :item' . (!$this->iaCore->get('api_enabled') ? " && `fieldgroup_id` != 3 " : '') . ' ORDER BY `order`';
		$this->iaDb->bind($where, array('status' => iaCore::STATUS_ACTIVE, 'item' => $itemName));

		if ($rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, self::getTable()))
		{
			foreach ($rows as $row) $fields[$row['id']] = $row;
			self::_unpackValues($fields);
		}

		return $fields;
	}

	protected function _fetchVisibleFieldsForPage($pageName, $itemName, $where)
	{
		$sql = <<<SQL
SELECT f.* 
	FROM `:prefix:table_fields` f 
LEFT JOIN `:prefix:table_pages` fp ON (fp.`field_id` = f.`id`) 
WHERE fp.`page_name` = ':page' 
	AND f.`status` = ':status' 
	AND f.`item` = ':item' 
	AND f.`adminonly` = 0 
	AND :where 
GROUP BY f.`id` 
ORDER BY f.`order`
SQL;

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table_fields' => self::getTable(),
			'table_pages' => self::getTablePages(),
			'page' => $pageName,
			'status' => iaCore::STATUS_ACTIVE,
			'item' => $itemName,
			'where' => $where
		));

		return $this->iaDb->getAll($sql);
	}

	public function filter($itemName, array &$itemData, $pageName = null, $where = null)
	{
		static $cache = array();

		is_null($pageName) && $pageName = $this->iaView->name();
		is_null($where) && $where = iaDb::EMPTY_CONDITION;

		$where.= !empty($itemData['sponsored_plan_id']) && !empty($itemData['sponsored'])
			? " AND (f.`plans` = '' OR FIND_IN_SET('{$itemData['sponsored_plan_id']}', f.`plans`)) "
			: " AND f.`plans` = '' ";

		if (isset($cache[$pageName][$itemName][$where]))
		{
			list($result, $planAssigned, $fields, $multilingual) = $cache[$pageName][$itemName][$where];
		}
		else
		{
			$result = array();
			$rows = $this->_fetchVisibleFieldsForPage($pageName, $itemName, $where);

			$iaAcl = $this->iaCore->factory('acl');

			$planAssigned = array();
			$fields = array();
			$empty = array();
			$multilingual = array();

			foreach ($rows as $row)
			{
				if ($iaAcl->checkAccess('field', $itemName . '_' . $row['name']))
				{
					$result[$row['id']] = $row;

					$empty[$row['name']] = $row['empty_field'];
					$row['multilingual'] && $multilingual[] = $row['name'];
					($row['required'] || !$row['for_plan'])
						? ($fields[] = $row['name'])
						: ($planAssigned[] = $row['name']);
				}
			}

			self::_unpackValues($result);

			$cache[$pageName][$itemName][$where] = array($result, $planAssigned, $fields, $multilingual);
		}

		if ($itemData)
		{
			if ($planAssigned)
			{
				$plans = $this->iaCore->factory('plan')->getPlans($itemName);

				if (!empty($itemData[iaPlan::SPONSORED_PLAN_ID])
					&& isset($plans[$itemData[iaPlan::SPONSORED_PLAN_ID]]['data']['fields']))
				{
					$planFields = $plans[$itemData[iaPlan::SPONSORED_PLAN_ID]]['data']['fields'];
					foreach ($planAssigned as $fieldName)
						in_array($fieldName, $planFields) && $fields[] = $fieldName;
				}
			}

			// assign a default value if not in allowed fields list
			foreach ($itemData as $fieldName => $value)
				in_array($fieldName, $fields) ||
					(isset($empty[$fieldName]) && $itemData[$fieldName] = $empty[$fieldName]);

			foreach ($multilingual as $fieldName)
			{
				$key = $fieldName . '_' . $this->iaCore->language['iso'];
				isset($itemData[$key]) && $itemData[$fieldName] = $itemData[$key];
			}
		}

		return $result;
	}

	/**
	 * Manages internal structure of fields: unpacks values, validates parent/dependent structure
	 *
	 * @param $fields array Array of fields
	 *
	 * @return void
	 */
	protected static function _unpackValues(array &$fields)
	{
		if (!$fields)
		{
			return;
		}

		$relations = iaCore::instance()->iaDb->all(array('field_id', 'element', 'child'),
			'`field_id` IN (' . implode(',', array_keys($fields)) . ')', null, null, self::getTableRelations());

		$relationsMap = array();
		foreach ($relations as $entry)
			$relationsMap[$entry['field_id']][$entry['child']][] = $entry['element'];

		foreach ($fields as $id => &$field)
		{
			// radios, combos and checkboxes needs special processing
			if (in_array($field['type'], array(self::CHECKBOX, self::COMBO, self::RADIO)))
			{
				if (self::CHECKBOX == $field['type'])
				{
					$field['default'] = explode(',', $field['default']);
				}

				$values = array();
				foreach (explode(',', $field['values']) as $v)
					$values[$v] = self::getLanguageValue($field['item'], $field['name'], $v);
				$field['values'] = $values;
			}

			$field['class'] = 'fieldzone';
			if ($field['plans'])
			{
				foreach (explode(',', $field['plans']) as $p)
				{
					$field['class'].= sprintf(' plan_%d ', $p);
				}
			}

			$field['title'] = self::getFieldTitle($field['item'], $field['name']);
			$field['children'] = isset($relationsMap[$id]) ? $relationsMap[$id] : null;
		}
	}

	protected function _getGroups($itemName, array $fields)
	{
		$where = '`item` = :item ORDER BY `order`';
		$this->iaDb->bind($where, array('item' => $itemName));

		$groups = array();
		$rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, self::getTableGroups());

		foreach ($rows as $row)
		{
			$row['title'] = iaLanguage::get(sprintf(self::FIELDGROUP_TITLE_PHRASE_KEY, $row['item'], $row['name']), '');
			$row['description'] = iaLanguage::get(sprintf(self::FIELDGROUP_DESCRIPTION_PHRASE_KEY, $row['item'], $row['name']), '');

			$groups[$row['id']] = $row;
		}

		if (!$fields)
		{
			return $groups;
		}

		foreach ($fields as $value)
		{
			$fieldGroupId = (int)$value['fieldgroup_id'];

			if (!isset($groups[$fieldGroupId])) // emulate tab to make TPL code compact
			{
				$groups[$fieldGroupId] = array('name' => '___empty___', 'title' => iaLanguage::get('other'),
					'tabview' => '', 'tabcontainer' => '', 'description' => null, 'collapsible' => false, 'collapsed' => false);
			}

			$groups[$fieldGroupId]['fields'][] = $value;
		}

		return $groups;
	}

	public function getGroups($itemName)
	{
		return $this->_getGroups($itemName, $this->get($itemName));
	}

	public function getGroupsFiltered($itemName, array &$itemData)
	{
		return $this->_getGroups($itemName, $this->filter($itemName, $itemData));
	}

	public function getTabs($itemName, array &$itemData, $defaultTab = 'common')
	{
		$fieldGroups = $this->getGroupsFiltered($itemName, $itemData);

		$tabs = array();
		foreach ($fieldGroups as $key => $group)
		{
			if ($group['tabview'])
			{
				$tabs['fieldgroup_' . $group['item'] . '_' . $group['name']][$key] = $group;
			}
			elseif ($group['tabcontainer'])
			{
				$tabs['fieldgroup_' . $group['tabcontainer']][$key] = $group;
			}
			else
			{
				$tabs[$defaultTab][$key] = $group;
			}
		}

		return $tabs;
	}

	public function parsePost($itemName, array &$itemData)
	{
		$errors = array();

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
						$errors['featured_end'] = iaLanguage::get('featured_status_finished_date_is_empty');
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
					$errors['added_date'] = iaLanguage::get('added_date_is_incorrect');
				}
				elseif ($time > time())
				{
					$errors['added_date'] = iaLanguage::get('future_date_specified_for_added_date');
				}
				else
				{
					$item['date_added'] = date(iaDb::DATETIME_SHORT_FORMAT, $time);
				}
			}

			if (isset($data['owner']))
			{
				if (trim($data['owner']) && isset($data['member_id']) && $data['member_id'] &&
					$memberId = $this->iaDb->one('id', iaDb::convertIds((int)$data['member_id']), iaUsers::getTable()))
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

		$fields = (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType())
			? $this->filter($itemName, $itemData)
			: $this->get($itemName);

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

		$this->iaCore->factory('util');
		iaUtil::loadUTF8Functions('validation', 'bad');

		foreach ($activeFields as $fieldName => $field)
		{
			$value = $field['allow_null'] ? null : '';
			isset($data[$fieldName]) && $value = $data[$fieldName];

			if ($field['extra_actions'])
			{
				if (false === eval($field['extra_actions']))
				{
					continue; // make possible to stop further processing of this field by returning FALSE
				}
			}

			if ($field['required'])
			{
				if ($field['required_checks'])
				{
					eval($field['required_checks']);
				}

				if (!$value && !$field['multilingual']
					&& in_array($field['type'], array(self::TEXT, self::TEXTAREA, self::NUMBER, self::RADIO, self::CHECKBOX, self::COMBO, self::DATE)))
				{
					$errors[$fieldName] = in_array($field['type'], array(self::RADIO, self::CHECKBOX, self::COMBO))
						? iaLanguage::getf('field_is_not_selected', array('field' => self::getFieldTitle($field['item'], $fieldName)))
						: iaLanguage::getf('field_is_empty', array('field' => self::getFieldTitle($field['item'], $fieldName)));
				}
			}

			switch ($field['type'])
			{
				case self::TEXT:
				case self::TEXTAREA:

					if ($field['multilingual'])
					{
						$langCode = (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType())
							? $this->iaView->language
							: iaLanguage::getMasterLanguage()->code;

						$value = isset($data[$fieldName][$langCode])
							? $data[$fieldName][$langCode]
							: null;

						if ($field['required'] && !$value)
						{
							$errors[$fieldName] = iaLanguage::getf('field_is_empty', array('field' => self::getFieldTitle($field['item'], $fieldName)));
						}
						else
						{
							$item[$fieldName . '_' . $langCode] = $value;

							foreach ($this->iaCore->languages as $code => $language)
							{
								if ($code == $langCode) continue;

								if (iaCore::ACCESS_FRONT == $this->iaCore->getAccessType())
								{
									$string = $value;
								}
								else
								{
									$string = empty($data[$fieldName][$code]) // copy the master language value if empty
										? $value
										: $data[$fieldName][$code];
								}

								utf8_is_valid($string) || $string = utf8_bad_replace($string);
								$string = (iaField::TEXT == $field['type'])
									? iaSanitize::tags($string)
									: ($field['use_editor'] ? iaUtil::safeHTML($string) : iaSanitize::tags($string));

								$item[$fieldName . '_' . $code] = $string;
							}
						}
					}
					else
					{
						// Check the UTF-8 is well formed
						utf8_is_valid($value) || $value = utf8_bad_replace($value);

						$item[$fieldName] = (iaField::TEXT == $field['type'])
							? iaSanitize::tags($value)
							: ($field['use_editor'] ? iaUtil::safeHTML($value) : iaSanitize::tags($value));
					}

					break;

				case self::NUMBER:
					$item[$fieldName] = (float)str_replace(' ', '', $value);

					break;

				case self::CHECKBOX:
					is_array($value) && $value = implode(',', $value);

					// BREAK stmt omitted intentionally

				case self::COMBO:
				case self::RADIO:
					$item[$fieldName] = $value;

					break;

				case self::IMAGE:
				case self::STORAGE:
				case self::PICTURES:
					if (!is_writable(IA_UPLOADS))
					{
						$errors[$fieldName] = iaLanguage::get('error_directory_readonly');
					}
					else
					{
						if ($field['required'] && !in_array(UPLOAD_ERR_OK, $_FILES[$fieldName]['error']))
						{
							$existImages = empty($previousValues[$fieldName]) ? null : $previousValues[$fieldName];
							$existImages = is_string($existImages) ? unserialize($existImages) : $existImages;

							$existImages || $errors[$fieldName] = iaLanguage::getf('field_is_empty', array('field' => self::getFieldTitle($field['item'], $fieldName)));
						}

						// custom folder for uploaded images
						if (!empty($field['folder_name']))
						{
							$fsPath = IA_UPLOADS . $field['folder_name'];
							is_dir($fsPath) || mkdir($fsPath);

							$path = $field['folder_name'] . IA_DS;
						}
						else
						{
							$path = iaUtil::getAccountDir();
						}

						$item[$fieldName] = isset($data[$fieldName]) ? $value : array();

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
								$file[$key] = $_FILES[$fieldName][$key][$id];

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
								$errors[$fieldName] = $processing[2];
							}
						}
					}

					// If already has images, append them.
					$item[$fieldName] = empty($item[$fieldName]) ? '' : serialize(array_merge($item[$fieldName])); // array_merge is used to reset numeric keys

					break;

				case iaField::DATE:
					if ($value = trim($value))
					{
						$value = date($field['timepicker'] ? iaDb::DATETIME_FORMAT : iaDb::DATE_FORMAT,
							strtotime($value));
					}

					$item[$fieldName] = $value;

					break;

				case iaField::TREE:
					$value && $value = str_replace(' ', '', iaSanitize::tags($value));
					$item[$fieldName] = $value;

					break;

				case iaField::URL:
					$validProtocols = array('http://', 'https://');
					$item[$fieldName] = '';

					if ($field['required']
						&& (empty($value['url']) || in_array($value['url'], $validProtocols)))
					{
						$errors[$fieldName] = iaLanguage::getf('field_is_empty', array('field' => iaField::getFieldTitle($field['item'], $fieldName)));
					}
					else
					{
						if (false === stripos($value['url'], 'http://')
							&& false === stripos($value['url'], 'https://'))
						{
							$value['url'] = 'http://' . $value['url'];
						}

						if (iaValidate::isUrl($value['url']))
						{
							$url = iaSanitize::tags($value['url']);
							$title = empty($value['title'])
								? str_replace($validProtocols, '', $value['url'])
								: $value['title'];

							$item[$fieldName] = $url . '|' . $title;
						}
						else
						{
							$errors[$fieldName] = iaLanguage::get('error_url') . ': ' . self::getFieldTitle($field['item'], $fieldName);
						}
					}
			}

			if (isset($item[$fieldName]))
			{
				// process hook if field value exists
				$this->iaCore->startHook('phpParsePostAfterCheckField', array(
					'field' => $field,
					'value' => $item[$fieldName],
					'errors' => &$errors
				));
			}
		}

		$error = !empty($errors);
		$fields = array_keys($errors);
		$messages = array_values($errors);

		return array($item, $error, $messages, $fields);
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

	public function getValues($fieldName, $itemName)
	{
		$values = $this->iaDb->one_bind(array('values'), '`name` = :field AND `item` = :item',
			array('field' => $fieldName, 'item' => $itemName), self::getTable());

		if ($values)
		{
			$result = array();
			foreach (explode(',', $values) as $key)
				$result[$key] = self::getLanguageValue($itemName, $fieldName, $key);

			return $result;
		}

		return false;
	}

	public function getImageFields($itemName = null)
	{
		return $this->_getFieldNames("`type` IN ('image','pictures')", $itemName);
	}

	public function getStorageFields($itemName = null)
	{
		return $this->_getFieldNames(iaDb::convertIds(self::STORAGE, 'type'), $itemName);
	}

	public function getSerializedFields($itemName = null)
	{
		return $this->_getFieldNames("`type` IN ('image', 'pictures', 'storage')", $itemName);
	}

	public function getMultilingualFields($itemName = null)
	{
		return $this->_getFieldNames(iaDb::convertIds(1, 'multilingual'), $itemName);
	}

	protected function _getFieldNames($condition, $itemName = null)
	{
		static $cache = array();

		$conditions = array("`status` = 'active'", $condition);
		is_null($itemName) || $conditions[] = iaDb::convertIds($itemName, 'item');
		$conditions = implode(' AND ', $conditions);

		if (!isset($cache[$conditions]))
		{
			$result = $this->iaDb->onefield('name', $conditions, null, null, self::getTable());
			$cache[$conditions] = $result ? $result : array();
		}

		return $cache[$conditions];
	}

	public function getTreeNodes($condition = '')
	{
		$rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $condition, null, null, 'fields_tree_nodes');

		if ($rows)
		{
			foreach ($rows as &$node)
				$node['title'] = self::getFieldValue($node['item'], $node['field'], $node['node_id']);
		}

		return $rows;
	}

	public function getTreeNode($condition)
	{
		$row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $condition, 'fields_tree_nodes');
		$row && $row['title'] = self::getFieldValue($row['item'], $row['field'], $row['node_id']);

		return $row;
	}


	public function alterTable(array $fieldData)
	{
		$dbTable = $this->iaCore->factory('item')->getItemTable($fieldData['item']);

		if ($fieldData['multilingual'])
		{
			$this->alterMultilingualColumns($dbTable, $fieldData['name'], $fieldData);
		}
		else
		{
			$this->alterColumnScheme($dbTable, $fieldData);
			$this->_alterColumnIndex($dbTable, $fieldData['name'], $fieldData['searchable']);
		}
	}

	// DB mgmt utility methods
	public function alterMultilingualColumns($dbTable, $fieldName, array $fieldData)
	{
		$defaultLanguageCode = null;

		foreach ($this->iaCore->languages as $language)
		{
			if ($language['default'])
			{
				$defaultLanguageCode = $language['iso'];
				break;
			}
		}

		if ($fieldData['multilingual'])
		{
			$fieldData['name'] = $fieldName;
			$this->alterColumnScheme($dbTable, $fieldData, $fieldName . '_' . $defaultLanguageCode);

			foreach ($this->iaCore->languages as $language)
			{
				if ($language['iso'] != $defaultLanguageCode)
				{
					$fieldData['name'] = $fieldName . '_' . $language['iso'];
					$this->alterColumnScheme($dbTable, $fieldData);
				}
			}
		}
		else
		{
			$fieldData['name'] = $fieldName . '_' . $defaultLanguageCode;
			$this->alterColumnScheme($dbTable, $fieldData, $fieldName);

			foreach ($this->iaCore->languages as $language)
			{
				if ($language['iso'] != $defaultLanguageCode)
				{
					$this->alterDropColumn($dbTable, $fieldName . '_' . $language['iso']);
				}
			}
		}
	}

	public function alterColumnScheme($dbTable, array $fieldData, $newName = null)
	{
		is_null($newName) && $newName = $fieldData['name'];

		$sql = $this->isDbColumnExist($dbTable, $fieldData['name'])
			? 'ALTER TABLE `:prefix:table` CHANGE `:column1` `:column2` :scheme'
			: 'ALTER TABLE `:prefix:table` ADD `:column2` :scheme';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table' => $dbTable,
			'column1' => $fieldData['name'],
			'column2' => $newName,
			'scheme' => $this->_alterCmdBody($fieldData)
		));

		$this->iaDb->query($sql);
	}

	private function _alterColumnIndex($dbTable, $fieldName, $enabled)
	{
		$sql = sprintf('SHOW INDEX FROM `%s%s`', $this->iaDb->prefix, $dbTable);

		$exists = false;
		if ($indexes = $this->iaDb->getAll($sql))
		{
			foreach ($indexes as $i)
			{
				if ($i['Key_name'] == $fieldName && $i['Index_type'] == 'FULLTEXT')
				{
					$exists = true;
					break;
				}
			}
		}

		if ($enabled && !$exists)
		{
			$sql = sprintf('ALTER TABLE `%s%s` ADD FULLTEXT(`%s`)', $this->iaDb->prefix, $dbTable, $fieldName);
		}
		elseif (!$enabled && $exists)
		{
			$sql = sprintf('ALTER TABLE `%s%s` DROP INDEX `%s`', $this->iaDb->prefix, $dbTable, $fieldName);
		}

		isset($sql) && $this->iaDb->query($sql);
	}

	public function alterDropColumn($dbTable, $columnName)
	{
		$sql = sprintf('ALTER TABLE `%s%s` DROP `%s`', $this->iaDb->prefix, $dbTable, $columnName);

		$this->iaDb->query($sql);
	}

	public function isDbColumnExist($dbTable, $columnName)
	{
		$sql = sprintf("SHOW COLUMNS FROM `%s%s` WHERE `Field` LIKE '%s'",
			$this->iaDb->prefix, $dbTable, $columnName);

		return (bool)$this->iaDb->getRow($sql);
	}

	private function _alterCmdBody(array $fieldData)
	{
		$result = '';

		switch ($fieldData['type'])
		{
			case iaField::DATE:
				$result.= 'DATETIME ';
				break;
			case iaField::NUMBER:
				$result.= 'DOUBLE ';
				break;
			case iaField::TEXT:
				$result.= 'VARCHAR(' . $fieldData['length'] . ') '
					. ($fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '');
				break;
			case iaField::URL:
			case iaField::TREE:
				$result.= 'TINYTEXT ';
				break;
			case iaField::IMAGE:
			case iaField::STORAGE:
			case iaField::PICTURES:
			case iaField::TEXTAREA:
				$result.= 'TEXT ';
				break;
			default:
				if (isset($fieldData['values']))
				{
					$values = explode(',', $fieldData['values']);

					$result.= ($fieldData['type'] == iaField::CHECKBOX) ? 'SET' : 'ENUM';
					$result.= "('" . implode("','", $values) . "')";

					if (!empty($fieldData['default']))
					{
						$result.= " DEFAULT '{$fieldData['default']}' ";
					}
				}
		}

		$result.= in_array($fieldData['type'], array(iaField::COMBO, iaField::RADIO)) ? 'NULL' : 'NOT NULL';

		return $result;
	}

	public function syncMultilingualFields()
	{
		$iaItem = $this->iaCore->factory('item');

		$multilingualFields = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds(1, 'multilingual'),
			null, null, self::getTable());

		$this->iaCore->languages = $this->iaDb->assoc(
			array('code', 'id', 'title', 'locale', 'date_format', 'direction', 'master', 'default', 'flagicon', 'iso' => 'code', 'status'),
			iaDb::EMPTY_CONDITION . ' ORDER BY `order` ASC',
			iaLanguage::getLanguagesTable()
		);

		foreach ($multilingualFields as $field)
		{
			$dbTable = $iaItem->getItemTable($field['item']);
			$this->alterMultilingualColumns($dbTable, $field['name'], $field);
		}
	}
}