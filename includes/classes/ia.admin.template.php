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

class iaTemplate extends abstractCore
{
	const INSTALL_FILE_NAME = 'install.xml';

	const TYPE_PACKAGE = 'package';
	const TYPE_PLUGIN = 'plugin';
	const DEPENDENCY_TYPE_TEMPLATE = 'template';

	const CONFIG_ROLLBACK_DATA = 'tmpl_rollback_data';
	const CONFIG_LAYOUT_DATA = 'tmpl_layout_data';

	const SETUP_INITIAL = 1;
	const SETUP_REPLACE = 2;

	//set of xml parser properties
	protected $_xml;

	protected $_inTag;
	protected $_path;
	protected $_attributes;
	protected $_section;

	public $error;

	protected $_notes = [];

	protected $_requiredFields = ['name', 'title', 'version', 'summary', 'author', 'contributor'];

	protected $_changeset;
	protected $_config;
	protected $_configGroups;
	protected $_dependencies;
	protected $_hooks;
	protected $_layout;
	protected $_positions;
	protected $_phrases;
	protected $_screenshots;
	protected $_requires;

	public $name;
	public $title;
	public $status = iaCore::STATUS_ACTIVE;
	public $summary;
	public $version;
	public $author;
	public $contributor;
	public $blocks;

	public $date;
	public $compatibility = false;


	public function getList()
	{
		$templates = [];

		$path = IA_FRONT_TEMPLATES;
		$directory = opendir($path);
		while ($file = readdir($directory))
		{
			if (substr($file, 0, 1) != '.')
			{
				if (is_dir($path . $file))
				{
					$infoXmlFile = $path . $file . IA_DS . self::INSTALL_FILE_NAME;

					if (file_exists($infoXmlFile))
					{
						$this->getFromPath($infoXmlFile);
						$this->parse();
						$this->check();

						if (!$this->error && $file == $this->name)
						{
							$buttons = false;
							if (!$this->getNotes())
							{
								$version = explode('-', $this->compatibility);

								if (!isset($version[1]))
								{
									$buttons = (bool)version_compare($version[0], IA_VERSION, '<=');
								}
								else
								{
									if (version_compare($version[0], IA_VERSION, '<=')
										&& version_compare($version[1], IA_VERSION, '>='))
									{
										$buttons = true;
									}
								}

								if ($buttons === false)
								{
									$this->compatibility = '<span style="color:red;font-weight:bold;">' . $this->compatibility . ' ' . iaLanguage::get('incompatible') . '</span>';
								}
							}

							$templates[$this->name] = [
								'name' => $this->name,
								'title' => $this->title,
								'author' => $this->author,
								'contributor' => $this->contributor,
								'date' => $this->date,
								'description' => $this->summary,
								'version' => $this->version,
								'compatibility' => $this->compatibility,
								'buttons' => $buttons,
								'notes' => $this->getNotes(),
								'config' => $this->_config,
								'config_groups' => $this->_configGroups,
								'url' => 'https://subrion.org/template/' . $this->name . '.html'
							];

							$templates[$this->name]['logo'] = file_exists(IA_FRONT_TEMPLATES . $this->name . '/docs/img/icon.png')
								? $this->iaView->assetsUrl . 'templates/' . $this->name . '/docs/img/icon.png'
								: $this->iaView->assetsUrl . 'admin/templates/default/img/not_available.png';
						}
						elseif ($file != $this->name)
						{
							$this->iaView->setMessages('One of your templates has an incorrect template name. Template name used in the descriptor ('
							. $path . $file . ') should have the same name as the template folder.');
						}
						else
						{
							$this->iaView->setMessages($this->getMessage());
						}
					}
				}
			}
		}
		closedir($directory);

		return $templates;
	}

	protected function _resetValues()
	{
		$this->_changeset = [];
		$this->_config = [];
		$this->_configGroups = [];
		$this->_dependencies = null;
		$this->_hooks = [];
		$this->_notes = [];
		$this->_layout = null;
		$this->_message = null;
		$this->_phrases = [];
		$this->_requires = null;
		$this->_screenshots = [];

		$this->error = false;
	}

