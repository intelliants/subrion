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

class iaExtra extends abstractCore
{
	const TYPE_CORE = 'core';
	const TYPE_PACKAGE = 'package';
	const TYPE_PLUGIN = 'plugin';

	const ACTION_INSTALL = 'install';
	const ACTION_UNINSTALL = 'uninstall';
	const ACTION_UPGRADE = 'upgrade';

	const DEPENDENCY_TYPE_PACKAGE = 'package';
	const DEPENDENCY_TYPE_PLUGIN = 'plugin';
	const DEPENDENCY_TYPE_TEMPLATE = 'template';

	const SQL_STAGE_START = 'start';
	const SQL_STAGE_MIDDLE = 'middle';
	const SQL_STAGE_END = 'end';

	const VERSION_EMPTY = '0.0.0';

	const INSTALL_FILE_NAME = 'install.xml';

	const BLOCK_FILENAME_PATTERN = 'extra:%s/%s';

	protected static $_table = 'extras';

	private $_builtinPlugins = array('kcaptcha', 'fancybox', 'personal_blog', 'elfinder');

	protected $_inTag;
	protected $_currentPath;
	protected $_attributes;

	protected $_url;
	protected $_menuGroups = array();

	protected $_parsed = false;
	protected $_quickParseMode = false;

	protected $_notes;

	protected $_xmlContent = '';

	public $itemData;

	public $error = false;
	public $isUpgrade = false;
	public $isUpdate = false;

	protected $_extrasTypePaths = array();


	public function init()
	{
		parent::init();

		$this->iaCore->factory(array('acl', 'util'));

		$this->_extrasTypePaths[self::TYPE_PLUGIN] = IA_PLUGINS;
		$this->_extrasTypePaths[self::TYPE_PACKAGE] = IA_PACKAGES;
	}

	protected function _resetValues()
	{
		$this->itemData = array(
			'type' => self::TYPE_PLUGIN,
			'name' => '',
			'info' => array(
				'author' => '',
				'contributor' => '',
				'date' => '',
				'summary' => '',
				'status' => iaCore::STATUS_ACTIVE,
				'title' => '',
				'version' => '',
				'category' => ''
			),
			'actions' => null,
			'blocks' => null,
			'changeset' => null,
			'code' => array(
				'install' => null,
				'upgrade' => null,
				'uninstall' => null
			),
			'compatibility' => null,
			'config' => null,
			'config_groups' => null,
			'cron_jobs' => null,
			'custom_pages' => null,
			'dependencies' => null,
			'dumps' => null,
			'fields' => null,
			'groups' => null,
			'hooks' => null,
			'items' => null,
			'item_fields' => null,
			'item_field_groups' => null,
			'objects' => null,
			'pages' => array(
				'admin' => null,
				'custom' => null,
				'front' => null,
			),
			'permissions' => null,
			'phrases' => null,
			'requirements' => null,
			'screenshots' => null,
			'url' => null,
			'usergroups' => null,
			'sql' => array(
				'install' => null,
				'upgrade' => null,
				'uninstall' => null
			)
		);

		$this->_notes = null;
	}

	public function setUrl($url)
	{
		$this->_url = $url;
	}

	protected function _lookupGroupId($groupName)
	{
		return (int)$this->iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`name` = :name', array('name' => $groupName), 'admin_pages_groups');
	}

	public function parse($quickMode = false)
	{
		$this->_resetValues();
		$this->_quickParseMode = $quickMode;

		require_once IA_INCLUDES . 'xml' . IA_DS . 'xml_saxy_parser.php';

		$xmlParser = new SAXY_Parser();

		$xmlParser->xml_set_element_handler(array(&$this, '_parserStart'), array(&$this, '_parserEnd'));
		$xmlParser->xml_set_character_data_handler(array(&$this, $quickMode ? '_parserQuickData' : '_parserData'));
		$xmlParser->xml_set_comment_handler(array(&$this, '_parserComment'));

		$xmlParser->parse($this->_xmlContent);

		$this->_parsed = true;

		$this->_checkDependencies();
	}

	public function doAction($action, $url = '')
	{
		if (empty($action) || !in_array($action, array(self::ACTION_INSTALL, self::ACTION_UPGRADE)))
		{
			$this->error = true;
			$this->setMessage('Fatal error: Action is invalid');

			return false;
		}
		$this->_url = $url;

		if (!$this->_parsed)
		{
			$this->parse();
		}

		$this->checkValidity();

		if ($this->error)
		{
			return false;
		}
		else
		{
			$action = (self::ACTION_INSTALL == $action && $this->_isExist() && $this->_compare()) ? self::ACTION_UPGRADE : $action;
			return $this->{$action}();
		}
	}

	protected function _getVersion()
	{
		return $this->iaDb->one_bind('version', '`name` = :name', array('name' => $this->itemData['name']), self::getTable());
	}

	protected function _compare()
	{
		return version_compare(
			$this->iaDb->one_bind('version', '`name` = :name', array('name' => $this->itemData['name']), self::getTable()),
			$this->itemData['info']['version'],
			'<'
		);
	}

	protected function _isExist()
	{
		return $this->iaDb->exists('`name` = :name', array('name' => $this->itemData['name']));
	}

	protected function _checkDependencies()
	{
		if ($this->itemData['dependencies'])
		{
			$iaCore = iaCore::instance();

			$currentTemplate = $iaCore->get('tmpl');
			$iaItem = $iaCore->factory('item');

			foreach ($this->itemData['dependencies'] as $extrasName => $dependency)
			{
				$shouldBeExist = (bool)$dependency['exist'];
				switch ($dependency['type'])
				{
					case self::DEPENDENCY_TYPE_PACKAGE:
					case self::DEPENDENCY_TYPE_PLUGIN:
						$exists = $iaItem->isExtrasExist($extrasName, $dependency['type']);
						break;
					case self::DEPENDENCY_TYPE_TEMPLATE:
						$exists = $extrasName == $currentTemplate;
						break;
				}
				if (isset($exists))
				{
					if (!$exists && $shouldBeExist)
					{
						$messageCode = 'installation_extra_requirement_exist';
					}
					elseif ($exists && !$shouldBeExist)
					{
						$messageCode = 'installation_extra_requirement_doesnot_exist';
					}
					if (isset($messageCode))
					{
						$this->_notes[] = iaDb::printf(iaLanguage::get($messageCode), array('extra' => ucfirst($extrasName), 'type' => $dependency['type']));
						$this->error = true;
					}
				}
				else {
					$this->setMessage(iaLanguage::get('installation_extra_requirement_incorrect'));
				}
			}
		}
	}

	public function checkValidity()
	{
		$requiredFields = array('title', 'version', 'summary', 'author', 'contributor');
		$missingFields = array();

		if (empty($this->itemData['name']))
		{
			$this->error = true;
			$missingFields[] = 'name';
		}
		else
		{
			foreach ($requiredFields as $field)
			{
				if (!array_key_exists($field, $this->itemData['info']))
				{
					$this->error = true;
					$missingFields[] = $field;
				}
			}
		}

		if ($this->error)
		{
			if ($this->_notes)
			{
				$this->setMessage(implode('<br>', $this->_notes));
			}
			elseif (empty($missingFields))
			{
				$this->setMessage('Fatal error: Probably specified file is not XML file or is not acceptable');
			}
			else
			{
				$this->setMessage('Fatal error: The following fields are required: ' . implode(', ', $missingFields));
			}
		}
	}

	protected function _checkPath($items)
	{
		if (is_array($items))
		{
			foreach ($items as $item)
			{
				if (in_array($item, $this->_currentPath))
				{
					return true;
				}
			}
		}
		else
		{
			if (in_array($items, $this->_currentPath))
			{
				return true;
			}
		}

		return false;
	}

	protected function _attr($key, $default = '', $inArray = false)
	{
		$result = false;

		if (is_array($key))
		{
			foreach ($key as $item)
			{
				if ($result === false && isset($this->_attributes[$item]))
				{
					$result = $this->_attributes[$item];
					break;
				}
			}
		}
		else
		{
			if (isset($this->_attributes[$key]))
			{
				$result = $this->_attributes[$key];
			}
		}

		if ($result !== false)
		{
			if (is_array($inArray) && !in_array($result, $inArray))
			{
				$result = $default;
			}
		}
		else
		{
			$result = $default;
		}

		return $result;
	}

