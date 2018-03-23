<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

    private $classInstances = [];

    protected $accessType = self::ACCESS_FRONT;

    protected $hooks = [];
    protected $config = [];
    protected $customConfig;

    protected $checkDomain;

    protected $baseUrl = '';

    public $domain = '';

    public $iaDb;
    public $iaView;
    public $iaCache;

    public $currencies = [];
    public $currency = null;

    public $languages = [];
    public $language = null;

    public $modulesData = [];
    public $requestPath = [];


    protected function __construct()
    {
    }
    protected function __clone()
    {
    }

    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    public function init()
    {
        $this->iaDb = $this->factory('db');
        $this->factory(['sanitize', 'validate', 'language', 'users']);
        $this->iaView = $this->factory('view');
        $this->iaCache = $this->factory('cache');
        iaSystem::renderTime('core', 'Basic Classes Initialized');

        $this->_parseUrl();

        $this->getConfig();
        iaSystem::renderTime('core', 'Configuration Loaded');

        $this->_fetchCurrencies();

        iaSystem::setDebugMode();

        // we can only load strings when we know if a specific language is requested based on URL
        iaLanguage::load($this->iaView->language);

        $this->_fetchHooks();
        iaSystem::renderTime('core', 'Hooks Loaded');

        $this->_setConstants();
        $this->startHook('phpCoreUrlRewrite');

        $this->startHook('init');

        // authorize user
        $iaUsers = $this->factory('users');
        $iaUsers->authorize();

        $this->getCustomConfig();

        $this->startHook('phpCoreBeforePageDefine');

        $this->iaView->definePage();
        $this->iaView->loadSmarty();

        $this->_forgeryCheck();

        $this->startHook('bootstrap');

        $this->_defineModule();
        $this->iaView->defineOutput();
        $this->_checkPermissions();
        $this->_executeModule();

        $this->startHook('phpCoreBeforeJsCache');
        $this->iaCache->createJsCache();

        if (self::ACCESS_FRONT == $this->getAccessType()
            && iaView::REQUEST_HTML == $this->iaView->getRequestType()
            && iaView::PAGE_ERROR != $this->iaView->name()) {
            $iaUsers->registerVisitor();
        }

        $this->startHook('phpCoreBeforePageDisplay');
        $this->iaView->output();

        $this->startHook('finalize');
    }

    public function __destruct()
    {
        if ((defined('INTELLI_DEBUG') && INTELLI_DEBUG)
            || (defined('INTELLI_QDEBUG') && INTELLI_QDEBUG)) { // output the debug info if enabled
            if (is_object($this->iaView) && iaView::REQUEST_HTML == $this->iaView->getRequestType()) {
                if (!$this->iaView->get('nodebug')) {
                    new iaDebug();
                }
            }
        }
    }

    public function getAccessType()
    {
        return $this->accessType;
    }

    protected function _parseUrl()
    {
        if (isset($_GET['_p'])) {
            $url = $_GET['_p'];
            unset($_GET['_p']);
        } else {
            $url = (!isset($_SERVER['REDIRECT_URL']) || $_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'])
                ? $_SERVER['REQUEST_URI']
                : $_SERVER['REDIRECT_URL'];
            $url = substr($url, strlen(FOLDER) + 1);
        }

        $url = explode('?', $url);
        $url = array_shift($url);
        $url = explode(IA_URL_DELIMITER, iaSanitize::htmlInjectionFilter(trim($url, IA_URL_DELIMITER)));

        $iaView = &$this->iaView;

        $lastChunk = end($url);
        $extension = IA_URL_DELIMITER;
        if ($pos = strrpos($lastChunk, '.')) {
            $extension = substr($lastChunk, $pos + 1);
            switch ($extension) {
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

        $params = $this->factory('config')->fetchKeyValue("name IN ('baseurl', 'admin_page', 'home_page', 'lang')");

        $iaView->language = $params['lang'];

        $isSystemChunk = true;
        $requestPath = [];
        foreach ($url as $value) {
            if (!$isSystemChunk) {
                $requestPath[] = $value;
                continue;
            }

            switch (true) {
                case ($params['admin_page'] == $value): // admin panel
                    $this->accessType = self::ACCESS_ADMIN;
                    continue 2;
                case ('logout' == $value): // logging out
                    define('IA_EXIT', true);
                    continue 2;
                case (2 == strlen($value)): // current language
                    if (isset($this->languages[$value])) {
                        $iaView->language = $value;
                        array_shift($url); // #1715
                        continue 2;
                    }
                default:
                    $iaView->name(empty($value) && 1 == count($url) ? $params['home_page'] : $value);
                    $isSystemChunk = false;
            }
        }

        $this->baseUrl = trim($params['baseurl'], IA_URL_DELIMITER) . IA_URL_DELIMITER;
        $this->requestPath = $requestPath;
        $this->language = $this->languages[$iaView->language];

        $this->domain = preg_replace('#[^a-z_0-9-.:]#i', '', $_SERVER['HTTP_HOST']);
        $setupHost = parse_url($this->baseUrl, PHP_URL_HOST);
        if ($this->domain != $setupHost) {
            // handling cases when script is running on domain differ from configured
            switch ($this->getAccessType()) {
                case self::ACCESS_FRONT:
                    // on frontend, just redirect to the same URL on the valid domain
                    $this->factory('util')->go_to($this->baseUrl . ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER));
                case self::ACCESS_ADMIN:
                    // on backend, make possible to open Admin Panel from every hostname possible
                    $this->baseUrl = str_replace($setupHost, $this->domain, $this->baseUrl);
            }
        }

        if (isset($_POST['_lang']) && isset($this->languages[$_POST['_lang']])) {
            $iaView->language = $_POST['_lang'];
        }

        if (self::ACCESS_ADMIN == $this->getAccessType()) {
            if ($isSystemChunk && $params['home_page'] == $iaView->name()) {
                $iaView->name(iaView::DEFAULT_HOMEPAGE);
            }
        }

        $iaView->url = empty($url[0]) ? [] : $url;
        $iaView->assetsUrl = '//' . $this->domain . IA_URL_DELIMITER . FOLDER_URL;
        $iaView->domainUrl = 'http' . (isset($_SERVER['HTTPS']) && 'off' != $_SERVER['HTTPS'] ? 's' : '') . ':' . $iaView->assetsUrl;

        if (isset($_SERVER['HTTP_CF_VISITOR'])) {
            $visitor = json_decode($_SERVER['HTTP_CF_VISITOR']);
            if (isset($visitor->scheme) && 'https' == $visitor->scheme) {
                $iaView->domainUrl = 'https:' . $iaView->assetsUrl;
            }
        }
    }

    protected function _defineModule()
    {
        $iaView = &$this->iaView;

        $moduleName = $iaView->get('module');
        $fileName = $iaView->get('filename');

        if (in_array($iaView->get('type'), ['plugin', 'package'])) {
            define('IA_CURRENT_MODULE', $moduleName);
            define('IA_MODULE_URL', ($iaView->packageUrl ? $iaView->packageUrl . IA_URL_LANG : $iaView->domainUrl . IA_URL_LANG . $iaView->extrasUrl));
            define('IA_MODULE_TEMPLATE', IA_MODULES . $moduleName . '/templates/' . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' : 'front') . IA_DS);

            $module = empty($fileName) ? iaView::DEFAULT_HOMEPAGE : $fileName;
            $module = IA_MODULES . $moduleName . IA_DS . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . $module . iaSystem::EXECUTABLE_FILE_EXT;

            file_exists($module) || $module = (self::ACCESS_ADMIN == $this->getAccessType() ? IA_ADMIN : IA_FRONT) . $fileName . iaSystem::EXECUTABLE_FILE_EXT;
        } else {
            $module = empty($fileName) ? $iaView->name() : $fileName;
            $module = (self::ACCESS_ADMIN == $this->getAccessType() ? IA_ADMIN : IA_FRONT) . $module . iaSystem::EXECUTABLE_FILE_EXT;
        }

        $iaView->set('filename', $module);
    }

    protected function _checkPermissions()
    {
        $iaAcl = $this->factory('acl');

        if (self::ACCESS_ADMIN == $this->getAccessType()) {
            if (!iaUsers::hasIdentity()) {
                iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
            } elseif (!$iaAcl->isAdmin()) {
                iaView::accessDenied();
            }
        } elseif (iaView::PAGE_ERROR == $this->iaView->name()) {
            return;
        }

        $iaAcl->isAccessible($this->iaView->get('name'), $this->iaView->get('action')) || iaView::accessDenied();
    }

    protected function _executeModule()
    {
        $module = $this->iaView->get('filename');

        if (empty($module)) {
            return;
        }

        if (!file_exists($module)) {
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
        if (self::ACCESS_ADMIN == $this->getAccessType()) {
            class_exists('iaBackendController') && (new iaBackendController())->process();
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
        if (empty($this->config) || $reloadRequired) {
            $key = 'config_' . $this->iaView->language;

            $this->config = $this->iaCache->get($key, 604800, true);
            iaSystem::renderTime('config', 'Cached Configuration Loaded');

            if (empty($this->config) || $reloadRequired) {
                $this->config = $this->factory('config')->fetchKeyValue();
                iaSystem::renderTime('config', 'Configuration loaded from DB');

                $extras = $this->iaDb->onefield('name', iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), null, null, 'modules');
                $extras[] = $this->config['tmpl'];

                $this->config['module'] = $extras;
                $this->config['block_positions'] = $this->iaView->positions;

                $this->iaCache->write($key, $this->config);
                iaSystem::renderTime('config', 'Configuration written to cache file');
            }

            $this->_setTimezone($this->get('timezone'));
            setlocale(LC_TIME, $this->language['locale']);
            setlocale(LC_COLLATE, $this->language['locale']);

            // set dynamic config
            $this->set('datetime_format', trim($this->language['date_format'] . ' ' . $this->language['time_format']));
            $this->set('date_format', $this->language['date_format']);
            $this->set('time_format', $this->language['time_format']);
            $this->set('locale', $this->language['locale']);
        }

        return $this->config;
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

        if (is_null($user) && is_null($group)) {
            $this->factory('users');

            $local = true;
            if (iaUsers::hasIdentity()) {
                $user = iaUsers::getIdentity()->id;
                $group = iaUsers::getIdentity()->usergroup_id;
            } else {
                $user = 0;
                $group = iaUsers::MEMBERSHIP_GUEST;
            }
        }

        if ($local && !is_null($this->customConfig)) {
            return $this->customConfig;
        }

        $result = [];
        foreach ($this->factory('config')->fetchCustom($user, $group) as $key => $value) {
            if (is_array($value)) {
                $value = $value[$this->language['iso']];
            }
            $result[$key] = $value;
        }

        if ($local) {
            $this->customConfig = $result;
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
        if ($custom && isset($this->customConfig[$key])) {
            return $this->customConfig[$key];
        }

        $result = $default;

        if ($db) {
            $value = $this->factory('config')->get($key);
            if (false !== $value) {
                $result = $value;
            }
        } else {
            if (isset($this->config[$key])) {
                return $this->config[$key];
            }
        }

        $this->config[$key] = $result;

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
        if ($permanent && !is_scalar($value)) {
            trigger_error(__METHOD__ . '() Could not write a non-scalar value to the database.', E_USER_ERROR);
        }

        $result = true;
        $this->config[$key] = $value;

        if ($permanent) {
            $result = $this->factory('config')->set($key, $value);

            $this->iaCache->clearConfigCache();
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
        return $this->hooks;
    }

    /**
     * Set the list of available hooks
     */
    protected function _fetchHooks()
    {
        $columns = ['name', 'code', 'type', 'module', 'filename', 'pages'];
        $stmt = "`module` IN('', '" . implode("','", $this->get('module')) . "') AND `status` = :status AND `page_type` IN ('both', :type) ORDER BY `order`";
        $this->iaDb->bind($stmt, [
            'status' => iaCore::STATUS_ACTIVE,
            'type' => (self::ACCESS_FRONT == $this->getAccessType()) ? iaCore::FRONT : iaCore::ADMIN
        ]);

        if ($rows = $this->iaDb->all($columns, $stmt, null, null, 'hooks')) {
            foreach ($rows as $row) {
                $this->hooks[$row['name']][$row['module']] = [
                    'pages' => empty($row['pages']) ? [] : explode(',', $row['pages']),
                    'code' => $row['code'],
                    'type' => $row['type'],
                    'filename' => $row['filename']
                ];
            }
        }
    }

    protected function _forgeryCheck()
    {
        if (isset($_POST[self::SECURITY_TOKEN_FORM_KEY])) {
            $tokenValue = $_POST[self::SECURITY_TOKEN_FORM_KEY];
            unset($_POST[self::SECURITY_TOKEN_FORM_KEY]);
        }

        if (!$_POST || !$this->get('prevent_csrf')) {
            return;
        }


        $exceptions = [
            self::ACCESS_FRONT => ['api'],
            self::ACCESS_ADMIN => ['adminer']
        ];

        if (in_array($this->iaView->name(), $exceptions[$this->getAccessType()])
            || ('ipn' == $this->iaView->url[0] && count($this->iaView->url) > 1)) {
            return;
        }


        $tokenValid = isset($tokenValue) && $tokenValue === $this->getSecurityToken();
        $referrerValid = true;

        if (isset($_SERVER['HTTP_REFERER'])) {
            $wwwChunk = 'www.';

            $referrerDomain = explode(IA_URL_DELIMITER, $_SERVER['HTTP_REFERER']);
            $referrerDomain = strtolower($referrerDomain[2]);
            $referrerDomain = str_replace($wwwChunk, '', $referrerDomain);

            $domain = explode(IA_URL_DELIMITER, $this->get('baseurl'));
            $domain = strtolower($domain[2]);
            $domain = str_replace($wwwChunk, '', $domain);

            if ($referrerDomain === $domain) {
                $referrerValid = true;
            }
        }

        if (!$referrerValid || !$tokenValid) {
            header('HTTP/1.1 203'); // reply with 203 "Non-Authoritative Information" status

            $contentType = 'text/html';
            $message = 'Request treated as a potential CSRF attack.';

            switch ($this->iaView->getRequestType()) {
                case iaView::REQUEST_JSON:
                    $contentType = 'application/json';

                    $output = json_encode(['result' => false, 'message' => $message]);

                    break;

                case iaView::REQUEST_XML:
                    $contentType = 'text/xml';

                    $xmlObject = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>');
                    $xmlObject->addChild('result', false);
                    $xmlObject->addChild('message', $message);

                    $output = $xmlObject->asXML();

                    break;

                default:
                    $output = $message;
            }

            $this->iaView->set('nodebug', true);

            header('Content-Type: ' . $contentType);
            die($output);
        }
    }

    public function checkDomain()
    {
        if (is_null($this->checkDomain)) {
            $dbUrl = str_replace(['http://www.', 'http://'], '', $this->get('baseurl'));
            $codeUrl = str_replace(['http://www.', 'http://'], '', $this->iaView->domainUrl);

            $this->checkDomain = ($dbUrl == $codeUrl);
        }

        return $this->checkDomain;
    }

    public function setPackagesData($regenerate = false)
    {
        if ($this->modulesData && !$regenerate) {
            return $this->modulesData;
        }

        $rows = $this->iaDb->all(['name', 'url', 'title'], "`type` = 'package' AND `status` = 'active'", null, null, 'modules');

        $modules = [];
        foreach ($rows as $entry) {
            $entry['url'] = (parse_url($entry['url'], PHP_URL_SCHEME) ? '' : IA_URL)
                . ($entry['url'] == IA_URL_DELIMITER ? '' : $entry['url']);
            $entry['tpl_url'] = IA_CLEAR_URL . 'modules' . IA_URL_DELIMITER . $entry['name'] . IA_URL_DELIMITER . 'templates' . IA_URL_DELIMITER;
            $modules[$entry['name']] = $entry;
        }

        return $this->modulesData = $modules;
    }

    public function getModules($module)
    {
        $rows = $this->iaDb->row_bind(
            ['name', 'url', 'title'],
            '`status` = :status AND `name` = :package',
            ['status' => iaCore::STATUS_ACTIVE, 'package' => $module],
            'modules'
        );

        return $rows;
    }

    public static function util()
    {
        return self::instance()->factory('util');
    }

    public function startHook($name, array $params = [])
    {
        if (empty($name)) {
            return false;
        }

        iaDebug::debug('php', $name, 'hooks');

        if (!isset($this->hooks[$name])) {
            return false;
        }

        iaSystem::renderTime('hook', $name);

        if (count($this->hooks[$name]) > 0) {
            $variablesList = array_keys($params);
            extract($params, EXTR_REFS | EXTR_SKIP);

            $iaCore = &$this;
            $iaView = &$this->iaView;
            $iaDb = &$this->iaDb;

            foreach ($this->hooks[$name] as $extras => $hook) {
                if ('php' == $hook['type']
                    && (empty($hook['pages']) || in_array($iaView->name(), $hook['pages']))) {
                    if ($hook['filename']) {
                        if (file_exists(IA_HOME . $hook['filename'])) {
                            include IA_HOME . $hook['filename'];
                        } else {
                            $message = sprintf('Can\'t start hook "%s". File does not exist: %s', $name, $hook['filename']);
                            iaDebug::debug($message, null, 'error');
                        }
                    } else {
                        iaSystem::renderTime('START TIME ' . $name . ' ' . $extras);
                        if (iaSystem::phpSyntaxCheck($hook['code'])) {
                            eval($hook['code']);
                        } else {
                            iaDebug::debug(['name' => $name, 'code' => '<textarea style="width:80%;height:100px;">' . $hook['code'] . '</textarea>'], '<b style="color:red;">Syntax error in hook "' . $name . '" of "' . $extras . '"</b>', 'error');
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

    public function factory($className, $type = self::CORE)
    {
        if (is_array($className)) {
            $result = [];
            foreach ($className as $class)
                $result[] = $this->factory($class, $type);

            return $result;
        }

        return $this->factoryClass($className, $type);
    }

    public function factoryItem($itemName, $type = null)
    {
        if ($itemName == iaUsers::getItemName()) {
            return $this->factory('users');
        }

        return $this->factory('item')->factory($itemName, $type);
    }

    public function factoryModule($name, $module, $type = null)
    {
        $path = IA_MODULES . $module . '/includes/classes/';

        // TODO: review
        $moduleInterface = IA_MODULES . $module . '/includes/classes/ia.base.module' . iaSystem::EXECUTABLE_FILE_EXT;
        if (is_file($moduleInterface)) {
            require_once $moduleInterface;
        }
        //

        return $this->factoryClass($name, $type, $path);
    }

    public function factoryClass($name, $type = null, $path = null)
    {
        $name = strtolower($name);

        if (is_null($type)) {
            $type = iaCore::ACCESS_FRONT == $this->getAccessType() ? iaCore::FRONT : iaCore::ADMIN;
        }

        $className = str_replace(' ', '', // 'camelize' class name
            ucwords(str_replace('_', ' ', $name)));
        $className = self::CLASSNAME_PREFIX . ucfirst($className);

        if (isset($this->classInstances[$className])) {
            return $this->classInstances[$className];
        }

        iaSystem::renderTime('class', 'Loading class ' . $className);

        if ('db' == $name) { // can't we get rid of this?
            $name = INTELLI_CONNECT;
        }

        $fileName = iaSystem::CLASSES_PREFIX . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT;
        $filePath = (is_null($path) ? IA_CLASSES : $path) . $fileName;

        if (file_exists($filePath)) {
            require_once $filePath;

            $instance = $this->classInstances[$className] = new $className();
            $instance->init();

            return $instance;
        }

        return false;
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

        define('IA_CANONICAL', preg_replace('/\?(.*)/', '', $this->baseUrl . ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER)));
        define('IA_CLEAR_URL', $this->baseUrl);
        define('IA_LANGUAGE', $iaView->language);
        define('IA_TEMPLATES', IA_HOME . (self::ACCESS_ADMIN == $this->getAccessType() ? 'admin' . IA_DS : '') . 'templates/');
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
        if (!isset($_SESSION[self::SECURITY_TOKEN_MEMORY_KEY])) {
            $_SESSION[self::SECURITY_TOKEN_MEMORY_KEY] = $this->_generateSecurityToken(40);
        }

        return $_SESSION[self::SECURITY_TOKEN_MEMORY_KEY];
    }

    private function _generateSecurityToken($length)
    {
        $result = null;

        if (function_exists('openssl_random_pseudo_bytes')) {
            $result = openssl_random_pseudo_bytes($length * 2);

            if (false !== $result) {
                $result = substr(str_replace(['/', '+', '='], '', base64_encode($result)), 0, $length);
            }
        }

        // fallback to built-in method if cryptographic solution is unavailable
        if (!$result) {
            iaDebug::debug('Unable to generate strong security token', 'Notice');
            $result = iaUtil::generateToken($length);
        }

        return $result;
    }

    protected function _fetchCurrencies()
    {
        $iaCurrency = $this->factory('currency');

        $this->currencies = $iaCurrency->fetch();
        $this->currency = $iaCurrency->get();

        $this->config['currency'] = $this->currency['code']; // compatibility fallback
    }
}
