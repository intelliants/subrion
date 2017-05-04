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

    private $_classInstances = [];

    protected static $_configDbTable = 'config';
    protected static $_configGroupsDbTable = 'config_groups';
    protected static $_customConfigDbTable = 'config_custom';

    protected $_accessType = self::ACCESS_FRONT;

    protected $_hooks = [];
    protected $_config = [];
    protected $_customConfig;

    protected $_checkDomain;

    public $iaDb;
    public $iaView;
    public $iaCache;

    public $languages = [];
    public $language = [];

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
            && iaView::PAGE_ERROR != $this->iaView->name()) {
            $iaUsers->registerVisitor();
        }

        $this->startHook('phpCoreBeforePageDisplay');
        $this->iaView->output();

        $this->startHook('finalize');
    }

    public function __destruct()
    {
        if (INTELLI_DEBUG || INTELLI_QDEBUG) { // output the debug info if enabled
            if (is_object($this->iaView) && iaView::REQUEST_HTML == $this->iaView->getRequestType()) {
                if (!$this->iaView->get('nodebug')) {
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

        $params = $this->iaDb->keyvalue(['name', 'value'],
            "`name` IN('baseurl', 'admin_page', 'home_page', 'lang')", self::getConfigTable());

        $domain = preg_replace('#[^a-z_0-9-.:]#i', '', $_SERVER['HTTP_HOST']);
        $requestPath = ltrim($_SERVER['REQUEST_URI'], IA_URL_DELIMITER);

        if (!preg_match('#^www\.#', $domain) && preg_match('#:\/\/www\.#', $params['baseurl'])) {
            $domain = preg_replace('#^#', 'www.', $domain);
            $this->factory('util')->go_to('http://' . $domain . IA_URL_DELIMITER . $requestPath);
        } elseif (preg_match('#^www\.#', $domain) && !preg_match('#:\/\/www\.#', $params['baseurl'])) {
            $domain = preg_replace('#^www\.#', '', $domain);
            $this->factory('util')->go_to('http://' . $domain . IA_URL_DELIMITER . $requestPath);
        }

        $iaView->assetsUrl = '//' . $domain . IA_URL_DELIMITER . FOLDER_URL;
        $iaView->domain = $domain;
        $iaView->domainUrl = 'http' . (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS'] ? 's' : '') . ':' . $iaView->assetsUrl;
        $iaView->language = $params['lang'];

        $doExit = false;
        $changeLang = false;

        if (isset($_GET['_p'])) {
            $url = $_GET['_p'];
            unset($_GET['_p']);
        } else {
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

        if (isset($_POST['_lang']) && isset($this->languages[$_POST['_lang']])) {
            $iaView->language = $_POST['_lang'];
            $changeLang = true;
        }

        $isSystemChunk = true;
        $array = [];
        foreach ($url as $value) {
            if (!$isSystemChunk) {
                $array[] = $value;
                continue;
            }

            switch (true) {
                case ($params['admin_page'] == $value): // admin panel
                    $this->_accessType = self::ACCESS_ADMIN;
                    continue 2;
                case ('logout' == $value): // logging out
                    $doExit = true;
                    continue 2;
                case (2 == strlen($value)): // current language
                    if (isset($this->languages[$value])) {
                        $changeLang || $iaView->language = $value;
                        array_shift($url); // #1715
                        continue 2;
                    }
                default:
                    $iaView->name(empty($value) && 1 == count($url) ? $params['home_page'] : $value);
                    $isSystemChunk = false;
            }
        }

        if (self::ACCESS_ADMIN == $this->getAccessType()) {
            if ($isSystemChunk && $params['home_page'] == $iaView->name()) {
                $iaView->name(iaView::DEFAULT_HOMEPAGE);
            }
        }

        $iaView->url = empty($url[0]) ? [] : $url;
        $this->requestPath = $array;

        // set system language
        $this->language = $this->languages[$this->iaView->language];

        define('IA_EXIT', $doExit);
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
        if (empty($this->_config) || $reloadRequired) {
            $key = 'config_' . $this->iaView->language;

            $this->_config = $this->iaCache->get($key, 604800, true);
            iaSystem::renderTime('config', 'Cached Configuration Loaded');

            if (empty($this->_config) || $reloadRequired) {
                $this->_config = $this->fetchConfig();
                iaSystem::renderTime('config', 'Configuration loaded from DB');

                $extras = $this->iaDb->onefield('name', "`status` = 'active'", null, null, 'modules');
                $extras[] = $this->_config['tmpl'];

                $this->_config['module'] = $extras;
                $this->_config['block_positions'] = $this->iaView->positions;

                $this->iaCache->write($key, $this->_config);
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

        if ($local && !is_null($this->_customConfig)) {
            return $this->_customConfig;
        }

        $result = [];
        $stmt = [];

        if ($user) {
            $stmt[] = "(cc.`type` = 'user' AND cc.`type_id` = $user) ";
        }
        if ($group) {
            $stmt[] = "(cc.`type` = 'group' AND cc.`type_id` = $group) ";
        }

        $sql = <<<SQL
SELECT 
  cc.`name`, cc.`value`, cc.`type`,
  c.`type` `config_type`, c.`options` `config_options`
FROM `:prefix:table_config` c
LEFT JOIN `:prefix:table_custom_config` cc ON (c.`name` = cc.`name`)
WHERE :where
SQL;
        $sql = iaDb::printf($sql, ['prefix' => $this->iaDb->prefix, 'table_config' => self::getConfigTable(),
            'table_custom_config' => self::getCustomConfigTable(), 'where' => implode(' OR ', $stmt)]);
        $rows = $this->iaDb->getAll($sql);

        if (empty($rows)) {
            return $result;
        }

        $result = ['group' => [], 'user' => [], 'plan' => []];

        $currentLangCode = $this->iaView->language;
        foreach ($rows as $row) {
            $value = $row['value'];

            if ('text' == $row['config_type'] || 'textarea' == $row['config_type']) {
                $options = empty($row['config_options']) ? [] : json_decode($row['config_options'], true);

                if (isset($options['multilingual']) && $options['multilingual']) {
                    $value = preg_match('#\{\:' . $currentLangCode . '\:\}(.*?)(?:$|\{\:[a-z]{2}\:\})#s', $value, $matches)
                        ? $matches[1]
                        : '';
                }
            }

            $result[$row['type']][$row['name']] = $value;
        }

        $result = array_merge($result['group'], $result['user'], $result['plan']);

        if ($local) {
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
        if ($custom && isset($this->_customConfig[$key])) {
            return $this->_customConfig[$key];
        }

        $result = $default;

        if ($db) {
            if ($result = $this->fetchConfig(iaDb::convertIds($key, 'name'))) {
                $result = array_shift($result);
            }
        } else {
            if (isset($this->_config[$key])) {
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
        if ($permanent && !is_scalar($value)) {
            trigger_error(__METHOD__ . '() Could not write a non-scalar value to the database.', E_USER_ERROR);
        }

        $result = true;
        $this->_config[$key] = $value;

        if ($permanent) {
            $result = (bool)$this->iaDb->update(['value' => $value], iaDb::convertIds($key, 'name'), null, self::getConfigTable());

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
        return $this->_hooks;
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
                $this->_hooks[$row['name']][$row['module']] = [
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
        if ($_POST && $this->get('prevent_csrf') && !$this->iaView->get('nocsrf')) {
            $referrerValid = false;
            $tokenValid = true;//(isset($_POST[self::SECURITY_TOKEN_FORM_KEY]) && $this->getSecurityToken() == $_POST[self::SECURITY_TOKEN_FORM_KEY]);

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
            } else {
                $referrerValid = true; // sad, but no other way
            }

            if (!$referrerValid || !$tokenValid) {
                header('HTTP/1.1 203'); // reply with 203 "Non-Authoritative Information" status

                $this->iaView->set('nodebug', true);
                die('Request treated as a potential CSRF attack.');
            }
        }

        unset($_POST[self::SECURITY_TOKEN_FORM_KEY]);
    }

    public function checkDomain()
    {
        if (is_null($this->_checkDomain)) {
            $dbUrl = str_replace(['http://www.', 'http://'], '', $this->get('baseurl'));
            $codeUrl = str_replace(['http://www.', 'http://'], '', $this->iaView->domainUrl);

            $this->_checkDomain = ($dbUrl == $codeUrl);
        }

        return $this->_checkDomain;
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

        if (!isset($this->_hooks[$name])) {
            return false;
        }

        iaSystem::renderTime('hook', $name);

        if (count($this->_hooks[$name]) > 0) {
            $variablesList = array_keys($params);
            extract($params, EXTR_REFS | EXTR_SKIP);

            $iaCore = &$this;
            $iaView = &$this->iaView;
            $iaDb = &$this->iaDb;

            foreach ($this->_hooks[$name] as $extras => $hook) {
                if ('php' == $hook['type']
                    && (empty($hook['pages']) || in_array($iaView->name(), $hook['pages']))) {
                    if ($hook['filename']) {
                        if (!file_exists(IA_HOME . $hook['filename'])) {
                            $message = sprintf('Can\'t start hook "%s". File does not exist: %s', $name, $hook['filename']);
                            iaDebug::debug($message, null, 'error');
                        } else {
                            include IA_HOME . $hook['filename'];
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

    public function factory($name, $type = self::CORE)
    {
        $result = null;
        if (is_string($name)) {
            $className = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
            if (isset($this->_classInstances[$className])) {
                $result = $this->_classInstances[$className];
            } else {
                iaSystem::renderTime('class', 'Loading class ' . $className);
                $fileSize = $this->loadClass($type, (strtolower($name) == 'db') ? INTELLI_CONNECT : $name);
                if (false === $fileSize) {
                    return false;
                }

                iaDebug::debug('ia.' . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

                $result = new $className();
                $result->init();

                $this->_classInstances[$className] = $result;
            }
        } elseif (is_array($name)) {
            $result = [];
            foreach ($name as $className) {
                $result[] = $this->factory($className, $type);
            }
        }

        return $result;
    }

    public function factoryModule($name, $module, $type = self::FRONT, $params = null)
    {
        if ('item' == $name && $params) {
            $name = substr($params, 0, -1);
            $class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
            $params = null;
        } else {
            $class = self::CLASSNAME_PREFIX . ucfirst(strtolower($name));
        }

        if (!isset($this->_classInstances[$class])) {
            $moduleInterface = IA_MODULES . $module . '/includes/classes/ia.base.module' . iaSystem::EXECUTABLE_FILE_EXT;
            if (is_file($moduleInterface)) {
                require_once $moduleInterface;
            }

            $fileSize = $this->loadClass($type, $name, $module);
            if (false === $fileSize) {
                return false;
            }

            iaDebug::debug('<b>package:</b> ia.' . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

            $this->_classInstances[$class] = new $class();
            $this->_classInstances[$class]->init();
        }

        return $this->_classInstances[$class];
    }

    protected static function _toClassName($name)
    {
        // from plural to singular
        $result = self::_toSingular($name);

        // camelize
        $result = str_replace(' ', '', ucwords(str_replace('_', ' ', $result)));

        return $result;
    }

    protected static function _toSingular($name)
    {
        return 's' == $name[strlen($name) - 1] && !in_array($name, ['news'])
            ? substr($name, 0, -1)
            : $name;
    }

    public function factoryPlugin($pluginName, $type = self::FRONT, $className = null)
    {
        empty($className) && $className = self::_toSingular($pluginName);

        $class = self::CLASSNAME_PREFIX . self::_toClassName($className);

        if (!isset($this->_classInstances[$class])) {
            $fileSize = $this->loadClass($type, $className, $pluginName);

            if (false === $fileSize) {
                return false;
            }

            iaDebug::debug('<b>plugin:</b> ia.' . $type . '.' . $className . ' (' . iaSystem::byteView($fileSize) . ')', 'Initialized Classes List', 'info');

            $this->_classInstances[$class] = new $class();
            $this->_classInstances[$class]->init();
        }

        return $this->_classInstances[$class];
    }

    public function loadClass($type = self::CORE, $className = '', $moduleName = null)
    {
        $name = strtolower($className);
        $filename = iaSystem::CLASSES_PREFIX . $type . '.' . $name . iaSystem::EXECUTABLE_FILE_EXT;

        if ($moduleName) {
            $classFile = IA_MODULES . $moduleName . '/includes/classes/' . $filename;
        } else {
            $classFile = IA_CLASSES . $filename;
        }

        if (file_exists($classFile)) {
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
        return isset($_SESSION[self::SECURITY_TOKEN_MEMORY_KEY]) ? $_SESSION[self::SECURITY_TOKEN_MEMORY_KEY] : null;
    }

    public function fetchConfig($where = null)
    {
        $result = [];

        is_null($where) && $where = iaDb::EMPTY_CONDITION;
        $where.= " AND `type` != ':divider'";

        $rows = $this->iaDb->all(['name', 'type', 'value', 'options'], $where, null, null, self::getConfigTable());

        if ($rows) {
            $currentLangCode = $this->iaView->language;

            foreach ($rows as $row) {
                $value = $row['value'];

                if ('text' == $row['type'] || 'textarea' == $row['type']) {
                    $options = empty($row['options']) ? [] : json_decode($row['options'], true);

                    if (isset($options['multilingual']) && $options['multilingual']) {
                        $value = preg_match('#\{\:' . $currentLangCode . '\:\}(.*?)(?:$|\{\:[a-z]{2}\:\})#s', $value, $matches)
                            ? $matches[1]
                            : '';
                    }
                }

                $result[$row['name']] = $value;
            }
        }

        return $result;
    }
}