	protected function _alterTable($fieldData)
	{
		$iaDb = $this->iaDb;
		$this->iaCore->factory('field');

		$sql = 'ALTER TABLE `' . $iaDb->prefix . $fieldData['table_name'] . '` ADD `' . $fieldData['name'] . '` ';
		switch ($fieldData['type'])
		{
			case iaField::DATE:
				$sql .= 'DATE ';
				break;
			case iaField::NUMBER:
				$sql .= 'DOUBLE ';
				break;
			case iaField::TEXT:
				$sql .= 'VARCHAR (' . $fieldData['length'] . ') '
					. ($fieldData['default'] ? "DEFAULT '{$fieldData['default']}' " : '');
				break;
			case iaField::URL:
			case iaField::TREE:
				$sql .= 'TINYTEXT ';
				break;
			case iaField::IMAGE:
			case iaField::STORAGE:
			case iaField::PICTURES:
			case iaField::TEXTAREA:
				$sql .= 'TEXT ';
				break;
			default:
				if (isset($fieldData['values']) && $fieldData['values'] != '')
				{
					$values = explode(',', $fieldData['values']);

					$sql .= ($fieldData['type'] == 'checkbox') ? 'SET' : 'ENUM';
					$sql .= "('" . implode("','", $values) . "')";

					if ($fieldData['default'])
					{
						$sql .= " DEFAULT '{$fieldData['default']}' ";
					}
				}
		}

		if (!isset($fieldData['allow_null']) || !$fieldData['allow_null'])
		{
			$sql .= 'NOT NULL';
		}

		$iaDb->query($sql);

		if ($fieldData['searchable'] && in_array($fieldData['type'], array('text','textarea')))
		{
			$indexes = $iaDb->getAll('SHOW INDEX FROM `' . $iaDb->prefix . $fieldData['table_name'] . '`');
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
				$sql = sprintf('ALTER TABLE `%s%s` ADD FULLTEXT(`%s`)', $iaDb->prefix, $fieldData['table_name'], $fieldData['name']);
				$iaDb->query($sql);
			}
		}

