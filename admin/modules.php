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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'modules';

    protected $_processAdd = false;
    protected $_processEdit = false;

    private $_folder;
    private $_type;

    private $_modules = [];

    private $_error;


    public function __construct()
    {
        parent::__construct();

        $iaModule = $this->_iaCore->factory('module', iaCore::ADMIN);

        $this->setHelper($iaModule);
        $this->setTable(iaModule::getTable());

        switch ($this->_iaCore->iaView->name()) {
            case 'packages':
                $this->_path = IA_ADMIN_URL . 'modules/packages/';
                $this->_folder = IA_MODULES;

                $this->_type = iaModule::TYPE_PACKAGE;
                break;

            case 'plugins':
                $this->_path = IA_ADMIN_URL . 'modules/plugins/';
                $this->_folder = IA_MODULES;

                $this->_type = iaModule::TYPE_PLUGIN;
                break;

            case 'templates':
                $this->_path = IA_ADMIN_URL . 'modules/templates/';
                $this->_folder = IA_FRONT_TEMPLATES;

                $this->_type = iaModule::TYPE_TEMPLATE;
        }
    }

    protected function _indexPage(&$iaView)
    {
        $start = !empty($params['start']) || 0;
        $limit = !empty($params['limit']) || 15;
        $sort = !empty($params['sort']) || '';
//		$dir = in_array($params['dir'], [iaDb::ORDER_ASC, iaDb::ORDER_DESC]) ? $params['dir'] : iaDb::ORDER_ASC;
//		$filter = empty($params['filter']) ? '' : $params['filter'];

        if (2 == count($this->_iaCore->requestPath)) {
            $this->_processAction($iaView);
        }

        switch ($iaView->name()) {
            case 'packages':
                list($localPackages, $moduleNames) = $this->_getList();
                $remotePackages = $this->getRemoteList($moduleNames);

                $this->_modules = array_merge($localPackages, $remotePackages);
                break;

            case 'plugins':
                $installedPlugins = $this->_iaDb->assoc(['name', 'status', 'version'],
                    iaDb::convertIds(iaModule::TYPE_PLUGIN, 'type'));
                $this->_getLocal(IA_MODULES, ['installed' => $installedPlugins]);
                $this->_getRemotePlugins($start, $limit, $sort);
                break;

            case 'templates':
                $this->_getTemplatesList();
                if ($this->_messages) {
                    $iaView->setMessages($this->_messages);
                }

                break;
            default:
                return iaView::accessDenied();
        }

        $iaView->assign('modules', $this->_modules);

        $iaView->display($this->_template);
    }

    /**
     * Process ajax actions
     *
     * @param $params
     *
     * @return array
     */
    protected function _gridRead($params)
    {
        if (1 == count($this->_iaCore->requestPath) && 'documentation' == $this->_iaCore->requestPath[0]) {
            $result = $this->_getDocumentation($params['name'], $this->_iaCore->iaView);
        } elseif (count($this->_iaCore->requestPath) > 1) {
            $iaAcl = $this->_iaCore->factory('acl');

            switch ($this->_iaCore->requestPath[1]) {
                case 'install':
                case 'reinstall':
                    $action = 'install';

                    if (!$iaAcl->isAccessible($this->_iaCore->requestPath[0], $action)) {
                        return iaView::accessDenied();
                    }

                    $result = $this->_installPlugin($this->_iaCore->requestPath[0], $action, $_POST['remote']);
                    break;

                case 'uninstall':
                    if (!$iaAcl->isAccessible($this->_iaCore->requestPath[0], $this->_iaCore->requestPath[1])) {
                        return iaView::accessDenied();
                    }

                    $result = $this->_uninstallPlugin($this->_iaCore->requestPath[0]);
            }
        } else {
            $result = [];
        }

        return $result;
    }

    private function _getDocumentation($moduleName, &$iaView)
    {
        $result = [];

        if (file_exists($documentationPath = $this->_folder . $moduleName . '/docs/')) {
            $docs = scandir($documentationPath);

            foreach ($docs as $doc) {
                if (substr($doc, 0, 1) != '.') {
                    if (is_file($documentationPath . $doc)) {
                        $tab = substr($doc, 0, count($doc) - 6);
                        $contents = file_get_contents($documentationPath . $doc);
                        $result['tabs'][] = [
                            'title' => iaLanguage::get('extra_' . $tab, ucfirst($tab)),
                            'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/',
                                '<a href="https://dev.subrion.org/issues/$1" target="_blank">#$1</a>',
                                $contents) : $contents),
                            'cls' => 'extension-docs'
                        ];
                    }
                }
            }

            $this->getHelper()->setXml(file_get_contents($this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME));
            $this->getHelper()->parse();

            $search = [
                '{icon}',
                '{link}',
                '{name}',
                '{author}',
                '{contributor}',
                '{version}',
                '{date}',
                '{compatibility}',
            ];

            $icon = '';
            if (file_exists($this->_folder . $moduleName . '/docs/img/icon.png')) {
                $icon = '<tr><td class="plugin-icon"><img src="' . $iaView->assetsUrl . 'modules/' . $moduleName . '/docs/img/icon.png" alt=""></td></tr>';
            }
            $data = &$this->getHelper()->itemData;

            $replacement = [
                $icon,
                '',
                $data['info']['title'],
                $data['info']['author'],
                $data['info']['contributor'],
                $data['info']['version'],
                $data['info']['date'],
                $data['compatibility']
            ];

            $result['info'] = str_replace($search, $replacement,
                file_get_contents(IA_ADMIN . 'templates/' . $this->_iaCore->get('admin_tmpl') . '/extra_information.tpl'));
        }

        return $result;
    }

    private function _processAction(&$iaView)
    {
        $iaAcl = $this->_iaCore->factory('acl');
        $iaLog = $this->_iaCore->factory('log');

        $module = iaSanitize::sql($this->_iaCore->requestPath[0]);
        $action = $this->_iaCore->requestPath[1];
        $error = false;

        switch ($action) {
            case 'download':
                $this->_download($module);

                break;
            case 'activate':
            case 'deactivate':
                if (!$iaAcl->isAccessible($this->getName(), 'activate')) {
                    return iaView::accessDenied();
                }

                $deactivate = ('deactivate' == $action);

                if ($this->_activate($module, $deactivate)) {
                    $this->_iaCore->startHook($deactivate ? 'phpModuleDeactivated' : 'phpModuleActivated',
                        ['module' => $module]);
                    $iaLog->write($deactivate ? iaLog::ACTION_DISABLE : iaLog::ACTION_ENABLE,
                        ['type' => iaModule::TYPE_PACKAGE, 'name' => $module], $module);
                } else {
                    $error = true;
                }

                break;

            case 'set_default':
                if (!$iaAcl->isAccessible($this->getName(), $action)) {
                    return iaView::accessDenied();
                }

                $error = !$this->_setDefault($module);

                break;

            case 'reset':
                if (!$iaAcl->isAccessible($this->getName(), 'set_default')) {
                    return iaView::accessDenied();
                }

                $error = !$this->_reset($this->_iaCore->domain);

                break;

            case iaModule::ACTION_INSTALL:
            case iaModule::ACTION_REINSTALL:
            case iaModule::ACTION_UPGRADE:
                if (!$iaAcl->isAccessible($this->getName(), $action)) {
                    return iaView::accessDenied();
                }

                if (iaModule::TYPE_TEMPLATE == $this->_type) {
                    if ($this->_installTemplate($module)) {
                        $iaView->setMessages(iaLanguage::getf('template_installed',
                            ['name' => $this->getHelper()->itemData['info']['title']]), iaView::SUCCESS);

                        $this->_iaCore->iaCache->clearAll();

                        $this->_iaCore->factory('log')->write(iaLog::ACTION_INSTALL,
                            ['type' => 'template', 'name' => $this->getHelper()->itemData['info']['title']]);
                    }
                } elseif (iaModule::TYPE_PLUGIN == $this->_type) {
                    if ($this->_installPlugin($module, $action, $_POST['remote'])) {
                        // log this event
                        $action = $this->getHelper()->isUpgrade ? iaLog::ACTION_UPGRADE : iaLog::ACTION_INSTALL;
                        $iaLog->write($action, [
                            'type' => iaModule::TYPE_PLUGIN,
                            'name' => $module,
                            'to' => $this->getHelper()->itemData['info']['version']
                        ], $module);
                        //

                        $iaSitemap = $this->_iaCore->factory('sitemap', iaCore::ADMIN);
                        $iaSitemap->generate();
                    }
                } elseif ($this->_install($module, $action, $this->_iaCore->domain)) {
                    // log this event
                    $action = $this->getHelper()->isUpgrade ? iaLog::ACTION_UPGRADE : iaLog::ACTION_INSTALL;
                    $iaLog->write($action, [
                        'type' => iaModule::TYPE_PACKAGE,
                        'name' => $module,
                        'to' => $this->getHelper()->itemData['info']['version']
                    ], $module);
                    //

                    $iaSitemap = $this->_iaCore->factory('sitemap', iaCore::ADMIN);
                    $iaSitemap->generate();
                } else {
                    $error = true;
                }

                break;

            case iaModule::ACTION_UNINSTALL:
                if (!$iaAcl->isAccessible($this->getName(), $action)) {
                    return iaView::accessDenied();
                }

                if ($this->_uninstall($module)) {
                    $iaLog->write(iaLog::ACTION_UNINSTALL, ['type' => iaModule::TYPE_PACKAGE, 'name' => $module],
                        $module);
                } else {
                    $error = true;
                }
        }

        $this->_iaCore->iaCache->clearAll();

        $iaView->setMessages($this->getMessages(), $error ? iaView::ERROR : iaView::SUCCESS);


        iaUtil::go_to($this->getPath());
    }

    private function _install($moduleName, $action, $domain)
    {
        $installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

        if (file_exists($installFile)) {
            $this->getHelper()->setXml(file_get_contents($installFile));

            $url = '';
            $_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;

            switch ($_GET['type']) {
                case 1:
                    $url = 'http://' . iaSanitize::sql(str_replace('www.', '',
                            $_GET['url'][1])) . '.' . $domain . IA_URL_DELIMITER;
                    break;
                case 2:
                    $url = ($action == iaModule::ACTION_UPGRADE)
                        ? $this->_iaDb->one('url', "`name` = '{$moduleName}' AND `type` = 'package'")
                        : $_GET['url'][2];
            }

            $url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;

            $this->getHelper()->doAction(iaModule::ACTION_INSTALL, $url);

            if ($this->getHelper()->error) {
                $this->addMessage($this->getHelper()->getMessage());
            } else {
                if ($_GET['type'] == 0) {
                    $this->_changeDefault(isset($_GET['url'][0]) ? $_GET['url'][0] : '', $moduleName);
                }

                $messagePhrase = $this->getHelper()->isUpgrade ? 'package_updated' : 'package_installed';
                $this->addMessage($messagePhrase);

                return true;
            }
        } else {
            $this->addMessage('file_doesnt_exist');
        }

        return false;
    }

    private function _uninstall($moduleName)
    {
        if ($this->_iaDb->exists('`name` = :name AND `type` = :type',
            ['name' => $moduleName, 'type' => iaModule::TYPE_PACKAGE])
        ) {
            $installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

            if (!file_exists($installFile)) {
                $this->addMessage('file_doesnt_exist');
            } else {
                $this->getHelper()->setXml(file_get_contents($installFile));
                $this->getHelper()->uninstall($moduleName);

                $this->addMessage('package_uninstalled');

                return true;
            }
        }

        return false;
    }

    private function _activate($moduleName, $deactivate)
    {
        $status = $deactivate ? iaCore::STATUS_INACTIVE : iaCore::STATUS_ACTIVE;

        return (bool)$this->_iaDb->update(['status' => $status], iaDb::convertIds($moduleName, 'name'));
    }

    private function _reset($domain)
    {
        $_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;
        $url = '';

        switch ($_GET['type']) {
            case 1:
                $url = 'http://' . iaSanitize::sql(str_replace('www.', '',
                        $_GET['url'][1])) . '.' . $domain . IA_URL_DELIMITER;
                break;
            case 2:
                $url = $_GET['url'][2];
        }

        if ($url) {
            $this->_changeDefault($url);

            $this->addMessage('reset_default_success');

            return true;
        } else {
            return false;
        }
    }

    private function _setDefault($moduleName)
    {
        $this->_changeDefault((isset($_GET['url']) ? $_GET['url'][0] : ''), $moduleName);

        $installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
        if (!file_exists($installFile)) {
            $this->addMessage('file_doesnt_exist');

            return false;
        }

        $this->getHelper()->getFromPath($installFile);
        $this->getHelper()->setUrl(IA_URL_DELIMITER);
        $this->getHelper()->parse();
        $this->getHelper()->checkValidity();

        $pages = $this->getHelper()->itemData['pages']['front'];
        foreach ($pages as $page) {
            $this->_iaDb->update(['alias' => $page['alias']], "`name` = '{$page['name']}' AND `module` = '$moduleName'",
                null, 'pages');
        }

        $this->addMessage('set_default_success');

        if (!$this->_iaCore->get('default_package')) {
            $this->addMessage('reset_previous_default_success');
        }

        return true;
    }

    private function _changeDefault($url, $module = '')
    {
        $defaultPackage = $this->_iaCore->get('default_package');

        if ($defaultPackage == $module) {
            return;
        }

        $iaDb = &$this->_iaDb;

        if ($defaultPackage) {
            $url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;

            $this->getHelper()->setUrl($url);
            $this->getHelper()->getFromPath($this->_folder . $defaultPackage . IA_DS . iaModule::INSTALL_FILE_NAME);
            $this->getHelper()->parse();
            $this->getHelper()->checkValidity();

            $iaDb->update(['url' => $url], iaDb::convertIds($defaultPackage, 'name'));

            if ($this->getHelper()->itemData['pages']['front']) {
                $iaDb->setTable('pages');
                foreach ($this->getHelper()->itemData['pages']['front'] as $page) {
                    $iaDb->update(['alias' => $page['alias']],
                        "`name` = '{$page['name']}' AND `module` = '$defaultPackage'");
                }
                $iaDb->resetTable();
            }
        }

        $iaDb->update(['url' => IA_URL_DELIMITER], iaDb::convertIds($module, 'name'));
        $this->_iaCore->set('default_package', $module, true);

        $iaDb->setTable('hooks');
        $iaDb->update(['status' => iaCore::STATUS_INACTIVE], "`name` = 'phpCoreUrlRewrite'");
        $iaDb->update(['status' => iaCore::STATUS_ACTIVE], "`name` = 'phpCoreUrlRewrite' AND `module` = '$module'");
        $iaDb->resetTable();
    }

    private function _getList()
    {
        $stmt = iaDb::convertIds(iaModule::TYPE_PACKAGE, 'type');

        $existPackages = $this->_iaDb->keyvalue(['name', 'version'], $stmt);
        $existPackages || $existPackages = [];
        $statuses = $this->_iaDb->keyvalue(['name', 'status'], $stmt);
        $dates = $this->_iaDb->keyvalue(['name', 'date'], $stmt);

        $directory = opendir($this->_folder);
        $result = $moduleNames = [];

        while ($file = readdir($directory)) {
            $installationFile = $this->_folder . $file . IA_DS . iaModule::INSTALL_FILE_NAME;
            if (substr($file, 0, 1) != '.' && is_dir($this->_folder . $file) && file_exists($installationFile)) {
                if ($fileContents = file_get_contents($installationFile)) {
                    $this->getHelper()->setXml($fileContents);
                    $this->getHelper()->parse();

                    if (iaModule::TYPE_PACKAGE != $this->getHelper()->itemData['type']) {
                        continue;
                    }

                    $this->getHelper()->itemData['url'] = '';

                    $data = &$this->getHelper()->itemData;

                    $compatible = $this->_checkCompatibility($this->getHelper()->itemData['compatibility']);


                    $status = 'notinstall';
                    $buttons = [
                        'readme' => true,
                        'activate' => false,
                        'set_default' => false,
                        'deactivate' => false,
                        'install' => false,
                        'uninstall' => false,
                        'upgrade' => false,
                        'config' => false,
                        'manage' => false,
                        'import' => false
                    ];
                    $installed = false;
                    if (isset($existPackages[$data['name']])) {
                        $installed = true;
                        $status = $statuses[$data['name']];
                    }

                    switch ($status) {
                        case 'install':
                        case 'active':
                            $buttons['deactivate'] = true;
                            $buttons['set_default'] = true;

                            if (is_dir($this->_folder . $file . '/includes/dumps')) {
                                $buttons['import'] = true;
                            }

                            if ($extraConfig = $this->_iaCore->factory('config')->getBy('module', $data['name'])) {
                                $buttons['config'] = [
                                    'url' => $extraConfig['config_group'],
                                    'anchor' => $extraConfig['key']
                                ];
                            }

                            if ($alias = $this->_iaDb->one_bind('alias', '`name` = :name',
                                ['name' => $data['name'] . '_manage'], 'admin_pages')
                            ) {
                                $buttons['manage'] = $alias;
                            }

                            if ($compatible && version_compare($data['info']['version'], $existPackages[$data['name']],
                                    '>')
                            ) {
                                $buttons['upgrade'] = true;
                            }

                            $buttons['reinstall'] = true;

                            break;

                        case 'inactive':

                            $buttons['activate'] = true;
                            $buttons['uninstall'] = true;

                            break;

                        case 'notinstall':
                            $buttons['install'] = true;
                    }

                    $moduleNames[] = $data['name'];

                    $buttons['docs'] = 'https://subrion.org/package/' . $data['name'] . '.html';

                    $result[] = [
                        'title' => $data['info']['title'],
                        'version' => $data['info']['version'],
                        'description' => $data['info']['title'],
                        'contributor' => $data['info']['contributor'],
                        'compatibility' => $data['compatibility'],
                        'author' => $data['info']['author'],
                        'summary' => $data['info']['summary'],
                        'date' => $data['info']['date'],
                        'name' => $data['name'],
                        'buttons' => $buttons,
                        'url' => $data['url'],
                        'logo' => IA_CLEAR_URL . 'modules/' . $data['name'] . '/docs/img/icon.png',
                        'file' => $file,
                        'price' => 0,
                        'status' => $status,
                        'installed' => $installed,
                        'date_updated' => ($status != 'notinstall') ? $dates[$data['name']] : false,
                        'install' => true,
                        'remote' => false
                    ];
                }
            }
        }

        return [$result, $moduleNames];
    }

    private function getRemoteList($localPackages)
    {
        $remotePackages = [];

        if ($cachedData = $this->_iaCore->iaCache->get('subrion_packages', 3600 * 24 * 7, true)) {
            $remotePackages = $cachedData; // get templates list from cache
        } else {
            if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/package/' . IA_VERSION)) {
                $response = json_decode($response, true);
                if (!empty($response['error'])) {
                    $this->_messages[] = $response['error'];
                    $this->_error = true;
                } elseif ($response['total'] > 0) {
                    if (isset($response['extensions']) && is_array($response['extensions'])) {
                        foreach ($response['extensions'] as $entry) {
                            $moduleInfo = (array)$entry;

                            // exclude uploaded packages
                            if (!in_array($moduleInfo['name'], $localPackages)) {
                                $moduleInfo['date'] = gmdate(iaDb::DATE_FORMAT, $moduleInfo['date']);
                                $moduleInfo['status'] = '';
                                $moduleInfo['summary'] = $moduleInfo['description'];
                                $moduleInfo['buttons'] = false;
                                $moduleInfo['remote'] = true;

                                $remotePackages[] = $moduleInfo;
                            }
                        }

                        // cache well-formed results
                        $this->_iaCore->iaCache->write('subrion_packages', $remotePackages);
                    } else {
                        $this->addMessage('error_incorrect_format_from_subrion');
                        $this->_error = true;
                    }
                }
            } else {
                $this->addMessage('error_incorrect_response_from_subrion');
                $this->_error = true;
            }
        }

        return $remotePackages;
    }

    private function _getRemotePlugins($start, $limit, $sort)
    {
        $pluginsData = [];

        if ($cachedData = $this->_iaCore->iaCache->get('subrion_plugins', 3600, true)) {
            $pluginsData = $cachedData;
        } else {
            if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/plugin/' . IA_VERSION)) {
                $response = json_decode($response, true);
                if (!empty($response['error'])) {
                    $this->addMessage($response['error']);
                } elseif ($response['total'] > 0) {
                    if (isset($response['extensions']) && is_array($response['extensions'])) {
                        $_local = array_keys($this->_modules);

                        $pluginsData = [];
                        foreach ($response['extensions'] as $entry) {
                            $pluginInfo = (array)$entry;

                            $buttons['docs'] = 'https://subrion.org/template/' . $pluginInfo['name'] . '.html';

                            // exclude installed plugins
                            if (!in_array($pluginInfo['name'], $_local)) {
                                if ((int)$pluginInfo['price'] <= 0) {
                                    $buttons['download'] = true;
                                }

                                $pluginInfo['summary'] = $pluginInfo['description'];
                                $pluginInfo['date'] = gmdate(iaDb::DATE_FORMAT, $pluginInfo['date']);
                                $pluginInfo['file'] = $pluginInfo['name'];
                                $pluginInfo['status'] = 'remote';
                                $pluginInfo['remote'] = true;
                                $pluginInfo['buttons'] = $buttons;

                                $pluginsData['plugins'][$pluginInfo['name']] = $pluginInfo;
                            }
                        }

                        // cache well-formed results
                        $this->_iaCore->iaCache->write('subrion_plugins', $pluginsData);
                    } else {
                        $this->addMessage('error_incorrect_format_from_subrion');
                    }
                }
            } else {
                $this->addMessage('error_incorrect_response_from_subrion');
            }
        }

        !empty($pluginsData['plugins']) && $this->_modules = array_merge($this->_modules, $pluginsData['plugins']);
    }

    private function _installPlugin($moduleName, $action, $remote = false)
    {
        $result = ['error' => true];

        if ($remote) {
            $this->_download($moduleName);
        }

        $iaModule = $this->getHelper();

        $installationFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
        if (!file_exists($installationFile)) {
            $result['message'] = iaLanguage::get('file_doesnt_exist');
        } else {
            $iaModule->setXml(file_get_contents($installationFile));
            $result['error'] = false;
        }

        $iaModule->parse();

        if (!$this->_checkCompatibility($iaModule->itemData['compatibility'])) {
            $result['message'] = iaLanguage::get('incompatible');
            $result['error'] = true;
        }

        if (!$result['error']) {
            $iaModule->doAction(iaModule::ACTION_INSTALL);
            if ($iaModule->error) {
                $result['message'] = $iaModule->getMessage();
                $result['error'] = true;
            } else {
                $iaLog = $this->_iaCore->factory('log');

                if ($iaModule->isUpgrade) {
                    $result['message'] = iaLanguage::get('plugin_updated');

                    $iaLog->write(iaLog::ACTION_UPGRADE, [
                        'type' => iaModule::TYPE_PLUGIN,
                        'name' => $iaModule->itemData['info']['title'],
                        'to' => $iaModule->itemData['info']['version']
                    ]);

                    $messagePhrase = 'plugin_updated';
                    $this->addMessage($messagePhrase);

                    return true;
                } else {
                    $result['groups'] = $iaModule->getMenuGroups();
                    $result['message'] = iaModule::ACTION_INSTALL == $action
                        ? iaLanguage::getf('plugin_installed', ['name' => $iaModule->itemData['info']['title']])
                        : iaLanguage::getf('plugin_reinstalled', ['name' => $iaModule->itemData['info']['title']]);

                    $iaLog->write(iaLog::ACTION_INSTALL,
                        ['type' => iaModule::TYPE_PLUGIN, 'name' => $iaModule->itemData['info']['title']]);
                }

                empty($iaModule->itemData['notes']) || $result['message'][] = $iaModule->itemData['notes'];

                $this->_iaCore->getConfig(true);
            }
        }

        $result['result'] = !$result['error'];
        unset($result['error']);

        return $result;
    }

    private function _uninstallPlugin($moduleName)
    {
        $result = ['result' => false, 'message' => iaLanguage::get('invalid_parameters')];

        if ($this->_iaDb->exists('`name` = :plugin AND `type` = :type AND `removable` = 1',
            ['plugin' => $moduleName, 'type' => iaModule::TYPE_PLUGIN])
        ) {
            $installationFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

            if (!file_exists($installationFile)) {
                $result['message'] = [iaLanguage::get('plugin_files_physically_missed')];
            }

            $this->getHelper()->uninstall($moduleName);

            is_array($result['message'])
                ? $result['message'][] = iaLanguage::get('plugin_uninstalled')
                : $result['message'] = iaLanguage::get('plugin_uninstalled');

            $result['result'] = true;

            // log this event
            $iaLog = $this->_iaCore->factory('log');
            $iaLog->write(iaLog::ACTION_UNINSTALL, ['type' => iaModule::TYPE_PLUGIN, 'name' => $moduleName]);
            //

            $this->_iaCore->getConfig(true);
        } else {
            $result['message'] = iaLanguage::get('plugin_may_not_be_removed');
        }

        return $result;
    }

    private function _installTemplate($moduleName)
    {
        $iaModule = $this->getHelper();

        if (empty($moduleName)) {
            $iaModule->error = true;
            $this->_messages[] = iaLanguage::get('template_name_empty');
        }

        if (!is_dir(IA_FRONT_TEMPLATES . $moduleName) && !$iaModule->error) {
            $iaModule->error = true;
            $this->_messages[] = iaLanguage::get('template_folder_error');
        }

        $installFile = IA_FRONT_TEMPLATES . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
        if (!file_exists($installFile) && !$iaModule->error) {
            $iaModule->error = true;
            $this->_messages[] = iaLanguage::getf('template_file_error', ['file' => $moduleName]);
        }

        if (!$iaModule->error) {
            $iaModule->getFromPath($installFile);
            $iaModule->parse();
            $iaModule->checkValidity();
            $iaModule->rollback();
            $iaModule->install();

            if (!$iaModule->error) {
                return true;
            }

            $iaModule->error = true;
            $this->_messages[] = $iaModule->getMessage();
        }

        return false;
    }

    private function _getTemplatesList()
    {
        $this->_getLocal(IA_FRONT_TEMPLATES);

        $remoteTemplates = [];
        if ($this->_iaCore->get('allow_remote_templates')) {
            if ($cachedData = $this->_iaCore->iaCache->get('subrion_templates', 3600, true)) {
                $remoteTemplates = $cachedData; // get templates list from cache, cache lives for 1 hour
            } else {
                if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/template/' . IA_VERSION)) {
                    $response = json_decode($response, true);
                    if (!empty($response['error'])) {
                        $this->_messages[] = $response['error'];
                        $this->getHelper()->error = true;
                    } elseif ($response['total'] > 0) {
                        if (isset($response['extensions']) && is_array($response['extensions'])) {
                            $_local = array_keys($this->_modules);

                            foreach ($response['extensions'] as $entry) {
                                $templateInfo = (array)$entry;
                                $templateInfo['summary'] = $templateInfo['description'];

                                // exclude installed templates
                                if (!in_array($templateInfo['name'], $_local)) {
                                    $buttons['docs'] = 'https://subrion.org/template/' . $templateInfo['name'] . '.html';
                                    $buttons['download'] = true;

                                    $templateInfo['date'] = gmdate(iaDb::DATE_FORMAT, $templateInfo['date']);
                                    $templateInfo['buttons'] = $buttons;
                                    $templateInfo['notes'] = [];
                                    $templateInfo['price'] = '';
                                    $templateInfo['status'] = 'notinstall';
                                    $templateInfo['remote'] = true;

                                    $remoteTemplates[$templateInfo['name']] = $templateInfo;
                                }
                            }

                            // cache well-formed results
                            $this->_iaCore->iaCache->write('subrion_templates', $remoteTemplates);
                        } else {
                            $this->addMessage('error_incorrect_format_from_subrion');
                            $this->getHelper()->error = true;
                        }
                    }
                } else {
                    $this->addMessage('error_incorrect_response_from_subrion');
                    $this->getHelper()->error = true;
                }
            }
        }
        $this->_modules = array_merge($this->_modules, $remoteTemplates);

        $moduleName = $this->_iaCore->get('tmpl');
        $activeTemplate = $this->_modules[$moduleName];
        unset($this->_modules[$moduleName]);

        $this->_modules = [$moduleName => $activeTemplate] + $this->_modules;
    }

    /**
     * Download and prepare module for installation
     *
     * @param $moduleName
     *
     * @return bool
     */
    private function _download($moduleName)
    {
        $tempFolder = IA_TMP . 'modules/';
        !is_dir($tempFolder) && mkdir($tempFolder);

        $filePath = $tempFolder . $moduleName;
        $fileName = $filePath . '.zip';

        // save remote module file
        if (iaUtil::downloadRemoteContent(
                iaUtil::REMOTE_TOOLS_URL . 'install/' . $moduleName . IA_URL_DELIMITER . IA_VERSION,
                $fileName) && file_exists($fileName)
        ) {
            if (is_writable($this->_folder)) {
                // delete previous folder
                is_dir($this->_folder . $moduleName) && unlink($this->_folder . $moduleName);

                include_once(IA_INCLUDES . 'utils/pclzip.lib.php');

                $pclZip = new PclZip($fileName);
                if ($result = $pclZip->extract(PCLZIP_OPT_PATH, $this->_folder . $moduleName)) {
                    $this->addMessage(iaLanguage::getf('module_downloaded', ['name' => $moduleName]), false);
                } else {
                    $this->error = true;
                    $this->addMessage('error_incorrect_format_from_subrion');
                }

                $this->_iaCore->iaCache->remove('subrion_plugins');
                $this->_iaCore->iaCache->remove('subrion_templates');

                return (bool)$result;
            } else {
                $this->error = true;
                $this->addMessage(iaLanguage::getf('upload_module_error', ['module' => $this->_folder]), false);
            }
        }

        return false;
    }

    /**
     * Validate module compatibility
     *
     * @param $compatibility
     *
     * @return bool
     */
    private function _checkCompatibility($compatibility)
    {
        $result = false;

        $version = explode('-', $compatibility);
        if (!isset($version[1])) {
            $result = (bool)version_compare($version[0], IA_VERSION, '<=');
        } elseif (version_compare($version[0], IA_VERSION, '<=') && version_compare($version[1], IA_VERSION, '>=')) {
            $result = true;
        }

        return $result;
    }

    /**
     * Get list of local modules
     *
     * @param $path folder to check
     * @param array $options
     */
    private function _getLocal($path, array $options = [])
    {
        $directory = opendir($path);
        while ($file = readdir($directory)) {
            if (substr($file, 0, 1) != '.') {
                if (is_dir($path . $file)) {
                    $installFile = $path . $file . IA_DS . iaModule::INSTALL_FILE_NAME;
                    if (file_exists($installFile)) {
                        $this->getHelper()->getFromPath($installFile);
                        $this->getHelper()->parse(true);
                        $this->getHelper()->checkValidity($file);

                        // use valid type only
                        if ($this->_type != $this->getHelper()->itemData['type']) {
                            continue;
                        }

                        $method = '_' . $this->_type;
                        $this->$method($this->getHelper()->itemData, $file, $options);
                    }
                }
            }
        }
        closedir($directory);
    }

    /**
     * Prepare plugin data
     *
     * @param $module
     * @param $folder
     */
    private function _plugin($module, $folder, array $options = [])
    {
        if ($folder == $module['name']) {
            $buttons = [];
            $notes = $this->getHelper()->getNotes();
            if ($notes) {
                $notes = implode(PHP_EOL, $notes);
                $notes .= PHP_EOL . PHP_EOL . iaLanguage::get('installation_impossible');
            }

            $installed = false;
            if (array_key_exists($module['name'], $options['installed'])) {
                if ($row = $this->_iaCore->factory('config')->getBy('module', $module['name'])) {
                    $buttons['config'] = [
                        'url' => $row['config_group'],
                        'anchor' => $row['name']
                    ];
                }

                if ($alias = $this->_iaDb->one_bind('alias', '`name` = :name', ['name' => $module['name']], 'admin_pages')) {
                    $buttons['manage'] = $alias;
                }

                $installed = true;
                $module['status'] = $options['installed'][$module['name']]['status'];
                switch ($module['status']) {
                    case iaCore::STATUS_ACTIVE:
                        $buttons['deactivate'] = true;
                        $buttons['reinstall'] = true;
                        $buttons['uninstall'] = true;
                        break;
                    case iaCore::STATUS_INACTIVE:
                        $buttons['activate'] = true;
                        $buttons['reinstall'] = false;
                        $buttons['uninstall'] = true;
                        break;
                }
            }

            $compatible = $this->_checkCompatibility($module['compatibility']);

            $buttons['install'] = $compatible && !isset($buttons['reinstall']);
            $buttons['docs'] = 'https://subrion.org/plugin/' . $module['name'] . '.html';
            $buttons['readme'] = true;

            if ($installed && $compatible &&
                version_compare($module['info']['version'], $options['installed'][$module['name']]['version'], '>')) {
                $buttons['upgrade'] = true;
            }

            $module = [
                'name' => $module['name'],
                'title' => $module['info']['title'],
                'summary' => $module['info']['summary'],
                'author' => $module['info']['author'],
                'date' => $module['info']['date'],
                'version' => $module['info']['version'],
                'compatibility' => $module['compatibility'],
                'compatible' => $compatible,
                'file' => $folder,
                'buttons' => $buttons,
                'status' => !empty($module['status']) ? $module['status'] : '',
                'installed' => $installed,
                'remote' => false,
                'price' => '',
                'notes' => $notes,
                'config' => $module['config'],
                'config_groups' => $module['config_groups'],
                'logo' => IA_CLEAR_URL . 'modules/' . $folder . '/docs/img/icon.png',
            ];

            $this->_modules[$module['name']] = $module;
        }
    }

    /**
     * Prepare template data
     *
     * @param $module
     * @param $folder
     */
    private function _template($module, $folder)
    {
        if ($folder == $module['name']) {
            $compatible = $this->_checkCompatibility($module['compatibility']);

            if ($this->getHelper()->getNotes()) {
                $compatible = false;
            }

            $buttons['reinstall'] = $this->_iaCore->get('tmpl') == $folder;
            $buttons['install'] = $compatible && !$buttons['reinstall'];
            $buttons['docs'] = 'https://subrion.org/template/' . $module['name'] . '.html';
            $buttons['config'] = $buttons['reinstall'] ? [
                'url' => 'template_' . $module['name'],
                'anchor' => ''
            ] : false;

            $module = [
                'name' => $module['name'],
                'title' => $module['info']['title'],
                'summary' => $module['info']['summary'],
                'author' => $module['info']['author'],
                'date' => $module['info']['date'],
                'version' => $module['info']['version'],
                'compatibility' => $module['compatibility'],
                'compatible' => $compatible,
                'file' => $folder,
                'buttons' => $buttons,
                'status' => $buttons['reinstall'] ? iaCore::STATUS_ACTIVE : '',
                'installed' => $buttons['reinstall'],
                'remote' => false,
                'price' => '',
                'notes' => $this->getHelper()->getNotes(),
                'config' => $module['config'],
                'config_groups' => $module['config_groups'],
                'logo' => IA_CLEAR_URL . 'templates/' . $module['name'] . '/docs/img/icon.png',
            ];

            $this->_modules[$module['name']] = $module;
        } else {
            $this->_iaCore->iaView->setMessages($this->getHelper()->getMessage(), iaView::ERROR);
        }
    }
}
