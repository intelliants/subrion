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
	protected $_name = 'packages';

	protected $_processAdd = false;
	protected $_processEdit = false;

	private $_folder;


	public function __construct()
	{
		parent::__construct();

		$iaExtra = $this->_iaCore->factory('extra', iaCore::ADMIN);

		$this->setHelper($iaExtra);
		$this->setTable(iaExtra::getTable());

		$this->_folder = IA_PACKAGES;
	}

	protected function _indexPage(&$iaView)
	{
		if (2 == count($this->_iaCore->requestPath))
		{
			$this->_processAction($iaView);
		}

		list($localPackages, $packageNames) = $this->_getList();
		$remotePackages = $this->getRemoteList($packageNames);

		$packages = array_merge($localPackages, $remotePackages);
		$iaView->assign('packages', $packages);

		$iaView->display($this->getName());
	}

	protected function _gridRead($params)
	{
		return (1 == count($this->_iaCore->requestPath) && 'documentation' == $this->_iaCore->requestPath[0])
			? $this->_getDocumentation($params['name'], $this->_iaCore->iaView)
			: array();
	}


	private function _getDocumentation($packageName, &$iaView)
	{
		$result = array();

		if (file_exists($documentationPath = $this->_folder . $packageName . IA_DS . 'docs' . IA_DS))
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
						$result['tabs'][] = array(
							'title' => iaLanguage::get('extra_' . $tab, ucfirst($tab)),
							'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="http://dev.subrion.org/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
							'cls' => 'extension-docs'
						);
					}
				}
			}

			$this->getHelper()->setXml(file_get_contents($this->_folder . $packageName . IA_DS . iaExtra::INSTALL_FILE_NAME));
			$this->getHelper()->parse();

			$search = array(
				'{icon}',
				'{link}',
				'{name}',
				'{author}',
				'{contributor}',
				'{version}',
				'{date}',
				'{compatibility}',
			);

			$icon = '';
			if (file_exists($this->_folder . $packageName . IA_DS . 'docs' . IA_DS . 'img' . IA_DS . 'icon.png'))
			{
				$icon = '<tr><td class="plugin-icon"><img src="' . $iaView->assetsUrl . 'packages/' . $packageName . '/docs/img/icon.png" alt=""></td></tr>';
			}
			$data = &$this->getHelper()->itemData;

			$replacement = array(
				$icon,
				'',
				$data['info']['title'],
				$data['info']['author'],
				$data['info']['contributor'],
				$data['info']['version'],
				$data['info']['date'],
				$data['compatibility']
			);

			$result['info'] = str_replace($search, $replacement,
				file_get_contents(IA_ADMIN . 'templates' . IA_DS . $this->_iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
		}

		return $result;
	}

	private function _processAction(&$iaView)
	{
		$iaAcl = $this->_iaCore->factory('acl');
		$iaLog = $this->_iaCore->factory('log');

		$package = iaSanitize::sql($this->_iaCore->requestPath[0]);
		$action = $this->_iaCore->requestPath[1];
		$error = false;

		switch ($action)
		{
			case 'activate':
			case 'deactivate':
				if (!$iaAcl->isAccessible($this->getName(), 'activate'))
				{
					return iaView::accessDenied();
				}

				$deactivate = ('deactivate' == $action);

				if ($this->_activate($package, $deactivate))
				{
					$this->_iaCore->startHook($deactivate ? 'phpPackageDeactivated' : 'phpPackageActivated',
						array('extra' => $package));
					$iaLog->write($deactivate ? iaLog::ACTION_DISABLE : iaLog::ACTION_ENABLE,
						array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package), $package);
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

				$error = !$this->_setDefault($package);

				break;

			case 'reset':
				if (!$iaAcl->isAccessible($this->getName(), 'set_default'))
				{
					return iaView::accessDenied();
				}

				$error = !$this->_reset($iaView->domain);

				break;

			case iaExtra::ACTION_INSTALL:
			case iaExtra::ACTION_UPGRADE:
				if (!$iaAcl->isAccessible($this->getName(), $action))
				{
					return iaView::accessDenied();
				}

				if ($this->_install($package, $action, $iaView->domain))
				{
					// log this event
					$action = $this->getHelper()->isUpgrade ? iaLog::ACTION_UPGRADE : iaLog::ACTION_INSTALL;
					$iaLog->write($action, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package, 'to' => $this->getHelper()->itemData['info']['version']), $package);
					//

					$iaSitemap = $this->_iaCore->factory('sitemap', iaCore::ADMIN);
					$iaSitemap->generate();
				}
				else
				{
					$error = true;
				}

				break;

			case iaExtra::ACTION_UNINSTALL:
				if (!$iaAcl->isAccessible($this->getName(), $action))
				{
					return iaView::accessDenied();
				}

				if ($this->_uninstall($package))
				{
					$iaLog->write(iaLog::ACTION_UNINSTALL, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package), $package);
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

	private function _install($packageName, $action, $domain)
	{
		$extraInstallFile = $this->_folder . $packageName . IA_DS . iaExtra::INSTALL_FILE_NAME;

		if (file_exists($extraInstallFile))
		{
			$this->getHelper()->setXml(file_get_contents($extraInstallFile));

			$url = '';
			$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;

			switch($_GET['type'])
			{
				case 1:
					$url = 'http://' . iaSanitize::sql(str_replace('www.', '', $_GET['url'][1])) . '.' . $domain . IA_URL_DELIMITER;
					break;
				case 2:
					$url = ($action == iaExtra::ACTION_UPGRADE)
						? $this->_iaDb->one('url', "`name` = '{$packageName}' AND `type` = 'package'")
						: $_GET['url'][2];
			}

			$url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;

			$this->getHelper()->doAction(iaExtra::ACTION_INSTALL, $url);

			if ($this->getHelper()->error)
			{
				$this->addMessage($this->getHelper()->getMessage());
			}
			else
			{
				if ($_GET['type'] == 0)
				{
					$this->_changeDefault(isset($_GET['url'][0]) ? $_GET['url'][0] : '', $packageName);
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

	private function _uninstall($packageName)
	{
		if ($this->_iaDb->exists('`name` = :name AND `type` = :type', array('name' => $packageName, 'type' => iaExtra::TYPE_PACKAGE)))
		{
			$extraInstallFile = $this->_folder . $packageName . IA_DS . iaExtra::INSTALL_FILE_NAME;

			if (!file_exists($extraInstallFile))
			{
				$this->addMessage('file_doesnt_exist');
			}
			else
			{
				$this->getHelper()->setXml(file_get_contents($extraInstallFile));
				$this->getHelper()->uninstall($packageName);

				$this->addMessage('package_uninstalled');

				return true;
			}
		}

		return false;
	}

	private function _activate($packageName, $deactivate)
	{
		$stmt = '`name` = :name AND `type` = :type';
		$this->_iaDb->bind($stmt, array('name' => $packageName, 'type' => iaExtra::TYPE_PACKAGE));

		$status = $deactivate ? iaCore::STATUS_INACTIVE : iaCore::STATUS_ACTIVE;

		return (bool)$this->_iaDb->update(array('status' => $status), $stmt);
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

	private function _setDefault($packageName)
	{
		$this->_changeDefault((isset($_GET['url']) ? $_GET['url'][0] : ''), $packageName);

		$extraInstallFile = $this->_folder . $packageName . IA_DS . iaExtra::INSTALL_FILE_NAME;
		if (!file_exists($extraInstallFile))
		{
			$this->addMessage('file_doesnt_exist');

			return false;
		}

		$this->getHelper()->setUrl(IA_URL_DELIMITER);
		$this->getHelper()->setXml(file_get_contents($extraInstallFile));
		$this->getHelper()->parse();
		$this->getHelper()->checkValidity();

		$pages = $this->getHelper()->itemData['pages']['front'];
		foreach ($pages as $page)
		{
			$this->_iaDb->update(array('alias' => $page['alias']), "`name` = '{$page['name']}' AND `extras` = '$packageName'", null, 'pages');
		}

		$this->addMessage('set_default_success');

		if (!$this->_iaCore->get('default_package'))
		{
			$this->addMessage('reset_previous_default_success');
		}

		return true;
	}

	private function _changeDefault($url = '', $package = '')
	{
		$iaDb = &$this->_iaDb;

		$defaultPackage = $this->_iaCore->get('default_package');

		if ($defaultPackage != $package)
		{
			if ($defaultPackage)
			{
				$oldExtras = $this->_iaCore->factory('extra', iaCore::ADMIN);

				$oldExtras->setUrl(trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER);
				$oldExtras->setXml(file_get_contents($this->_folder . $defaultPackage . IA_DS . iaExtra::INSTALL_FILE_NAME));
				$oldExtras->parse();
				$oldExtras->checkValidity();

				$iaDb->update(array('url' => $oldExtras->getUrl()), iaDb::convertIds($defaultPackage, 'name'));

				if ($oldExtras->itemData['pages']['front'])
				{
					$iaDb->setTable('pages');
					foreach ($oldExtras->itemData['pages']['front'] as $page)
					{
						$iaDb->update(array('alias' => $page['alias']), "`name` = '{$page['name']}' AND `extras` = '$defaultPackage'");
					}
					$iaDb->resetTable();
				}
			}

			$iaDb->update(array('url' => IA_URL_DELIMITER), iaDb::convertIds($package, 'name'));
			$this->_iaCore->set('default_package', $package, true);

			$iaDb->setTable('hooks');
			$iaDb->update(array('status' => iaCore::STATUS_INACTIVE), "`name` = 'phpCoreUrlRewrite'");
			$iaDb->update(array('status' => iaCore::STATUS_ACTIVE), "`name` = 'phpCoreUrlRewrite' AND `extras` = '$package'");
			$iaDb->resetTable();
		}
	}

	private function _getList()
	{
		$stmt = iaDb::convertIds(iaExtra::TYPE_PACKAGE, 'type');

		$existPackages = $this->_iaDb->keyvalue(array('name', 'version'), $stmt);
		$existPackages || $existPackages = array();
		$statuses = $this->_iaDb->keyvalue(array('name', 'status'), $stmt);
		$dates = $this->_iaDb->keyvalue(array('name', 'date'), $stmt);

		$directory = opendir($this->_folder);
		$result = $packageNames = array();

		while ($file = readdir($directory))
		{
			$installationFile = $this->_folder . $file . IA_DS . iaExtra::INSTALL_FILE_NAME;
			if (substr($file, 0, 1) != '.' && is_dir($this->_folder . $file) && file_exists($installationFile))
			{
				if ($fileContents = file_get_contents($installationFile))
				{
					$this->getHelper()->itemData['screenshots'] = array();
					$this->getHelper()->itemData['url'] = '';

					$this->getHelper()->setXml($fileContents);
					$this->getHelper()->parse();

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
						$this->getHelper()->itemData['compatibility'] = '<span style="color:red">' . $this->getHelper()->itemData['compatibility'] . ' ' . iaLanguage::get('incompatible') . '</span>';
					}

					$data = &$this->getHelper()->itemData;
					$status = 'notinstall';
					$preview = array();
					$screenshots = array();
					$items = array(
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
					);
					if (isset($existPackages[$data['name']]))
					{
						$status = $statuses[$data['name']];
					}

					if ($array = $data['screenshots'])
					{
						foreach ($array as $key => $value)
						{
							('preview' == $value['type'])
								? $preview[] = $value
								: $screenshots[] = $value;
						}
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

							if ($extraConfig = $this->_iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`extras` = :name ORDER BY `order` ASC', array('name' => $data['name']), iaCore::getConfigTable()))
							{
								$items['config'] = array(
									'url' => $extraConfig['config_group'],
									'anchor' => $extraConfig['name']
								);
							}

							if ($alias = $this->_iaDb->one_bind('alias', '`name` = :name', array('name' => $data['name'] . '_manage'), 'admin_pages'))
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

					$packageNames[] = $data['name'];

					$result[] = array(
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
						'preview' => $preview,
						'logo' => IA_CLEAR_URL . 'packages/' . $data['name'] . '/docs/img/icon.png',
						'screenshots' => $screenshots,
						'file' => $file,
						'items' => $items,
						'price' => 0,
						'status' => $status,
						'date_updated' => ($status != 'notinstall') ? $dates[$data['name']] : false,
						'install' => true,
						'remote' => false
					);
				}
			}
		}

		return array($result, $packageNames);
	}

	private function getRemoteList($localPackages)
	{
		$remotePackages = array();

		if ($cachedData = $this->_iaCore->iaCache->get('subrion_packages', 3600 * 24 * 7, true))
		{
			$remotePackages = $cachedData; // get templates list from cache
		}
		else
		{
			if ($response = iaUtil::getPageContent(iaUtil::REMOTE_TOOLS_URL . 'list/package/' . IA_VERSION))
			{
				$response = iaUtil::jsonDecode($response);
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
							$packageInfo = (array)$entry;

							// exclude uploaded packages
							if (!in_array($packageInfo['name'], $localPackages))
							{
								$packageInfo['date'] = gmdate(iaDb::DATE_FORMAT, $packageInfo['date']);
								$packageInfo['status'] = '';
								$packageInfo['summary'] = $packageInfo['description'];
								$packageInfo['buttons'] = false;
								$packageInfo['remote'] = true;

								$remotePackages[] = $packageInfo;
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
}