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

final class iaCore
{
	const STATUS_ACTIVE = 'active';
	const STATUS_APPROVAL = 'approval';
	const STATUS_DRAFT = 'draft';
	const STATUS_INACTIVE = 'inactive';

	const CORE = 'core';
	const FRONT = 'front';
	const ADMIN = 'admin';

	const ACTION_ADD = 'add';
	const ACTION_EDIT = 'edit';
	const ACTION_DELETE = 'delete';
	const ACTION_READ = 'read';

	const ACCESS_FRONT = 0;
	const ACCESS_ADMIN = 1;

	const EXTENSION_JSON = 'json';
	const EXTENSION_XML = 'xml';

	const CLASSNAME_PREFIX = 'ia';

	const INTELLI = 'intelli';

	const SECURITY_TOKEN_MEMORY_KEY = 'csrftoken';
	const SECURITY_TOKEN_FORM_KEY = '__st';


	private static $_instance;

	private $_classInstances = array();

	protected static $_configDbTable = 'config';
	protected static $_configGroupsDbTable = 'config_groups';
	protected static $_customConfigDbTable = 'config_custom';

	protected $_accessType = self::ACCESS_FRONT;

	protected $_hooks = array();
	protected $_config = array();
	protected $_customConfig;

	protected $_checkDomain;

	public $iaDb;
	public $iaView;
	public $iaCache;

	public $languages = array();
	public $language = array();

	public $packagesData = array();
	public $requestPath = array();


	protected function __construct(){}
	protected function __clone(){}

