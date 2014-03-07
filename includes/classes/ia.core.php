<?php
//##copyright##

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

	const CLASSNAME_PREFIX = 'ia';

	const INTELLI = 'intelli';

	private static $_instance;

	protected static $_configDbTable = 'config';
	protected static $_configGroupsDbTable = 'config_groups';

	private $_classInstances = array(
		self::CORE => array(),
		self::ADMIN => array(),
		self::FRONT => array()
	);

	protected $_accessType = self::ACCESS_FRONT;

	protected $_hooks = array();
	protected $_config = array();
	protected $_customConfig = array();

	protected $_checkDomainValue;

	public $iaDb;
	public $iaView;

	public $packagesData = array();
	public $languages = array();
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
		$this->factory(array('sanitize', 'validate'));
		$this->iaDb = $this->factory('db');
		$this->factory(array('language', 'users'));
		$this->iaView = $this->factory('view');
		iaSystem::renderTime('<b>core</b> - Basic Classes Initialized');

		$this->getConfig();
		iaSystem::renderTime('<b>core</b> - Configuration Loaded');

		date_default_timezone_set($this->get('timezone'));
		setlocale(LC_ALL, $this->get('locale'));

		iaSystem::setDebugMode();

		$this->_parseUrl();

		$this->_retrieveHooks();
		iaSystem::renderTime('<b>core</b> - Hooks Loaded');

		$this->startHook('init');

		iaLanguage::load($this->iaView->language);

		$this->startHook('phpCoreBeforeAuth');

		$this->_authorize();
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
		$this->factory('cache')->createJsCache();

		if (self::ACCESS_FRONT == $this->getAccessType()
			&& iaView::REQUEST_HTML == $this->iaView->getRequestType()
			&& iaView::PAGE_ERROR != $this->iaView->name())
		{
			$this->factory('users')->registerVisitor();
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
				if (!$this->iaView->get('nodebug') && !$this->iaView->get('nocsrf'))
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

		$domain = $_SERVER['HTTP_HOST'];
		$requestPath = preg_replace('#^\/#', '', $_SERVER['REQUEST_URI']);

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

		$iaView->domain = $domain;
		$iaView->domainUrl = 'http://' . $domain . IA_URL_DELIMITER . FOLDER_URL;
		$iaView->language = $this->get('lang');
		$iaView->homePage = $this->get('home_page', iaView::DEFAULT_HOMEPAGE);

		define('IA_CLEAR_URL', $iaView->domainUrl);

		$doExit = false;
		$changeLang = false;

		if (isset($_GET['_p']))
		{
			$url = $_GET['_p'];
			unset($_GET['_p']);
		}
		else
		{
			if (!isset($_SERVER['REDIRECT_URL']) || $_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'])
			{
				$url = $_SERVER['REQUEST_URI'];
			}
			else
			{
				$url = $_SERVER['REDIRECT_URL'];
			}
			$url = substr($url, strlen(FOLDER) . IA_URL_DELIMITER);
		}

		$extension = IA_URL_DELIMITER;

		$url = array_shift(explode('?', $url));
		$url = explode(IA_URL_DELIMITER, trim($url, IA_URL_DELIMITER));

		$lastChunk = end($url);
		if ($pos = strrpos($lastChunk, '.'))
		{
			$extension = substr($lastChunk, $pos + 1);
			switch ($extension)
			{
				case 'json':
					$iaView->setRequestType(iaView::REQUEST_JSON);
					break;
				case 'xml':
					$iaView->setRequestType(iaView::REQUEST_XML);
			}

			$url = str_replace('.' . $extension, '', $url);
		}
		$iaView->set('extension', $extension);

		$this->startHook('phpCoreGetUrlBeforeParseUrl', array('url' => &$url));

		if (isset($_GET['_lang']) && isset($this->languages[$_GET['_lang']]))
		{
			$iaView->language = $_GET['_lang'];
			$changeLang = true;
		}

		$adminPanelUrl = $this->get('admin_page', 'admin');

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
				case ($adminPanelUrl == $value): // admin panel
					$this->_accessType = self::ACCESS_ADMIN;
					continue 2;
				case ('logout' == $value): // logging out
					$doExit = true;
					continue 2;
				case (2 == strlen($value)): // current language
					if (isset($this->languages[$value]))
					{
						$changeLang || $iaView->language = $value;
						continue 2;
					}
				default:
					$iaView->name($value);
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

		$iaView->url = $url;
		$this->requestPath = $array;

		define('IA_EXIT', $doExit);
		define('IA_TEMPLATES', IA_HOME . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . 'templates' . IA_DS);

		if (isset($_POST['_lang']) && isset($this->languages[$_POST['_lang']]))
		{
			$iaView->language = $_POST['_lang'];
		}

		$languagesEnabled = $this->get('language_switch') && count($this->languages) > 1;

		define('IA_URL_LANG', $languagesEnabled ? $iaView->language . IA_URL_DELIMITER : '');
		define('IA_URL', IA_CLEAR_URL . IA_URL_LANG);
		define('IA_LANGUAGE', $iaView->language);
		define('IA_SELF', rtrim($iaView->domainUrl . implode(IA_URL_DELIMITER, $iaView->url), IA_URL_DELIMITER) . $extension);
		define('IA_CANONICAL', preg_replace('/\?(.*)/', '', 'http://' . $iaView->domain . IA_URL_DELIMITER . ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER)));

		$iaView->theme = $this->get((self::ACCESS_ADMIN == $this->getAccessType() ? 'admin_' : '') . 'tmpl', 'default');

		define('IA_ADMIN_URL', IA_URL . $adminPanelUrl . IA_URL_DELIMITER);
		define('IA_TPL_URL', IA_CLEAR_URL . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin/' : '') . 'templates/' . $iaView->theme . IA_URL_DELIMITER);
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
				define('IA_PACKAGE_PATH', IA_PACKAGES . $extrasName . IA_DS);
				define('IA_PACKAGE_TEMPLATE', IA_PACKAGES . $extrasName . IA_DS . 'templates' . IA_DS);
				define('IA_PACKAGE_TEMPLATE_ADMIN', IA_PACKAGE_TEMPLATE . 'admin' . IA_DS);
				define('IA_PACKAGE_TEMPLATE_COMMON', IA_PACKAGE_TEMPLATE . 'common' . IA_DS);

				iaDebug::debug('<br>', null, 'info');
				iaDebug::debug(IA_PACKAGE_PATH, 'IA_PACKAGE_PATH', 'info');
				iaDebug::debug(IA_CURRENT_PACKAGE, 'IA_CURRENT_PACKAGE', 'info');
				iaDebug::debug(IA_PACKAGE_URL, 'IA_PACKAGE_URL', 'info');
				iaDebug::debug(IA_PACKAGE_TEMPLATE, 'IA_PACKAGE_TEMPLATE', 'info');
				iaDebug::debug(IA_PACKAGE_TEMPLATE_ADMIN, 'IA_PACKAGE_TEMPLATE_ADMIN', 'info');
				iaDebug::debug(IA_PACKAGE_TEMPLATE_COMMON, 'IA_PACKAGE_TEMPLATE_COMMON', 'info');

				$module = empty($fileName) ? iaView::DEFAULT_HOMEPAGE : $fileName;
				$module = IA_PACKAGES . $extrasName . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . $module . iaSystem::EXECUTABLE_FILE_EXT;

				file_exists($module) || $module = (self::ACCESS_ADMIN == $this->getAccessType() ? IA_ADMIN : IA_FRONT) . $fileName . iaSystem::EXECUTABLE_FILE_EXT;

				break;

			case 'plugin':
				define('IA_CURRENT_PLUGIN', $extrasName);
				define('IA_PLUGIN_TEMPLATE', IA_PLUGINS . $extrasName . IA_DS . 'templates' . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' : 'front') . IA_DS);

				iaDebug::debug('<br>', null, 'info');
				iaDebug::debug(IA_CURRENT_PLUGIN, 'IA_CURRENT_PLUGIN', 'info');
				iaDebug::debug(IA_PLUGIN_TEMPLATE, 'IA_PLUGIN_TEMPLATE', 'info');

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
		$object = (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin_' : '') . 'pages';
		$objectId = $this->iaView->get('name');
		if ($parent = $this->iaView->get('parent'))
		{
			$object .= '-' . $parent;
			$objectId = null;
		}

		$iaAcl = $this->factory('acl');
		$accessGranted = $iaAcl->checkAccess($object . iaAcl::SEPARATOR . $this->iaView->get('action'), 0, 0, $objectId);

		if (self::ACCESS_ADMIN == $this->getAccessType())
		{
			if (!iaUsers::hasIdentity())
			{
				iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
			}
			elseif (!$iaAcl->isAdmin() || !$accessGranted)
			{
				iaView::accessDenied();
			}
		}
		else
		{
/*			if (!$accessGranted)
			{
				iaView::accessDenied();
			}*/
		}
	}

	protected function _executeModule()
	{
		$module = $this->iaView->get('filename');

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
		iaDebug::debug($pageAction, 'Page Action', 'info');

		$this->startHook('phpCoreCodeBeforeStart');

		iaDebug::debug($this->iaView->get('filename'), 'Module', 'info');
		require $module;

		$this->startHook('phpCoreCodeAfterAll');
	}

	public function getConfig($reloadRequired = false)
	{
		if (empty($this->_config) || $reloadRequired)
		{
			$iaCache = $this->factory('cache');

			$this->_config = $iaCache->get('config', 604800, true);
			iaSystem::renderTime('<b>config</b> - Cached Configuration Loaded');

			if (empty($this->_config) || $reloadRequired)
			{
				$this->_config = $this->iaDb->keyvalue(array('name', 'value'), "`type` != 'divider'", self::getConfigTable());
				iaSystem::renderTime('<b>config</b> - Configuration Loaded from DB');

				$extras = $this->iaDb->onefield('name', "`status` = 'active'", null, null, 'extras');
				$extras[] = $this->_config['tmpl'];

				$this->_config['extras'] = $extras;

				$iaCache->write('config', $this->_config);
				iaSystem::renderTime('<b>config</b> - Configuration written to cache file');
			}

			$this->languages = unserialize($this->_config['languages']);
		}

		return $this->_config;
	}

	public function getCustomConfig($user = false, $group = false)
	{
		$where = array();
		$config = array();
		$local = false;

		if ($user === false && $group === false)
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

		if ($user !== false)
		{
			$where[] = "(`type` = 'user' AND `type_id` = $user) ";
		}
		if ($group !== false)
		{
			$where[] = "(`type` = 'group' AND `type_id` = $group) ";
		}
		$rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, implode(' OR ', $where), null, null, 'config_custom');
		if (empty($rows))
		{
			return $config;
		}

		$config['plan'] = array();
		$config['user'] = array();
		$config['group'] = array();
		foreach ($rows as $row)
		{
			$config[$row['type']][$row['name']] = $row['value'];
		}
		$config = array_merge($config['group'], $config['user'], $config['plan']);

		if ($local)
		{
			$this->_customConfig = $config;
		}

		return $config;
	}

	public function get($key, $default = '', $custom = true, $db = false)
	{
		if ($custom && isset($this->_customConfig[$key]))
		{
			return $this->_customConfig[$key];
		}

		$result = $default;

		if ($db)
		{
			if ($value = $this->iaDb->one_bind('`value`', '`name` = :key', array('key' => $key), self::getConfigTable()))
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

	public function set($key, $value, $db = false)
	{
		if ($db && !is_scalar($value))
		{
			trigger_error(__METHOD__ . '() Could not write a non-scalar value to the database.', E_USER_ERROR);
		}

		$result = true;
		$this->_config[$key] = $value;

		if ($db)
		{
			$result = (bool)$this->iaDb->update(array('value' => $value), "`name`='{$key}'", null, self::getConfigTable());

			$iaCache = $this->factory('cache');

			$iaCache->createJsCache(array('config'));
			$iaCache->remove('config.inc');
		}

		return $result;
	}

	private function _authorize()
	{
		$auth = false;
		$domains = array();
		$authorized = 0;

		if (isset($_POST['register']))
		{
			$login = '';
		}
		elseif (isset($_POST['username']))
		{
			$login = $_POST['username'];
			$authorized++;
		}
		else
		{
			$login = '';
		}

		$iaUsers = $this->factory('users');

		if (isset($_POST['register']))
		{
			$pass = '';
		}
		elseif (isset($_POST['password']))
		{
			$pass = $iaUsers->encodePassword($_POST['password']);
			$authorized++;
		}
		else
		{
			$pass = '';
		}

		$isBackend = (self::ACCESS_ADMIN == $this->getAccessType());

		if (IA_EXIT && $authorized != 2)
		{
			// use this hook to logout
			$this->startHook('phpUserLogout');

			iaUsers::clearIdentity();

			unset($_SESSION['_achkych']);
			if (strpos($_SERVER['HTTP_REFERER'], $this->iaView->domainUrl) === 0)
			{
				if ($isBackend)
				{
					$_SESSION['IA_EXIT'] = true;
				}
				$url = $isBackend ? IA_ADMIN_URL : IA_URL;
				header('Location: ' . $url);
			}
			else
			{
				header('Location: ' . $this->iaView->domainUrl . ($isBackend ? $this->get('admin_page') . IA_URL_DELIMITER : ''));
			}
			exit();
		}
		elseif ($authorized == 2 && $login && $pass)
		{
			$auth = (bool)$iaUsers->getAuth(0, $login, $pass);
			if (!$auth)
			{
				if ($isBackend)
				{
					$this->iaView->assign('error_login', true);
				}
				else
				{
					$this->iaView->setMessages(iaLanguage::get('error_login'), iaView::ERROR);
					$this->iaView->name('login');
				}
			}
			else
			{
				unset($_SESSION['_achkych']);
				if (isset($_SESSION['referrer'])) // this variable is set by Login page handler
				{
					header('Location: ' . $_SESSION['referrer']);
					unset($_SESSION['referrer']);
					exit();
				}
				else
				{
					if ($isBackend)
					{
						$this->factory('log')->write(iaLog::ACTION_LOGIN, array('ip' => $this->util()->getIp(false)));
					}
				}
			}
		}
		elseif ($authorized == 2)
		{
			if ($isBackend)
			{
				$this->iaView->assign('empty_login', true);
			}
			else
			{
				$this->iaView->setMessages(iaLanguage::get('empty_login'), iaView::ERROR);
				$this->iaView->name('login');
			}
		}
		elseif (iaUsers::hasIdentity())
		{
			$auth = (bool)iaUsers::getIdentity(true);
		}

		if (!isset($_SESSION['_achkych']) && $isBackend && $auth)
		{
			$msg	= 'L' . 'ic' . 'en' . 'se nee' . 'ded!';
			$login	= '';
			$_host = $this->iaView->domain;
			if (strpos($_host, ':'))
			{
				$_host = substr($_host, 0, strpos($_host, ':'));
			}
			if (0 === strpos($_host, 'www.'))
			{
				$_host = substr($_host, 4);
			}
			$jnm = (false !== strpos($_host, '.'));

			if ($jnm)
			{
				$auth = true;
				if (!in_array(str_rot13($_host), $domains) && !in_array(str_rot13('www.' . $_host), $domains))
				{
					$auth = false;
					$sbr_rmt_host = 'h' . 't' . 't'
						. 'p' . ':' . '//t'	. 'oo'
						. 'ls.s' . 'ub' . 'ri' . 'on.c' . 'om/p' . 'in' . 'g.p' . 'hp'
						. '?g' . 'name=' . ${'lo' . 'g' . 'in'} . '&do' . 'main=' . $_host;
					$iaUtil = self::util();
					$rmt_rst = $iaUtil->getPageContent($sbr_rmt_host);
					if (false !== $rmt_rst)
					{
						$auth = true;
						$sbr_rmt_rst = unserialize($rmt_rst);
						if (!$sbr_rmt_rst['passed'])
						{
							$auth = false;
							$msg = $sbr_rmt_rst['msg'];
						}
					}
				}
			}

			if (!$jnm)
			{
				$auth = true;
			}

			$auth = true;

			if ($auth)
			{
				$_SESSION['_achkych'] = $auth;
			}
			else
			{
				return iaView::accessDenied($msg);
			}
		}
	}

	public function getHooks()
	{
		return $this->_hooks;
	}

	protected function _retrieveHooks()
	{
		$columns = array('name', 'code', 'type', 'extras', 'filename', 'pages');
		$stmt = "`extras` IN('', '" . implode("','", $this->get('extras')) . "') AND `status` = :status AND `page_type` IN ('both', :type) ORDER BY `order`";
		$this->iaDb->bind($stmt, array(
			'status' => iaCore::STATUS_ACTIVE,
			'type' => (self::ACCESS_FRONT == $this->getAccessType()) ? 'front' : 'admin'
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
		// FIXME: this implementation provides a basic (!) forgery protection only.
		// Referrer info could be faked easily
		if ($this->get('prevent_csrf') && $_POST)
		{
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
					return;
				}
			}

			header('HTTP/1.1 203'); // reply with 203 "Non-Authoritative Information" status

			$this->iaView->set('nodebug', true);
			die('Request treated as potential CSRF attack.');
		}

		/* PREVIOUS IMPLEMENTATION VIA POOL OF SESSION VARIABLES **
		if ($_SERVER['REQUEST_METHOD'] == 'POST' && $this->get('prevent_csrf', false))
		{
			if (INTELLI_QDEBUG || ($this->iaView->name() == 'manage' && isset($this->requestPath[0]) && $this->requestPath[0] == 'pages'))
			{
				iaDebug::debug('preventCsrf disabled', 'Information for debug', 'info');
			}
			else
			{
			 if (
				 (
				 	// if not exists start post protection
				 	!isset($_SESSION['prevent_csrf']) || empty($_SESSION['prevent_csrf'])
				 	// if not exists prevent_csrf in post values
				 	|| !isset($_POST['prevent_csrf']) || empty($_POST['prevent_csrf'])
				 	// if prevent_csrf not exists in session
				 	|| !in_array($_POST['prevent_csrf'], $_SESSION['prevent_csrf'], true)
				 ) && (!isset($_SERVER['HTTP_X_FLAGTOPREVENTCSRF']) && IN_ADMIN || IN_FRONT)
			 )
			 {
			 	unset($_SESSION['prevent_csrf']);
			 	unset($_POST);

					$this->iaView->csrfAttack();
			 }
			 elseif (isset($_SESSION['prevent_csrf']) && isset($_POST['prevent_csrf']))
			 {
			 	unset($_SESSION['prevent_csrf'][array_search($_POST['prevent_csrf'], $_SESSION['prevent_csrf'])]);
			 }
			}
		}
		*/
	}

	public function checkDomain()
	{
		if (is_null($this->_checkDomainValue))
		{
			$baseUrl = $this->get('baseurl', $this->iaView->domainUrl);

			$dbUrl = str_replace(array('http://www.', 'http://'), '', $baseUrl);
			$codeUrl = str_replace(array('http://www.', 'http://'), '', $this->iaView->domainUrl);

			$this->_checkDomainValue = ($dbUrl == $codeUrl);
		}

		return $this->_checkDomainValue;
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

		$this->packagesData = $packages;

		if (iaView::REQUEST_HTML == $this->iaView->getRequestType())
		{
			$this->iaView->assign('packages', $packages);
		}

		return $packages;
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
		if (empty($name) || !isset($this->_hooks[$name]))
		{
			return false;
		}

		iaSystem::renderTime('<b>hook</b> - ' . $name);

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
					if ($hook['filename'] && file_exists(IA_HOME . $hook['filename']))
					{
						include IA_HOME . $hook['filename'];
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
			if (isset($this->_classInstances[$type][$className]))
			{
				$result = $this->_classInstances[$type][$className];
			}
			else
			{
				iaSystem::renderTime('<b>class</b> - beforeAddClass ' . $className);
				$this->startHook('phpCoreFactoryBeforeLoadClass' . ucfirst(strtolower($name)));
				$fileSize = $this->loadClass($type, (strtolower($name) == 'db') ? INTELLI_CONNECT : $name);
				if (false === $fileSize)
				{
					return false;
				}

				iaDebug::debug('ia.' . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

				$result = new $className();
				$result->init();

				$this->_classInstances[$type][$className] = $result;
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

		if (!isset($this->_classInstances[$type][$class]))
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

			$this->_classInstances[$type][$class] = new $class();
			$this->_classInstances[$type][$class]->init();
		}

		return $this->_classInstances[$type][$class];
	}

	public function factoryPlugin($plugin, $type = self::FRONT, $name = null)
	{
		if (empty($name))
		{
			$name = $plugin;
		}
		$class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));

		if (!isset($this->_classInstances[$type][$class]))
		{
			$fileSize = $this->loadClass($type, $name, $plugin);
			if (false === $fileSize)
			{
				return false;
			}
			iaDebug::debug('<b>plugin:</b> ia.' . $type . '.' . $name . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');
			$this->_classInstances[$type][$class] = new $class();
			$this->_classInstances[$type][$class]->init();
		}

		return $this->_classInstances[$type][$class];
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

	public static function getConfigTable()
	{
		return self::$_configDbTable;
	}

	public static function getConfigGroupsTable()
	{
		return self::$_configGroupsDbTable;
	}
}