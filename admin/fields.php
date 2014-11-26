<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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
	protected $_name = 'fields';

	protected $_gridColumns = array('name', 'item', 'group', 'fieldgroup_id', 'type', 'relation', 'length', 'order', 'status', 'delete' => 'editable');
	protected $_gridFilters = array('status' => 'equal', 'id' => 'equal', 'item' => 'equal', 'relation' => 'equal');

	protected $_tooltipsEnabled = true;

	protected $_phraseAddSuccess = 'field_added';
	protected $_phraseGridEntryDeleted = 'field_deleted';


	public function __construct()
	{
		parent::__construct();

		$iaField = $this->_iaCore->factory('field');
		$this->setHelper($iaField);
	}

	// support displaying of custom item's fields
	protected function _htmlAction(&$iaView)
	{
		$this->_indexPage($iaView);
	}

	protected function _jsonAction(&$iaView)
	{
		$itemName = str_replace('_fields', '', $iaView->get('action'));
		$params = array_merge($_GET, array('item' => $itemName));

		return parent::_gridRead($params);
	}
	//

	protected function _gridRead($params)
	{
		if (isset($params['get']) && 'groups' == $params['get'])
		{
			return $this->_iaDb->all(array('id', 'name'), iaDb::convertIds($_GET['item'], 'item'), null, null, iaField::getTableGroups());
		}

		if ($this->getName() != $this->_iaCore->iaView->name())
		{
			$params['item'] = str_replace('_fields', '', $this->_iaCore->iaView->name());
		}

		return parent::_gridRead($params);
	}

	protected function _modifyGridResult(array &$entries)
	{
		$groups = $this->_iaDb->keyvalue(array('id', 'name'), '1 ORDER BY `item`, `name`', iaField::getTableGroups());

		foreach ($entries as &$entry)
		{
			$entry['title'] = iaLanguage::get('field_' . $entry['name'], $entry['name']);
			$entry['group'] = isset($groups[$entry['fieldgroup_id']]) ? iaLanguage::get('fieldgroup_' . $groups[$entry['fieldgroup_id']], $entry['fieldgroup_id']) : iaLanguage::get('other');
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'fieldgroup_id' => 0,
			'name' => '',
			'title' => '',
			'item' => null,
			'type' => null,
			'relation' => iaField::RELATION_REGULAR,
			'required' => false,
			'length' => iaField::DEFAULT_LENGTH,
			'searchable' => false,
			'default' => null,
			'status' => iaCore::STATUS_ACTIVE,
			'values' => array(),
			'pages' => array()
		);
	}

	protected function _entryDelete($entryId)
	{
		$result = false;

		if ($this->_iaDb->exists('`id` = :id AND `editable` = :editable', array('id' => $entryId, 'editable' => 1)))
		{
			$field = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($entryId));

			$result = (bool)$this->_iaDb->delete(iaDb::convertIds($entryId));
			$this->_iaDb->delete(iaDb::convertIds($entryId, 'field_id'), iaField::getTablePages());

			// we will delete language entries if there is no similar field for another package
			if (!$this->_iaDb->exists('`name` = :name', $field))
			{
				$this->_iaDb->delete("`key` LIKE 'field_{$field['name']}%' ", iaLanguage::getTable());
			}

			if ($field['item'])
			{
				$itemTable = $this->_iaCore->factory('item')->getItemTable($field['item']);
				// just an additional check
				$fields = $this->_iaDb->describe($itemTable);

				foreach ($fields as $f)
				{
					if ($f['Field'] == $field['name'])
					{
						$this->_iaDb->query("ALTER TABLE `{$this->_iaDb->prefix}{$itemTable}` DROP `{$field['name']}`");
						break;
					}
				}
			}
		}

		return $result;
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		if (iaCore::ACTION_EDIT == $iaView->get('action'))
		{
			$entryData = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($this->getEntryId()));
			$values = $entryData['values'] = explode(',', $entryData['values']);
			$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'field_" . $entryData['name'] . "' AND `category` = 'common'", null, null, iaLanguage::getTable());
			foreach ($rows as $row)
			{
				$titles[$row['code']] = $row['value'];
			}

			$entryData['values_titles'] = array();
			if ($entryData['default'] != '')
			{
				if (iaField::CHECKBOX == $entryData['type'])
				{
					$entryData['default'] = explode(',', $entryData['default']);
					foreach ($entryData['default'] as $key_d => $key)
					{
						$entryData['default'][$key_d] = iaLanguage::get('field_' . $entryData['name'] . '_' . $key, $key);
					}
				}
				else
				{
					$entryData['default'] = iaLanguage::get('field_' . $entryData['name'] . '_' . $entryData['default'], $entryData['default']);
				}
			}
			foreach ($values as $key)
			{
				$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'field_{$entryData['name']}_$key'", null, null, iaLanguage::getTable());
				foreach ($rows as $row)
				{
					$entryData['values_titles'][$key][$row['code']] = $row['value'];
				}
			}

			if (is_array($entryData['default']))
			{
				$entryData['default'] = implode('|', $entryData['default']);
			}

			if (empty($entryData['values_titles']))
			{
				unset($entryData['values_titles']);
			}

			if (!$entryData['editable'])
			{
				unset($entryData['status']);
				$iaView->assign('noSystemFields', true);
			}

			foreach ($this->_iaCore->languages as $code => $val)
			{
				$entryData['title'][$code] = (isset($titles[$code]) ? $titles[$code] : '');
			}

			$entryData['pages'] = $this->getEntryId()
				? $this->_iaDb->keyvalue(array('id', 'page_name'), iaDb::convertIds($this->getEntryId(), 'field_id'), iaField::getTablePages())
				: array();
		}
		elseif (!empty($_GET['item']))
		{
			$entryData['item'] = $_GET['item'];
		}

		$iaItem = $this->_iaCore->factory('item');

		$pages = $groups = array();

		$stmt = (iaCore::ACTION_ADD == $iaView->get('action'))
			? iaDb::EMPTY_CONDITION
			: iaDb::convertIds($entryData['item'], 'item');

		// get items pages
		$itemPagesList = $this->_iaDb->all(array('id', 'page_name', 'item'), $stmt . ' ORDER BY `item`, `page_name`', null, null, 'items_pages');
		foreach ($itemPagesList as $entry)
		{
			$pages[$entry['id']] = array(
				'name' => $entry['page_name'],
				'title' => iaLanguage::get('page_title_' . $entry['page_name'], $entry['page_name']),
				'item' => $entry['item']
			);
		}

		// get field groups
		$fieldGroups = $this->_iaDb->all(array('id', 'name', 'item'), $stmt . ' ORDER BY `item`, `name`', null, null, iaField::getTableGroups());
		foreach ($fieldGroups as $entry)
		{
			$groups[$entry['id']] = array(
				'name' => iaLanguage::get('fieldgroup_' . $entry['name'], $entry['name']),
				'item' => $entry['item']
			);
		}

		$fieldTypes = $this->_iaDb->getEnumValues(iaField::getTable(), 'type');
		$items = $iaItem->getItems();
		$parents = array();

		$fieldsList = $this->_iaDb->all(array('item', 'name'), (empty($entryData['name']) ? '' : "`name` != '{$entryData['name']}' AND ") . " `relation` = 'parent' AND `type` IN ('combo', 'radio', 'checkbox') AND " . $stmt);
		foreach ($fieldsList as $row)
		{
			isset($parents[$row['item']]) || $parents[$row['item']] = array();
			$array = $this->_iaDb->getEnumValues($iaItem->getItemTable($row['item']), $row['name']);
			$parents[$row['item']][$row['name']] = $array['values'];
		}

		$entryData['pages'] || $entryData['pages'] = array();
		$entryData['regular_field'] = (iaField::RELATION_REGULAR == $entryData['relation']);


		$iaView->assign('parents', $parents);
		$iaView->assign('fieldTypes', $fieldTypes['values']);
		$iaView->assign('groups', $groups);
		$iaView->assign('items', $items);
		$iaView->assign('pages', $pages);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry = array(
			'item' => iaUtil::checkPostParam('item'),
			'default' => iaUtil::checkPostParam('default'),
			'lang_values' => iaUtil::checkPostParam('lang_values'),
			'text_default' => iaSanitize::html(iaUtil::checkPostParam('text_default')),
			'type' => iaUtil::checkPostParam('type'),
			'annotation' => iaUtil::checkPostParam('annotation'),
			'fieldgroup_id' => (int)iaUtil::checkPostParam('fieldgroup_id'),
			'name' => iaSanitize::alias(iaUtil::checkPostParam('name')),
			'text_length' => (int)iaUtil::checkPostParam('text_length', 100),
			'length' => iaUtil::checkPostParam('length', false),
			'title' => iaUtil::checkPostParam('title'),
			'pages' => iaUtil::checkPostParam('pages', array()),
			'required' => iaUtil::checkPostParam('required'),
			'use_editor' => (int)iaUtil::checkPostParam('use_editor'),
			'empty_field' => iaSanitize::html(iaUtil::checkPostParam('empty_field')),
			'url_nofollow' => (int)iaUtil::checkPostParam('url_nofollow'),
			'groups' => iaUtil::checkPostParam('groups'),
			'searchable' => (int)iaUtil::checkPostParam('searchable'),
			'adminonly' => (int)iaUtil::checkPostParam('adminonly'),
			'for_plan' => (int)iaUtil::checkPostParam('for_plan'),
			'required_checks' => iaUtil::checkPostParam('required_checks'),
			'extra_actions' => iaUtil::checkPostParam('extra_actions'),
			'link_to' => (int)iaUtil::checkPostParam('link_to'),
			'values' => '',
			'relation' => iaUtil::checkPostParam('relation', iaField::RELATION_REGULAR),
			'parents' => isset($data['parents']) && is_array($data['parents']) ? $data['parents'] : array(),
			'children' => isset($data['children']) && is_array($data['children']) ? $data['children'] : array(),
			'status' => iaUtil::checkPostParam('status', iaCore::STATUS_ACTIVE)
		);

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad');

		if (!$this->_iaDb->exists(iaDb::convertIds($entry['fieldgroup_id']), null, iaField::getTableGroups()))
		{
			$entry['fieldgroup_id'] = 0;
		}

		$relationType = iaUtil::checkPostParam('relation_type', -1);
		if ($relationType != -1)
		{
			if (0 == $relationType && !empty($entry['children']))
			{
				$entry['relation'] = iaField::RELATION_DEPENDENT;
			}
			else
			{
				$entry['relation'] = iaField::RELATION_REGULAR;
				unset($entry['parents']);
			}
			unset($entry['children']);
		}
		else
		{
			if (!in_array($entry['relation'], array(iaField::RELATION_REGULAR, iaField::RELATION_DEPENDENT, iaField::RELATION_PARENT)))
			{
				$entry['relation'] = iaField::RELATION_REGULAR;
			}

			if ($entry['relation'] != iaField::RELATION_DEPENDENT)
			{
				unset($entry['parents']);
			}
			if ($entry['relation'] != iaField::RELATION_PARENT)
			{
				unset($entry['children']);
			}
		}

		foreach ($this->_iaCore->languages as $code => $l)
		{
			if (!empty($entry['annotation'][$code]))
			{
				if (!utf8_is_valid($entry['annotation'][$code]))
				{
					$entry['annotation'][$code] = utf8_bad_replace($entry['annotation'][$code]);
				}
			}
			if (!empty($entry['title'][$code]))
			{
				if (!utf8_is_valid($entry['title'][$code]))
				{
					$entry['title'][$code] = utf8_bad_replace($entry['title'][$code]);
				}
			}
			else
			{
				$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => $l . ' ' . iaLanguage::get('title'))), false);

				break;
			}
		}

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['name'] = trim(strtolower(iaSanitize::paranoid($entry['name'])));
			if (empty($entry['name']))
			{
				$this->addMessage('field_name_incorrect');
			}
		}
		else
		{
			unset($entry['name']);
		}

		$fieldTypes = $this->_iaDb->getEnumValues(iaField::getTable(), 'type');
		if ($fieldTypes['values'] && !in_array($entry['type'], $fieldTypes['values']))
		{
			$this->addMessage('field_type_invalid');
		}
		else
		{
			if (!$entry['length'])
			{
				$entry['length'] = iaField::DEFAULT_LENGTH;
			}

			switch ($entry['type'])
			{
				case iaField::TEXT:
					if (empty($entry['text_length']))
					{
						$entry['text_length'] = 100;
					}
					$entry['length'] = min(255, max(1, $entry['text_length']));
					$entry['default'] = $entry['text_default'];

					break;

				case iaField::TEXTAREA:
					$entry['default'] = '';

					break;

				case iaField::COMBO:
				case iaField::RADIO:
				case iaField::CHECKBOX:
					if (!empty($data['values']) && is_array($data['values']))
					{
						$keys = array();
						$lang_values = array();

						$multiDefault = explode('|', iaUtil::checkPostParam('multiple_default'));
						$_keys = iaUtil::checkPostParam('keys');
						$_values = iaUtil::checkPostParam('values');
						$_langValues = iaUtil::checkPostParam('lang_values');

						foreach ($_keys as $index => $key)
						{
							if (trim($key) == '') // add key if not exists
							{
								$key = $index + 1;
								$_keys[$index] = $key;
							}

							if (isset($_values[$index]) && trim($_values[$index]) != '') // add values if not exists
							{
								$values[$key] = $_values[$index];
								$keys[$key] = $key;
							}
							else
							{
								unset($_keys[$index], $_values[$index]);
							}

							if ($_langValues)
							{
								foreach ($this->_iaCore->languages as $lang_code => $lang_title)
								{
									if ($lang_code != $this->_iaCore->iaView->language)
									{
										if (!isset($_values[$index]))
										{
											unset($_langValues[$lang_code][$index]);
										}
										elseif (!isset($_langValues[$lang_code][$index]) || trim($_langValues[$lang_code][$index]) == '') // add values if not exists
										{
											$lang_values[$lang_code][$key] = $values[$key];
										}
										else
										{
											$lang_values[$lang_code][$key] = $_langValues[$lang_code][$index];
										}
									}
								}
							}

						}

						// delete default values if not exists in values
						foreach ($multiDefault as $index => $default)
						{
							if (!in_array($default, $values))
							{
								unset($multiDefault[$index]);
							}
							else
							{
								$k = array_search($default, $values);
								$multiDefault[$index] = $k;
							}
						}
						$multiDefault = array_values($multiDefault);

						if (iaField::CHECKBOX == $entry['type'])
						{
							$multiDefault = implode(',', $multiDefault);
						}
						elseif (isset($multiDefault[0]))
						{
							// multiple default is available for checkboxes only
							$_POST['multiple_default'] = $multiDefault = $multiDefault[0];
						}
						else
						{
							$_POST['multiple_default'] = $multiDefault = '';
						}

						$entry['default'] = $multiDefault;
						$entry['keys'] = $keys;
						$entry['values'] = $values;
						$entry['lang_values'] = $lang_values;
					}
					else
					{
						$this->addMessage('one_value');
					}

					break;

				case iaField::STORAGE:
					if (!empty($data['file_types']))
					{
						$entry['file_types'] = str_replace(' ', '', iaUtil::checkPostParam('file_types'));
						$entry['length'] = (int)iaUtil::checkPostParam('max_files', 5);
					}
					else
					{
						$this->addMessage('file_types_empty');
					}

					break;

				case iaField::URL:
					$entry['url_nofollow'] = (int)iaUtil::checkPostParam('url_nofollow');

					break;

				case iaField::IMAGE:
					$entry['length'] = 1;
					$entry['image_height'] = (int)iaUtil::checkPostParam('image_height');
					$entry['image_width'] = (int)iaUtil::checkPostParam('image_width');
					$entry['thumb_height'] = (int)iaUtil::checkPostParam('thumb_height');
					$entry['thumb_width'] = (int)iaUtil::checkPostParam('thumb_width');
					$entry['file_prefix'] = iaUtil::checkPostParam('file_prefix');
					$entry['resize_mode'] = iaUtil::checkPostParam('resize_mode');

					break;

				case iaField::NUMBER:
					$entry['length'] = (int)iaUtil::checkPostParam('number_length', 8);
					$entry['default'] = iaUtil::checkPostParam('number_default');

					break;

				case iaField::PICTURES:
					$entry['length'] = (int)iaUtil::checkPostParam('pic_max_images', 5);
					$entry['file_prefix'] = iaUtil::checkPostParam('pic_file_prefix');
					$entry['image_height'] = (int)iaUtil::checkPostParam('pic_image_height');
					$entry['image_width'] = (int)iaUtil::checkPostParam('pic_image_width');
					$entry['thumb_height'] = (int)iaUtil::checkPostParam('pic_thumb_height');
					$entry['thumb_width'] = (int)iaUtil::checkPostParam('pic_thumb_width');
					$entry['resize_mode'] = iaUtil::checkPostParam('pic_resize_mode');
			}

			unset($entry['text_length'], $entry['text_default']);
		}

		if (empty($entry['pages']) && !$entry['adminonly'])
		{
			$this->addMessage('mark_at_least_one_page');
		}

		$entry['required'] = (int)iaUtil::checkPostParam('required');
		if ($entry['required'])
		{
			$entry['required_checks'] = iaUtil::checkPostParam('required_checks');
		}
		$entry['extra_actions'] = iaUtil::checkPostParam('extra_actions');

		if ($entry['searchable'])
		{
			if (isset($data['show_as']) && $entry['type'] != iaField::NUMBER && in_array($data['show_as'], array(iaField::COMBO, iaField::RADIO, iaField::CHECKBOX)))
			{
				$entry['show_as'] = $data['show_as'];
			}
			elseif ($entry['type'] == iaField::NUMBER && !empty($data['_values']))
			{
				$entry['sort_order'] = ('asc' == $data['sort_order']) ? $data['sort_order'] : 'desc';
				$entry['_numberRangeForSearch'] = $data['_values'];
			}
		}

		$this->_iaCore->startHook('phpAdminFieldsEdit', array('field' => &$entry));

		return !$this->getMessages();
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		return (count($entryData) == 1)
			? $this->_iaDb->update($entryData, iaDb::convertIds($entryId))
			: $this->_update($entryData, $entryId);
	}

	protected function _entryAdd(array $entryData)
	{
		if (!$this->_iaDb->exists('`name` = :name AND `item` = :item', $entryData))
		{
			return $this->_insert($entryData);
		}
		else
		{
			$this->addMessage('field_exists');

			return false;
		}
	}

	private function _update(array $fieldData, $id)
	{
		$iaDb = &$this->_iaDb;

		$field = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
		if (empty($field) || $field['type'] != $fieldData['type'])
		{
			return false;
		}

		if (isset($fieldData['parents']))
		{
			$this->_setParents($field['name'], $fieldData['parents']);
			$fieldData['relation'] = iaField::RELATION_DEPENDENT;

			unset($fieldData['parents']);
		}

		if (isset($fieldData['children']))
		{
			$this->_setChildren($field['name'], $field['item'], $fieldData['values'], $fieldData['children']);
			unset($fieldData['children']);
		}

		$this->_setRelations();

		$iaDb->setTable(iaLanguage::getTable());
		$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_%'");

		foreach ($this->_iaCore->languages as $code => $l)
		{
			iaLanguage::addPhrase('field_' . $field['name'], $fieldData['title'][$code], $code, $field['extras']);

			if (isset($fieldData['annotation'][$code]) && $fieldData['annotation'][$code])
			{
				iaLanguage::addPhrase('field_' . $field['name'] . '_annotation', $fieldData['annotation'][$code], $code, $field['extras']);
			}
		}

		unset($fieldData['title'], $fieldData['annotation']);

		$keys = array();
		if (isset($fieldData['values']) && is_array($fieldData['values']))
		{
			$newKeys = array();
			foreach ($fieldData['values'] as $key => $value)
			{
				$key = $keys[$key] = isset($fieldData['keys'][$key]) ? $fieldData['keys'][$key] : $key;
				iaLanguage::addPhrase('field_' . $field['name'] . '_' . $key, $value, null, $field['extras']);

				$newKeys[] = $key;
			}
			$fieldData['values'] = implode(',', $newKeys);
		}
		unset($fieldData['keys']);

		if (isset($fieldData['lang_values']) && is_array($fieldData['lang_values']))
		{
			foreach ($fieldData['lang_values'] as $languageCode => $phrases)
			{
				foreach ($phrases as $phraseKey => $phraseValue)
				{
					iaLanguage::addPhrase('field_' . $field['name'] . '_' . $phraseKey, $phraseValue, $languageCode, $field['extras']);
				}
			}
		}
		if (isset($fieldData['lang_values']))
		{
			unset($fieldData['lang_values']);
		}

		if ($fieldData['searchable'] && $fieldData['type'] == iaField::NUMBER && isset($fieldData['_numberRangeForSearch']) && is_array($fieldData['_numberRangeForSearch']) && !empty($fieldData['_numberRangeForSearch']))
		{
			$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_range\_%'");

			foreach ($fieldData['_numberRangeForSearch'] as $value)
			{
				iaLanguage::addPhrase('field_' . $field['name'] . '_range_' . $value, $value, null, $field['extras']);
			}
			unset($fieldData['_numberRangeForSearch']);
		}
		else
		{
			$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_range\_%'");
		}

		$iaDb->resetTable();

		$tableName = $this->_iaCore->factory('item')->getItemTable($fieldData['item']);

		// avoid making fulltext second time
		if (!$field['searchable'] && $fieldData['searchable'] && in_array($fieldData['type'], array(iaField::TEXT, iaField::TEXTAREA)))
		{
			$indexes = $iaDb->getAll("SHOW INDEX FROM `{$iaDb->prefix}{$tableName}`");
			$keyExists = false;
			foreach ($indexes as $i)
			{
				if ($i['Key_name'] == $field['name'] && $i['Index_type'] == 'FULLTEXT')
				{
					$keyExists = true;
					break;
				}
			}

			if (!$keyExists)
			{
				$iaDb->query("ALTER TABLE `{$iaDb->prefix}{$tableName}` ADD FULLTEXT (`{$field['name']}`)");
			}
		}
		if ($field['searchable'] && !$fieldData['searchable'] && in_array($fieldData['type'], array(iaField::TEXT, iaField::TEXTAREA)))
		{
			$indexes = $iaDb->getAll("SHOW INDEX FROM `{$iaDb->prefix}{$tableName}`");
			$keyExists = false;
			foreach ($indexes as $i)
			{
				if ($i['Key_name'] == $field['name'] && $i['Index_type'] == 'FULLTEXT')
				{
					$keyExists = true;
					break;
				}
			}

			if ($keyExists)
			{
				$iaDb->query("ALTER TABLE `{$iaDb->prefix}{$tableName}` DROP INDEX `{$field['name']}`");
			}
		}

		$pagesList = $fieldData['pages'];

		unset($fieldData['pages'], $fieldData['groups'], $fieldData['item']);

		$result = parent::_entryUpdate($fieldData, $id);

		if ($pagesList)
		{
			$this->_setPagesList($id, $pagesList, $field['extras']);
		}

		if ($result)
		{
			if (in_array($fieldData['type'], array(iaField::TEXT, iaField::COMBO, iaField::RADIO, iaField::CHECKBOX)))
			{
				$sql = "ALTER TABLE `{$this->_iaDb->prefix}{$tableName}` ";
				$sql .= "CHANGE `{$field['name']}` `{$field['name']}` ";

				switch ($fieldData['type'])
				{
					case iaField::TEXT:
						$sql .= "VARCHAR ({$fieldData['length']}) ";
						$sql .= $fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '';
						break;
					default:
						if (isset($fieldData['values']))
						{
							$values = explode(',', $fieldData['values']);

							$sql .= $fieldData['type'] == iaField::CHECKBOX ? 'SET' : 'ENUM';
							$sql .= "('" . implode("','", $values) . "')";

							if (!empty($fieldData['default']))
							{
								$sql .= " DEFAULT '{$fieldData['default']}' ";
							}
						}
						break;
				}
				$sql .= 'NOT NULL';
				$iaDb->query($sql);
			}
		}

		return $result;
	}

	private function _insert(array $fieldData)
	{
		$iaDb = &$this->_iaDb;

		if (isset($fieldData['parents']))
		{
			$this->_setParents($fieldData['name'], $fieldData['parents']);
			$fieldData['relation'] = iaField::RELATION_DEPENDENT;
			unset($fieldData['parents']);
		}

		if (isset($fieldData['children']))
		{
			$this->_setChildren($fieldData['name'], $fieldData['item'], $fieldData['values'], $fieldData['children']);
			unset($fieldData['children']);
		}

		$this->_setRelations();

		$pagesList = $fieldData['pages'];

		unset($fieldData['pages'], $fieldData['groups']);

		foreach ($this->_iaCore->languages as $code => $l)
		{
			if (!empty($fieldData['title'][$code]))
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'], $fieldData['title'][$code], $code);
			}

			if (isset($fieldData['annotation'][$code]) && !empty($fieldData['annotation'][$code]))
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_annotation', $fieldData['annotation'][$code], $code);
			}
		}
		unset($fieldData['title'], $fieldData['annotation']);

		if (!isset($fieldData['relation']) || $fieldData['relation'] != iaField::RELATION_PARENT)
		{
			$fieldData['relation'] = iaField::RELATION_REGULAR;
		}
		if (isset($fieldData['parents']))
		{
			$this->_setParents($fieldData['name'], $fieldData['parents']);
			$fieldData['relation'] = iaField::RELATION_DEPENDENT;

			unset($fieldData['parents']);
		}

		if (isset($fieldData['group']) && !isset($fieldData['fieldgroup_id']))
		{
			$fieldData['fieldgroup_id'] = 0;

			$rows = $iaDb->all(array('id', 'name', 'item'), "`item` = '{$fieldData['name']}' ORDER BY `item`, `name`", null, null, iaField::getTableGroups());
			foreach ($rows as $val)
			{
				if ($fieldData['group'] == $val['name'])
				{
					$fieldData['fieldgroup_id'] = $val['id'];
				}
			}
			unset($fieldData['group']);
		}

		//add language number field search ranges
		if (isset($fieldData['_numberRangeForSearch']) && is_array($fieldData['_numberRangeForSearch']))
		{
			foreach ($fieldData['_numberRangeForSearch'] as $number)
			{
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_range_' . $number, $number);
			}
		}
		unset($fieldData['_numberRangeForSearch']);
		$keys = array();
		if (isset($fieldData['values']) && is_array($fieldData['values']))
		{
			foreach ($fieldData['values'] as $key => $value)
			{
				$key = $keys[$key] = isset($fieldData['keys'][$key]) ? $fieldData['keys'][$key] : $key;
				iaLanguage::addPhrase('field_' . $fieldData['name'] . '_' . $key, $value);
			}
		}
		else
		{
			unset($fieldData['values']);
		}

		if (isset($fieldData['lang_values']) && is_array($fieldData['lang_values']))
		{
			foreach ($fieldData['lang_values'] as $lng_code => $lng_phrases)
			{
				foreach ($lng_phrases as $ph_key => $ph_value)
				{
					iaLanguage::addPhrase('field_' . $fieldData['name'] . '_' . $ph_key, $ph_value, $lng_code);
				}
			}
			unset($fieldData['lang_values']);
		}

		if (isset($fieldData['values']) && $fieldData['values'] && isset($fieldData['keys']))
		{
			$fieldData['values'] = implode(',', $fieldData['keys']);
		}
		unset($fieldData['keys']);

		if (isset($fieldData['lang_values']))
		{
			unset($fieldData['lang_values']);
		}

		$fieldData['order'] = $iaDb->getMaxOrder() + 1;

		$fieldId = $iaDb->insert($fieldData);

		if ($fieldId && $pagesList)
		{
			$this->_setPagesList($fieldId, $pagesList);
		}

		$fieldData['table_name'] = $this->_iaCore->factory('item')->getItemTable($fieldData['item']);

		$fields = $iaDb->describe($fieldData['table_name']);
		$exists = false;
		foreach ($fields as $f)
		{
			if ($f['Field'] == $fieldData['name'])
			{
				$exists = true;
				break;
			}
		}

		$exists || $this->_alterDbTable($fieldData);

		return $fieldId;
	}

	protected function _setPageTitle(&$iaView, array $entryData, $action)
	{
		if (in_array($action, array(iaCore::ACTION_ADD, iaCore::ACTION_EDIT)))
		{
			$entryName = empty($entryData['name']) ? '' : iaLanguage::get('field_' . $entryData['name']);
			$title = iaLanguage::getf($action . '_field', array('field' => $entryName));

			$iaView->title($title);
		}
	}


	private function _setParents($name, $parents = array())
	{
		$iaDb = &$this->_iaDb;

		$iaDb->setTable(iaField::getTableRelations());
		foreach ($parents as $item => $item_list)
		{
			$iaDb->delete('`child` = :name AND `item` = :item', null, array('name' => $name, 'item' => $item));
			foreach ($item_list as $field => $field_list)
			{
				foreach ($field_list as $element => $value)
				{
					$iaDb->insert(array(
						'field' => $field,
						'element' => $element,
						'child' => $name,
						'extras' => '',
						'item' => $item
					));
				}
			}
		}

		$iaDb->resetTable();
	}

	protected function _setChildren($name, $item, $values, $children = array())
	{
		$iaDb = &$this->_iaDb;

		$values = array_keys($values);

		$iaDb->setTable(iaField::getTableRelations());
		$iaDb->delete('`field` = :field AND `item` = :item', null, array('field' => $name, 'item' => $item));

		foreach ($children as $index => $fieldsList)
		{
			$fieldsList = explode(',', $fieldsList);

			foreach ($fieldsList as $field)
			{
				if (trim($field))
				{
					$iaDb->insert(array(
						'field' => $name,
						'element' => $values[$index],
						'child' => $field,
						'item' => $item
					));
				}
			}
		}

		$iaDb->resetTable();
	}

	protected function _setRelations()
	{
		$sql =
			'UPDATE `:prefix:table` f ' .
			"SET f.relation = ':dependent' " .
			'WHERE (' .
			'SELECT COUNT(*) FROM `:prefix:table_relations` fr WHERE fr.`child` = f.`name`' .
			') > 0';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table' => iaField::getTable(),
			'dependent' => iaField::RELATION_DEPENDENT,
			'table_relations' => iaField::getTableRelations()
		));

		$this->_iaDb->query($sql);
	}

	protected function _setPagesList($fieldId, array $pages, $extras = '')
	{
		$this->_iaDb->setTable(iaField::getTablePages());

		$this->_iaDb->delete(iaDb::convertIds($fieldId, 'field_id'));

		foreach ($pages as $pageName)
		{
			if (trim($pageName))
			{
				$this->_iaDb->insert(array('page_name' => $pageName, 'field_id' => $fieldId, 'extras' => $extras));
			}
		}

		$this->_iaDb->resetTable();
	}


	protected function _alterDbTable(array $fieldData)
	{
		$prefix = $this->_iaDb->prefix;

		$sql = sprintf('ALTER TABLE `%s%s` ', $prefix, $fieldData['table_name']);
		$sql .= 'ADD `' . $fieldData['name'] . '` ';

		switch ($fieldData['type'])
		{
			case iaField::DATE:
				$sql .= 'DATE ';
				break;
			case iaField::NUMBER:
				$sql .= 'DOUBLE ';
				break;
			case iaField::TEXT:
				$sql .= 'VARCHAR(' . $fieldData['length'] . ') '
					. ($fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '');
				break;
			case iaField::URL:
				$sql .= 'TINYTEXT ';
				break;
			case iaField::IMAGE:
			case iaField::STORAGE:
			case iaField::PICTURES:
			case iaField::TEXTAREA:
				$sql .= 'TEXT ';
				break;
			default:
				if (isset($fieldData['values']))
				{
					$values = explode(',', $fieldData['values']);

					$sql .= ($fieldData['type'] == iaField::CHECKBOX) ? 'SET' : 'ENUM';
					$sql .= "('" . implode("','", $values) . "')";

					if (!empty($fieldData['default']))
					{
						$sql .= " DEFAULT '{$fieldData['default']}' ";
					}
				}
		}
		$sql .= 'NOT null';

		$this->_iaDb->query($sql);

		if ($fieldData['searchable'] && in_array($fieldData['type'], array(iaField::TEXT, iaField::TEXTAREA)))
		{
			$indexes = $this->_iaDb->getAll("SHOW INDEX FROM `" . $prefix . $fieldData['table_name'] . "`");
			$keyExists = false;
			if ($indexes)
			{
				foreach ($indexes as $i)
				{
					if ($i['Key_name'] == $fieldData['name'] && $i['Index_type'] == 'FULLTEXT')
					{
						$keyExists = true;
						break;
					}
				}
			}

			if (!$keyExists)
			{
				$this->_iaDb->query("ALTER TABLE `" . $prefix . $fieldData['table_name'] . "` ADD FULLTEXT (`{$fieldData['name']}`)");
			}
		}
	}
}