	public static function instance()
	{
		if (is_null(self::$_instance))
		{
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init()
	{
		$this->iaDb = $this->factory('db');
		$this->factory(array('sanitize', 'validate', 'language', 'users'));
		$this->iaView = $this->factory('view');
		$this->iaCache = $this->factory('cache');
		iaSystem::renderTime('core', 'Basic Classes Initialized');

		$this->getConfig();
		iaSystem::renderTime('core', 'Configuration Loaded');

		iaSystem::setDebugMode();

		$this->_parseUrl();

		setlocale(LC_COLLATE|LC_TIME, $this->get('locale'));

		// we can only load strings when we know if a specific language is requested based on URL
		iaLanguage::load($this->iaView->language);

		$this->_fetchHooks();
		iaSystem::renderTime('core', 'Hooks Loaded');

		$this->startHook('phpCoreUrlRewrite');
		$this->_setConstants();

		$this->startHook('init');

		// authorize user
		$iaUsers = $this->factory('users');
		$iaUsers->authorize();

		$this->_forgeryCheck();
		$this->getCustomConfig();

		$this->startHook('phpCoreBeforePageDefine');

		$this->iaView->definePage();
		$this->iaView->loadSmarty();

		$this->startHook('bootstrap');

		$this->_defineModule();
		$this->iaView->defineOutput();
		$this->_checkPermissions();
		$this->_executeModule();

		$this->startHook('phpCoreBeforeJsCache');
		$this->iaCache->createJsCache();

		if (self::ACCESS_FRONT == $this->getAccessType()
			&& iaView::REQUEST_HTML == $this->iaView->getRequestType()
			&& iaView::PAGE_ERROR != $this->iaView->name())
		{
			$iaUsers->registerVisitor();
		}

		$this->startHook('phpCoreBeforePageDisplay');
		$this->iaView->output();

		$this->startHook('finalize');
	}

	public function __destruct()
	{
		if (INTELLI_DEBUG || INTELLI_QDEBUG) // output the debug info if enabled
		{
			if (is_object($this->iaView) && iaView::REQUEST_HTML == $this->iaView->getRequestType())
			{
				if (!$this->iaView->get('nodebug'))
				{
					new iaDebug();
				}
			}
		}
	}

	public function getAccessType()
	{
		return $this->_accessType;
	}

	protected function _parseUrl()
	{
		$iaView = &$this->iaView;

		$domain = preg_replace('#[^a-z_0-9-.:]#i', '', $_SERVER['HTTP_HOST']);
		$requestPath = ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER);

		if (!preg_match('#^www\.#', $domain) && preg_match('#:\/\/www\.#', $this->get('baseurl')))
		{
			$domain = preg_replace('#^#', 'www.', $domain);
			$this->factory('util')->go_to('http://' . $domain . IA_URL_DELIMITER . $requestPath);
		}
		elseif (preg_match('#^www\.#', $domain) && !preg_match('#:\/\/www\.#', $this->get('baseurl')))
		{
			$domain = preg_replace('#^www\.#', '', $domain);
			$this->factory('util')->go_to('http://' . $domain . IA_URL_DELIMITER . $requestPath);
		}

		$iaView->assetsUrl = '//' . $domain . IA_URL_DELIMITER . FOLDER_URL;
		$iaView->domain = $domain;
		$iaView->domainUrl = 'http' . (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS'] ? 's' : '') . ':' . $iaView->assetsUrl;
		$iaView->language = $this->get('lang');

		$doExit = false;
		$changeLang = false;

		if (isset($_GET['_p']))
		{
			$url = $_GET['_p'];
			unset($_GET['_p']);
		}
		else
		{
			$url = (!isset($_SERVER['REDIRECT_URL']) || $_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'])
				? $_SERVER['REQUEST_URI']
				: $_SERVER['REDIRECT_URL'];
			$url = substr($url, strlen(FOLDER) + 1);
		}

		$extension = IA_URL_DELIMITER;

		$url = explode('?', $url);
		$url = array_shift($url);
		$url = explode(IA_URL_DELIMITER, iaSanitize::htmlInjectionFilter(trim($url, IA_URL_DELIMITER)));

		$lastChunk = end($url);
		if ($pos = strrpos($lastChunk, '.'))
		{
			$extension = substr($lastChunk, $pos + 1);
			switch ($extension)
			{
				case self::EXTENSION_JSON:
					$iaView->setRequestType(iaView::REQUEST_JSON);
					break;
				case self::EXTENSION_XML:
					$iaView->setRequestType(iaView::REQUEST_XML);
			}

			$extension = '.' . $extension;
			$url = str_replace($extension, '', $url);
		}
		$iaView->set('extension', $extension);

		if (isset($_POST['_lang']) && isset($this->languages[$_POST['_lang']]))
		{
			$iaView->language = $_POST['_lang'];
			$changeLang = true;
		}

		$isSystemChunk = true;
		$array = array();
		foreach ($url as $value)
		{
			if (!$isSystemChunk)
			{
				$array[] = $value;
				continue;
			}

			switch (true)
			{
				case ($this->get('admin_page') == $value): // admin panel
					$this->_accessType = self::ACCESS_ADMIN;
					continue 2;
				case ('logout' == $value): // logging out
					$doExit = true;
					continue 2;
				case (2 == strlen($value)): // current language
					if (isset($this->languages[$value]))
					{
						$changeLang || $iaView->language = $value;
						array_shift($url); // #1715
						continue 2;
					}
				default:
					$iaView->name(empty($value) && 1 == count($url) ? $this->get('home_page') : $value);
					$isSystemChunk = false;
			}
		}

		if (self::ACCESS_ADMIN == $this->getAccessType())
		{
			if ($isSystemChunk && $this->get('home_page') == $iaView->name())
			{
				$iaView->name(iaView::DEFAULT_HOMEPAGE);
			}
		}

		$iaView->url = empty($url[0]) ? array() : $url;
		$this->requestPath = $array;

		// set system language
		$this->language = $this->languages[$iaView->language];

		// set dynamic config
		$this->set('date_format', $this->language['date_format']);
		$this->set('locale', $this->language['locale']);

		define('IA_EXIT', $doExit);
	}

	protected function _defineModule()
	{
		$iaView = &$this->iaView;

		$extrasName = $iaView->get('extras');
		$fileName = $iaView->get('filename');

		switch ($iaView->get('type'))
		{
			case 'package':
				define('IA_CURRENT_PACKAGE', $extrasName);
				define('IA_PACKAGE_URL', ($iaView->packageUrl ? $iaView->packageUrl . IA_URL_LANG : $iaView->domainUrl . IA_URL_LANG . $iaView->extrasUrl));
				define('IA_PACKAGE_TEMPLATE', IA_PACKAGES . $extrasName . IA_DS . 'templates' . IA_DS);

				$module = empty($fileName) ? iaView::DEFAULT_HOMEPAGE : $fileName;
				$module = IA_PACKAGES . $extrasName . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . $module . iaSystem::EXECUTABLE_FILE_EXT;

				file_exists($module) || $module = (self::ACCESS_ADMIN == $this->getAccessType() ? IA_ADMIN : IA_FRONT) . $fileName . iaSystem::EXECUTABLE_FILE_EXT;

				break;

			case 'plugin':
				define('IA_CURRENT_PLUGIN', $extrasName);
				define('IA_PLUGIN_TEMPLATE', IA_PLUGINS . $extrasName . IA_DS . 'templates' . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' : 'front') . IA_DS);

				$module = empty($fileName) ? iaView::DEFAULT_HOMEPAGE : $fileName;
				$module = IA_PLUGINS . $extrasName . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . $module . iaSystem::EXECUTABLE_FILE_EXT;

				break;

			default:
				$module = empty($fileName) ? $iaView->name() : $fileName;
				$module = (self::ACCESS_ADMIN == $this->getAccessType() ? IA_ADMIN : IA_FRONT) . $module . iaSystem::EXECUTABLE_FILE_EXT;
		}

		$iaView->set('filename', $module);
	}

	protected function _checkPermissions()
	{
		$iaAcl = $this->factory('acl');

		if (self::ACCESS_ADMIN == $this->getAccessType())
		{
			if (!iaUsers::hasIdentity())
			{
				iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
			}
			elseif (!$iaAcl->isAdmin())
			{
				iaView::accessDenied();
			}
		}
		elseif (iaView::PAGE_ERROR == $this->iaView->name())
		{
			return;
		}

		$iaAcl->isAccessible($this->iaView->get('name'), $this->iaView->get('action')) || iaView::accessDenied();
	}

	protected function _executeModule()
	{
		$module = $this->iaView->get('filename');

		if (empty($module))
		{
			return;
		}

		if (!file_exists($module))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		};

		// this set of variables should be defined since there is a PHP file inclusion below
		$iaCore = &$this;
		$iaView = &$this->iaView;
		$iaDb = &$this->iaDb;
		$iaAcl = $this->factory('acl');
		//

		$pageName = $this->iaView->name();
		$permission = (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin_' : '') . 'pages-' . $pageName . iaAcl::SEPARATOR;
		$pageAction = $this->iaView->get('action');

		$this->startHook('phpCoreCodeBeforeStart');

		require $module;

		// temporary stub
		if (self::ACCESS_ADMIN == $this->getAccessType())
		{
			if (class_exists('iaBackendController'))
			{
				$iaModule = new iaBackendController();
				$iaModule->process();
			}
		}
		//

		$this->startHook('phpCoreCodeAfterAll');
	}

	/**
	 * Get the list of configuration values & cache it when needed
	 *
	 * @param bool|false $reloadRequired true forces cache reload
	 *
	 * @return array
	 */
	public function getConfig($reloadRequired = false)
	{
		if (empty($this->_config) || $reloadRequired)
		{
			$this->_config = $this->iaCache->get('config', 604800, true);
			iaSystem::renderTime('config', 'Cached Configuration Loaded');

			if (empty($this->_config) || $reloadRequired)
			{
				$this->_config = $this->iaDb->keyvalue(array('name', 'value'), "`type` != 'divider'", self::getConfigTable());
				iaSystem::renderTime('config', 'Configuration loaded from DB');

				$extras = $this->iaDb->onefield('name', "`status` = 'active'", null, null, 'extras');
				$extras[] = $this->_config['tmpl'];

				$this->_config['extras'] = $extras;
				$this->_config['block_positions'] = $this->iaView->positions;

				$this->iaCache->write('config', $this->_config);
				iaSystem::renderTime('config', 'Configuration written to cache file');
			}

			$this->_setTimezone($this->get('timezone'));
		}

		return $this->_config;
	}

	/**
	 * Get the list of user/group specific configuration values
	 *
	 * @param null $user user id
	 * @param null $group group id
	 *
	 * @return array
	 */
	public function getCustomConfig($user = null, $group = null)
	{
		$local = false;

		if (is_null($user) && is_null($group))
		{
			$this->factory('users');

			$local = true;
			if (iaUsers::hasIdentity())
			{
				$user = iaUsers::getIdentity()->id;
				$group = iaUsers::getIdentity()->usergroup_id;
			}
			else
			{
				$user = 0;
				$group = iaUsers::MEMBERSHIP_GUEST;
			}
		}

		if ($local && !is_null($this->_customConfig))
		{
			return $this->_customConfig;
		}

		$result = array();
		$stmt = array();

		if ($user)
		{
			$stmt[] = "(`type` = 'user' AND `type_id` = $user) ";
		}
		if ($group)
		{
			$stmt[] = "(`type` = 'group' AND `type_id` = $group) ";
		}

		$rows = $this->iaDb->all(array('type', 'name', 'value'), implode(' OR ', $stmt), null, null, self::getCustomConfigTable());

		if (empty($rows))
		{
			return $result;
		}

		$result = array('group' => array(), 'user' => array(), 'plan' => array());

		foreach ($rows as $row)
		{
			$result[$row['type']][$row['name']] = $row['value'];
		}

		$result = array_merge($result['group'], $result['user'], $result['plan']);

		if ($local)
		{
			$this->_customConfig = $result;
		}

		return $result;
	}

	/**
	 * Get the specified configuration value
	 *
	 * @param string $key configuration key
	 * @param bool|false $default default value
	 * @param bool|true $custom custom config flag
	 * @param bool|false $db true gets from database directly
	 *
	 * @return string
	 */
	public function get($key, $default = false, $custom = true, $db = false)
	{
		if ($custom && isset($this->_customConfig[$key]))
		{
			return $this->_customConfig[$key];
		}

		$result = $default;

		if ($db)
		{
			if ($value = $this->iaDb->one('`value`', iaDb::convertIds($key, 'name'), self::getConfigTable()))
			{
				$result = $value;
			}
		}
		else
		{
			if (isset($this->_config[$key]))
			{
				return $this->_config[$key];
			}
		}

		$this->_config[$key] = $result;

		return $result;
	}

	/**
	 * Set a given configuration value
	 *
	 * @param string $key configuration key
	 * @param string $value configuration value
	 * @param bool|false $permanent saves permanently in db
	 *
	 * @return bool
	 */
	public function set($key, $value, $permanent = false)
	{
		if ($permanent && !is_scalar($value))
		{
			trigger_error(__METHOD__ . '() Could not write a non-scalar value to the database.', E_USER_ERROR);
		}

		$result = true;
		$this->_config[$key] = $value;

		if ($permanent)
		{
			$result = (bool)$this->iaDb->update(array('value' => $value), iaDb::convertIds($key, 'name'), null, self::getConfigTable());

			$this->iaCache->createJsCache(array('config'));
			$this->iaCache->remove('config');
		}

		return $result;
	}

	/**
	 * Get the list of hooks
	 *
	 * @return array
	 */
	public function getHooks()
	{
		return $this->_hooks;
	}

	/**
	 * Set the list of available hooks
	 */
	protected function _fetchHooks()
	{
		$columns = array('name', 'code', 'type', 'extras', 'filename', 'pages');
		$stmt = "`extras` IN('', '" . implode("','", $this->get('extras')) . "') AND `status` = :status AND `page_type` IN ('both', :type) ORDER BY `order`";
		$this->iaDb->bind($stmt, array(
			'status' => iaCore::STATUS_ACTIVE,
			'type' => (self::ACCESS_FRONT == $this->getAccessType()) ? iaCore::FRONT : iaCore::ADMIN
		));

		if ($rows = $this->iaDb->all($columns, $stmt, null, null, 'hooks'))
		{
			foreach ($rows as $row)
			{
				$this->_hooks[$row['name']][$row['extras']] = array(
					'pages' => empty($row['pages']) ? array() : explode(',', $row['pages']),
					'code' => $row['code'],
					'type' => $row['type'],
					'filename' => $row['filename']
				);
			}
		}
	}

	protected function _forgeryCheck()
	{
		if ($_POST && $this->get('prevent_csrf') && !$this->iaView->get('nocsrf'))
		{
			$referrerValid = false;
			$tokenValid = true;//(isset($_POST[self::SECURITY_TOKEN_FORM_KEY]) && $this->getSecurityToken() == $_POST[self::SECURITY_TOKEN_FORM_KEY]);

			if (isset($_SERVER['HTTP_REFERER']))
			{
				$wwwChunk = 'www.';

				$referrerDomain = explode(IA_URL_DELIMITER, $_SERVER['HTTP_REFERER']);
				$referrerDomain = strtolower($referrerDomain[2]);
				$referrerDomain = str_replace($wwwChunk, '', $referrerDomain);

				$domain = explode(IA_URL_DELIMITER, $this->get('baseurl'));
				$domain = strtolower($domain[2]);
				$domain = str_replace($wwwChunk, '', $domain);

				if ($referrerDomain === $domain)
				{
					$referrerValid = true;
				}
			}
			else
			{
				$referrerValid = true; // sad, but no other way
			}

			if (!$referrerValid || !$tokenValid)
			{
				header('HTTP/1.1 203'); // reply with 203 "Non-Authoritative Information" status

				$this->iaView->set('nodebug', true);
				die('Request treated as a potential CSRF attack.');
			}
		}

		unset($_POST[self::SECURITY_TOKEN_FORM_KEY]);
	}

	public function checkDomain()
	{
		if (is_null($this->_checkDomain))
		{
			$dbUrl = str_replace(array('http://www.', 'http://'), '', $this->get('baseurl'));
			$codeUrl = str_replace(array('http://www.', 'http://'), '', $this->iaView->domainUrl);

			$this->_checkDomain = ($dbUrl == $codeUrl);
		}

		return $this->_checkDomain;
	}

	public function setPackagesData($regenerate = false)
	{
		if ($this->packagesData && !$regenerate)
		{
			return $this->packagesData;
		}

		$rows = $this->iaDb->all(array('name', 'url', 'title'), "`type` = 'package' AND `status` = 'active'", null, null, 'extras');

		$packages = array();
		foreach ($rows as $entry)
		{
			$entry['url'] = ($entry['url'] == IA_URL_DELIMITER) ? '' : $entry['url'];
			$entry['url'] = (strpos($entry['url'], 'http://') === false) ? IA_URL . $entry['url'] : $entry['url'];
			$entry['tpl_url'] = IA_CLEAR_URL . 'packages' . IA_URL_DELIMITER . $entry['name'] . IA_URL_DELIMITER . 'templates' . IA_URL_DELIMITER;
			$entry['tpl_common'] = IA_HOME . 'packages' . IA_URL_DELIMITER . $entry['name'] . IA_URL_DELIMITER . 'templates' . IA_URL_DELIMITER . 'common' . IA_URL_DELIMITER;
			$packages[$entry['name']] = $entry;
		}

		return $this->packagesData = $packages;
	}

	public function getExtras($package)
	{
		$rows = $this->iaDb->row_bind(
			array('name', 'url', 'title'),
			'`status` = :status AND `name` = :package',
			array('status' => iaCore::STATUS_ACTIVE, 'package' => $package),
			'extras'
		);

		return $rows;
	}

	public static function util()
	{
		return self::instance()->factory('util');
	}

	public function startHook($name, array $params = array())
	{
		if (empty($name))
		{
			return false;
		}

		iaDebug::debug('php', $name, 'hooks');

		if (!isset($this->_hooks[$name]))
		{
			return false;
		}

		iaSystem::renderTime('hook', $name);

		if (count($this->_hooks[$name]) > 0)
		{
			$variablesList = array_keys($params);
			extract($params, EXTR_REFS | EXTR_SKIP);

			$iaCore = &$this;
			$iaView = &$this->iaView;
			$iaDb = &$this->iaDb;

			foreach ($this->_hooks[$name] as $extras => $hook)
			{
				if ('php' == $hook['type']
					&& (empty($hook['pages']) || in_array($iaView->name(), $hook['pages'])))
				{
					if ($hook['filename'])
					{
						if (!file_exists(IA_HOME . $hook['filename']))
						{
							$message = sprintf('Can\'t start hook "%s". File does not exist: %s', $name, $hook['filename']);
							iaDebug::debug($message, null, 'error');
						}
						else
						{
							include IA_HOME . $hook['filename'];
						}
					}
					else
					{
						iaSystem::renderTime('START TIME ' . $name . ' ' . $extras);
						if (iaSystem::phpSyntaxCheck($hook['code']))
						{
							eval($hook['code']);
						}
						else
						{
							iaDebug::debug(array('name' => $name, 'code' => '<textarea style="width:80%;height:100px;">' . $hook['code'] . '</textarea>'), '<b style="color:red;">Syntax error in hook "' . $name . '" of "' . $extras . '"</b>', 'error');
						}
						iaSystem::renderTime('END TIME ' . $name . ' ' . $extras);
					}
				}
			}

			compact($variablesList);

			return true;
		}

		return false;
	}

	public function factory($name, $type = self::CORE)
	{
		$result = null;
		if (is_string($name))
		{
			$className = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
			if (isset($this->_classInstances[$className]))
			{
				$result = $this->_classInstances[$className];
			}
			else
			{
				iaSystem::renderTime('class', 'Loading class ' . $className);
				$fileSize = $this->loadClass($type, (strtolower($name) == 'db') ? INTELLI_CONNECT : $name);
				if (false === $fileSize)
				{
					return false;
				}

				iaDebug::debug('ia.' . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

				$result = new $className();
				$result->init();

				$this->_classInstances[$className] = $result;
			}
		}
		elseif (is_array($name))
		{
			$result = array();
			foreach ($name as $className)
			{
				$result[] = $this->factory($className, $type);
			}
		}

		return $result;
	}

	public function factoryPackage($name, $package, $type = self::FRONT, $params = null)
	{
		if ('item' == $name && $params)
		{
			$name = substr($params, 0, -1);
			$class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
			$params = null;
		}
		else
		{
			$class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
		}

		if (!isset($this->_classInstances[$class]))
		{
			$packageInterface = IA_PACKAGES . $package . IA_DS . 'includes/classes/ia.base.package' . iaSystem::EXECUTABLE_FILE_EXT;
			if (is_file($packageInterface))
			{
				require_once $packageInterface;
			}

			$fileSize = $this->loadClass($type, $name, null, $package);
			if (false === $fileSize)
			{
				return false;
			}

			iaDebug::debug('<b>package:</b> ia.' . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

			$this->_classInstances[$class] = new $class();
			$this->_classInstances[$class]->init();
		}

		return $this->_classInstances[$class];
	}

	public function factoryPlugin($plugin, $type = self::FRONT, $name = null)
	{
		if (empty($name))
		{
			$name = $plugin;
		}
		$class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));

		if (!isset($this->_classInstances[$class]))
		{
			$fileSize = $this->loadClass($type, $name, $plugin);
			if (false === $fileSize)
			{
				return false;
			}
			iaDebug::debug('<b>plugin:</b> ia.' . $type . '.' . $name . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');
			$this->_classInstances[$class] = new $class();
			$this->_classInstances[$class]->init();
		}

		return $this->_classInstances[$class];
	}

	public function loadClass($type = self::CORE, $className = '', $pluginName = null, $packageName = null)
	{
		$name = strtolower($className);
		$filename = iaSystem::CLASSES_PREFIX . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT;

		if ($packageName)
		{
			$classFile = IA_PACKAGES . $packageName . IA_DS . 'includes' . IA_DS . 'classes' . IA_DS . $filename;
		}
		elseif ($pluginName)
		{
			$classFile = IA_PLUGINS . $pluginName . IA_DS . 'includes' . IA_DS . 'classes' . IA_DS . $filename;
		}
		else
		{
			$classFile = IA_CLASSES . $filename;
		}

		if (file_exists($classFile))
		{
			include_once $classFile;

			return filesize($classFile);
		}

		return false;
	}

	/**
	 * Get config table name
	 *
	 * @return string
	 */
	public static function getConfigTable()
	{
		return self::$_configDbTable;
	}

	/**
	 * Get config groups table name
	 *
	 * @return string
	 */
	public static function getConfigGroupsTable()
	{
		return self::$_configGroupsDbTable;
	}

	/**
	 * Get custom config table name
	 *
	 * @return string
	 */
	public static function getCustomConfigTable()
	{
		return self::$_customConfigDbTable;
	}

	/**
	 * Set constants
	 */
	protected function _setConstants()
	{
		$iaView = &$this->iaView;

		$languagesEnabled = (iaCore::ACCESS_FRONT == $this->getAccessType())
			? ($this->get('language_switch') && count($this->languages) > 1)
			: (count($this->languages) > 1);
		$baseUrl = trim($this->get('baseurl'), IA_URL_DELIMITER) . IA_URL_DELIMITER;

		define('IA_CANONICAL', preg_replace('/\?(.*)/', '', $baseUrl . ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER)));
		define('IA_CLEAR_URL', $baseUrl);
		define('IA_LANGUAGE', $iaView->language);
		define('IA_TEMPLATES', IA_HOME . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . 'templates' . IA_DS);
		define('IA_URL_LANG', $languagesEnabled ? $iaView->language . IA_URL_DELIMITER : '');
		define('IA_URL', IA_CLEAR_URL . IA_URL_LANG);
		define('IA_ADMIN_URL', IA_URL . $this->get('admin_page') . IA_URL_DELIMITER);
		define('IA_SELF', rtrim($iaView->domainUrl . IA_URL_LANG . implode(IA_URL_DELIMITER, $iaView->url), IA_URL_DELIMITER) . $iaView->get('extension'));

		$iaView->theme = $this->get((self::ACCESS_ADMIN == $this->getAccessType() ? 'admin_' : '') . 'tmpl', 'default');
		define('IA_TPL_URL', $iaView->assetsUrl . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin/' : '') . 'templates/' . $iaView->theme . IA_URL_DELIMITER);
	}

	private function _setTimezone($timezone)
	{
		date_default_timezone_set($timezone);

		// calculate an offset for DBMS
		$now = new DateTime();
		$minutes = $now->getOffset() / 60;
		$sign = ($minutes < 0 ? -1 : 1);
		$minutes = abs($minutes);
		$hours = floor($minutes / 60);
		$minutes -= $hours * 60;

		$offset = sprintf('%+d:%02d', $hours * $sign, $minutes);

		$this->iaDb->setTimezoneOffset($offset);
	}

	public function getSecurityToken()
	{
		return isset($_SESSION[self::SECURITY_TOKEN_MEMORY_KEY]) ? $_SESSION[self::SECURITY_TOKEN_MEMORY_KEY] : null;
	}
}