	public function parse()
	{
		require_once IA_INCLUDES . 'xml' . IA_DS . 'xml_saxy_parser' . iaSystem::EXECUTABLE_FILE_EXT;

		$xmlParser = new SAXY_Parser();

		$xmlParser->xml_set_element_handler([&$this, 'startElement'], [&$this, 'endElement']);
		$xmlParser->xml_set_character_data_handler([&$this, 'charData']);

		$this->_resetValues();

		$xmlParser->parse($this->_xml);
	}

	/**
	 * checkFields
	 *
	 * Checking mandatory fields. If there is any error the 'error' flag will set to true.
	 *
	 * @access public
	 * @return void
	 */
	public function check()
	{
		$missingFields = [];

		$vars = get_object_vars($this);

		foreach ($this->_requiredFields as $field)
		{
			if (!array_key_exists($field, $vars) || empty($vars[$field]))
			{
				$this->error = true;
				$missingFields[] = $field;
			}
		}

		if ($this->error)
		{
			empty($missingFields)
				? $this->setMessage('Fatal error: Probably specified file is not XML file or is not acceptable')
				: $this->setMessage('Fatal error: The following fields are required: ' . implode(', ', $missingFields));

			return;
		}

		$this->error = false;

		if ($this->_dependencies)
		{
			$currentTemplate = $this->iaCore->get('tmpl');
			$iaItem = $this->iaCore->factory('item');

			foreach ($this->_dependencies as $moduleName => $dependency)
			{
				$shouldBeExist = (bool)$dependency['exist'];
				switch ($dependency['type'])
				{
					case self::TYPE_PACKAGE:
					case self::TYPE_PLUGIN:
						$exists = $iaItem->isModuleExist($moduleName, $dependency['type']);
						break;
					case self::DEPENDENCY_TYPE_TEMPLATE:
						$exists = ($moduleName == $currentTemplate);
				}
				if (isset($exists))
				{
					if (!$exists && $shouldBeExist)
					{
						$message = 'Requires the «:module» :type to be installed. Currently installation is impossible.';
					}
					elseif ($exists && !$shouldBeExist)
					{
						$message = 'The currently installed :type «:module» is not compatible with the template. Installation impossible.';
					}
					if (isset($message))
					{
						$this->_notes[] = iaDb::printf($message, ['module' => ucfirst($moduleName), 'type' => $dependency['type']]);
					}
				}
				else {
					$this->_notes[] = 'Invalid dependencies specified. Ignored.';
				}
			}
		}
	}

	public function rollback()
	{
		$rollbackData = $this->iaCore->get(self::CONFIG_ROLLBACK_DATA);
		if (empty($rollbackData))
		{
			return;
		}
		$rollbackData = unserialize($rollbackData);
		if (!is_array($rollbackData))
		{
			return;
		}

		if (isset($rollbackData['blocks']))
		{
			$existPositions = [];
			if ($this->_positions)
			{
				foreach ($this->_positions as $entry)
				{
					$existPositions[] = $entry['name'];
				}
			}
		}

		foreach ($rollbackData as $dbTable => $actions)
		{
			foreach ($actions as $name => $itemData)
			{
				switch ($dbTable)
				{
					case 'fields':
						list($fieldName, $itemName) = explode('-', $name);
						$stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", ['name' => $fieldName, 'item' => $itemName]);
						break;
					case 'blocks': // menus are handled here as well
						if (isset($itemData['position']))
						{
							if (!in_array($itemData['position'], $existPositions))
							{
								$itemData['position'] = '';
								$itemData['status'] = iaCore::STATUS_INACTIVE;
							}
						}
						// BREAK stmt missed intentionally
					default:
						$stmt = iaDb::printf("`name` = ':name'", ['name' => $name]);
				}
				$this->iaDb->update($itemData, $stmt, null, $dbTable);
			}
		}
	}

