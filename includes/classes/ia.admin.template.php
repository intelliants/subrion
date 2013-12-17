<?php
//##copyright##

class iaTemplate extends abstractCore
{
	const INSTALL_FILE_NAME = 'install.xml';

	const DEPENDENCY_TYPE_PACKAGE = 'package';
	const DEPENDENCY_TYPE_PLUGIN = 'plugin';
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

	protected $_notes = array();

	protected $_requiredFields = array('name', 'title', 'version', 'summary', 'author', 'contributor');

	public $name;
	public $title;
	public $status = iaCore::STATUS_ACTIVE;
	public $summary;
	public $version;
	public $author;
	public $contributor;
	public $blocks;
	public $hooks;
	public $phrases;
	public $screenshots;
	public $requires;
	public $config;
	public $config_groups;
	public $date;
	public $compatibility = false;
	public $dependencies;
	public $changeset;
	public $layout;


	public function getList()
	{
		$templates = array();

		$path = IA_FRONT_TEMPLATES;
		$directory = opendir($path);
		while ($file = readdir($directory))
		{
			if (substr($file, 0, 1) != '.')
			{
				if (is_dir($path . $file))
				{
					$infoXmlFile = $path . $file . IA_DS . 'info' . IA_DS . self::INSTALL_FILE_NAME;

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

							$templates[$this->name] = array(
								'name' => $this->name,
								'title' => $this->title,
								'author' => $this->author,
								'contributor' => $this->contributor,
								'date' => $this->date,
								'description' => $this->summary,
								'version' => $this->version,
								'compatibility'	=> $this->compatibility,
								'buttons' => $buttons,
								'screenshots' => $this->screenshots,
								'notes' => $this->getNotes(),
								'config' => $this->config,
								'url' => 'http://www.subrion.com/product/templates/' . $this->name . '.html'
							);

							if (file_exists(IA_FRONT_TEMPLATES . $this->name . '/info' . IA_DS . 'preview.jpg'))
							{
								$templates[$this->name]['logo'] = IA_CLEAR_URL . 'templates/' . $this->name . '/info/preview.jpg';
							}
							else
							{
								$templates[$this->name]['logo'] = IA_CLEAR_URL . 'admin/templates/default/img/not_available.png';
							}
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
		$this->error = false;
		$this->_notes = array();
		$this->_message = null;
		$this->screenshots = array();

		$this->dependencies = null;
	}

	public function parse()
	{
		require_once IA_INCLUDES . 'xml' . IA_DS . 'xml_saxy_parser' . iaSystem::EXECUTABLE_FILE_EXT;

		$xmlParser = new SAXY_Parser();

		$xmlParser->xml_set_element_handler(array(&$this, 'startElement'), array(&$this, 'endElement'));
		$xmlParser->xml_set_character_data_handler(array(&$this, 'charData'));

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
		$missingFields = array();

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
			if (empty($missingFields))
			{
				$this->setMessage('Fatal error: Probably specified file is not XML file or is not acceptable');
			}
			else
			{
				$this->setMessage('Fatal error: The following fields are required: ' . implode(', ', $missingFields));
			}

			return;
		}

		$this->error = false;

		if ($this->dependencies)
		{
			$currentTemplate = $this->iaCore->get('tmpl');
			$iaItem = $this->iaCore->factory('item');

			foreach ($this->dependencies as $extrasName => $dependency)
			{
				$shouldBeExist = (bool)$dependency['exist'];
				switch ($dependency['type'])
				{
					case self::DEPENDENCY_TYPE_PACKAGE:
					case self::DEPENDENCY_TYPE_PLUGIN:
						$exists = $iaItem->isExtrasExist($extrasName, $dependency['type']);
						break;
					case self::DEPENDENCY_TYPE_TEMPLATE:
						$exists = ($extrasName == $currentTemplate);
						break;
				}
				if (isset($exists))
				{
					if (!$exists && $shouldBeExist)
					{
						$message = 'Requires the «:extra» :type to be installed. Currently installation is impossible.';
					}
					elseif ($exists && !$shouldBeExist)
					{
						$message = 'The currently installed :type «:extra» is not compatible with the template. Installation impossible.';
					}
					if (isset($message))
					{
						$this->_notes[] = iaDb::printf($message, array('extra' => ucfirst($extrasName), 'type' => $dependency['type']));
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

		$existPositions = array();
		if ($this->config)
		{
			foreach ($this->config as $entry)
			{
				if ($entry['name'] == 'block_positions')
				{
					$existPositions = explode(',', $entry['value']);
					break;
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
						$stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", array('name' => $fieldName, 'item' => $itemName));
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
						$stmt = iaDb::printf("`name` = ':name'", array('name' => $name));
				}
				$this->iaDb->update($itemData, $stmt, null, $dbTable);
			}
		}
	}

	public function install($type = self::SETUP_REPLACE)
	{
		$iaDb = &$this->iaDb;

		// TODO: check for relations and deactivate all needed extras
		if ($this->requires)
		{
			$messages = array();
			foreach ($this->requires as $require)
			{
				if ($require['min'] || $require['max'])
				{
					$min = $max = false;
					if (isset($extrasList[$require['name']]))
					{
						$info = $extrasList[$require['name']];
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

						$replace = array(
							':extra' => $require['type'],
							':name' => $require['name'],
							':version' => $ver
						);
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

			$tablesList = array('hooks', 'blocks', iaLanguage::getTable(), 'pages', iaCore::getConfigTable(), iaCore::getConfigGroupsTable());

			$iaDb->cascadeDelete($tablesList, "`extras` = '{$template}'");
			$iaDb->cascadeDelete($tablesList, "`extras` = '{$this->name}'");
		}

		$iaDb->update(array('value' => $this->name), "`name` = 'tmpl'", null, iaCore::getConfigTable());

		if ($this->phrases)
		{
			$installedLanguages = unserialize($iaDb->one('value', "`name` = 'languages'", iaCore::getConfigTable()));

			if (!array_key_exists('en', $installedLanguages))
			{
				foreach ($installedLanguages as $code => $language)
				{
					foreach ($this->phrases as $key => $phrase)
					{
						$this->phrases[$key]['lang'] = $language;
						$this->phrases[$key]['code'] = $code;
					}
				}
			}
			else
			{
				foreach ($installedLanguages as $code => $language)
				{
					if ('en' != $code)
					{
						foreach ($this->phrases as $phrase)
						{
							iaLanguage::addPhrase($phrase['key'], $phrase['value'], $code, $this->name, $phrase['category'], false);
						}
					}
				}
			}

			foreach ($this->phrases as $phrase)
			{
				iaLanguage::addPhrase($phrase['key'], $phrase['value'], $phrase['code'], $this->name, $phrase['category'], false);
			}
		}

		$positions = explode(',', $iaDb->one('value', "`name` = 'block_positions'", iaCore::getConfigTable()));

		if ($this->config)
		{
			$iaDb->setTable(iaCore::getConfigTable());

			$maxOrder = $iaDb->one('MAX(`order`) + 1');
			$maxOrder = $maxOrder ? (int)$maxOrder : 1;

			foreach ($this->config as $config)
			{
				$order = $config['order'];
				unset($config['order']);

				if ($config['name'] == 'block_positions')
				{
					$positions = explode(',', $config['value']);
				}
				$stmt = iaDb::printf("`name` = ':name'", $config);
				if ($iaDb->exists($stmt))
				{
					$iaDb->update($config, $stmt);
				}
				else
				{
					$iaDb->insert($config, array('order' => $order ? $order : $maxOrder));
					$maxOrder++;
				}
			}

			$iaDb->resetTable();
		}

		if ($this->config_groups)
		{
			$iaDb->setTable(iaCore::getConfigGroupsTable());

			$maxOrder = $iaDb->getMaxOrder() + 1;

			foreach ($this->config_groups as $config)
			{
				$iaDb->insert($config, array('order' => $maxOrder));
				$maxOrder++;
			}

			$iaDb->resetTable();
		}

		if ($this->hooks)
		{
			$iaDb->setTable('hooks');

			$maxOrder = $iaDb->one('MAX(`order`) + 1');
			$maxOrder = $maxOrder ? $maxOrder : 1;

			foreach ($this->hooks as $hook)
			{
				$array = explode(',', $hook['name']);
				foreach ($array as $hookName)
				{
					if (trim($hookName) != '')
					{
						$hook['name'] = $hookName;
						if (isset($hook['code']) && $hook['code'])
						{
							$hook['code'] = str_replace('{extras}', $this->name, $hook['code']);
						}
						$iaDb->insert($hook, array('order' => $maxOrder));
						$maxOrder++;
					}
				}
			}
			$iaDb->resetTable();
		}

		if ($this->blocks)
		{
			$iaDb->setTable('blocks');

			$maxOrder = $iaDb->one('MAX(`order`)');
			$maxOrder = ($maxOrder ? $maxOrder : 1);

			foreach ($this->blocks as $block)
			{
				if (!$block['order'])
				{
					$maxOrder++;
					$block['order'] = $maxOrder;
				}
				else
				{
					$block['order'] = intval($block['order']);
				}

				if (!empty($block['filename']))
				{
					$block['external'] = 1;
				}

				$blockPages = $block['pages'];
				$blockExceptPages = $block['pagesexcept'];

				unset($block['pages'], $block['pagesexcept'], $block['added']);

				if (!in_array($block['position'], $positions))
				{
					$block['position'] = $positions[0];
				}
				if (isset($block['contents']) && $block['contents'])
				{
					$block['contents'] = str_replace('{extras}', $this->name, $block['contents']);
				}

				$id = $iaDb->insert($block);

				if ($blockPages)
				{
					$blockExceptPages = $blockExceptPages
						? explode(',', $blockExceptPages)
						: array();
					$blockPages = explode(',', $blockPages);

					$rows = array();
					foreach ($blockPages as $page)
					{
						if (!in_array($page, $blockExceptPages))
						{
							$rows[] = array(
								'page_name' => iaSanitize::sql($page),
								'block_id' => $id
							);
						}
					}

					$iaDb->insert($rows, null, 'blocks_pages');
				}
			}

			$iaDb->resetTable();
		}

		$rollbackData = array();
		if ($this->changeset)
		{
			$tablesMapping = array(
				'block' => 'blocks',
				'field' => 'fields',
				'menu' => 'blocks'
			);

			foreach ($this->changeset as $changeset)
			{
				if (!isset($tablesMapping[$changeset['type']]))
				{
					continue;
				}

				$type = $changeset['type'];
				$name = $changeset['name'];

				unset($changeset['type'], $changeset['name']);

				switch ($type)
				{
					case 'field':
						list($fieldName, $itemName) = explode('-', $name);
						if (empty($fieldName) || empty($itemName)) // incorrect identity specified by template
						{
							continue;
						}
						$stmt = iaDb::printf("`name` = ':name' AND `item` = ':item'", array('name' => $fieldName, 'item' => $itemName));
						break;
					case 'block':
					case 'menu':
						if (isset($changeset['pages']) && $changeset['pages'])
						{
							$pagesList = explode(',', $changeset['pages']);
							unset($changeset['pages']);
						}
						// intentionally missing break stmt
					default:
						$stmt = iaDb::printf("`name` = ':name'", array('name' => $name));
				}

				$tableName = $tablesMapping[$type];

				$entryData = $iaDb->row('`' . implode('`,`', array_keys($changeset)) . '`', $stmt, $tableName);
				if ($iaDb->update($changeset, $stmt, null, $tableName))
				{
					$rollbackData[$tableName][$name] = $entryData;

					if (isset($pagesList))
					{
						$entryId = $iaDb->one(iaDb::ID_COLUMN_SELECTION, $stmt, $tableName);

						$iaBlock = $this->iaCore->factory('block', iaCore::ADMIN);
						$iaBlock->setVisiblePages($entryId, $pagesList);
					}
				}
			}
		}
		$rollbackData = empty($rollbackData) ? '' : serialize($rollbackData);
		$rollbackData = array('value' => $rollbackData);

		$this->iaCore->set(self::CONFIG_LAYOUT_DATA, serialize($this->layout), true);

		$stmt = sprintf("`name` = '%s'", self::CONFIG_ROLLBACK_DATA);
		$iaDb->update($rollbackData, $stmt, null, iaCore::getConfigTable());

		if (self::SETUP_INITIAL != $type)
		{
			setcookie('template_color_scheme', '', time() - 3600, '/');

			$iaCache = $this->iaCore->factory('cache');
			$iaCache->clearAll();
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
		$attr = $this->_attributes;

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
				$this->dependencies[$text] = array(
					'type' => $this->attr('type'),
					'exist' => $this->attr('exist', true)
				);
				break;

			case 'phrase':
				if (in_array('phrases', $this->_path))
				{
					$this->phrases[] = array(
						'key' => $attr['key'],
						'value' => $text,
						'category' => $this->attr('category', iaLanguage::CATEGORY_COMMON),
						'code' => $this->attr('code', IA_LANGUAGE)
					);
				}
				break;

			case 'screenshot':
				if (in_array('screenshots', $this->_path))
				{
					$this->screenshots[] = array(
						'name' => $this->attr('name'),
						'title' => $text,
						'type' => $this->attr('type', false)
					);
				}
				break;

			case 'config':
				$this->config[] = array(
					'name' => $this->attr('name'),
					'value' => $text,
					'config_group' => $this->attr(array('group','configgroup')),
					'multiple_values' => $this->attr(array('values','multiplevalues')),
					'type' => $this->attr('type'),
					'description' => $this->attr('description'),
					'wysiwyg' => $this->attr('wysiwyg', 0),
					'code_editor' => $this->attr('code_editor', 0),
					'private' => $this->attr('private', 0),
					'order' => $this->attr('order', false),
					'extras' => $this->name
				);
				break;

			case 'configgroup':
				$this->config_groups[] = array(
					'name' => $this->attr('name'),
					'extras' => $this->name,
					'title' => $text
				);
				break;

			case 'extension':
				$this->requires[] = array(
					'name' => $text,
					'type' => $this->attr('type', 'package', array('package', 'plugin')),
					'min' => $this->attr(array('min_version', 'min'), false),
					'max' => $this->attr(array('max_version', 'max'), false)
				);
				break;

			case 'hook':
				$this->hooks[] = array(
					'name' => $this->attr('name'),
					'type' => $this->attr('type', 'php', array('php', 'html', 'smarty', 'plain')),
					'filename' => $this->attr('filename'),
					'extras' => $this->name,
					'code' => $text,
					'status' => $this->attr('status', iaCore::STATUS_ACTIVE)
				);
				break;

			case 'block':
				if (in_array('blocks', $this->_path))
				{
					$this->blocks[] = array(
						'name' => $this->attr('name', 'block_' . mt_rand(1000, 9999)),
						'title' => $this->attr('title'),
						'contents' => $text,
						'position' => $this->attr('position'),
						'type' => $this->attr('type'),
						'order' => $this->attr('order', false),
						'extras' => $this->name,
						'status' => $this->attr('status', iaCore::STATUS_ACTIVE, array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)),
						'header' => $this->attr('header', 1),
						'collapsible' => $this->attr('collapsible', 0),
						'sticky' => $this->attr('sticky', 0),
						'multi_language' => $this->attr('multilanguage', 1),
						'pages' => $this->attr('pages'),
						'pagesexcept' => $this->attr('pagesexcept'),
						'added' => $this->attr('added'),
						'rss' => $this->attr('rss'),
						'filename' => $this->attr('filename'),
						'classname' => $this->attr('classname')
					);
				}
			// intentionally missing break stmt
			case 'field':
			case 'menu':
				if (in_array('changeset', $this->_path))
				{
					$this->changeset[] = array_merge($this->_attributes, array('type' => $this->_inTag, 'name' => $text));
				}
				break;

			case 'position':
				if (in_array('section', $this->_path))
				{
					$this->layout[$this->_section][$text] = array(
						'width' => (int)$this->attr('width', 3),
						'fixed' => (bool)$this->attr('fixed', false)
					);
				}
		}
	}

	public function getNotes()
	{
		return $this->_notes;
	}
}