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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'modules';

	protected $_processAdd = false;
	protected $_processEdit = false;

	private $_folder;
	private $_type;


	public function __construct()
	{
		parent::__construct();

		$iaModule = $this->_iaCore->factory('module', iaCore::ADMIN);

		$this->setHelper($iaModule);
		$this->setTable(iaModule::getTable());

		switch ($this->_iaCore->iaView->name())
		{
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
				$this->_folder = IA_TEMPLATES;
				$this->_template = 'templates';

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

		switch ($iaView->name())
		{
			case 'packages':
				list($localPackages, $moduleNames) = $this->_getList();
				$remotePackages = $this->getRemoteList($moduleNames);

				$modules = array_merge($localPackages, $remotePackages);
				break;

			case 'plugins':
				list($modules, $moduleNames) = $this->_getLocalPlugins($start, $limit, $sort);
				$remotePlugins = $this->_getRemotePlugins($start, $limit, $sort);

				!empty($remotePlugins['total']) && $modules = array_merge($modules, $remotePlugins);
				break;

			case 'templates':
				$this->_folder = IA_TEMPLATES;

				$modules = $this->_getTemplatesList();
				if ($this->_messages)
				{
					$iaView->setMessages($this->_messages);
				}

				break;
			default:
				return iaView::accessDenied();
		}
		$iaView->assign('modules', $modules);

		if (2 == count($this->_iaCore->requestPath))
		{
			$this->_processAction($iaView);
		}

		$iaView->display($this->_template);
	}

	protected function _gridRead($params)
	{
		return (1 == count($this->_iaCore->requestPath) && 'documentation' == $this->_iaCore->requestPath[0])
			? $this->_getDocumentation($params['name'], $this->_iaCore->iaView)
			: [];
	}


	private function _getDocumentation($moduleName, &$iaView)
	{
		$result = [];

		if (file_exists($documentationPath = $this->_folder . $moduleName . IA_DS . 'docs' . IA_DS))
		{
			$docs = scandir($documentationPath);

			foreach ($docs as $doc)
			{
				if (substr($doc, 0, 1) != '.')
				{
					if (is_file($documentationPath . $doc))
					{
						$tab = substr($doc, 0, count($doc) - 6);
						$contents = file_get_contents($documentationPath . $doc);
						$result['tabs'][] = [
							'title' => iaLanguage::get('extra_' . $tab, ucfirst($tab)),
							'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="https://dev.subrion.org/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
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
			if (file_exists($this->_folder . $moduleName . IA_DS . 'docs' . IA_DS . 'img' . IA_DS . 'icon.png'))
			{
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
				file_get_contents(IA_ADMIN . 'templates' . IA_DS . $this->_iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
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

		switch ($action)
		{
			case 'download':
				if ($this->_downloadTemplate($module))
				{
					$this->_iaCore->iaCache->remove('subrion_templates');
				}
				break;
			case 'activate':
			case 'deactivate':
				if (!$iaAcl->isAccessible($this->getName(), 'activate'))
				{
					return iaView::accessDenied();
				}

				$deactivate = ('deactivate' == $action);

				if ($this->_activate($module, $deactivate))
				{
					$this->_iaCore->startHook($deactivate ? 'phpModuleDeactivated' : 'phpModuleActivated',
						['module' => $module]);
					$iaLog->write($deactivate ? iaLog::ACTION_DISABLE : iaLog::ACTION_ENABLE,
						['type' => iaModule::TYPE_PACKAGE, 'name' => $module], $module);
				}
				else
				{
					$error = true;
				}

				break;

			case 'set_default':
				if (!$iaAcl->isAccessible($this->getName(), $action))
				{
					return iaView::accessDenied();
				}

				$error = !$this->_setDefault($module);

				break;

			case 'reset':
				if (!$iaAcl->isAccessible($this->getName(), 'set_default'))
				{
					return iaView::accessDenied();
				}

				$error = !$this->_reset($iaView->domain);

				break;

			case iaModule::ACTION_INSTALL:
			case iaModule::ACTION_REINSTALL:
			case iaModule::ACTION_UPGRADE:
				if (!$iaAcl->isAccessible($this->getName(), $action))
				{
					return iaView::accessDenied();
				}

				if (iaModule::TYPE_TEMPLATE == $this->_type)
				{
					if ($this->_installTemplate($module))
					{
						$iaView->setMessages(iaLanguage::getf('template_installed', ['name' => $this->getHelper()->itemData['info']['title']]), iaView::SUCCESS);

						$this->_iaCore->iaCache->clearAll();

						$this->_iaCore->factory('log')->write(iaLog::ACTION_INSTALL, ['type' => 'template', 'name' => $this->getHelper()->itemData['info']['title']]);
					}
				}
				elseif (iaModule::TYPE_PLUGIN == $this->_type)
				{

				}
				elseif ($this->_install($module, $action, $iaView->domain))
				{
					// log this event
					$action = $this->getHelper()->isUpgrade ? iaLog::ACTION_UPGRADE : iaLog::ACTION_INSTALL;
					$iaLog->write($action, ['type' => iaModule::TYPE_PACKAGE, 'name' => $module, 'to' => $this->getHelper()->itemData['info']['version']], $module);
					//

					$iaSitemap = $this->_iaCore->factory('sitemap', iaCore::ADMIN);
					$iaSitemap->generate();
				}
				else
				{
					$error = true;
				}

				break;

			case iaModule::ACTION_UNINSTALL:
				if (!$iaAcl->isAccessible($this->getName(), $action))
				{
					return iaView::accessDenied();
				}

				if ($this->_uninstall($module))
				{
					$iaLog->write(iaLog::ACTION_UNINSTALL, ['type' => iaModule::TYPE_PACKAGE, 'name' => $module], $module);
				}
				else
				{
					$error = true;
				}
		}

		$this->_iaCore->iaCache->clearAll();

		$iaView->setMessages($this->getMessages(), $error ? iaView::ERROR : iaView::SUCCESS);


		iaUtil::go_to($this->getPath());
	}

	private function _prepareModule()
	{

	}

	private function _install($moduleName, $action, $domain)
	{
		$installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

		if (file_exists($installFile))
		{
			$this->getHelper()->setXml(file_get_contents($installFile));

			$url = '';
			$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;

			switch($_GET['type'])
			{
				case 1:
					$url = 'http://' . iaSanitize::sql(str_replace('www.', '', $_GET['url'][1])) . '.' . $domain . IA_URL_DELIMITER;
					break;
				case 2:
					$url = ($action == iaModule::ACTION_UPGRADE)
						? $this->_iaDb->one('url', "`name` = '{$moduleName}' AND `type` = 'package'")
						: $_GET['url'][2];
			}

			$url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;

			$this->getHelper()->doAction(iaModule::ACTION_INSTALL, $url);

			if ($this->getHelper()->error)
			{
				$this->addMessage($this->getHelper()->getMessage());
			}
			else
			{
				if ($_GET['type'] == 0)
				{
					$this->_changeDefault(isset($_GET['url'][0]) ? $_GET['url'][0] : '', $moduleName);
				}

				$messagePhrase = $this->getHelper()->isUpgrade ? 'package_updated' : 'package_installed';
				$this->addMessage($messagePhrase);

				return true;
			}
		}
		else
		{
			$this->addMessage('file_doesnt_exist');
		}

		return false;
	}

	private function _uninstall($moduleName)
	{
		if ($this->_iaDb->exists('`name` = :name AND `type` = :type', ['name' => $moduleName, 'type' => iaModule::TYPE_PACKAGE]))
		{
			$installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

			if (!file_exists($installFile))
			{
				$this->addMessage('file_doesnt_exist');
			}
			else
			{
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
		$stmt = '`name` = :name AND `type` = :type';
		$this->_iaDb->bind($stmt, ['name' => $moduleName, 'type' => iaModule::TYPE_PACKAGE]);

		$status = $deactivate ? iaCore::STATUS_INACTIVE : iaCore::STATUS_ACTIVE;

		return (bool)$this->_iaDb->update(['status' => $status], $stmt);
	}

	private function _reset($domain)
	{
		$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;
		$url = '';

		switch($_GET['type'])
		{
			case 1:
				$url = 'http://' . iaSanitize::sql(str_replace('www.', '', $_GET['url'][1])) . '.' . $domain . IA_URL_DELIMITER;
				break;
			case 2:
				$url = $_GET['url'][2];
		}

		if ($url)
		{
			$url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;
			$this->_changeDefault($url);

			$this->addMessage('reset_default_success');

			return true;
		}
		else
		{
			return false;
		}
	}

	private function _setDefault($moduleName)
	{
		$this->_changeDefault((isset($_GET['url']) ? $_GET['url'][0] : ''), $moduleName);

		$installFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
		if (!file_exists($installFile))
		{
			$this->addMessage('file_doesnt_exist');

			return false;
		}

		$this->getHelper()->getFromPath($installFile);
//		$this->getHelper()->setUrl(IA_URL_DELIMITER);
//		$this->getHelper()->setXml(file_get_contents($installFile));
		$this->getHelper()->parse();
		$this->getHelper()->checkValidity();

		$pages = $this->getHelper()->itemData['pages']['front'];
		foreach ($pages as $page)
		{
			$this->_iaDb->update(['alias' => $page['alias']], "`name` = '{$page['name']}' AND `module` = '$moduleName'", null, 'pages');
		}

		$this->addMessage('set_default_success');

		if (!$this->_iaCore->get('default_package'))
		{
			$this->addMessage('reset_previous_default_success');
		}

		return true;
	}

	private function _changeDefault($url = '', $module = '')
	{
		$iaDb = &$this->_iaDb;

		$defaultPackage = $this->_iaCore->get('default_package');

		if ($defaultPackage != $module)
		{
			if ($defaultPackage)
			{
				$oldModule = $this->_iaCore->factory('module', iaCore::ADMIN);

//				$oldModule->setUrl(trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER);
//				$oldModule->setXml(file_get_contents($this->_folder . $defaultPackage . IA_DS . iaModule::INSTALL_FILE_NAME));
				$this->getHelper()->getFromPath($this->_folder . $defaultPackage . IA_DS . iaModule::INSTALL_FILE_NAME);
				$oldModule->parse();
				$oldModule->checkValidity();

				$iaDb->update(['url' => $oldModule->getUrl()], iaDb::convertIds($defaultPackage, 'name'));

				if ($oldModule->itemData['pages']['front'])
				{
					$iaDb->setTable('pages');
					foreach ($oldModule->itemData['pages']['front'] as $page)
					{
						$iaDb->update(['alias' => $page['alias']], "`name` = '{$page['name']}' AND `module` = '$defaultPackage'");
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

		while ($file = readdir($directory))
		{
			$installationFile = $this->_folder . $file . IA_DS . iaModule::INSTALL_FILE_NAME;
			if (substr($file, 0, 1) != '.' && is_dir($this->_folder . $file) && file_exists($installationFile))
			{
				if ($fileContents = file_get_contents($installationFile))
				{
					$this->getHelper()->setXml($fileContents);
					$this->getHelper()->parse();

					if (iaModule::TYPE_PACKAGE != $this->getHelper()->itemData['type'])
					{
						continue;
					}

					$this->getHelper()->itemData['url'] = '';

					$buttons = false;

					$version = explode('-', $this->getHelper()->itemData['compatibility']);
					if (!isset($version[1]))
					{
						if (version_compare($version[0], IA_VERSION, '<='))
						{
							$buttons = true;
						}
					}
					else
					{
						if (version_compare($version[0], IA_VERSION, '<=')
							&& version_compare($version[1], IA_VERSION, '>='))
						{
							$buttons = true;
						}
					}

					if (false === $buttons)
					{
						$this->getHelper()->itemData['compatibility'] = $this->getHelper()->itemData['compatibility'] . ' ' . iaLanguage::get('incompatible');
					}

					$data = &$this->getHelper()->itemData;
					$status = 'notinstall';
					$items = [
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
					if (isset($existPackages[$data['name']]))
					{
						$status = $statuses[$data['name']];
					}

					switch ($status)
					{
						case 'install':
						case 'active':
							$items['deactivate'] = true;
							$items['set_default'] = true;

							if (is_dir($this->_folder . $file . IA_DS . 'includes' . IA_DS . 'dumps'))
							{
								$items['import'] = true;
							}

							if ($extraConfig = $this->_iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`module` = :name ORDER BY `order` ASC', ['name' => $data['name']], iaCore::getConfigTable()))
							{
								$items['config'] = [
									'url' => $extraConfig['config_group'],
									'anchor' => $extraConfig['name']
								];
							}

							if ($alias = $this->_iaDb->one_bind('alias', '`name` = :name', ['name' => $data['name'] . '_manage'], 'admin_pages'))
							{
								$items['manage'] = $alias;
							}

							if ($buttons && version_compare($data['info']['version'], $existPackages[$data['name']], '>')
							)
							{
								$items['upgrade'] = true;
							}

							break;

						case 'inactive':
							$items['activate'] = true;
							$items['uninstall'] = true;

							break;

						case 'notinstall':
							$items['install'] = true;
					}

					$moduleNames[] = $data['name'];

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
						'items' => $items,
						'price' => 0,
						'status' => $status,
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

		if ($cachedData = $this->_iaCore->iaCache->get('subrion_packages', 3600 * 24 * 7, true))
		{
			$remotePackages = $cachedData; // get templates list from cache
		}
		else
		{
			if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/package/' . IA_VERSION))
			{
				$response = json_decode($response, true);
				if (!empty($response['error']))
				{
					$this->_messages[] = $response['error'];
					$this->_error = true;
				}
				elseif ($response['total'] > 0)
				{
					if (isset($response['extensions']) && is_array($response['extensions']))
					{
						foreach ($response['extensions'] as $entry)
						{
							$moduleInfo = (array)$entry;

							// exclude uploaded packages
							if (!in_array($moduleInfo['name'], $localPackages))
							{
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
					}
					else
					{
						$this->addMessage('error_incorrect_format_from_subrion');
						$this->_error = true;
					}
				}
			}
			else
			{
				$this->addMessage('error_incorrect_response_from_subrion');
				$this->_error = true;
			}
		}

		return $remotePackages;
	}

	private function _getRemotePlugins($start, $limit, $sort)
	{
		$pluginsData = [];

		if ($cachedData = $this->_iaCore->iaCache->get('subrion_plugins', 3600, true))
		{
			$pluginsData = $cachedData;
		}
		else
		{
			if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/plugin/' . IA_VERSION))
			{
				$response = json_decode($response, true);
				if (!empty($response['error']))
				{
					$this->addMessage($response['error']);
				}
				elseif ($response['total'] > 0)
				{
					if (isset($response['extensions']) && is_array($response['extensions']))
					{
						$pluginsData = [];
						$installedPlugins = $this->_iaDb->keyvalue(['name', 'version'], iaDb::convertIds(iaModule::TYPE_PLUGIN, 'type'));

						foreach ($response['extensions'] as $entry)
						{
							$pluginInfo = (array)$entry;
							$pluginInfo['install'] = 0;

							// exclude installed plugins
							if (!array_key_exists($pluginInfo['name'], $installedPlugins))
							{
								$pluginsData['pluginsList'][$pluginInfo['name']] = $pluginInfo['title'];

								if (isset($pluginInfo['compatibility']) && version_compare($pluginInfo['compatibility'], IA_VERSION, '<='))
								{
									$pluginInfo['install'] = 1;
								}
								$pluginInfo['date'] = gmdate(iaDb::DATE_FORMAT, $pluginInfo['date']);
								$pluginInfo['file'] = $pluginInfo['name'];
								$pluginInfo['readme'] = false;
								$pluginInfo['reinstall'] = false;
								$pluginInfo['uninstall'] = false;
								$pluginInfo['remove'] = false;
								$pluginInfo['removable'] = false;

								$pluginsData['plugins'][$pluginInfo['name']] = $pluginInfo;
							}
						}

						// cache well-formed results
						$this->_iaCore->iaCache->write('subrion_plugins', $pluginsData);
					}
					else
					{
						$this->addMessage('error_incorrect_format_from_subrion');
					}
				}
			}
			else
			{
				$this->addMessage('error_incorrect_response_from_subrion');
			}
		}

		return $this->getMessages()
			? ['result' => false, 'message' => $this->getMessages()]
			: $pluginsData;
	}

	private function _getLocalPlugins($start, $limit, $sort)
	{
		$total = 0;
		$pluginsData = [];
		$installedPlugins = $this->_iaDb->assoc(['name', 'status', 'version'], iaDb::convertIds(iaModule::TYPE_PLUGIN, 'type'));

		$directory = opendir($this->_folder);
		while ($file = readdir($directory))
		{
			if (substr($file, 0, 1) != '.' && is_dir($this->_folder . $file))
			{
				if (is_file($installationFile = $this->_folder . $file . IA_DS . iaModule::INSTALL_FILE_NAME))
				{
					if ($fileContent = file_get_contents($installationFile))
					{
						$this->getHelper()->setXml($fileContent);
						$this->getHelper()->parse(true);

						if (iaModule::TYPE_PLUGIN != $this->getHelper()->itemData['type'])
						{
							continue;
						}
						/*
						$installationPossible = false;
						if (!$this->getHelper()->getNotes())
						{
							$version = explode('-', $this->getHelper()->itemData['compatibility']);
							if (!isset($version[1]))
							{
								if (version_compare($version[0], IA_VERSION, '<='))
								{
									$installationPossible = true;
								}
							}
							else
							{
								if (version_compare($version[0], IA_VERSION, '<=')
									&& version_compare($version[1], IA_VERSION, '>='))
								{
									$installationPossible = true;
								}
							}
						}
						*/
						$buttons = [];
						$notes = $this->getHelper()->getNotes();
						if ($notes)
						{
							$notes = implode(PHP_EOL, $notes);
							$notes .= PHP_EOL . PHP_EOL . iaLanguage::get('installation_impossible');
						}

						$moduleData = [
							'title' => $this->getHelper()->itemData['info']['title'],
							'version' => $this->getHelper()->itemData['info']['version'],
							'compatibility' => $this->getHelper()->itemData['compatibility'],
							'summary' => $this->getHelper()->itemData['info']['summary'],
							'author' => $this->getHelper()->itemData['info']['author'],
							'date' => $this->getHelper()->itemData['info']['date'],
							'name' => $this->getHelper()->itemData['name'],
							'file' => $file,
							'buttons' => $buttons,
							'status' => '',
							'notes' => $notes,
							'price' => '',
							'info' => true,
							'install' => true,
							'remote' => false,
							'logo' => IA_CLEAR_URL . 'modules/' . $file . '/docs/img/icon.png',
						];

						if (array_key_exists($this->getHelper()->itemData['name'], $installedPlugins))
						{
							$moduleData['status'] = $installedPlugins[$this->getHelper()->itemData['name']]['status'];
						}


						$pluginsData['plugins'][$this->getHelper()->itemData['name']] = $moduleData;
						$pluginsData['pluginsList'][$this->getHelper()->itemData['name']] = $this->getHelper()->itemData['info']['title'];

						$total++;
					}
				}
			}
		}
		closedir($directory);

		return array($pluginsData['plugins'], $pluginsData['pluginsList']);
	}

	private function _getInstalledPlugins($start, $limit, $sort, $dir, $filter)
	{
		$where = "`type` = '" . iaModule::TYPE_PLUGIN . "'" . (empty($filter) ? '' : " AND `title` LIKE '%{$filter}%'");
		$order = ($sort && $dir) ? " ORDER BY `{$sort}` {$dir}" : '';

		$result = [
			'data' => $this->_iaDb->all(['id', 'name', 'title', 'version', 'status', 'author', 'summary', 'removable', 'date'], $where . $order, $start, $limit),
			'total' => $this->_iaDb->one(iaDb::STMT_COUNT_ROWS, $where)
		];

		if ($result['data'])
		{
			foreach ($result['data'] as &$entry)
			{
				if ($row = $this->_iaDb->row_bind(['name', 'config_group'], '`module` = :plugin ORDER BY `order` ASC', ['plugin' => $entry['name']], iaCore::getConfigTable()))
				{
					$entry['config'] = $row['config_group'] . '/#' . $row['name'] . '';
				}

				if ($alias = $this->_iaDb->one_bind('alias', '`name` = :name', ['name' => $entry['name']], 'admin_pages'))
				{
					$entry['manage'] = $alias;
				}

				$entry['file'] = $entry['name'];
				$entry['info'] = true;
				$entry['reinstall'] = true;
				$entry['uninstall'] = $entry['removable'];
				$entry['remove'] = $entry['removable'];

				if (is_dir(IA_MODULES . $entry['name']))
				{
					$installationFile = IA_MODULES . $entry['name'] . IA_DS . iaModule::INSTALL_FILE_NAME;

					if (file_exists($installationFile))
					{
						$fileContent = file_get_contents($installationFile);

						$this->getHelper()->setXml($fileContent);
						$this->getHelper()->parse();

						if (($this->getHelper()->itemData['compatibility'] && version_compare(IA_VERSION, $this->getHelper()->itemData['compatibility'], '>=')) && version_compare($this->getHelper()->itemData['info']['version'], $entry['version'], '>'))
						{
							$entry['upgrade'] = $entry['name'];
						}

						$entry['name'] = $this->getHelper()->itemData['name'];
					}
				}
			}
		}

		return $result;
	}

	private function _installPlugin($moduleName, $action)
	{
		$result = ['error' => true];

		if (isset($_POST['mode']) && 'remote' == $_POST['mode'])
		{
			$modulesTempFolder = IA_TMP . 'modules' . IA_DS;
			is_dir($modulesTempFolder) || mkdir($modulesTempFolder);

			$filePath = $modulesTempFolder . $moduleName;
			$fileName = $filePath . '.zip';

			// save remote plugin file
			iaUtil::downloadRemoteContent(iaUtil::REMOTE_TOOLS_URL . 'install/' . $moduleName . IA_URL_DELIMITER . IA_VERSION, $fileName);

			if (file_exists($fileName))
			{
				if (is_writable($this->_folder))
				{
					// delete previous folder
					if (is_dir($this->_folder . $moduleName))
					{
						unlink($this->_folder . $moduleName);
					}

					include_once (IA_INCLUDES . 'utils' . IA_DS . 'pclzip.lib.php');

					$pclZip = new PclZip($fileName);
					$pclZip->extract(PCLZIP_OPT_PATH, IA_MODULES . $moduleName);

					$this->_iaCore->iaCache->remove('subrion_plugins');
				}
				else
				{
					$result['message'] = iaLanguage::get('upload_module_error');
				}
			}
		}

		$iaModule = $this->getHelper();

		$installationFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
		if (!file_exists($installationFile))
		{
			$result['message'] = iaLanguage::get('file_doesnt_exist');
		}
		else
		{
			$iaModule->setXml(file_get_contents($installationFile));
			$result['error'] = false;
		}

		$iaModule->parse();

		$installationPossible = false;
		$version = explode('-', $iaModule->itemData['compatibility']);
		if (!isset($version[1]))
		{
			if (version_compare($version[0], IA_VERSION, '<='))
			{
				$installationPossible = true;
			}
		}
		else
		{
			if (version_compare($version[0], IA_VERSION, '<=')
				&& version_compare($version[1], IA_VERSION, '>='))
			{
				$installationPossible = true;
			}
		}

		if (!$installationPossible)
		{
			$result['message'] = iaLanguage::get('incompatible');
			$result['error'] = true;
		}

		if (!$result['error'])
		{
			$iaModule->doAction(iaModule::ACTION_INSTALL);
			if ($iaModule->error)
			{
				$result['message'] = $iaModule->getMessage();
				$result['error'] = true;
			}
			else
			{
				$iaLog = $this->_iaCore->factory('log');

				if ($iaModule->isUpgrade)
				{
					$result['message'] = iaLanguage::get('plugin_updated');

					$iaLog->write(iaLog::ACTION_UPGRADE, ['type' => iaModule::TYPE_PLUGIN, 'name' => $iaModule->itemData['info']['title'], 'to' => $iaModule->itemData['info']['version']]);
				}
				else
				{
					$result['groups'] = $iaModule->getMenuGroups();
					$result['message'] = (iaModule::ACTION_INSTALL == $action)
						? iaLanguage::getf('plugin_installed', ['name' => $iaModule->itemData['info']['title']])
						: iaLanguage::getf('plugin_reinstalled', ['name' => $iaModule->itemData['info']['title']]);

					$iaLog->write(iaLog::ACTION_INSTALL, ['type' => iaModule::TYPE_PLUGIN, 'name' => $iaModule->itemData['info']['title']]);
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

		if ($this->_iaDb->exists('`name` = :plugin AND `type` = :type AND `removable` = 1', ['plugin' => $moduleName, 'type' => iaModule::TYPE_PLUGIN]))
		{
			$installationFile = $this->_folder . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;

			if (!file_exists($installationFile))
			{
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
		}
		else
		{
			$result['message'] = iaLanguage::get('plugin_may_not_be_removed');
		}

		return $result;
	}

	private function _installTemplate($moduleName)
	{
		$iaModule = $this->getHelper();

		if (empty($moduleName))
		{
			$iaModule->error = true;
			$this->_messages[] = iaLanguage::get('template_name_empty');
		}

		if (!is_dir(IA_FRONT_TEMPLATES . $moduleName) && !$iaModule->error)
		{
			$iaModule->error = true;
			$this->_messages[] = iaLanguage::get('template_folder_error');
		}

		$installFile = IA_FRONT_TEMPLATES . $moduleName . IA_DS . iaModule::INSTALL_FILE_NAME;
		if (!file_exists($installFile) && !$iaModule->error)
		{
			$iaModule->error = true;
			$this->_messages[] = iaLanguage::getf('template_file_error', ['file' => $moduleName]);
		}

		if (!$iaModule->error)
		{
			$iaModule->getFromPath($installFile);
			$iaModule->parse();
			$iaModule->checkValidity();
			$iaModule->rollback();
			$iaModule->install();

			if (!$iaModule->error)
			{
				return true;
			}

			$iaModule->error = true;
			$this->_messages[] = $iaModule->getMessage();
		}

		return false;
	}

	private function _downloadTemplate($moduleName)
	{
		$templatesTempFolder = IA_TMP . 'templates' . IA_DS;
		if (!is_dir($templatesTempFolder))
		{
			mkdir($templatesTempFolder);
		}

		$filePath = $templatesTempFolder . $moduleName;
		$fileName = $filePath . '.zip';

		// save remote template file
		iaUtil::downloadRemoteContent(iaUtil::REMOTE_TOOLS_URL . 'install/' . $moduleName . IA_URL_DELIMITER . IA_VERSION, $fileName);

		if (file_exists($fileName))
		{
			if (is_writable(IA_FRONT_TEMPLATES))
			{
				// delete previous folder
				if (is_dir(IA_FRONT_TEMPLATES . $moduleName))
				{
					unlink(IA_FRONT_TEMPLATES . $moduleName);
				}

				include_once (IA_INCLUDES . 'utils' . IA_DS . 'pclzip.lib.php');

				$pclZip = new PclZip($fileName);
				if ($result = $pclZip->extract(PCLZIP_OPT_PATH, IA_FRONT_TEMPLATES . $moduleName))
				{
					$this->addMessage(iaLanguage::getf('template_downloaded', ['name' => $moduleName]), false);
				}
				else
				{
					$this->error = true;
					$this->addMessage('error_incorrect_format_from_subrion');
				}

				return (bool)$result;
			}
			else
			{
				$this->_error = true;
				$this->addMessage('upload_template_error');
			}
		}

		return false;
	}

	private function _getTemplatesList()
	{
		$templates = $this->_getLocalTemplates(); // get list of available local templates
		$remoteTemplates = [];

		if ($this->_iaCore->get('allow_remote_templates'))
		{
			if ($cachedData = $this->_iaCore->iaCache->get('subrion_templates', 3600, true))
			{
				$remoteTemplates = $cachedData; // get templates list from cache, cache lives for 1 hour
			}
			else
			{
				if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/template/' . IA_VERSION))
				{
					$response = json_decode($response, true);
					if (!empty($response['error']))
					{
						$this->_messages[] = $response['error'];
						$this->getHelper()->error = true;
					}
					elseif ($response['total'] > 0)
					{
						if (isset($response['extensions']) && is_array($response['extensions']))
						{
							foreach ($response['extensions'] as $entry)
							{
								$templateInfo = (array)$entry;
								$templateInfo['summary'] = $templateInfo['description'];

								// exclude installed templates
								if (!array_key_exists($templateInfo['name'], $templates))
								{
									$templateInfo['date'] = gmdate(iaDb::DATE_FORMAT, $templateInfo['date']);
									$templateInfo['buttons'] = '';
									$templateInfo['notes'] = [];
									$templateInfo['remote'] = true;

									$remoteTemplates[] = $templateInfo;
								}
							}

							// cache well-formed results
							$this->_iaCore->iaCache->write('subrion_templates', $remoteTemplates);
						}
						else
						{
							$this->addMessage('error_incorrect_format_from_subrion');
							$this->getHelper()->error = true;
						}
					}
				}
				else
				{
					$this->addMessage('error_incorrect_response_from_subrion');
					$this->getHelper()->error = true;
				}
			}
		}
		$_templates = array_merge($templates, $remoteTemplates);

		$moduleName = $this->_iaCore->get('tmpl');
		$activeTemplate = $_templates[$moduleName];
		unset($_templates[$moduleName]);

		return [$moduleName => $activeTemplate] + $_templates;
	}

	private function _getLocalTemplates()
	{
		$templates = [];

		$directory = opendir(IA_FRONT_TEMPLATES);
		while ($file = readdir($directory))
		{
			if (substr($file, 0, 1) != '.')
			{
				if (is_dir(IA_FRONT_TEMPLATES . $file))
				{
					$installFile = IA_FRONT_TEMPLATES . $file . IA_DS . iaModule::INSTALL_FILE_NAME;
					if (file_exists($installFile))
					{
						$this->getHelper()->getFromPath($installFile);
						$this->getHelper()->parse(true);
						$this->getHelper()->checkValidity($file);

						$moduleData = $this->getHelper()->itemData;

						if ($file == $moduleData['name'])
						{
							$buttons = [];
							if (!$this->getHelper()->getNotes())
							{
								$version = explode('-', $moduleData['compatibility']);

								if (!isset($version[1]))
								{
									$buttons = (bool)version_compare($version[0], IA_VERSION, '>=');
								}
								else
								{
									if (version_compare($version[0], IA_VERSION, '>=') && version_compare($version[1], IA_VERSION, '<='))
									{
										$buttons = true;
									}
								}
							}
							$module['compatible'] = false;

							$module = [
								'name' => $moduleData['name'],
								'title' => $moduleData['info']['title'],
								'author' => $moduleData['info']['author'],
								'contributor' => $moduleData['info']['contributor'],
								'date' => $moduleData['info']['date'],
								'summary' => $moduleData['info']['summary'],
								'version' => $moduleData['info']['version'],
								'compatibility' => $moduleData['compatibility'],
								'compatible' => (bool)($buttons === []),
								'buttons' => $buttons,
								'notes' => $this->getHelper()->getNotes(),
								'config' => $moduleData['config'],
								'config_groups' => $moduleData['config_groups'],
								'url' => 'https://subrion.org/template/' . $moduleData['name'] . '.html',
								'logo' => IA_CLEAR_URL . 'templates/' . $moduleData['name'] . '/docs/img/icon.png',
							];

							$modules[$moduleData['name']] = $module;
						}
						else
						{
							$this->_iaCore->iaView->setMessages($this->getHelper()->getMessage(), iaView::ERROR);
						}
					}
				}
			}
		}
		closedir($directory);

		return $modules;
	}
}