		return true;
	}

	public function upgrade()
	{
		$this->isUpgrade = true;
		$iaDb = &$this->iaDb;
		$this->iaCore->startHook('phpExtrasUpgradeBefore', array('extra' => $this->itemData['name']));

		$this->_processQueries('install', self::SQL_STAGE_START, true);
		$this->_processQueries('upgrade', self::SQL_STAGE_START);

		if ($this->itemData['groups'])
		{
			$iaDb->setTable('admin_pages_groups');

			$maxOrder = $iaDb->getMaxOrder();
			foreach ($this->itemData['groups'] as $title => $entry)
			{
				$iaDb->exists("`extras` = '{$this->itemData['name']}' AND `name` = '{$entry['name']}'")
					? $iaDb->update($entry, "`extras` = '{$this->itemData['name']}' AND `name` = '{$entry['name']}'")
					: $iaDb->insert($entry, array('order' => ++$maxOrder));
				$this->_addPhrase('pages_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['pages']['admin'])
		{
			$this->_processAdminPages($this->itemData['pages']['admin']);
		}

		if ($this->itemData['actions'])
		{
			$iaDb->setTable('admin_actions');

			foreach ($this->itemData['actions'] as $action)
			{
				if ($action['name'] = strtolower(str_replace(' ', '_', $action['name'])))
				{
					$action['order'] = (empty($action['order']) || !is_numeric($action['order']))
						? $iaDb->getMaxOrder() + 1
						: $action['order'];

					$iaDb->exists('`name` = :name', array('name' => $action['name']))
						? $iaDb->update($action, "`name` = '{$action['name']}'")
						: $iaDb->insert($action);
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['phrases'])
		{
			$this->_processPhrases($this->itemData['phrases']);
		}

		if ($this->itemData['config_groups'])
		{
			$iaDb->setTable(iaCore::getConfigGroupsTable());
			$iaDb->delete(iaDb::convertIds($this->itemData['name'], 'extras'));

			$maxOrder = $iaDb->getMaxOrder();

			foreach ($this->itemData['config_groups'] as $title => $entry)
			{
				$iaDb->insert($entry, array('order' => ++$maxOrder));
				$this->_addPhrase('config_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['objects'])
		{
			$iaDb->setTable('acl_objects');

			foreach ($this->itemData['objects'] as $obj)
			{
				$where = "`object` = '{$obj['object']}' AND `action` = '{$obj['action']}'";
				if ($obj['title'])
				{
					$key = ($obj['object'] == $obj['pre_object'] ? '' : $obj['pre_object'] . '-') . $obj['object'];
					iaLanguage::addPhrase($key, $obj['title'], null, $this->itemData['name'], iaLanguage::CATEGORY_COMMON, false);
					unset($obj['title']);
				}

				$iaDb->exists($where)
					? $iaDb->update(array('access' => $obj['access']), $where)
					: $iaDb->insert($obj);
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['config'])
		{
			$this->_processConfig($this->itemData['config']);
		}

		$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

		if ($this->itemData['pages']['front'])
		{
			$iaDb->setTable('pages');

			$maxOrder = $iaDb->getMaxOrder();

			foreach ($this->itemData['pages']['front'] as $title => $page)
			{
				if ($page['blocks'] && ($ids = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION,
						"`name` IN ('" . implode("','", $page['blocks']) . "')", null, null, iaBlock::getTable())))
				{
					foreach ($ids as $blockId)
					{
						$iaBlock->setVisibility($blockId, true, array($page['name']), false);
					}
				}

				$content = empty($page['contents']) ? null : $page['contents'];

				is_int($page['group']) || $page['group'] = $this->_lookupGroupId($page['group']);
				$page['last_updated'] = date(iaDb::DATETIME_FORMAT);
				$page['order'] = ++$maxOrder;

				unset($page['blocks']);

				$result = $iaDb->exists('`name` = :name', $page)
					? $iaDb->update($page, iaDb::convertIds($page['name'], 'name'))
					: $iaDb->insert($page);

				if ($result)
				{
					empty($title) || $this->_addPhrase('page_title_' . $page['name'], $title, iaLanguage::CATEGORY_PAGE);
					empty($contents) || $this->_addPhrase('page_content_' . $page['name'], $content, iaLanguage::CATEGORY_PAGE);

					if ($page['fields_item'] && self::TYPE_PACKAGE == $this->itemData['type']
						&& !$iaDb->exists('`page_name` = :name AND `item` = :item', $page, 'items_pages'))
					{
						$iaDb->insert(array('page_name' => $page['name'], 'item' => $page['fields_item']), null, 'items_pages');
					}
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['blocks'])
		{
			foreach ($this->itemData['blocks'] as $block)
			{
				$blockId = $iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`extras` = :plugin AND `name` = :block',
					array('plugin' => $this->itemData['name'], 'block' => $block['name']), iaBlock::getTable());

				if ($blockId && in_array($block['type'], array(iaBlock::TYPE_PHP, iaBlock::TYPE_SMARTY)))
				{
					unset($block['classname']);
					$iaBlock->update($block, $blockId);
				}
				elseif (!$blockId)
				{
					$iaBlock->insert($block);
				}
			}
		}

		if ($this->itemData['hooks'])
		{
			$iaDb->setTable('hooks');
			foreach ($this->itemData['hooks'] as $hook)
			{
				$array = explode(',', $hook['name']);
				foreach ($array as $hookName)
				{
					if (trim($hookName))
					{
						$hook['name'] = $hookName;

						$stmt = '`extras` = :plugin AND `name` = :hook';
						$iaDb->bind($stmt, array('plugin' => $this->itemData['name'], 'hook' => $hook['name']));

						$iaDb->exists($stmt)
							? $iaDb->update($hook, $stmt)
							: $iaDb->insert($hook);
					}
				}
			}
			$iaDb->resetTable();
		}

		if ($this->itemData['usergroups'])
		{
			$iaAcl = $this->iaCore->factory('acl');

			$iaDb->setTable(iaUsers::getUsergroupsTable());
			foreach ($this->itemData['usergroups'] as $item)
			{
				if (!$iaDb->exists('`name` = :name', array('name' => $item['name'])))
				{
					$configs = $item['configs'];
					$permissions = $item['permissions'];
					$usergroupId = $iaDb->insert(array(
						'extras' => $item['extras'],
						'name' => $item['name'],
						'system' => true,
						'assignable' => $item['assignable'],
						'visible' => $item['visible']
					));

					$this->_addPhrase('usergroup_' . $item['name'], $item['title']);

					$iaDb->setTable(iaCore::getCustomConfigTable());
					$iaDb->delete("`type` = 'group' AND `type_id` = '$usergroupId'");
					foreach ($configs as $config)
					{
						$iaDb->insert(array(
							'name' => $config['name'],
							'value' => $config['value'],
							'type' => iaAcl::GROUP,
							'type_id' => $usergroupId,
							'extras' => $this->itemData['name']
						)); // add custom config
					}
					$iaDb->resetTable();

					$iaDb->setTable('acl_privileges');
					$iaDb->delete('`type` = :type AND `type_id` = :id', null, array('type' => iaAcl::GROUP, 'id' => $usergroupId));
					foreach ($permissions as $permission)
					{
						$data = array(
							'object' => $permission['object'],
							'object_id' => $permission['object_id'],
							'action' => $permission['action'],
							'access' => $permission['access'],
							'type' => iaAcl::GROUP,
							'type_id' => $usergroupId,
							'extras' => $permission['extras']
						);

						$iaDb->insert($data); // add privileges for usergroup
					}
					$iaDb->resetTable();
				}
			}
			$iaDb->resetTable();
		}

		$this->_processQueries('install', self::SQL_STAGE_MIDDLE, true);
		$this->_processQueries('upgrade', self::SQL_STAGE_MIDDLE);

		if ($this->itemData['items'])
		{
			$iaDb->setTable('items');

			foreach ($this->itemData['items'] as $item)
			{
				if (!$this->iaDb->exists('`item` = :item', $item))
				{
					$iaDb->insert(array_merge($item, array('package' => $this->itemData['name'])));
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['item_field_groups'])
		{
			$this->iaCore->factory('field');

			$iaDb->setTable(iaField::getTableGroups());

			$maxOrder = $iaDb->getMaxOrder();
			foreach ($this->itemData['item_field_groups'] as $entry)
			{
				$entry['order'] || $entry['order'] = ++$maxOrder;

				$title = $entry['title'];
				$description = $entry['description'];

				unset($entry['title'], $entry['description']);

				if ($id = $iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`name` = :name AND `item` = :item', $entry))
				{
					unset($entry['name'], $entry['item']);

					$iaDb->update($entry, iaDb::convertIds($id));
					$result = (0 == $iaDb->getErrorNumber());
				}
				else
				{
					$result = $iaDb->insert($entry);
				}

				if ($result)
				{
					$this->_addPhrase('fieldgroup_' . $entry['name'], $title);
					$this->_addPhrase('fieldgroup_description_' . $entry['item'] . '_' . $entry['name'], $description);
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['item_fields'])
		{
			$this->_processFields($this->itemData['item_fields']);
		}

		$this->_processQueries('install', self::SQL_STAGE_END, true);
		$this->_processQueries('upgrade', self::SQL_STAGE_END);

		if ($this->itemData['code']['upgrade'])
		{
			$this->_runPhpCode($this->itemData['code']['upgrade']);
		}

		$this->iaCore->startHook('phpExtrasUpgradeBeforeSql', array('extra' => $this->itemData['name'], 'data' => &$this->itemData['info']));

		$iaDb->update($this->itemData['info'], "`name` = '{$this->itemData['name']}' AND `type` = '{$this->itemData['type']}'",
			array('date' => iaDb::FUNCTION_NOW), self::getTable());

		$this->iaCore->startHook('phpExtrasUpgradeAfter', array('extra' => $this->itemData['name']));

		$this->iaCore->iaCache->clearAll();
	}

	public function uninstall($extraName)
	{
		if (empty($extraName))
		{
			$this->error = true;
			$this->setMessage('Extra name is empty.');

			return false;
		}

		$this->iaCore->startHook('phpExtrasUninstallBefore', array('extra' => $extraName));

		if ($this->iaCore->get('default_package') == $extraName)
		{
			$this->iaCore->set('default_package', '', true);
		}
		$this->checkValidity();

		$extraName = iaSanitize::sql($extraName);

		$iaDb = &$this->iaDb;

		$code = $iaDb->row_bind(array('uninstall_code', 'uninstall_sql', 'rollback_data'), '`name` = :name', array('name' => $extraName), self::getTable());
		$pagesList = $iaDb->onefield('`name`', "`extras` = '{$extraName}'", null, null, 'pages');
		$iaDb->delete("`page_name` IN ('" . implode("','", $pagesList) . "')", 'menus');

		if (in_array($this->iaCore->get('home_page'), $pagesList))
		{
			$this->iaCore->set('home_page', 'index', true);
		}

		if ($itemsList = $iaDb->onefield('item', "`package` = '{$extraName}'", null, null, 'items'))
		{
			$stmt = "`item` IN ('" . implode("','", $itemsList) . "')";
			$iaDb->cascadeDelete(array('items_pages', 'favorites', 'views_log'), $stmt);
		}

		if ($pagesList)
		{
			$iaDb->cascadeDelete(array('objects_pages'), "`page_name` IN ('" . implode("','", $pagesList) . "')");

			$iaDb->setTable(iaLanguage::getTable());
			$iaDb->delete("`key` IN ('page_title_" . implode("','page_title_", $pagesList) . "')");
			$iaDb->delete("`key` IN ('page_content_" . implode("','page_content_", $pagesList) . "')");
			$iaDb->delete("`key` IN ('page_metakeyword_" . implode("','page_metakeyword_", $pagesList) . "')");
			$iaDb->delete("`key` IN ('page_metadescr_" . implode("','page_metadescr_", $pagesList) . "')");
			$iaDb->resetTable();
		}

		$tableList = array(
			'admin_actions',
			'admin_pages_groups',
			'admin_pages',
			'acl_privileges',
			iaLanguage::getTable(),
			iaCore::getConfigGroupsTable(),
			iaCore::getConfigTable(),
			iaCore::getCustomConfigTable(),
			'pages',
			'hooks',
			'acl_objects',
			'fields_groups',
			'fields_pages',
			'fields_relations',
			'fields_tree_nodes',
			'cron'
		);
		$iaDb->cascadeDelete($tableList, "`extras` = '{$extraName}'");

		$this->iaCore->factory('field');

		$iaDb->setTable(iaField::getTable());

		$stmt = '`extras` LIKE :extras';
		$this->iaDb->bind($stmt, array('extras' => '%' . $extraName . '%'));
		if ($itemsList)
		{
			$stmt.= " OR `item` IN ('" . implode("','", $itemsList) . "')";
		}

		if ($fields = $iaDb->all(array('id', 'extras'), $stmt))
		{
			foreach ($fields as $field)
			{
				$pluginsList = explode(',', $field['extras']);
				if (count($pluginsList) > 1)
				{
					unset($pluginsList[array_search($extraName, $pluginsList)]);
					$iaDb->update(array('extras' => implode(',', $pluginsList), 'id' => $field['id']));
				}
				else
				{
					$iaDb->delete(iaDb::convertIds($field['id']));
				}
			}
		}

		$iaDb->resetTable();

		$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);
		if ($blockIds = $iaDb->onefield(iaDb::ID_COLUMN_SELECTION, "`extras` = '{$extraName}'", null, null, iaBlock::getTable()))
		{
			foreach ($blockIds as $blockId)
			{
				$iaBlock->delete($blockId, false);
			}
		}

		if ($code['uninstall_sql'])
		{
			$code['uninstall_sql'] = unserialize($code['uninstall_sql']);
			if ($code['uninstall_sql'] && is_array($code['uninstall_sql']))
			{
				foreach ($code['uninstall_sql'] as $sql)
				{
					$iaDb->query(str_replace('{prefix}', $iaDb->prefix, $sql['query']));
				}
			}
		}

		$entry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $extraName), self::getTable());

		$iaDb->delete('`name` = :plugin', self::getTable(), array('plugin' => $extraName));
		$iaDb->delete('`package` = :plugin', 'items', array('plugin' => $extraName));

		empty($entry) || $this->_processCategory($entry, self::ACTION_UNINSTALL);

		if ($code['uninstall_code'])
		{
			$this->_runPhpCode($code['uninstall_code']);
		}

		if ($code['rollback_data'])
		{
			$rollbackData = unserialize($code['rollback_data']);
			if (is_array($rollbackData))
			{
				$existPositions = $this->iaView->positions;
				foreach ($rollbackData as $sectionName => $actions)
				{
					foreach ($actions as $name => $itemData)
					{
						if (isset($itemData['position']))
						{
							if (!in_array($itemData['position'], $existPositions))
							{
								$itemData['position'] = '';
								$itemData['status'] = iaCore::STATUS_INACTIVE;
							}
						}
						$stmt = iaDb::printf("`name` = ':name'", array('name' => $name));
						$this->iaDb->update($itemData, $stmt, null, $sectionName);
					}
				}
			}
		}

		// clear usergroups
		if ($usergroups = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($extraName, 'extras'), 0, null, iaUsers::getUsergroupsTable()))
		{
			$iaUsers = $this->iaCore->factory('users');
			foreach ($usergroups as $usergroup)
			{
				$iaUsers->deleteUsergroup($usergroup['id']);
			}
		}

		$this->iaCore->startHook('phpExtrasUninstallAfter', array('extra' => $extraName));

		$this->iaCore->iaCache->clearAll();

		return true;
	}

	public function install()
	{
		$iaDb = &$this->iaDb;
		$this->iaCore->startHook('phpExtrasInstallBefore', array('extra' => $this->itemData['name']));

		$extrasList = array();
		$array = $iaDb->all(array('id', 'name', 'version'), "`status` = 'active'", null, null, self::getTable());
		foreach ($array as $item)
		{
			$extrasList[$item['name']] = $item;
		}

		// TODO: check for relations and deactivate all needed extras
		if ($this->itemData['requirements'])
		{
			$messages = array();
			foreach ($this->itemData['requirements'] as $requirement)
			{
				if ($requirement['min'] || $requirement['max'])
				{
					$min = $max = false;
					if (isset($extrasList[$requirement['name']]))
					{
						$info = $extrasList[$requirement['name']];
						$min = $requirement['min'] ? version_compare($requirement['min'], $info['version'], '<=') : true;
						$max = $requirement['max'] ? version_compare($requirement['max'], $info['version'], '>=') : true;
					}
					if (!$max || !$min)
					{
						$ver = '';
						if ($requirement['min'])
						{
							$ver .= $requirement['min'];
						}
						if ($requirement['max'])
						{
							if ($requirement['min'])
							{
								$ver .= '-';
							}
							$ver .= $requirement['max'];
						}

						$values = array(
							':extra' => $requirement['type'],
							':name' => $requirement['name'],
							':version' => $ver
						);
						$messages[] = iaLanguage::getf('required_extras_error', $values);
						$this->error = true;
					}
					else
					{
						// TODO: add relations in database to deactivate when parent is uninstalled
					}
				}
			}

			if ($this->error)
			{
				$this->setMessage(implode('<br />', $messages));
				return false;
			}
		}

		$this->uninstall($this->itemData['name']);

		if (false !== stristr('update', $this->itemData['name']))
		{
			$this->isUpdate = true;
		}

		$this->_processQueries('install', self::SQL_STAGE_START);

		if ($this->itemData['groups'])
		{
			$iaDb->setTable('admin_pages_groups');

			$maxOrder = $iaDb->getMaxOrder();
			foreach ($this->itemData['groups'] as $title => $entry)
			{
				$iaDb->insert($entry, array('order' => ++$maxOrder));
				$this->_addPhrase('pages_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['pages']['admin'])
		{
			$this->_processAdminPages($this->itemData['pages']['admin']);
		}

		if ($this->itemData['actions'])
		{
			$iaDb->setTable('admin_actions');
			foreach ($this->itemData['actions'] as $action)
			{
				$action['name'] = strtolower(str_replace(' ', '_', $action['name']));
				if ($action['name'] && !$iaDb->exists('`name` = :name', array('name' => $action['name'])))
				{
					$action['order'] = (empty($action['order']) || !is_numeric($action['order']))
						? $iaDb->getMaxOrder() + 1
						: $action['order'];

					$iaDb->insert($action);
				}
			}
			$iaDb->resetTable();
		}

		if ($this->itemData['phrases'])
		{
			$this->_processPhrases($this->itemData['phrases']);
		}

		if ($this->itemData['config_groups'])
		{
			$iaDb->setTable(iaCore::getConfigGroupsTable());

			$maxOrder = $iaDb->getMaxOrder();
			foreach ($this->itemData['config_groups'] as $title => $entry)
			{
				$iaDb->insert($entry, array('order' => ++$maxOrder));
				$this->_addPhrase('config_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['objects'])
		{
			$iaDb->setTable('acl_objects');
			foreach ($this->itemData['objects'] as $obj)
			{
				if ($obj['title'])
				{
					$key = ($obj['object'] == $obj['pre_object'] ? '' : $obj['pre_object'] . '-') . $obj['object'] . '--' . $obj['action'];
					iaLanguage::addPhrase($key, $obj['title'], null, $this->itemData['name'], iaLanguage::CATEGORY_COMMON, false);
					unset($obj['title']);
				}
				$iaDb->insert($obj);
			}
			$iaDb->resetTable();
		}

		if ($this->itemData['permissions'])
		{
			$iaDb->setTable('acl_privileges');
			foreach ($this->itemData['permissions'] as $permission)
			{
				$iaDb->insert($permission);
			}
			$iaDb->resetTable();
		}

		if ($this->itemData['config'])
		{
			$this->_processConfig($this->itemData['config']);
		}

		if ($this->itemData['pages']['custom'] && $this->itemData['type'] == self::TYPE_PACKAGE)
		{
			$iaDb->setTable('items_pages');
			foreach ($this->itemData['pages']['custom'] as $page)
			{
				$iaDb->insert(array('page_name' => $page['name'], 'item' => $page['item']));
			}
			$iaDb->resetTable();
		}

		$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

		$extraPages = array();
		if ($this->itemData['pages']['front'])
		{
			$pageGroups = $iaDb->keyvalue(array('name', 'id'), null, 'admin_pages_groups');

			$iaDb->setTable('pages');

			$maxOrder = $iaDb->getMaxOrder();
			$existPages = $iaDb->keyvalue(array('name', 'id'));

			foreach ($this->itemData['pages']['front'] as $title => $page)
			{
				if (!isset($existPages[$page['name']]))
				{
					if (self::TYPE_PACKAGE == $this->itemData['type'] && $page['fields_item'])
					{
						$iaDb->insert(array('page_name' => $page['name'], 'item' => $page['fields_item']), null, 'items_pages');
					}

					$blocks = empty($page['blocks']) ? false : $page['blocks'];
					$menus = empty($page['menus']) ? array() : explode(',', $page['menus']);
					$contents = empty($page['contents']) ? false : $page['contents'];

					unset($page['blocks'], $page['menus'], $page['contents']);

					$page['group'] = $pageGroups[$page['group']];

					$pageId = $iaDb->insert($page, array('order' => ++$maxOrder, 'last_updated' => iaDb::FUNCTION_NOW));

					empty($title) || $this->_addPhrase('page_title_' . $page['name'], $title, iaLanguage::CATEGORY_PAGE);

					if ($blocks && ($ids = $this->iaDb->onefield(iaDb::ID_COLUMN_SELECTION,
							"`name` IN ('" . implode("','", $blocks) . "')", null, null, iaBlock::getTable())))
					{
						foreach ($ids as $blockId)
						{
							$iaBlock->setVisibility($blockId, true, array($page['name']), false);
						}
					}

					if (!is_int($page['group']))
					{
						$page['group'] = $this->_lookupGroupId($page['group']);
					}

					if ($menus)
					{
						$iaDb->setTable(iaBlock::getTable());
						$added = array();
						$items = array();
						$menusData = $iaDb->keyvalue(array('id', 'name'), "`type` = 'menu'");
						$db = false;

						foreach ($menusData as $id => $name)
						{
							if (in_array($name, $menus))
							{
								$added[] = $name;
								$items[] = array(
									'parent_id' => 0,
									'menu_id' => $id,
									'el_id' => $pageId . '_' . iaUtil::generateToken(4),
									'level' => 0,
									'page_name' => $page['name']
								);
								$db = true;

								$this->iaCore->iaCache->remove('menu_' . $id);
							}
						}

						if ($db)
						{
							$iaDb->insert($items, null, iaBlock::getMenusTable());
						}

						foreach ($menus as $val)
						{
							if (!in_array($val, $added))
							{
								$menuItem = array(
									'type' => iaBlock::TYPE_MENU,
									'status' => iaCore::STATUS_ACTIVE,
									'position' => 'left',
									'collapsible' => true,
									'title' => $this->itemData['info']['title'],
									'extras' => $this->itemData['name'],
									'name' => $this->itemData['name'],
									'sticky' => true,
									'removable' => false
								);

								$menuItem['id'] = $iaBlock->insert($menuItem);

								$entry = array(
									'parent_id' => 0,
									'menu_id' => $menuItem['id'],
									'el_id' => $pageId . '_' . iaUtil::generateToken(5),
									'level' => 0,
									'page_name' => $page['name']
								);

								$iaDb->insert($entry, null, iaBlock::getMenusTable());
							}
						}

						$iaDb->resetTable();
					}

					empty($contents) || $this->_addPhrase('page_content_' . $page['name'], $contents, iaLanguage::CATEGORY_PAGE);

					$extraPages[] = $page['name'];
				}
			}

			$iaDb->resetTable();
		}

		$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

		if ($this->itemData['blocks'])
		{
			foreach ($this->itemData['blocks'] as $block)
			{
				$iaBlock->insert($block);
			}
		}

		if ($this->itemData['hooks'])
		{
			$iaDb->setTable('hooks');

			$maxOrder = $iaDb->getMaxOrder();

			foreach ($this->itemData['hooks'] as $hook)
			{
				$array = explode(',', $hook['name']);
				foreach ($array as $hookName)
				{
					if (trim($hookName))
					{
						$hook['name'] = $hookName;
						if (isset($hook['code']) && $hook['code'])
						{
							$hook['code'] = str_replace('{extras}', $this->itemData['name'], $hook['code']);
						}
						$rawValues = array();
						if (!isset($hook['order']))
						{
							$rawValues['order'] = ++$maxOrder;
						}

						$iaDb->insert($hook, $rawValues);
					}
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['usergroups'])
		{
			$this->iaCore->factory('acl');

			$iaDb->setTable(iaUsers::getUsergroupsTable());
			foreach ($this->itemData['usergroups'] as $item)
			{
				if (!$iaDb->exists('`name` = :name', array('name' => $item['name'])))
				{
					$configs = $item['configs'];
					$permissions = $item['permissions'];

					$groupId = $iaDb->insert(array(
						'extras' => $item['extras'],
						'name' => $item['name'],
						'system' => true,
						'assignable' => $item['assignable'],
						'visible' => $item['visible']
					));

					// update language records
					$this->_addPhrase('usergroup_' . $item['name'], $item['title']);

					$iaDb->setTable(iaCore::getCustomConfigTable());
					$iaDb->delete('`type` = :type AND `type_id` = :id', null, array('type' => iaAcl::GROUP, 'id' => $groupId));
					foreach ($configs as $config)
					{
						$data = array(
							'name' => $config['name'],
							'value' => $config['value'],
							'type' => iaAcl::GROUP,
							'type_id' => $groupId,
							'extras' => $this->itemData['name']
						);
						$iaDb->insert($data);
					}
					$iaDb->resetTable();

					$iaDb->setTable('acl_privileges');
					$iaDb->delete('`type` = :type AND `type_id` = :id', null, array('type' => iaAcl::GROUP, 'id' => $groupId));
					foreach ($permissions as $permission)
					{
						$data = array(
							'object' => $permission['object'],
							'object_id' => $permission['object_id'],
							'action' => $permission['action'],
							'access' => $permission['access'],
							'type' => iaAcl::GROUP,
							'type_id' => $groupId,
							'extras' => $permission['extras']
						);

						$iaDb->insert($data);
					}
					$iaDb->resetTable();
				}
			}
			$iaDb->resetTable();
		}

		$extraEntry = array_merge($this->itemData['info'], array(
			'name' => $this->itemData['name'],
			'type' => $this->itemData['type']
		));
		unset($extraEntry['date']);

		if ($this->itemData['sql']['uninstall'])
		{
			$extraEntry['uninstall_sql'] = serialize($this->itemData['sql']['uninstall']);
		}

		if ($this->itemData['code']['uninstall'])
		{
			$extraEntry['uninstall_code'] = $this->itemData['code']['uninstall'];
		}

		$this->_processQueries('install', self::SQL_STAGE_MIDDLE);

		if (self::TYPE_PACKAGE == $this->itemData['type'])
		{
			$extraEntry['url'] = $this->_url;
		}

		if ($this->itemData['items'])
		{
			$extraEntry['items'] = serialize($this->itemData['items']);
			$iaDb->setTable('items');
			foreach ($this->itemData['items'] as $item)
			{
				$iaDb->insert(array_merge($item, array('package' => $this->itemData['name'])));
			}
			$iaDb->resetTable();
		}

		$this->iaCore->factory('field');

		if ($this->itemData['item_field_groups'])
		{
			$iaDb->setTable(iaField::getTableGroups());

			$maxOrder = $iaDb->getMaxOrder();
			foreach ($this->itemData['item_field_groups'] as $entry)
			{
				$entry['order'] || $entry['order'] = ++$maxOrder;

				$title = $entry['title'];
				$description = $entry['description'];

				unset($entry['title'], $entry['description']);

				if ($iaDb->insert($entry))
				{
					$this->_addPhrase('fieldgroup_' . $entry['name'], $title);
					$this->_addPhrase('fieldgroup_description_' . $entry['item'] . '_' . $entry['name'], $description);
				}
			}

			$iaDb->resetTable();
		}

		if ($this->itemData['item_fields'])
		{
			$this->_processFields($this->itemData['item_fields']);
		}

		if ($this->itemData['cron_jobs'])
		{
			$this->iaCore->factory('cron');

			foreach ($this->itemData['cron_jobs'] as $job)
			{
				$job['extras'] = $this->itemData['name'];
				$iaDb->insert($job, null, iaCron::getTable());
			}
		}

		$rollbackData = array();
		if ($this->itemData['changeset'])
		{
			$tablesMapping = array(
				'block' => 'blocks',
				'field' => 'fields',
				'menu' => 'blocks'
			);

			foreach ($this->itemData['changeset'] as $entry)
			{
				if (!isset($tablesMapping[$entry['type']]))
				{
					continue;
				}

				switch ($entry['type'])
				{
					case 'field':
						list($fieldName, $itemName) = explode('-', $entry['name']);
						if (empty($fieldName) || empty($itemName)) // incorrect identity specified by template
						{
							continue;
						}
						$stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", array('name' => $fieldName, 'item' => $itemName));
						break;
					default:
						$stmt = iaDb::printf("`name` = ':name'", $entry);
				}

				$tableName = $tablesMapping[$entry['type']];
				$name = $entry['name'];
				$pages = isset($entry['pages']) ? explode(',', $entry['pages']) : array();

				unset($entry['type'], $entry['name'], $entry['pages']);

				$entryData = $iaDb->row('`id`, `' . implode('`,`', array_keys($entry)) . '`', $stmt, $tableName);

				if ($iaDb->update($entry, $stmt, null, $tableName))
				{
					if ('field' != $entry['type'] && isset($entry['sticky']))
					{
						$iaBlock->setVisibility($entryData['id'], $entry['sticky'], $pages);
					}
					unset($entryData['id']);

					$rollbackData[$tableName][$name] = $entryData;
				}
			}
		}
		$extraEntry['rollback_data'] = empty($rollbackData) ? '' : serialize($rollbackData);

		if (self::TYPE_PLUGIN == $this->itemData['type'])
		{
			$extraEntry['removable'] = !in_array($this->itemData['name'], $this->_builtinPlugins);
		}

		if (!$this->isUpdate)
		{
			$this->iaCore->startHook('phpExtrasInstallBeforeSql', array('extra' => $this->itemData['name'], 'data' => &$this->itemData['info']));
			$iaDb->insert($extraEntry, array('date' => iaDb::FUNCTION_NOW), self::getTable());
		}

		$this->_processCategory($extraEntry);

		$this->_processQueries('install', self::SQL_STAGE_END);

		if ($this->itemData['code']['install'])
		{
			$this->_runPhpCode($this->itemData['code']['install']);
		}

		$this->iaCore->startHook('phpExtrasInstallAfter', array('extra' => $this->itemData['name']));

		$this->iaCore->factory('cache')->clearAll();

		return true;
	}

	public function setXml($xmlContent)
	{
		$this->_xmlContent = $xmlContent;
		$this->_parsed = false;
	}

	public function getFromPath($filePath)
	{
		$xmlContent = file_get_contents($filePath);
		if (false !== $xmlContent)
		{
			$this->setXml($xmlContent);
			return true;
		}

		trigger_error('Could not open the installation XML file: ' . $filePath, E_USER_ERROR);
		return false;
	}

	public function _parserStart($parser, $name, $attributes)
	{
		$this->_inTag = $name;
		$this->_attributes = $attributes;
		$this->_currentPath[] = $name;

		if (in_array($this->_inTag, array(self::TYPE_PACKAGE, self::TYPE_PLUGIN)) && isset($attributes['name']))
		{
			$this->itemData['name'] = $attributes['name'];
			$this->itemData['type'] = ($name == self::TYPE_PLUGIN) ? self::TYPE_PLUGIN : self::TYPE_PACKAGE;
		}

		if ('usergroup' == $name)
		{
			$this->itemData['usergroups'][] = array(
				'extras' => $this->itemData['name'],
				'name' => $this->itemData['name'] . '_' . ($this->_attr('name', iaUtil::generateToken())),
				'title' => $attributes['title'],
				'assignable' => $this->_attr('assignable', false),
				'visible' => $this->_attr('visible', true),
				'configs' => array(),
				'permissions' => array()
			);
		}
	}

	public function _parserQuickData($parser, $text)
	{
		if (in_array($this->_inTag, array('title', 'summary', 'author', 'contributor', 'version', 'date')))
		{
			$this->itemData['info'][$this->_inTag] = trim($text);
		}
		if ('compatibility' == $this->_inTag)
		{
			$this->itemData[$this->_inTag] = $text;
		}
		if ('dependency' == $this->_inTag)
		{
			$this->itemData['dependencies'][$text] = array(
				'type' => $this->_attr('type'),
				'exist' => $this->_attr('exist', true)
			);
		}
	}

	public function _parserData($parser, $text)
	{
		$text = trim($text);

		switch ($this->_inTag)
		{
			case 'title':
			case 'summary':
			case 'author':
			case 'contributor':
			case 'version':
			case 'date':
			case 'category':
				$this->itemData['info'][$this->_inTag] = $text;
				break;

			case 'compatibility':
			case 'url':
				$this->itemData[$this->_inTag] = $text;
				break;

			case 'item':
				if ($this->_checkPath('packageitems'))
				{
					$this->itemData['items'][$text] = array(
						'item' => $text,
						'payable' => (int)$this->_attr('payable', true),
						'pages' => $this->_attr('pages'),
						'table_name' => $this->_attr('table_name'),
						'class_name' => $this->_attr('class_name')
					);

					if (isset($this->_attributes['pages']) && $this->_attributes['pages'])
					{
						foreach (explode(',', $this->_attributes['pages']) as $val)
						{
							$this->itemData['pages']['custom'][] = array('name' => $val, 'item' => $text);
						}
					}
				}
				break;

			case 'screenshot':
				if ($this->_checkPath('screenshots'))
				{
					$this->itemData['screenshots'][] = array(
						'name' => $this->_attr('name'),
						'title' => $text,
						'type' => $this->_attr('type', 'lightbox')
					);
				}
				break;

			case 'dependency':
				$this->itemData['dependencies'][$text] = array(
					'type' => $this->_attr('type'),
					'exist' => $this->_attr('exist', true)
				);
				break;

			case 'extension':
				if ($this->_checkPath('requires'))
				{
					$this->itemData['requirements'][] = array(
						'name' => $text,
						'type' => $this->_attr('type', 'package', array(self::TYPE_PACKAGE, self::TYPE_PLUGIN)),
						'min' => $this->_attr(array('min_version', 'min'), false),
						'max' => $this->_attr(array('max_version', 'max'), false)
					);
				}
				break;

			case 'action':
				if ($this->_checkPath('actions'))
				{
					$this->itemData['actions'][] = array(
						'attributes' => $this->_attr('attributes'),
						'extras' => $this->itemData['name'],
						'icon' => $this->_attr('icon'),
						'name' => $this->_attr('name'),
						'pages' => $this->_attr('pages'),
						'text' => $text,
						'type' => $this->_attr('type', 'regular'),
						'url' => $this->_attr('url')
					);
				}
				break;

			case 'cron':
				$cron = $this->_attributes;
				$cron['data'] = $text;
				$this->itemData['cron_jobs'][] = $cron;
				break;

			case 'page':
				if ($this->_checkPath('adminpages'))
				{
					$this->itemData['pages']['admin'][$text] = array(
						'name' => $this->_attr('name'),
						'filename' => $this->_attr('filename'),
						'alias' => $this->_attr('url'),
						'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)),
						'group' => $this->_attr('group', 'extensions'),
						'order' => $this->_attr('order', null),
						'menus' => $this->_attr('menus'),
						'action' => $this->_attr('action', iaCore::ACTION_READ),
						'parent' => $this->_attr('parent'),
						'extras' => $this->itemData['name']
					);
				}
				elseif ($this->_checkPath('pages'))
				{
					$url = $this->_attr('url');
					$url = $this->itemData['url'] && $url ? str_replace('|PACKAGE|', ltrim($this->_url, IA_URL_DELIMITER), $url) : $url;
					$url = empty($url) ? $this->itemData['name'] . IA_URL_DELIMITER : $url;

					$blocks = trim($this->_attr('blocks'));
					$blocks = empty($blocks) ? null : explode(',', $blocks);

					// TODO: add pages param to display some existing blocks on new page
					$this->itemData['pages']['front'][$text] = array(
						'name' => $this->_attr('name'),
						'filename' => $this->_attr('filename'),
						'custom_tpl' => (bool)$this->_attr('template', 0),
						'template_filename' => $this->_attr('template', ''),
						'menus' => $this->_attr('menus'),
						'status' => $this->_attr('status', iaCore::STATUS_ACTIVE),
						'alias' => $url,
						'custom_url' => $this->_attr('custom_url'),
						'service' => $this->_attr('service', false),
						'blocks' => $blocks,
						'nofollow' => $this->_attr('nofollow', false),
						'new_window' => $this->_attr('new_window', false),
						'readonly' => $this->_attr('readonly', true),
						'extras' => $this->itemData['name'],
						'group' => $this->_attr('group', ($this->itemData['type'] == self::TYPE_PLUGIN) ? 'extensions' : $this->itemData['type']),
						'action' => $this->_attr('action', iaCore::ACTION_READ),
						'parent' => $this->_attr('parent'),
						'suburl' => $this->_attr('suburl'),
						'fields_item' => $this->_attr('fields_item', '')
					);
				}
				break;

			case 'configgroup':
				$this->itemData['config_groups'][$text] = array(
					'name' => $this->_attr('name'),
					'extras' => $this->itemData['name']
				);
				break;

			case 'config':
				if ($this->_checkPath('usergroup') && $this->itemData['usergroups'])
				{
					$this->itemData['usergroups'][count($this->itemData['usergroups']) - 1]['configs'][] = array(
						'name' => $this->_attr('name'),
						'value' => $text
					);
				}
				else
				{
					$this->itemData['config'][] = array(
						'config_group' => $this->_attr(array('group', 'configgroup')),
						'name' => $this->_attr('name'),
						'value' => $text,
						'multiple_values' => $this->_attr(array('values', 'multiplevalues', 'multiple_values')),
						'type' => $this->_attr('type'),
						'description' => $this->_attr('description'),
						'wysiwyg' => $this->_attr('wysiwyg', false),
						'code_editor' => $this->_attr('code_editor', false),
						'private' => $this->_attr('private', true),
						'custom' => $this->_attr('custom', true),
						'extras' => $this->itemData['name'],
						'show' => $this->_attr('show')
					);
				}
				break;

			case 'permission':
				$entry = array(
					'access' => $this->_attr('access', 0, array(0, 1)),
					'action' => $this->_attr('action', iaCore::ACTION_READ),
					'object' => $this->_attr('object', iaAcl::OBJECT_PAGE),
					'object_id' => $text,
					'extras' => $this->itemData['name']
				);

				if ($this->_checkPath('permissions'))
				{
					$this->itemData['permissions'][] = $entry + array(
						'type' => $this->_attr('type', iaAcl::GROUP, array(iaAcl::USER, iaAcl::GROUP, iaAcl::PLAN)),
						'type_id' => $this->_attr('type_id')
					);
				}
				elseif ($this->_checkPath('usergroup') && $this->itemData['usergroups'])
				{
					$this->itemData['usergroups'][count($this->itemData['usergroups']) - 1]['permissions'][] = $entry;
				}

				break;

			case 'object':
				$this->itemData['objects'][] = array(
					'object' => $this->_attr('id'),
					'pre_object' => $this->_attr('meta_object', iaAcl::OBJECT_PAGE),
					'action' => $this->_attr('action', iaCore::ACTION_READ),
					'access' => $this->_attr('access', '0', array(0, 1)),
					'extras' => $this->itemData['name'],
					'title' => $text
				);
				break;

			case 'group':
				switch (true)
				{
					case $this->_checkPath('fields_groups'):
						$this->itemData['item_field_groups'][] = array(
							'extras' => $this->itemData['name'],
							'item' => $this->_attr('item'),
							'name' => $this->_attr('name'),
							'collapsible' => $this->_attr('collapsible', false),
							'collapsed' => $this->_attr('collapsed', false),
							'tabview' => $this->_attr('tabview', false),
							'tabcontainer' => $this->_attr('tabcontainer'),
							'order' => $this->_attr('order', 0),
							'title' => $this->_attr('title'),
							'description' => $text
						);
						break;
					case $this->_checkPath('groups'):
						$this->itemData['groups'][$text] = array(
							'name' => $this->_attr('name'),
							'extras' => $this->itemData['name']
						);
				}
				break;

			case 'field':
				if ($this->_checkPath('fields'))
				{
					$values = '';

					if (isset($this->_attributes['values']))
					{
						$values = $this->_attributes['values'];

						if ('tree' != $this->_attr('type'))
						{
							$array = explode((false !== strpos($values, '::')) ? '::' : ',', $values);
							$values = array();

							foreach ($array as $k => $v)
							{
								$a = explode('||', $v);
								isset($a[1]) ? ($values[$a[0]] = $a[1]) : ($values[$k + 1] = $v);
							}
						}
					}

					// get item table & class names
					$itemTable = empty($this->itemData['items'][$this->_attr('item')]['table_name'])
						? $this->_attr('item')
						: $this->itemData['items'][$this->_attr('item')]['table_name'];

					$itemClass = empty($this->itemData['items'][$this->_attr('item')]['class_name'])
						? $this->_attr('item')
						: $this->itemData['items'][$this->_attr('item')]['class_name'];

					$this->itemData['item_fields'][] = array(
						'extras' => $this->itemData['name'],
						'table_name' => $itemTable,
						'class_name' => $itemClass,
						'title' => $text,
						'values' => $values,
						'order' => $this->_attr('order', 0),
						'item' => $this->_attr('item'),
						'item_pages' => explode(',', $this->_attr('page')),
						'group' => $this->_attr('group', $this->itemData['name']), // will be changed to the inserted ID by the further code
						'name' => $this->_attr('name'),
						'type' => $this->_attr('type'),
						'use_editor' => $this->_attr('editor', false),
						'timepicker' => $this->_attr('timepicker', false),
						'length' => (int)$this->_attr('length'),
						'default' => $this->_attr('default'),
						'editable' => $this->_attr('editable', true),
						'required' => $this->_attr('required', false),
						'required_checks' => $this->_attr('required_checks'),
						'extra_actions' => $this->_attr('actions'),
						'relation' => $this->_attr('relation', 'regular', array('regular', 'dependent', 'parent')),
						'parent' => $this->_attr('parent', ''),
						'empty_field' => $this->_attr('empty_field'),
						'link_to' => $this->_attr('link_to', false),
						'adminonly' => $this->_attr('adminonly', false),
						'allow_null' => $this->_attr('allow_null', false),
						'searchable' => $this->_attr('searchable', false),
						'sort_order' => $this->_attr(array('sort', 'sort_order'), 'asc', array('asc', 'desc')),
						'show_as' => $this->_attr('show_as', 'combo'),
						'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)),
						'image_width' => $this->_attr('width', 0),
						'image_height' => $this->_attr('height', 0),
						'thumb_width' => $this->_attr(array('thumb_width', 'width'), 0),
						'thumb_height' => $this->_attr(array('thumb_height', 'height'), 0),
						'resize_mode' => $this->_attr(array('resize', 'resize_mode', 'mode'), 'crop', array('fit', 'crop')),
						'file_prefix' => $this->_attr(array('prefix', 'file_prefix')),
						'file_types' => $this->_attr(array('types', 'file_types')),
						'numberRangeForSearch' => isset($this->_attributes['numberRangeForSearch']) ? explode(',', $this->_attributes['numberRangeForSearch']) : '',
						'folder_name' => $this->_attr('folder_name', '')
					);
				}
				elseif ($this->_checkPath('changeset'))
				{
					$this->itemData['changeset'][] = array_merge($this->_attributes, array('type' => $this->_inTag, 'name' => $text));
				}

				break;

			case 'phrase':
			case 'tooltip':
				if ($this->_checkPath('phrases') || $this->_checkPath('tooltips'))
				{
					if ($key = trim($this->_attr('key')))
					{
						$category = ('phrase' == $this->_inTag) ? $this->_attr('category', iaLanguage::CATEGORY_COMMON) : $this->_inTag;
						$phrases = &$this->itemData['phrases'];

						isset($phrases[$key]) || $phrases[$key] = array('values' => array(), 'category' => $category);
						$phrases[$key]['values'][$this->_attr('code', $this->iaView->language)] = $text;
					}
				}

				break;

			case 'hook':
				$type = $this->_attr('type', 'php', array('php', 'html', 'smarty', 'plain'));

				if ($filename = $this->_attr('filename'))
				{
					switch ($type)
					{
						case 'php':
							$filename = $this->itemData['type'] . 's' . IA_DS . $this->itemData['name']
								. IA_DS . 'includes' . IA_DS . $filename . iaSystem::EXECUTABLE_FILE_EXT;

							// compatibility layer
							// todo: remove in v5
							if (false !== strpos($this->_attr('filename'), '/'))
							{
								$filename = $this->_attr('filename');
							}
							//

							break;

						case 'smarty':
							//$filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);

							// compatibility layer for v4.0 plugins
							// todo: remove in v5
							if (false !== stripos($filename, '.tpl'))
							{
								if ('payments' != @$this->itemData['info']['category']
									&& false !== stripos($filename, '/templates/front/'))
								{
									$filename = str_replace('.tpl', '', basename($filename));
									$filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);
								}
							}
							else
							{
								$filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);
							}
						//
					}

				}

				$this->itemData['hooks'][] = array(
					'name' => $this->_attr('name'),
					'type' => $type,
					'page_type' => $this->_attr('page_type', 'both', array('both', iaCore::ADMIN, iaCore::FRONT)),
					'filename' => $filename,
					'pages' => $this->_attr('pages'),
					'extras' => $this->itemData['name'],
					'code' => $text,
					'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)),
					'order' => $this->_attr('order', 0)
				);
				break;

			case 'block':
				if ($this->_checkPath('blocks'))
				{
					$filename = $this->_attr('filename');
					if ($filename && 'smarty' == $this->_attr('type'))
					{
						$filename = sprintf(self::BLOCK_FILENAME_PATTERN, $this->itemData['name'], $filename);
					}

					$this->itemData['blocks'][] = array(
						'name' => $this->_attr('name', 'block_' . mt_rand(1000, 9999)),
						'title' => $this->_attr('title'),
						'contents' => $text,
						'position' => $this->_attr('position'),
						'type' => $this->_attr('type'),
						'order' => $this->_attr('order', false),
						'extras' => $this->itemData['name'],
						'status' => $this->_attr('status', iaCore::STATUS_ACTIVE, array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)),
						'header' => $this->_attr('header', true),
						'collapsible' => $this->_attr('collapsible', false),
						'sticky' => $this->_attr('sticky', true),
						'multilingual' => $this->_attr('multilanguage', true),
						'pages' => $this->_attr('pages'),
						'rss' => $this->_attr('rss'),
						'filename' => $filename,
						'classname' => $this->_attr('classname')
					);
				}
				elseif ($this->_checkPath('changeset'))
				{
					$this->itemData['changeset'][] = array_merge($this->_attributes, array('type' => $this->_inTag, 'name' => $text));
				}
				break;

			case 'menu':
				if ($this->_checkPath('changeset'))
				{
					$this->itemData['changeset'][] = array_merge($this->_attributes, array('type' => $this->_inTag, 'name' => $text));
				}
				break;

			case 'code':
				if ($this->_checkPath('install'))
				{
					$this->itemData['code']['install'] = $text;
				}
				elseif ($this->_checkPath('uninstall'))
				{
					$this->itemData['code']['uninstall'] = $text;
				}
				elseif ($this->_checkPath('upgrade'))
				{
					$this->itemData['code']['upgrade'] = $text;
				}
				break;

			case 'sql':
				$entry = array(
					'query' => $text,
					'external' => isset($this->_attributes['external'])
				);

				$version = $this->_attr('version', self::VERSION_EMPTY);
				$stage = $this->_attr('stage', self::SQL_STAGE_MIDDLE, array(self::SQL_STAGE_START, self::SQL_STAGE_MIDDLE, self::SQL_STAGE_END));

				if ($this->_checkPath('install'))
				{
					$this->itemData['sql']['install'][$stage][$version][] = $entry;
				}
				elseif ($this->_checkPath('upgrade'))
				{
					$this->itemData['sql']['upgrade'][$stage][$version][] = $entry;
				}
				elseif ($this->_checkPath('uninstall'))
				{
					$this->itemData['sql']['uninstall'][] = $entry;
				}
		}
	}

	public function getNotes()
	{
		return $this->_notes;
	}

	public function getUrl()
	{
		return $this->_url;
	}

	public function getMenuGroups()
	{
		return array_unique($this->_menuGroups);
	}

	public function _parserEnd($parser, $name)
	{
		array_pop($this->_currentPath);
	}

	public function _parserComment($parser)
	{
	}


	private function _processCategory(array $entryData, $action = self::ACTION_INSTALL)
	{
		switch ($entryData['category'])
		{
			case 'payments':
				$iaTransaction = $this->iaCore->factory('transaction');

				if (self::ACTION_INSTALL == $action)
				{
					$entry = array(
						'name' => $entryData['name'],
						'title' => $entryData['title']
					);

					$this->iaDb->insert($entry, null, $iaTransaction->getTableGateways());
				}
				elseif (self::ACTION_UNINSTALL == $action)
				{
					$this->iaDb->delete('`name` = :name', $iaTransaction->getTableGateways(), $entryData);
				}

				break;

			case 'lightbox':
			case 'captchas':
				$configName = ('lightbox' == $entryData['category']) ? 'lightbox_name' : 'captcha_name';

				$stmt = iaDb::convertIds($configName, 'name');

				$this->iaDb->setTable(iaCore::getConfigTable());

				if (self::ACTION_INSTALL == $action)
				{
					if ($currentValues = $this->iaDb->one('`multiple_values`', $stmt))
					{
						$values = explode(',', $currentValues);
					}

					$values[] = $entryData['name'];

					$this->iaDb->update(array('multiple_values' => implode(',', $values)), $stmt);

					if (1 == count($values))
					{
						$this->iaCore->set($configName, $entryData['name'], true);
					}
				}
				elseif (self::ACTION_UNINSTALL == $action)
				{
					// get possible values
					if ($values = explode(',', $this->iaDb->one('`multiple_values`', $stmt)))
					{
						$installed = array_diff($values, array($entryData['name']));
						$this->iaDb->update(array('multiple_values' => implode(',', $installed)), $stmt);

						if ($this->iaCore->get($configName) == $entryData['name'])
						{
							$value = empty($installed) ? '' : array_shift($installed);

							if (in_array($entryData['name'], $this->_builtinPlugins))
							{
								$value = $entryData['name'];
							}

							$this->iaCore->set($configName, $value, true);
						}
					}
				}

				$this->iaDb->resetTable();
		}
	}

	protected function _processQueries($type, $stage, $ignoreNonVersionedQueries = false)
	{
		if (!isset($this->itemData['sql'][$type][$stage]))
		{
			return;
		}

		$iaDb = &$this->iaDb;
		$iaDbControl = $this->iaCore->factory('dbcontrol', iaCore::ADMIN);

		require_once IA_INCLUDES . 'utils' . IA_DS . 'pclzip.lib.php';

		$mysqlOptions = 'ENGINE=MyISAM DEFAULT CHARSET=utf8';

		$path = isset($this->_extrasTypePaths[$this->itemData['type']]) ? $this->_extrasTypePaths[$this->itemData['type']] : IA_HOME;
		$extrasVersion = $this->itemData['info']['version'];

		foreach ($this->itemData['sql'][$type][$stage] as $version => $entries)
		{
			if (($ignoreNonVersionedQueries && self::VERSION_EMPTY == $version))
			{
				continue;
			}

			if (self::VERSION_EMPTY != $version && version_compare($version, $extrasVersion) > 0)
			{
				continue;
			}

			foreach ($entries as $entry)
			{
				if ($entry['external'])
				{
					$filePath = str_replace('{DS}', IA_DS, $entry['query']);
					$fileFullPath = $path . $this->itemData['name'] . IA_DS . $filePath;

					if (iaUtil::isZip($fileFullPath))
					{
						$archive = new PclZip($fileFullPath);

						$files = $archive->extract(PCLZIP_OPT_PATH, IA_TMP);

						if (0 == $files)
						{
							continue;
						}

						foreach ($files as $file)
						{
							$iaDbControl->splitSQL($file['filename']);
							iaUtil::deleteFile($file['filename']);
						}
					}
					else
					{
						$iaDbControl->splitSQL($fileFullPath);
					}
				}
				else
				{
					if ($entry['query'])
					{
						$query = str_replace(array('{prefix}', '{mysql_version}'), array($iaDb->prefix, $mysqlOptions), $entry['query']);
						$iaDb->query($query);
					}
				}
			}
		}
	}

	protected function _processPhrases(array $phrases)
	{
		if (!$phrases)
		{
			return;
		}

		$defaultLangCode = $this->iaView->language;

		foreach ($phrases as $key => $phrase)
		{
			foreach ($this->iaCore->languages as $isoCode => $language)
			{
				$value = isset($phrase['values'][$isoCode])
					? $phrase['values'][$isoCode]
					: $phrase['values'][$defaultLangCode];

				iaLanguage::addPhrase($key, $value, $isoCode, $this->itemData['name'], $phrase['category'], false);
			}
		}
	}

	protected function _processFields(array $fields)
	{
		if (!$fields)
		{
			return;
		}

		$this->iaCore->factory('field');

		$fieldGroups = $this->iaDb->keyvalue('CONCAT(`item`, `name`) `key`, `id`', null, iaField::getTableGroups());

		$this->iaDb->setTable(iaField::getTable());

		foreach ($fields as $entry)
		{
			$stmt = '`item` = :item AND `name` = :name';
			$this->iaDb->bind($stmt, array('item' => $entry['item'], 'name' => $entry['name']));

			if ($row = $this->iaDb->row(array('id', 'extras'), $stmt))
			{
				if (false === stripos($row['extras'], $this->itemData['name']))
				{
					$value = ($row['extras'] ? ',' : '') . $this->itemData['name'];
					$value = sprintf("CONCAT(`extras`, '%s')", $value);

					$this->iaDb->update(null, iaDb::convertIds($row['id']), array('extras' => $value));
				}

				continue;
			}

			$entry['order'] || $entry['order'] = $this->iaDb->getMaxOrder(null, array('item', $entry['item'])) + 1;
			$entry['fieldgroup_id'] = isset($fieldGroups[$entry['item'] . $entry['group']])
				? $fieldGroups[$entry['item'] . $entry['group']]
				: 0;

			$this->_addPhrase('field_' . $entry['name'], $entry['title']);

			unset($entry['group'], $entry['title']);

			if (is_array($entry['numberRangeForSearch']))
			{
				foreach ($entry['numberRangeForSearch'] as $num)
				{
					$this->_addPhrase('field_' . $entry['name'] . '_range_' . $num, $num, iaLanguage::CATEGORY_FRONTEND);
				}
			}
			unset($entry['numberRangeForSearch']);

			if (iaField::RELATION_DEPENDENT == $entry['relation'])
			{
				$this->iaDb->setTable(iaField::getTableRelations());

				foreach (explode(';', $entry['parent']) as $parent)
				{
					$list = explode(':', $parent);
					if (2 == count($list))
					{
						list($fieldName, $fieldValues) = $list;

						foreach (explode(',', $fieldValues) as $fieldValue)
						{
							$entryData = array(
								'field' => $fieldName,
								'element' => $fieldValue,
								'child' => $entry['name'],
								'item' => $entry['item'],
								'extras' => $this->itemData['name']
							);

							$this->iaDb->insert($entryData);
						}
					}
				}

				$this->iaDb->resetTable();
			}
			unset($entry['parent']);

			if (is_array($entry['values']))
			{
				foreach ($entry['values'] as $key => $value)
				{
					$key = sprintf('field_%s_%s', $entry['name'], $key);
					$this->_addPhrase($key, $value);
				}

				if ($entry['default'])
				{
					// TODO: multiple default values for checkboxes should be implemented
					if (!in_array($entry['default'], array_keys($entry['values'])))
					{
						$entry['default'] = array_search($entry['default'], $entry['values']);
					}
				}

				$entry['values'] = implode(',', array_keys($entry['values']));
			}

			$fieldPages = $entry['item_pages'] ? $entry['item_pages'] : array();
			$tableName = $entry['table_name'];
			$className = $entry['class_name'];

			unset($entry['item_pages'], $entry['table_name'], $entry['class_name']);

			$fieldId = $this->iaDb->insert($entry);

			$entry['table_name'] = $tableName;
			$entry['class_name'] = $className;

			foreach ($fieldPages as $pageName)
			{
				if (trim($pageName))
				{
					$row = array('page_name' => $pageName, 'field_id' => $fieldId, 'extras' => $this->itemData['name']);
					$this->iaDb->insert($row, null, iaField::getTablePages());
				}
			}

			$columnExists = false;
			foreach ($this->iaDb->describe($tableName) as $f)
			{
				if ($f['Field'] == $entry['name'])
				{
					$columnExists = true;
					break;
				}
			}

			$columnExists || $this->_alterTable($entry);
		}

		$this->iaDb->resetTable();
	}

	protected function _processAdminPages(array $entries)
	{
		$this->iaDb->setTable('admin_pages');

		$this->iaDb->delete(iaDb::convertIds($this->itemData['name'], 'extras'));

		foreach ($entries as $title => $entry)
		{
			empty($entry['group']) || ($this->_menuGroups[] = $entry['group']);

			$entry['group'] = $this->_lookupGroupId($entry['group']);
			$entry['order'] = (int)(is_null($entry['order'])
				? $this->iaDb->one_bind('MAX(`order`) + 5', '`group` = :group', $entry)
				: $entry['order']);
			empty($entry['name']) && $entry['attr'] = iaUtil::generateToken(8);

			$this->iaDb->insert($entry);
			$this->_addPhrase('page_title_' . ($entry['name'] ? $entry['name'] : $entry['attr']), $title, iaLanguage::CATEGORY_ADMIN);
		}

		$this->iaDb->resetTable();
	}

	protected function _processConfig(array $entries)
	{
		$this->iaDb->setTable(iaCore::getConfigTable());

		$maxOrder = $this->iaDb->getMaxOrder();
		foreach ($entries as $entry)
		{
			$id = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($entry['name'], 'name'));
			$entry['order'] = isset($entry['order']) ? $entry['order'] : ++$maxOrder;

			if (!$id || empty($entry['name']))
			{
				$this->iaDb->insert($entry);
			}
			elseif ($id)
			{
				if (isset($entry['value']))
				{
					unset($entry['value']);
				}

				$this->iaDb->update($entry, iaDb::convertIds($id));
			}
		}

		$this->iaDb->resetTable();
	}

	protected function _runPhpCode($code)
	{
		if (iaSystem::phpSyntaxCheck($code))
		{
			$iaCore = &$this->iaCore;
			$iaDb = &$this->iaDb;

			eval($code);
		}
	}

	protected function _addPhrase($key, $value, $category = iaLanguage::CATEGORY_COMMON)
	{
		foreach ($this->iaCore->languages as $isoCode => $language)
		{
			iaLanguage::addPhrase($key, $value, $isoCode, $this->itemData['name'], $category, false);
		}
	}
}