	public function install($type = self::SETUP_REPLACE)
	{
		$iaDb = &$this->iaDb;

		// TODO: check for relations and deactivate all needed extras
		if ($this->_requires)
		{
			$messages = [];
			foreach ($this->_requires as $require)
			{
				if ($require['min'] || $require['max'])
				{
					$min = $max = false;
					if (isset($modulesList[$require['name']]))
					{
						$info = $modulesList[$require['name']];
						$min = $require['min'] ? version_compare($require['min'], $info['version'], '<=') : true;
						$max = $require['max'] ? version_compare($require['max'], $info['version'], '>=') : true;
					}
					if (!$max || !$min)
					{
						$ver = '';
						if ($require['min'])
						{
							$ver .= $require['min'];
						}
						if ($require['max'])
						{
							if ($require['min'])
							{
								$ver .= '-';
							}
							$ver .= $require['max'];
						}

						$replace = [
							':extra' => $require['type'],
							':name' => $require['name'],
							':version' => $ver
						];
						$messages[] = iaLanguage::getf('required_template_error', $replace);
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
				$this->_message = implode('<br>', $messages);

				return false;
			}
		}

		if (self::SETUP_REPLACE == $type)
		{
			$template = $iaDb->one('value', "`name` = 'tmpl'", iaCore::getConfigTable());

			$tablesList = ['hooks', 'blocks', iaLanguage::getTable(), 'pages', iaCore::getConfigTable(),
				iaCore::getConfigGroupsTable(), iaCore::getCustomConfigTable()];

			$iaDb->cascadeDelete($tablesList, "`module` = '{$template}'");
			$iaDb->cascadeDelete($tablesList, "`module` = '{$this->name}'");
		}

		$iaDb->update(['value' => $this->name], "`name` = 'tmpl'", null, iaCore::getConfigTable());

		$this->_phrases && $this->_processPhrases();

		if ($this->_config)
		{
			$iaDb->setTable(iaCore::getConfigTable());

			$maxOrder = $iaDb->one_bind('MAX(`order`) + 1', '`module` = :module', ['module' => $this->name]);
			$maxOrder = $maxOrder ? (int)$maxOrder : 1;

			foreach ($this->_config as $entry)
			{
				$id = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($entry['name'], 'name'));
				$entry['order'] = isset($entry['order']) ? $entry['order'] : ++$maxOrder;

				$description = $entry['description'];
				unset($entry['description']);

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

				self::_addPhrase('config_' . $entry['name'], $description, iaLanguage::CATEGORY_ADMIN);
			}

			$iaDb->resetTable();
		}

		if ($this->_configGroups)
		{
			$iaDb->setTable(iaCore::getConfigGroupsTable());

			$maxOrder = $iaDb->getMaxOrder() + 1;

			foreach ($this->_configGroups as $title => $entry)
			{
				$iaDb->insert($entry, ['order' => $maxOrder]);
				$this->_addPhrase('config_group_' . $entry['name'], $title, iaLanguage::CATEGORY_ADMIN);

				$maxOrder++;
			}

			$iaDb->resetTable();
		}

		if ($this->_hooks)
		{
			$iaDb->setTable('hooks');

			$maxOrder = $iaDb->one('MAX(`order`) + 1');
			$maxOrder = $maxOrder ? $maxOrder : 1;

			foreach ($this->_hooks as $hook)
			{
				$array = explode(',', $hook['name']);
				foreach ($array as $hookName)
				{
					if (trim($hookName))
					{
						$hook['name'] = $hookName;
						if (isset($hook['code']) && $hook['code'])
						{
							$hook['code'] = str_replace('{extras}', $this->name, $hook['code']);
						}
						$iaDb->insert($hook, ['order' => $maxOrder]);
						$maxOrder++;
					}
				}
			}
			$iaDb->resetTable();
		}

		$positionsList = [];
		if ($this->_positions)
		{
			$positionPages = [];

			$iaDb->setTable('positions');
			$iaDb->truncate();
			foreach ($this->_positions as $position)
			{
				$positionsList[] = $position['name'];

				$iaDb->insert(['name' => $position['name'], 'menu' => (int)$position['menu'], 'movable' => (int)$position['movable']]);

				if (null != $position['default_access'])
				{
					$positionPages[] = ['object_type' => 'positions', 'page_name' => '', 'object' => $position['name'], 'access' => (int)$position['default_access']];
				}

				if ($position['pages'])
				{
					foreach (explode(',', $position['pages']) as $pageName)
						$positionPages[] = ['object_type' => 'positions', 'page_name' => $pageName,
							'object' => $position['name'], 'access' => (int)$position['access']];
				}
			}
			$iaDb->resetTable();

			if ($positionPages)
			{
				$iaDb->delete("`object_type` = 'positions'", 'objects_pages');
				foreach ($positionPages as $positionPage)
					$iaDb->insert($positionPage, null, 'objects_pages');
			}
		}

		$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);

		if ($this->blocks)
		{
			foreach ($this->blocks as $block)
				$iaBlock->insert($block);
		}

		$rollbackData = [];
		if ($this->_changeset)
		{
			$tablesMapping = [
				'block' => 'blocks',
				'field' => 'fields',
				'menu' => 'blocks',
				'page' => 'pages'
			];

			foreach ($this->_changeset as $changeset)
			{
				if (!isset($tablesMapping[$changeset['type']]))
				{
					continue;
				}

				$entity = $changeset['type'];
				$name = $changeset['name'];

				unset($changeset['type'], $changeset['name']);

				switch ($entity)
				{
					case 'field':
						list($fieldName, $itemName) = explode('-', $name);
						if (empty($fieldName) || empty($itemName)) // incorrect identity specified by template
						{
							continue;
						}
						$stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", ['name' => $fieldName, 'item' => $itemName]);
						break;
					case 'block':
					case 'menu':
						$pagesList = isset($changeset['pages']) ? explode(',', $changeset['pages']) : [];
						unset($changeset['pages']);
						// intentionally missing break stmt
					default:
						$stmt = iaDb::printf("`name` = ':name'", ['name' => $name]);
				}

				$tableName = $tablesMapping[$entity];

				$entryData = $iaDb->row('`id`, `' . implode('`,`', array_keys($changeset)) . '`', $stmt, $tableName);

				if ($iaDb->update($changeset, $stmt, null, $tableName))
				{
					if (isset($changeset['sticky']) && ('block' == $entity || 'menu' == $entity))
					{
						$iaBlock->setVisibility($entryData['id'], $changeset['sticky'], $pagesList);
					}
					unset($entryData['id']);

					$rollbackData[$tableName][$name] = $entryData;
				}
			}
		}
		$rollbackData = empty($rollbackData) ? '' : serialize($rollbackData);

		$this->iaCore->set(self::CONFIG_LAYOUT_DATA, serialize($this->_layout), true);
		$this->iaCore->set(self::CONFIG_ROLLBACK_DATA, $rollbackData, true);

		if (self::SETUP_INITIAL != $type)
		{
			setcookie('template_color_scheme', '', time() - 3600, '/');
		}

		return true;
	}

