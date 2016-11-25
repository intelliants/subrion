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
	private $_values;


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
			'item' => null,
			'type' => null,
			'relation' => iaField::RELATION_REGULAR,
			'multilingual' => false,
			'required' => false,
			'length' => iaField::DEFAULT_LENGTH,
			'searchable' => false,
			'default' => '',
			'status' => iaCore::STATUS_ACTIVE
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

			$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $field['item'], $field['name']);

			$this->_iaDb->delete('`key` LIKE :key1 OR `key` LIKE :key2', iaLanguage::getTable(),
				array('key1' => $key . '_%', 'key2' => $key . '+%'));

			$itemTable = $this->_iaCore->factory('item')->getItemTable($field['item']);

			$this->_isDbColumnExist($itemTable, $field['name']) && $this->_alterDropColumn($itemTable, $field['name']);

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
		$this->_values = null;

		$entry['name'] = iaSanitize::alias($data['name']);
		$entry['item'] = $data['item'];
		$entry['type'] = $data['type'];
		$entry['empty_field'] = $data['empty_field'];
		$entry['relation'] = $data['relation'];
		$entry['status'] = $data['status'];

		$entry['fieldgroup_id'] = (int)$data['fieldgroup_id'];
		$entry['length'] = (int)$data['length'];

		$entry['required'] = (int)$data['required'];
		$entry['multilingual'] = (int)$data['multilingual'];
		$entry['searchable'] = (int)$data['searchable'];
		$entry['adminonly'] = (int)$data['adminonly'];
		$entry['for_plan'] = (int)$data['for_plan'];
		$entry['link_to'] = (int)$data['link_to'];
		$entry['use_editor'] = (int)$data['use_editor'];

		$entry['extra_actions'] = $data['extra_actions'];

		foreach ($this->_iaCore->languages as $code => $language)
		{
			if (empty($data['title'][$code]))
			{
				$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('title') . ': ' . $language['title'])), false);
			}
		}

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['name'] = trim(strtolower(iaSanitize::paranoid($entry['name'])));

			if (empty($entry['name']))
			{
				$this->addMessage('field_name_invalid');
			}
			elseif ($this->_isDbColumnExist($this->_iaCore->factory('item')->getItemTable($entry['item']), $entry['name']))
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
			empty($entry['length']) && $entry['length'] = iaField::DEFAULT_LENGTH;

			switch ($entry['type'])
			{
				case iaField::TEXT:
					$entry['length'] = min(255, max(1, $data['text_length']));
					$entry['default'] = $data['text_default'];

					break;

				case iaField::TEXTAREA:
					$entry['default'] = '';

					break;

				case iaField::COMBO:
				case iaField::RADIO:
				case iaField::CHECKBOX:
					$values = array();

					foreach ($data['keys'] as $idx => $key)
					{
						$key = trim($key);
						$key || $key = self::_obtainKey($data['keys']);

						$hasValue = false;
						foreach ($this->_iaCore->languages as $iso => $language)
						{
							if (!empty($data['values'][$iso][$idx]))
							{
								$hasValue = true;
								break;
							}
						}

						if (!$hasValue) continue;

						foreach ($this->_iaCore->languages as $iso => $language)
							$values[$key][$iso] = trim($data['values'][$iso][$idx]);
					}

					$this->_values = $values;
					$entry['values'] = implode(',', array_keys($values));

					// default value
					$defaultValues = array();
					foreach (explode('|', $data['multiple_default']) as $idx => $default)
					{
						foreach ($values as $key => $phrases)
							$phrases[$this->_iaCore->language['iso']] == $default
							&& $defaultValues[] = $key;
					}

					if ($defaultValues)
					{
						$entry['default'] = (iaField::CHECKBOX == $entry['type'])
							? implode(',', $defaultValues)
							: $defaultValues[0];
					}
					else
					{
						$entry['default'] = '';
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
					$entry['timepicker'] = (int)$data['timepicker'];

					break;

				case iaField::URL:
					$entry['url_nofollow'] = (int)$data['url_nofollow'];

					break;

				case iaField::IMAGE:
					$entry['length'] = 1;
					$entry['image_height'] = (int)$data['image_height'];
					$entry['image_width'] = (int)$data['image_width'];
					$entry['thumb_height'] = (int)$data['thumb_height'];
					$entry['thumb_width'] = (int)$data['thumb_width'];
					$entry['file_prefix'] = $data['file_prefix'];
					$entry['resize_mode'] = $data['resize_mode'];

					break;

				case iaField::NUMBER:
					$entry['length'] = (int)iaUtil::checkPostParam('number_length', 8);
					$entry['default'] = $data['number_default'];

					break;

				case iaField::PICTURES:
					$entry['length'] = (int)iaUtil::checkPostParam('pic_max_images', 5);
					$entry['image_height'] = (int)$data['pic_image_height'];
					$entry['image_width'] = (int)$data['pic_image_width'];
					$entry['thumb_height'] = (int)$data['pic_thumb_height'];
					$entry['thumb_width'] = (int)$data['pic_thumb_width'];
					$entry['file_prefix'] = $data['pic_file_prefix'];
					$entry['resize_mode'] = $data['pic_resize_mode'];

					break;

				case iaField::TREE:
					list($entry['values'], $entry['tree_nodes']) = $this->_parseTreeNodes($data['nodes']);

					$entry['timepicker'] = (int)$data['multiple'];
			}
		}

		$entry['required'] && $entry['required_checks'] = $data['required_checks'];

		if (!$this->_iaDb->exists(iaDb::convertIds($entry['fieldgroup_id']), null, iaField::getTableGroups()))
		{
			$entry['fieldgroup_id'] = 0;
		}

		if ($entry['searchable'])
		{
			if (isset($data['show_as']) && $entry['type'] != iaField::NUMBER && in_array($data['show_as'], array(iaField::COMBO, iaField::RADIO, iaField::CHECKBOX)))
			{
				$entry['show_as'] = $data['show_as'];
			}
			elseif ($entry['type'] == iaField::NUMBER && !empty($data['_values']))
			{
				$entry['sort_order'] = ('asc' == $data['sort_order']) ? $data['sort_order'] : 'desc';
			}
		}

		$this->_iaCore->startHook('phpAdminFieldsEdit', array('field' => &$entry));

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		$this->_savePhrases($entry, $data);
		$this->_savePages($data);
		$this->_alterDbTable($entry, $action);
		$this->_saveRelations($entry, $data);

		if (iaField::TREE == $entry['type'])
		{
			$this->_saveTreeNodes($data['tree_nodes'], $entry);
		}

		$this->_iaCore->startHook('phpAdminFieldsSaved', array('field' => &$entry, '_this' => $this));
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		$titles = $values = array();

		if (iaCore::ACTION_EDIT == $iaView->get('action'))
		{
			$entryData = $this->getById($this->getEntryId());

			$title = array();
			$annotation = array();

			$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` IN ('field_" . $entryData['item'] . '_' . $entryData['name']
				. "', 'field_" . $entryData['item'] . '_' . $entryData['name'] . "_annotation') AND `category` = 'common'", null, null, iaLanguage::getTable());
			foreach ($rows as $row)
			{
				sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $entryData['item'], $entryData['name']) == $row['key']
					? ($title[$row['code']] = $row['value'])
					: ($annotation[$row['code']] = $row['value']);
			}

			if ($entryData['default'])
			{
				if (iaField::CHECKBOX == $entryData['type'])
				{
					$entryData['default'] = explode(',', $entryData['default']);
					foreach ($entryData['default'] as $key_d => $key)
					{
						$entryData['default'][$key_d] = iaField::getFieldValue($entryData['item'], $entryData['name'], $key);
					}
				}
				else
				{
					$entryData['default'] = iaField::getFieldValue($entryData['item'], $entryData['name'], $entryData['default']);
				}
			}

			if (iaField::TREE == $entryData['type'])
			{
				$values = $this->_getTree($entryData['item'], $entryData['name'], $entryData['values']);
			}
			elseif ($entryData['values'])
			{
				$values = explode(',', $entryData['values']);
				foreach ($values as $key)
				{
					$phrase = sprintf(iaField::FIELD_VALUE_PHRASE_KEY, $entryData['item'], $entryData['name'], $key);
					$rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($phrase, 'key'), null, null, iaLanguage::getTable());
					foreach ($rows as $row)
						$titles[$key][$row['code']] = $row['value'];
				}
			}

			if (is_array($entryData['default']))
			{
				$entryData['default'] = implode('|', $entryData['default']);
			}

			if (!$entryData['editable'])
			{
				unset($entryData['status']);
				$iaView->assign('noSystemFields', true);
			}

			foreach ($this->_iaCore->languages as $code => $language)
			{
				$entryData['title'][$code] = (isset($title[$code]) ? $title[$code] : '');
				$entryData['annotation'][$code] = (isset($annotation[$code]) ? $annotation[$code] : '');
			}

			$entryData['pages'] = $this->_iaDb->onefield('page_name', iaDb::convertIds($this->getEntryId(), 'field_id'), null, null, iaField::getTablePages());
			$entryData['parents'] = $this->_getParents($entryData['name']);

			iaField::PICTURES != $entryData['type'] || $entryData['pic_max_images'] = $entryData['length'];
		}
		elseif (!empty($_GET['item']) || !empty($_POST['item']))
		{
			$entryData['item'] = isset($_POST['item']) ? $_POST['item'] : $_GET['item'];
		}

		$iaItem = $this->_iaCore->factory('item');
		$iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

		$pages = array();

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

		$fieldTypes = $this->_iaDb->getEnumValues(iaField::getTable(), 'type');
		$items = $iaItem->getItems();
		$parents = array();

		$fieldsList = $this->_iaDb->all(array('id', 'item', 'name'), (empty($entryData['name']) ? '' : "`name` != '{$entryData['name']}' AND ") . " `relation` = 'parent' AND `type` IN ('combo', 'radio', 'checkbox') AND " . $stmt);
		foreach ($fieldsList as $row)
		{
			isset($parents[$row['item']]) || $parents[$row['item']] = array();
			$array = $this->_iaDb->getEnumValues($iaItem->getItemTable($row['item']), $row['name']);
			$parents[$row['item']][$row['name']] = array($row['id'], $array['values']);
		}

		$entryData['pages'] || $entryData['pages'] = array();

		$this->_iaCore->startHook('phpAdminFieldsAssignValues', array('entry' => &$entryData));

		$iaView->assign('parents', $parents);
		$iaView->assign('fieldTypes', $fieldTypes['values']);
		$iaView->assign('groups', $this->_fetchFieldGroups($entryData['item']));
		$iaView->assign('items', $items);
		$iaView->assign('pages', $pages);
		$iaView->assign('titles', $titles);
		$iaView->assign('values', $values);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		return (1 == count($entryData))
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
		$this->_data = $this->getById($id);

		return (bool)parent::_entryUpdate($fieldData, $id);
	}

	private function _insert(array $fieldData)
	{
		$fieldData['order'] = $this->_iaDb->getMaxOrder(null, array('item', $fieldData['item'])) + 1;

		return $this->_iaDb->insert($fieldData);
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

	protected function _savePages(array $data)
	{
		$this->_iaDb->setTable(iaField::getTablePages());

		$this->_iaDb->delete(iaDb::convertIds($this->getEntryId(), 'field_id'));

		if (isset($data['pages']))
		{
			foreach ($data['pages'] as $pageName)
			{
				if ($pageName = trim($pageName))
				{
					$this->_iaDb->insert(array('page_name' => $pageName, 'field_id' => $this->getEntryId()));
				}
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
				|| $fieldData['default'] != $this->_data['default']
				|| ($fieldData['values'] != $this->_data['values']))
			{
				$fieldData['name'] = $this->_data['name'];
				$this->_alterColumnScheme($dbTable, $fieldData);
			}

			if ($fieldData['searchable'] != $this->_data['searchable'])
			{
				$this->_alterColumnIndex($dbTable, $this->_data['name'], $fieldData['searchable']);
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

	private function _alterColumnIndex($dbTable, $fieldName, $enabled)
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

		if ($enabled && !$exists)
		{
			$sql = sprintf('ALTER TABLE `%s%s` ADD FULLTEXT(`%s`)', $this->_iaDb->prefix, $dbTable, $fieldName);
		}
		elseif (!$enabled && $exists)
		{
			$sql = sprintf('ALTER TABLE `%s%s` DROP INDEX `%s`', $this->_iaDb->prefix, $dbTable, $fieldName);
		}

		isset($sql) && $this->_iaDb->query($sql);
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

	private function _getParents($fieldName)
	{
		$result = array();

		if ($parents = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($fieldName, 'child'), null, null, iaField::getTableRelations()))
		{
			foreach ($parents as $parent)
			{
				$result[$parent['field_id']][$parent['element']] = true;
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

	protected function _savePhrases(array $fieldData, array $data)
	{
		$extras = empty($this->_data['extras']) ? '' : $this->_data['extras'];
		$itemName = $fieldData['item'];
		$fieldName = empty($fieldData['name']) ? $this->_data['name'] : $fieldData['name'];

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad');

		$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName);

		$this->_iaDb->delete('`key` LIKE :key1 OR `key` LIKE :key2', iaLanguage::getTable(),
			array('key1' => $key, 'key2' => $key . '_annotation'));

		foreach ($this->_iaCore->languages as $code => $language)
		{
			$key = sprintf(iaField::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName);

			$title = $data['title'][$code];
			utf8_is_valid($title) || $title = utf8_bad_replace($title);

			iaLanguage::addPhrase($key, $title, $code, $extras);

			$key.= '_annotation';
			$annotation = $data['annotation'][$code];
			utf8_is_valid($annotation) || $annotation = utf8_bad_replace($annotation);

			iaLanguage::addPhrase($key, $annotation, $code, $extras);
		}

		if ($this->_values)
		{
			$this->_iaDb->delete('`key` LIKE :key', iaLanguage::getTable(), array('key' => sprintf(
					iaField::FIELD_TITLE_PHRASE_KEY, $itemName, $fieldName) . '+%'));

			foreach ($this->_values as $key => $phrases)
			{
				foreach ($phrases as $iso => $phrase)
					iaLanguage::addPhrase(sprintf(iaField::FIELD_VALUE_PHRASE_KEY, $itemName, $fieldName, $key), $phrase, $iso, $extras);
			}
		}
	}

	protected function _saveRelations(array $fieldData, array $data)
	{
		$fieldName = empty($fieldData['name']) ? $this->_data['name'] : $fieldData['name'];

		// set correct relations
		if (iaField::RELATION_REGULAR == $fieldData['relation'])
		{
			$this->_relationsReset($fieldName, $fieldData['item']);
			return;
		}

		empty($data['parents']) ||  $this->setParents($fieldName, $data['parents']);
		empty($data['children']) || $this->_relationsSetChildren($this->_data, $data['children']);

		$this->_relationsSetup();
	}

	public function setParents($fieldName, array $parents)
	{
		$fieldIds = $this->_iaDb->keyvalue(array('name', 'id'));

		$this->_iaDb->setTable(iaField::getTableRelations());

		foreach ($parents as $itemName => $list)
		{
			//$this->_iaDb->delete('`child` = :name AND `item` = :item', null,
			//	array('name' => $fieldName, 'item' => $itemName));
			$this->_iaDb->delete(iaDb::convertIds($fieldName, 'child'));

			foreach ($list as $parentFieldName => $values)
			{
				foreach ($values as $value => $flag)
					$this->_iaDb->insert(array(
						'field_id' => $fieldIds[$parentFieldName],
						'element' => $value,
						'child' => $fieldName
					));
			}
		}

		$this->_iaDb->resetTable();
	}

	private function _relationsSetChildren($values, $children)
	{
		$values = array_keys($values);

		$this->_iaDb->setTable(iaField::getTableRelations());

		$this->_iaDb->delete(iaDb::convertIds($this->getEntryId(), 'field_id'));

		if ($children)
		{
			foreach ($children as $index => $fieldsList)
			{
				$fieldsList = explode(',', $fieldsList);

				foreach ($fieldsList as $field)
				{
					if ($field = trim($field))
					{
						$this->_iaDb->insert(array(
							'field_id' => $this->getEntryId(),
							'element' => $values[$index],
							'child' => $field
						));
					}
				}
			}
		}

		$this->_iaDb->resetTable();
	}

	private function _relationsReset($fieldName, $itemName)
	{
		// mark dependent fields as regular
		$children = $this->_iaDb->onefield('child', iaDb::convertIds($this->getEntryId(), 'field_id'), null, null, iaField::getTableRelations());

		if ($children)
		{
			foreach ($children as $child)
			{
				$where = '`item` = :item AND `name` = :name';
				$this->_iaDb->bind($where, array('item' => $itemName, 'name' => $child));

				$this->_iaDb->update(array('relation' => iaField::RELATION_REGULAR), $where, null, iaField::getTable());
			}
		}

		// delete dependent relations
		$where = '`field_id` = :id OR `child` = :child';
		$this->_iaDb->bind($where, array('id' => $this->getEntryId(), 'child' => $fieldName));

		$this->_iaDb->delete($where, iaField::getTableRelations());
	}

	private function _relationsSetup()
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

	private static function _obtainKey(array $keys)
	{
		$i = 1;
		while (in_array($i, $keys)) $i++;

		return $i;
	}
}