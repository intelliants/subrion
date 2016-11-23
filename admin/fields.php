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
	const TREE_NODE_TITLE = 'field_%s_%s_%s';

	protected $_name = 'fields';

	protected $_gridColumns = array('name', 'item', 'group', 'fieldgroup_id', 'type', 'relation', 'length', 'order', 'status', 'delete' => 'editable');
	protected $_gridFilters = array('status' => self::EQUAL, 'id' => self::EQUAL, 'item' => self::EQUAL, 'relation' => self::EQUAL);

	protected $_tooltipsEnabled = true;

	protected $_phraseAddSuccess = 'field_added';
	protected $_phraseGridEntryDeleted = 'field_deleted';

	private $_data;


	public function __construct()
	{
		parent::__construct();

		$iaField = $this->_iaCore->factory('field');
		$this->setHelper($iaField);

		$this->_iaCore->factory('picture');
	}

	/**
	 * Custom item fields support
	 *
	 * @param $iaView
	 */
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

	protected function _gridRead($params)
	{
		if (isset($params['get']) && 'groups' == $params['get'])
		{
			return $this->_fetchFieldGroups($params['item']);
		}

		if (1 == count($this->_iaCore->requestPath) && 'tree' == $this->_iaCore->requestPath[0])
		{
			return $this->_treeActions($params);
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
			$entry['title'] = iaField::getFieldTitle($entry['item'], $entry['name']);
			$entry['group'] = isset($groups[$entry['fieldgroup_id']])
				? iaField::getFieldgroupTitle($entry['item'], $groups[$entry['fieldgroup_id']])
				: iaLanguage::get('other');
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
			'multilingual' => false,
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
			$this->_iaDb->delete(iaDb::convertIds($entryId, 'field_id'), iaField::getTableRelations());

			$this->_iaDb->delete("`key` LIKE 'field_{$field['item']}_{$field['name']}%' ", iaLanguage::getTable());

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

			// delete tree stuff
			if (iaField::TREE == $field['type'])
			{
				$this->_iaDb->delete(
					'`field` = :name && `item` = :item',
					'fields_tree_nodes',
					array('name' => $field['name'], 'item' => $field['item'])
				);

				$this->_iaDb->delete("`key` LIKE 'field_tree_{$field['item']}_{$field['name']}_%' ", iaLanguage::getTable());
			}
		}

		return $result;
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry = array(
			'name' => iaSanitize::alias(iaUtil::checkPostParam('name')),
			'item' => iaUtil::checkPostParam('item'),
			'default' => iaUtil::checkPostParam('default'),
			'lang_values' => iaUtil::checkPostParam('lang_values'),
			'text_default' => iaSanitize::html(iaUtil::checkPostParam('text_default')),
			'type' => iaUtil::checkPostParam('type'),
			'annotation' => iaUtil::checkPostParam('annotation'),
			'fieldgroup_id' => (int)iaUtil::checkPostParam('fieldgroup_id'),
			'text_length' => (int)iaUtil::checkPostParam('text_length', 255),
			'length' => iaUtil::checkPostParam('length', false),
			'title' => iaUtil::checkPostParam('title'),
			'pages' => iaUtil::checkPostParam('pages', array()),
			'multilingual' => iaUtil::checkPostParam('multilingual'),
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

		foreach ($this->_iaCore->languages as $code => $language)
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
				$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => $language['title'] . ' ' . iaLanguage::get('title'))), false);
				break;
			}
		}

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['name'] = trim(strtolower(iaSanitize::paranoid($entry['name'])));

			if (empty($entry['name']))
			{
				$this->addMessage('field_name_invalid');
			}
			elseif ($this->_dbColumnExists($entry['item'], $entry['name']))
			{
				$this->addMessage('field_name_exists');
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
						$entry['text_length'] = 255;
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
								foreach ($this->_iaCore->languages as $code => $language)
								{
									if ($code != $this->_iaCore->iaView->language)
									{
										if (!isset($_values[$index]))
										{
											unset($_langValues[$code][$index]);
										}
										elseif (!isset($_langValues[$code][$index]) || trim($_langValues[$code][$index]) == '') // add values if not exists
										{
											$lang_values[$code][$key] = $values[$key];
										}
										else
										{
											$lang_values[$code][$key] = $_langValues[$code][$index];
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
						$this->addMessage('error_file_type');
					}

					break;

				case iaField::DATE:
					$entry['timepicker'] = (int)iaUtil::checkPostParam('timepicker');

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

					break;

				case iaField::TREE:
					list($entry['values'], $entry['tree_nodes']) = $this->_parseTreeNodes(iaUtil::checkPostParam('nodes'));

					$entry['timepicker'] = (int)iaUtil::checkPostParam('multiple');
			}

			unset($entry['text_length'], $entry['text_default'], $entry['nodes'], $entry['multiple']);
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

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		$this->_alterDbTable($entry, $action);

		$this->_iaCore->startHook('phpAdminFieldsSaved', array('field' => &$entry, '_this' => $this));
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		if (iaCore::ACTION_EDIT == $iaView->get('action'))
		{
			$entryData = $this->getById($this->getEntryId());
			$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` IN ('field_" . $entryData['item'] . '_' . $entryData['name']
				. "', 'field_" . $entryData['item'] . '_' . $entryData['name'] . "_annotation') AND `category` = 'common'", null, null, iaLanguage::getTable());

			foreach ($rows as $row)
			{
				sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $entryData['item'], $entryData['name']) == $row['key']
					? ($titles[$row['code']] = $row['value'])
					: ($annotations[$row['code']] = $row['value']);
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

			if (iaField::TREE != $entryData['type'])
			{
				$values = $entryData['values'] = explode(',', $entryData['values']);
				foreach ($values as $key)
				{
					$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'field_{$entryData['name']}_$key'", null, null, iaLanguage::getTable());
					foreach ($rows as $row)
					{
						$entryData['values_titles'][$key][$row['code']] = $row['value'];
					}
				}
			}
			else
			{
				$entryData['values'] = $this->_getTree($entryData['item'], $entryData['name'], $entryData['values']);
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

			foreach ($this->_iaCore->languages as $code => $language)
			{
				$entryData['title'][$code] = (isset($titles[$code]) ? $titles[$code] : '');
				$entryData['annotation'][$code] = (isset($annotations[$code]) ? $annotations[$code] : '');
			}

			$entryData['pages'] = $this->getEntryId()
				? $this->_iaDb->onefield('page_name', iaDb::convertIds($this->getEntryId(), 'field_id'), null, null, iaField::getTablePages())
				: array();

			// get parents values
			$entryData['parents'] = $this->_getParents($entryData['name']);
			iaField::PICTURES != $entryData['type'] || $entryData['pic_max_images'] = $entryData['length'];
		}
		elseif (!empty($_GET['item']) || !empty($_POST['item']))
		{
			$entryData['item'] = isset($_POST['item']) ? $_POST['item'] : $_GET['item'];
		}

		$iaItem = $this->_iaCore->factory('item');
		$iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

		$pages = $groups = array();

		$stmt = empty($entryData['item'])
			? iaDb::EMPTY_CONDITION
			: iaDb::convertIds($entryData['item'], 'item');

		// get items pages
		$itemPagesList = $this->_iaDb->all(array('id', 'page_name', 'item'), $stmt . ' ORDER BY `item`, `page_name`', null, null, 'items_pages');
		foreach ($itemPagesList as $entry)
		{
			$pages[$entry['id']] = array(
				'name' => $entry['page_name'],
				'title' => $iaPage->getPageTitle($entry['page_name']),
				'item' => $entry['item']
			);
		}

		// get field groups
		if (!empty($entryData['item']))
		{
			$groups = $this->_fetchFieldGroups($entryData['item']);
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

		$this->_iaCore->startHook('phpAdminFieldsAssignValues', array('entry' => &$entryData));

		$iaView->assign('parents', $parents);
		$iaView->assign('fieldTypes', $fieldTypes['values']);
		$iaView->assign('groups', $groups);
		$iaView->assign('items', $items);
		$iaView->assign('pages', $pages);
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

		// set correct relations
		if (iaField::RELATION_REGULAR == $fieldData['relation'])
		{
			$this->_resetRelations($field['name'], $field['item']);
		}
		else
		{
			if ($fieldData['parents'])
			{
				$this->setParents($field['name'], $fieldData['parents']);
			}

			if ($fieldData['children'])
			{
				$this->_setChildren($field['name'], $field['item'], $fieldData['values'], $fieldData['children']);
			}

			$this->_setRelations();
		}
		unset($fieldData['parents'], $fieldData['children']);

		$iaDb->setTable(iaLanguage::getTable());
		$iaDb->delete("`key` LIKE 'field\_" . $field['name'] . "\_%'");

		foreach ($this->_iaCore->languages as $code => $language)
		{
			$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $field['item'], $field['name']);
			iaLanguage::addPhrase($key, $fieldData['title'][$code], $code, $field['extras']);

			if (isset($fieldData['annotation'][$code]) && $fieldData['annotation'][$code])
			{
				$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY . '_annotation', $field['item'], $field['name']);
				iaLanguage::addPhrase($key, $fieldData['annotation'][$code], $code, $field['extras']);
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
		elseif (iaField::TREE != $fieldData['type'])
		{
			unset($fieldData['values']);
		}
		elseif (iaField::TREE == $fieldData['type'])
		{
			$this->_saveTreeNodes($fieldData['tree_nodes'], $field);
			unset($fieldData['tree_nodes']);
		}
		unset($fieldData['keys']);

		if (isset($fieldData['lang_values']))
		{
			if (is_array($fieldData['lang_values']))
			{
				foreach ($fieldData['lang_values'] as $languageCode => $phrases)
				{
					foreach ($phrases as $phraseKey => $phraseValue)
					{
						$key = sprintf(iaField::FIELD_VALUE_PHRASE_KEY, $field['item'], $field['name'], $phraseKey);
						iaLanguage::addPhrase($key, $phraseValue, $languageCode, $field['extras']);
					}
				}
			}

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

		$this->_data = $this->getById($id);
		$result = parent::_entryUpdate($fieldData, $id);

		if ($pagesList)
		{
			$this->_setPagesList($id, $pagesList);
		}

		if ($result)
		{
			if (in_array($fieldData['type'], array(/*iaField::TEXT, */iaField::COMBO, iaField::RADIO, iaField::CHECKBOX)))
			{
				$sql = sprintf('ALTER TABLE `%s%s` CHANGE `%s` `%s`', $this->_iaDb->prefix, $tableName,
					$field['name'], $field['name']);

				switch ($fieldData['type'])
				{
					/*case iaField::TEXT:
						$sql .= "VARCHAR ({$fieldData['length']}) ";
						$sql .= $fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '';
						break;*/
					default:
						if (isset($fieldData['values']))
						{
							$values = explode(',', $fieldData['values']);

							$sql .= $fieldData['type'] == iaField::CHECKBOX ? 'SET' : 'ENUM';
							$sql .= "('" . implode("','", $values) . "')";
							$fieldData['default'] && $sql.= " DEFAULT '{$fieldData['default']}' ";
						}
				}

				$sql.= in_array($fieldData['type'], array(iaField::COMBO, iaField::RADIO)) ? 'NULL' : 'NOT NULL';

				$iaDb->query($sql);
			}
		}

		return $result;
	}

	private function _insert(array $fieldData)
	{
		$iaDb = &$this->_iaDb;

		if ($fieldData['parents'])
		{
			$this->setParents($fieldData['name'], $fieldData['parents']);
		}
		if (isset($fieldData['children']) && iaField::TREE != $fieldData['type'])
		{
			$this->_setChildren($fieldData['name'], $fieldData['item'], $fieldData['values'], $fieldData['children']);
		}
		unset($fieldData['parents'], $fieldData['children']);

		$this->_setRelations();

		$pagesList = $fieldData['pages'];

		unset($fieldData['pages'], $fieldData['groups']);

		foreach ($this->_iaCore->languages as $code => $language)
		{
			if (!empty($fieldData['title'][$code]))
			{
				$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $fieldData['item'], $fieldData['name']);
				iaLanguage::addPhrase($key, $fieldData['title'][$code], $code);
			}

			if (isset($fieldData['annotation'][$code]) && !empty($fieldData['annotation'][$code]))
			{
				$key = sprintf('field_%s_%s_annotation', $fieldData['item'], $fieldData['name']);
				iaLanguage::addPhrase($key, $code);
			}
		}
		unset($fieldData['title'], $fieldData['annotation']);

		// TODO: check the code below
		/*if (isset($fieldData['group']) && !isset($fieldData['fieldgroup_id']))
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
		}*/

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
		elseif (iaField::TREE != $fieldData['type'])
		{
			unset($fieldData['values']);
		}
		elseif (iaField::TREE == $fieldData['type'])
		{
			$iaDb->setTable('fields_tree_nodes');

			if ($fieldData['tree_nodes'])
			{
				foreach ($fieldData['tree_nodes'] as $id => $node)
				{
					$node['field'] = $fieldData['name'];
					$node['item'] = $fieldData['item'];

					$iaDb->insert($node);
				}
			}
			$iaDb->resetTable();

			unset($fieldData['tree_nodes']);
		}

		if (isset($fieldData['lang_values']) && is_array($fieldData['lang_values']))
		{
			$values = $fieldData['lang_values'];
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

		$fieldData['order'] = $iaDb->getMaxOrder(null, array('item', $fieldData['item'])) + 1;

		$fieldId = $iaDb->insert($fieldData);

		if (!$fieldId)
		{
			return false;
		}

		if (isset($values) && is_array($values))
		{
			foreach ($values as $lng_code => $lng_phrases)
			{
				foreach ($lng_phrases as $ph_key => $ph_value)
				{
					$key = sprintf(iaField::FIELD_VALUE_PHRASE_KEY, $fieldData['item'], $fieldData['name'], $ph_key);
					iaLanguage::addPhrase($key, $ph_value, $lng_code);
				}
			}
		}

		empty($pagesList) || $this->_setPagesList($fieldId, $pagesList);

		return $fieldId;
	}

	protected function _setPageTitle(&$iaView, array $entryData, $action)
	{
		if (in_array($action, array(iaCore::ACTION_ADD, iaCore::ACTION_EDIT)))
		{
			$entryName = empty($entryData['name']) ? '' : iaField::getFieldTitle($entryData['item'], $entryData['name']);
			$title = iaLanguage::getf($action . '_field', array('field' => $entryName));

			$iaView->title($title);
		}
	}

	public function setParents($fieldName, array $parents)
	{
		$iaDb = &$this->_iaDb;

		$iaDb->setTable(iaField::getTableRelations());

		foreach ($parents as $itemName => $list)
		{
			$iaDb->delete('`child` = :name AND `item` = :item', null, array('name' => $fieldName, 'item' => $itemName));

			foreach ($list as $parentFieldName => $values)
			{
				foreach ($values as $value => $flag)
				{
					$iaDb->insert(array(
						'field' => $parentFieldName,
						'element' => $value,
						'child' => $fieldName,
						'item' => $itemName
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

	protected function _resetRelations($name, $item)
	{
		$where = '`item` = :item AND `field` = :field';
		$this->_iaDb->bind($where, array('item' => $item, 'field' => $name));

		// set dependent fields to regular
		if ($children = $this->_iaDb->onefield('child', $where, null, null, iaField::getTableRelations()))
		{
			foreach ($children as $child)
			{
				$this->_iaDb->update(array('relation' => iaField::RELATION_REGULAR),
					iaDb::printf("`item` = ':item' && `name` = ':name' ", array('item' => $item, 'name' => $child)),
					null, iaField::getTable()
				);
			}
		}

		// delete dependent relations
		$this->_iaDb->delete(
			iaDb::printf("`item` = ':item' && (`field` = ':field' || `child` = ':field')",
			array('item' => $item, 'field' => $name)
		), iaField::getTableRelations());
	}

	protected function _setRelations()
	{
		$sql = 'UPDATE `:prefix:table_fields` f '
			. "SET f.relation = ':dependent' "
			. 'WHERE ('
				. 'SELECT COUNT(*) FROM `:prefix:table_relations` fr WHERE fr.`child` = f.`name`'
			. ') > 0';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_fields' => iaField::getTable(),
			'table_relations' => iaField::getTableRelations(),
			'dependent' => iaField::RELATION_DEPENDENT
		));

		$this->_iaDb->query($sql);
	}

	protected function _setPagesList($fieldId, array $pages)
	{
		$this->_iaDb->setTable(iaField::getTablePages());

		$this->_iaDb->delete(iaDb::convertIds($fieldId, 'field_id'));

		foreach ($pages as $pageName)
		{
			if (trim($pageName))
			{
				$this->_iaDb->insert(array('page_name' => $pageName, 'field_id' => $fieldId));
			}
		}

		$this->_iaDb->resetTable();
	}

	protected function _alterDbTable(array $fieldData, $action)
	{
		$dbTable = $this->_iaCore->factory('item')->getItemTable($fieldData['item']);

		if (iaCore::ACTION_ADD == $action)
		{
			$fieldData['multilingual']
				? $this->_alterMultilingualColumns($dbTable, $fieldData['name'], $fieldData)
				: $this->_alterColumnScheme($dbTable, $fieldData);
		}
		elseif (iaCore::ACTION_EDIT == $action && $this->_data)
		{
			if ($fieldData['multilingual'] != $this->_data['multilingual'])
			{
				$this->_alterMultilingualColumns($dbTable, $this->_data['name'], $fieldData);
			}
			elseif ($fieldData['length'] != $this->_data['length']
				|| $fieldData['default'] != $this->_data['default'])
			{
				$fieldData['name'] = $this->_data['name'];
				$this->_alterColumnScheme($dbTable, $fieldData);
			}

			if ($fieldData['searchable'] != $this->_data['searchable'])
			{
				$this->_alterColumnIndex($dbTable, $this->_data['name']);
			}
		}
	}

	private function _alterMultilingualColumns($dbTable, $fieldName, array $fieldData)
	{
		$defaultLanguage = null;

		foreach ($this->_iaCore->languages as $language)
		{
			if ($language['default'])
			{
				$defaultLanguage = $language;
				break;
			}
		}

		if ($fieldData['multilingual'])
		{
			$fieldData['name'] = $fieldName;
			$this->_alterColumnScheme($dbTable, $fieldData, $fieldName . '_' . $defaultLanguage['iso']);

			foreach ($this->_iaCore->languages as $language)
			{
				if ($language['iso'] != $defaultLanguage['iso'])
				{
					$fieldData['name'] = $fieldName . '_' . $language['iso'];
					$this->_alterColumnScheme($dbTable, $fieldData);
				}
			}
		}
		else
		{
			$fieldData['name'] = $fieldName . '_' . $defaultLanguage['iso'];
			$this->_alterColumnScheme($dbTable, $fieldData, $fieldName);

			foreach ($this->_iaCore->languages as $language)
			{
				if ($language['iso'] != $defaultLanguage['iso'])
				{
					$this->_alterDropColumn($dbTable, $fieldName . '_' . $language['iso']);
				}
			}
		}
	}

	private function _alterColumnScheme($dbTable, array $fieldData, $newName = null)
	{
		is_null($newName) && $newName = $fieldData['name'];

		$sql = $this->_isDbColumnExist($dbTable, $fieldData['name'])
			? 'ALTER TABLE `:prefix:table` CHANGE `:column1` `:column2` :scheme'
			: 'ALTER TABLE `:prefix:table` ADD `:column2` :scheme';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table' => $dbTable,
			'column1' => $fieldData['name'],
			'column2' => $newName,
			'scheme' => $this->_alterCmdBody($fieldData)
		));

		$this->_iaDb->query($sql);
	}

	private function _isDbColumnExist($dbTable, $columnName)
	{
		$sql = sprintf("SHOW COLUMNS FROM `%s%s` WHERE `Field` LIKE '%s'", $this->_iaDb->prefix, $dbTable, $columnName);

		return (bool)$this->_iaDb->getRow($sql);
	}

	private function _alterDropColumn($dbTable, $columnName)
	{
		$sql = sprintf('ALTER TABLE `%s%s` DROP `%s`', $this->_iaDb->prefix, $dbTable, $columnName);

		$this->_iaDb->query($sql);
	}

	private function _alterColumnIndex($dbTable, $fieldName)
	{
		$sql = sprintf('SHOW INDEX FROM `%s%s`', $this->_iaDb->prefix, $dbTable);

		$exists = false;
		if ($indexes = $this->_iaDb->getAll($sql))
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

		if (!$exists)
		{
			$this->_iaDb->query("ALTER TABLE `" . $this->_iaDb->prefix . $dbTable . "` ADD FULLTEXT (`" . $fieldName . "`)");
		}
	}

	private function _alterCmdBody(array $fieldData)
	{
		$result = '';

		switch ($fieldData['type'])
		{
			case iaField::DATE:
				$result.= $fieldData['timepicker'] ? 'DATETIME ' : 'DATE ';
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

	private function _getParents($name)
	{
		$result = array();

		if ($parents = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`child` = '{$name}'", 0, null, iaField::getTableRelations()))
		{
			foreach ($parents as $parent)
			{
				$result[$parent['item']][$parent['field']][$parent['element']] = true;
			}
		}

		return $result;
	}

	private function _parseTreeNodes($nodesFlatData)
	{
		$nestedIds = array();
		$preservedKeys = array('id', 'text', 'parent');
		$data = iaUtil::jsonDecode($nodesFlatData);

		foreach ($data as $i => $node)
		{
			foreach ($node as $key => $value)
			{
				if (!in_array($key, $preservedKeys)) unset($data[$i][$key]);
			}

			$alias = strtolower(iaSanitize::alias($node['text']));
			$nestedIds[$node['id']] = array(
				'node_id' => $node['id'],
				'text' => $node['text'],
				'parent_node_id' => '#' != $node['parent'] ? $node['parent'] : '',
				'alias' => ('#' != $node['parent'] && isset($nestedIds[$node['parent']])) ?
					$nestedIds[$node['parent']]['alias'] . $alias . IA_URL_DELIMITER :
					$alias . IA_URL_DELIMITER
			);
		}

		return array(iaUtil::jsonEncode($data), $nestedIds);
	}

	private function _dbColumnExists($itemName, $columnName)
	{
		$result = false;

		$iaItem = $this->_iaCore->factory('item');

		foreach ($this->_iaDb->describe($iaItem->getItemTable($itemName)) as $column)
		{
			if ($columnName == $column['Field'])
			{
				$result = true;
				break;
			}
		}

		return $result;
	}

	private function _treeActions(array $params)
	{
		$output = array();

		$key = sprintf(self::TREE_NODE_TITLE, $params['item'], $params['field'], $params['id']);

		if ($_POST)
		{
			$packageName = $this->_iaCore->factory('item')->getPackageByItem($params['item']);

			$this->_iaDb->delete(iaDb::convertIds($key, 'key'), iaLanguage::getTable());

			foreach ($_POST as $langCode => $title)
			{
				iaLanguage::addPhrase($key, $title, $langCode, $packageName);
			}

			$output['message'] = iaLanguage::get('saved');
			$output['success'] = true;
		}
		else
		{
			$phrases = $this->_iaDb->keyvalue(array('code', 'value'), iaDb::convertIds($key, 'key'), iaLanguage::getTable());

			foreach ($this->_iaCore->languages as $code => $language)
			{
				$output[] = array(
					'fieldLabel' => $language['title'],
					'name' => $code,
					'value' => isset($phrases[$code]) ? $phrases[$code] : null
				);
			}
		}

		return $output;
	}

	private function _getTree($itemName, $fieldName, $nodes)
	{
		$unpackedNodes = is_string($nodes) ? iaUtil::jsonDecode($nodes) : array();

		foreach ($unpackedNodes as &$node)
		{
			$node['text'] = iaLanguage::get(sprintf(self::TREE_NODE_TITLE, $itemName, $fieldName, $node['id']), $node['text']);
		}

		return iaUtil::jsonEncode($unpackedNodes);
	}

	protected function _saveTreeNodes($nodes, array $field)
	{
		$this->_iaDb->setTable('fields_tree_nodes');

		$this->_iaDb->delete('`field` = :name && `item` = :item', null, array('name' => $field['name'], 'item' => $field['item']));
		$this->_iaDb->delete('`key` LIKE :key', iaLanguage::getTable(), array('key' => 'field_' . $field['item'] . '_' . $field['name'] . '_%'));

		if ($nodes)
		{
			foreach ($nodes as $node)
			{
				$caption = $node['text'];
				unset($node['text']);

				$node['field'] = $field['name'];
				$node['item'] = $field['item'];
				$node['extras'] = $field['extras'];

				if ($this->_iaDb->insert($node))
				{
					$key = sprintf(self::TREE_NODE_TITLE, $field['item'], $field['name'], $node['node_id']);
					$this->_addPhrase($key, $caption, $field['extras']);
				}
			}
		}

		$this->_iaDb->resetTable();
	}

	protected function _addPhrase($key, $value, $extras = '')
	{
		foreach ($this->_iaCore->languages as $code => $language)
		{
			iaLanguage::addPhrase($key, $value, $code, $extras);
		}
	}

	private function _fetchFieldGroups($itemName)
	{
		$result = array();

		$where = '`item` = :item ORDER BY `item`, `name`';
		$this->_iaDb->bind($where, array('item' => $itemName));

		$rows = $this->_iaDb->all(array('id', 'name', 'item'), $where, null, null, iaField::getTableGroups());
		foreach ($rows as $row)
			$result[] = array('id' => $row['id'], 'title' => iaField::getFieldgroupTitle($row['item'], $row['name']));

		return $result;
	}
}