	public function getFromPath($filePath)
	{
		if (empty($filePath))
		{
			trigger_error('Path to installation instructions file was not specified.', E_USER_ERROR);
			return false;
		}
		$this->_xml = file_get_contents($filePath);
	}

	public function attr($key, $default = '', $in_array = false)
	{
		$return = false;
		if (is_array($key))
		{
			foreach ($key as $item)
			{
				if ($return === false && isset($this->_attributes[$item]))
				{
					$return = $this->_attributes[$item];
				}
			}
		}
		else
		{
			if (isset($this->_attributes[$key]))
			{
				$return = $this->_attributes[$key];
			}
		}
		if ($return !== false)
		{
			if (is_array($in_array) && !in_array($return, $in_array))
			{
				$return = $default;
			}
		}
		else
		{
			$return = $default;
		}

		return $return;
	}

	public function startElement($parser, $name, $attributes)
	{
		$this->_inTag = $name;
		$this->_attributes = $attributes;

		if ('section' == $this->_inTag && isset($attributes['name']))
		{
			$this->_section = $attributes['name'];
		}
		elseif ($this->_inTag == 'template' && isset($attributes['name']))
		{
			$this->name = $attributes['name'];
		}

		$this->_path[] = $name;
	}

	public function endElement($parser, $name)
	{
		array_pop($this->_path);
	}

	public function charData($parser, $text)
	{
		$text = trim($text);

		switch ($this->_inTag)
		{
			case 'version':
			case 'summary':
			case 'title':
			case 'author':
			case 'contributor':
			case 'notes':
			case 'status':
			case 'date':
			case 'compatibility':
				$this->{$this->_inTag} = $text;
				break;

			case 'dependency':
				$this->_dependencies[$text] = [
					'type' => $this->attr('type'),
					'exist' => $this->attr('exist', true)
				];
				break;

			case 'phrase':
				if (in_array('phrases', $this->_path))
				{
					if ($key = trim($this->attr('key')))
					{
						isset($this->_phrases[$key]) || $this->_phrases[$key] = ['values' => [], 'category' => $this->attr('category', iaLanguage::CATEGORY_COMMON)];
						$this->_phrases[$key]['values'][$this->attr('code', $this->iaView->language)] = $text;
					}
				}
				break;

			case 'screenshot':
				if (in_array('screenshots', $this->_path))
				{
					$this->_screenshots[] = [
						'name' => $this->attr('name'),
						'title' => $text,
						'type' => $this->attr('type', false)
					];
				}
				break;

			case 'config':
				$this->_config[] = [
					'name' => $this->attr('name'),
					'value' => $text,
					'config_group' => $this->attr(['group','configgroup']),
					'multiple_values' => $this->attr('values'),
					'type' => $this->attr('type'),
					'description' => $this->attr('description'),
					'private' => $this->attr('private', false),
					'order' => $this->attr('order', false),
					'module' => $this->name,
					'options' => json_encode([
						'wysiwyg' => $this->attr('wysiwyg', false),
						'code_editor' => $this->attr('code_editor', false),
						'show' => $this->attr('show'),
						'multilingual' => $this->attr('multilingual', false)
					])
				];
				break;

			case 'configgroup':
				$this->_configGroups[$text] = [
					'name' => $this->attr('name'),
					'module' => $this->name
				];
				break;

			case 'extension':
				$this->_requires[] = [
					'name' => $text,
					'type' => $this->attr('type', 'package', ['package', 'plugin']),
					'min' => $this->attr(['min_version', 'min'], false),
					'max' => $this->attr(['max_version', 'max'], false)
				];
				break;

			case 'hook':
				$this->_hooks[] = [
					'name' => $this->attr('name'),
					'type' => $this->attr('type', 'php', ['php', 'html', 'smarty', 'plain']),
					'filename' => $this->attr('filename'),
					'module' => $this->name,
					'code' => $text,
					'status' => $this->attr('status', iaCore::STATUS_ACTIVE),
					'page_type' => $this->attr('page_type')
				];
				break;

			case 'block':
				if (in_array('blocks', $this->_path))
				{
					$this->blocks[] = [
						'name' => $this->attr('name', 'block_' . mt_rand(1000, 9999)),
						'title' => $this->attr('title'),
						'content' => $text,
						'position' => $this->attr('position'),
						'type' => $this->attr('type'),
						'order' => $this->attr('order', false),
						'module' => $this->name,
						'status' => $this->attr('status', iaCore::STATUS_ACTIVE, [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]),
						'header' => $this->attr('header', true),
						'collapsible' => $this->attr('collapsible', false),
						'sticky' => $this->attr('sticky', false),
						'pages' => $this->attr('pages'),
						//'added' => $this->attr('added'),
						'rss' => $this->attr('rss'),
						'filename' => $this->attr('filename'),
						'classname' => $this->attr('classname')
					];
				}
				// intentionally missing break stmt
			case 'field':
			case 'menu':
			case 'page':
				if (in_array('changeset', $this->_path))
				{
					$this->_changeset[] = array_merge($this->_attributes, ['type' => $this->_inTag, 'name' => $text]);
				}
				break;

			case 'position':
				if (in_array('section', $this->_path))
				{
					$this->_layout[$this->_section][$text] = [
						'width' => (int)$this->attr('width', 3),
						'fixed' => (bool)$this->attr('fixed', false)
					];
				}

				$this->_positions[] = [
					'name' => $text,
					'menu' => $this->attr('menu', false),
					'movable' => $this->attr('movable', true),
					'pages' => $this->attr('pages', ''),
					'access' => $this->attr('access', null),
					'default_access' => $this->attr('default_access', null)
				];
		}
	}

	public function getNotes()
	{
		return $this->_notes;
	}

	protected function _processPhrases()
	{
		if ($this->_phrases)
		{
			$defaultLangCode = $this->iaView->language;

			foreach ($this->_phrases as $key => $phrase)
			{
				foreach ($this->iaCore->languages as $languageCode => $language)
				{
					$value = isset($phrase['values'][$languageCode])
						? $phrase['values'][$languageCode]
						: $phrase['values'][$defaultLangCode];

					iaLanguage::addPhrase($key, $value, $languageCode, $this->name, $phrase['category'], false);
				}
			}
		}
	}

	protected function _addPhrase($key, $value, $category = iaLanguage::CATEGORY_COMMON)
	{
		foreach ($this->iaCore->languages as $isoCode => $language)
		{
			iaLanguage::addPhrase($key, $value, $isoCode, $this->name, $category, false);
		}
	}
}