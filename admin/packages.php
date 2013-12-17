<?php
//##copyright##

$iaExtra = $iaCore->factory('extra', iaCore::ADMIN);

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if ('info' == $_POST['action'])
	{
		$output = array('tabs' => null);
		$packageName = $_POST['name'];

		if (file_exists($documentationPath = IA_PACKAGES . $packageName . IA_DS . 'docs' . IA_DS))
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
						$output['tabs'][] = array(
							'title' => iaLanguage::get('extra_' . $tab, ucfirst($tab)),
							'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="http://dev.subrion.com/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
							'cls' => 'extension-docs'
						);
					}
				}
			}

			$iaExtra->setXml(file_get_contents(IA_PACKAGES . $packageName . IA_DS . iaExtra::INSTALL_FILE_NAME));
			$iaExtra->parse();

			$search = array(
				'{icon}',
				'{name}',
				'{author}',
				'{contributor}',
				'{version}',
				'{date}',
				'{compatibility}',
			);
			$icon = '';
			if (file_exists(IA_PACKAGES . $packageName . IA_DS . 'docs' . IA_DS . 'img' . IA_DS . 'icon.png'))
			{
				$icon = '<tr><td class="plugin-icon"><img src="' . IA_CLEAR_URL . 'packages/' . $packageName . '/docs/img/icon.png" alt="" /></td></tr>';
			}
			$replacement = array(
				$icon,
				$iaExtra->itemData['info']['title'],
				$iaExtra->itemData['info']['author'],
				$iaExtra->itemData['info']['contributor'],
				$iaExtra->itemData['info']['version'],
				$iaExtra->itemData['info']['date'],
				$iaExtra->itemData['compatibility']
			);

			$output['info'] = str_replace($search, $replacement,
				file_get_contents(IA_ADMIN . 'templates' . IA_DS . $iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl'));
		}

		$iaView->assign($output);
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaDb->setTable(iaExtra::getTable());

	if (isset($iaCore->requestPath[0]) && isset($iaCore->requestPath[1]))
	{
		$package = iaSanitize::sql($iaCore->requestPath[0]);
		$action = $iaCore->requestPath[1];
		$message = iaLanguage::get('invalid_parameters');
		$error = true;

		switch ($action)
		{
			case 'activate':
				$iaAcl->checkPage($permission . 'activate');
				$message = iaLanguage::get('package_activated');
				$error = false;

				if ($iaDb->update(array('status' => iaCore::STATUS_ACTIVE), "`name` = '{$package}' AND `type` = 'package'"))
				{
					$iaCore->factory('log')->write(iaLog::ACTION_ENABLE, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package), $package);
				}

				break;

			case 'deactivate':
				$iaAcl->checkPage($permission . 'activate');
				$message = iaLanguage::get('package_deactivated');
				$error = false;

				if ($iaDb->update(array('status' => iaCore::STATUS_INACTIVE), "`name` = '{$package}' AND `type` = 'package'"))
				{
					$iaCore->factory('log')->write(iaLog::ACTION_DISABLE, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package), $package);
				}

				break;

			case 'set_default':
				$iaAcl->checkPage($permission . 'set_default');

				change_default((isset($_GET['url']) ? $_GET['url'][0] : ''), $package);

				$extraInstallFile = IA_PACKAGES . $package . IA_DS . iaExtra::INSTALL_FILE_NAME;
				if (!file_exists($extraInstallFile))
				{
					$message = iaLanguage::get('file_doesnt_exist');
				}
				else
				{
					$iaExtra->setXml(file_get_contents($extraInstallFile));
					$error = false;
				}

				$iaExtra->setUrl(IA_URL_DELIMITER);
				$iaExtra->setXml(file_get_contents(IA_PACKAGES . $package . IA_DS . iaExtra::INSTALL_FILE_NAME));
				$iaExtra->parse();
				$iaExtra->checkValidity();

				$pages = $iaExtra->itemData['pages']['front'];
				foreach ($pages as $page)
				{
					$iaDb->update(array('alias' => $page['alias']), "`name` = '{$page['name']}' AND `extras` = '$package'", null, 'pages');
				}

				$message = array(iaLanguage::get('set_default_success'));

				if ($iaCore->get('default_package', '') == '')
				{
					$message[] = iaLanguage::get('reset_previous_default_success');
				}
				$error = false;

				break;

			case 'reset':
				$iaAcl->checkPage($permission . 'set_default');
				$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;
				$url = '';
				switch($_GET['type'])
				{
					case 1:
						$url = 'http://' . iaSanitize::sql(str_replace('www.', '', $_GET['url'][1])) . '.' . $iaView->domain . IA_URL_DELIMITER;
						break;
					case 2:
						$url = $_GET['url'][2];
				}

				if ($url)
				{
					$url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;
					change_default($url);
				}

				$message = iaLanguage::get('reset_default_success');
				$error = false;

				break;

			case iaExtra::ACTION_INSTALL:
			case iaExtra::ACTION_UPGRADE:
				$iaAcl->checkPage($permission . $action);
				$extraInstallFile = IA_PACKAGES . $package . IA_DS . iaExtra::INSTALL_FILE_NAME;

				if (!file_exists($extraInstallFile))
				{
					$message = iaLanguage::get('file_doesnt_exist');
				}
				else
				{
					$iaExtra->setXml(file_get_contents($extraInstallFile));
					$error = false;
				}

				if (!$error)
				{
					$url = '';
					$_GET['type'] = isset($_GET['type']) ? $_GET['type'] : 2;

					switch($_GET['type'])
					{
						case 1:
							$url = 'http://' . iaSanitize::sql(str_replace('www.', '', $_GET['url'][1])) . '.' . $iaView->domain . IA_URL_DELIMITER;
							break;
						case 2:
							$url = ($action == iaExtra::ACTION_UPGRADE)
								? $iaDb->one('`url`', "`name` = '{$package}' AND `type` = 'package'")
								: $_GET['url'][2];
					}

					$url = trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER;

					$iaExtra->doAction(iaExtra::ACTION_INSTALL, $url);

					if ($iaExtra->error)
					{
						$message = $iaExtra->getMessage();
						$error = true;
					}
					else
					{
						if ($_GET['type'] == 0)
						{
							change_default(isset($_GET['url'][0]) ? $_GET['url'][0] : '', $package);
						}

						$message = $iaExtra->isUpgrade
							? iaLanguage::get('package_updated')
							: iaLanguage::get('package_installed');

						// log this event
						$iaLog = $iaCore->factory('log');
						$action = $iaExtra->isUpgrade ? iaLog::ACTION_UPGRADE : iaLog::ACTION_INSTALL;
						$iaLog->write($action, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package, 'to' => $iaExtra->itemData['info']['version']), $package);
						//

						$iaCore->factory('sitemap', iaCore::ADMIN)->generate();
					}
				}

				break;

			case 'uninstall':
				$iaAcl->checkPage($permission . 'uninstall');
				$message = iaLanguage::get($action);
				$error = false;
				if ($iaDb->exists("`name` = :name AND `type` = :type", array('name' => $package, 'type' => iaExtra::TYPE_PACKAGE)))
				{
					$extraInstallFile = IA_PACKAGES . $package . IA_DS . iaExtra::INSTALL_FILE_NAME;

					if (!file_exists($extraInstallFile))
					{
						$message = iaLanguage::get('file_doesnt_exist');
						$error = true;
					}
					else
					{
						$iaExtra->setXml(file_get_contents($extraInstallFile));
						$error = false;
					}

					if (!$error)
					{
						$iaExtra->uninstall($package);

						$message = iaLanguage::get('package_uninstalled');
						$error = false;

						// log this event
						$iaCore->factory('log')->write(iaLog::ACTION_UNINSTALL, array('type' => iaExtra::TYPE_PACKAGE, 'name' => $package), $package);
						//
					}
				}
		}

		$iaCore->factory('cache')->clearAll();

		$iaView->setMessages($message, $error ? iaView::ERROR : iaView::SUCCESS);

		iaCore::util();
		iaUtil::go_to(IA_ADMIN_URL . 'packages/');
	}

	$stmt = "`type` = 'package'";
	$existPackages = $iaDb->keyvalue(array('name', 'version'), $stmt);
	$statuses = $iaDb->keyvalue(array('name', 'status'), $stmt);
	$dates = $iaDb->keyvalue(array('name', 'date'), $stmt);

	if (empty($existPackages))
	{
		$existPackages = array();
	}

	$directory = opendir(IA_PACKAGES);
	$packages = array();

	while ($file = readdir($directory))
	{
		$installationFile = IA_PACKAGES . $file . IA_DS . iaExtra::INSTALL_FILE_NAME;
		if (substr($file, 0, 1) != '.' && is_dir(IA_PACKAGES . $file) && file_exists($installationFile))
		{
			if ($fileContents = file_get_contents($installationFile))
			{
				$iaExtra->itemData['screenshots'] = array();
				$iaExtra->itemData['url'] = '';

				$iaExtra->setXml($fileContents);
				$iaExtra->parse();

				$buttons = false;

				$version = explode('-', $iaExtra->itemData['compatibility']);
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
					$iaExtra->itemData['compatibility'] = '<span style="color:red">' . $iaExtra->itemData['compatibility'] . ' ' . iaLanguage::get('incompatible') . '</span>';
				}

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
				if (isset($existPackages[$iaExtra->itemData['name']]))
				{
					$status = $statuses[$iaExtra->itemData['name']];
				}

				if ($array = $iaExtra->itemData['screenshots'])
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

						if (is_dir(IA_PACKAGES . $file . IA_DS . 'includes' . IA_DS . 'dumps'))
						{
							$items['import'] = true;
						}

						if ($extraConfig = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`extras` = :name ORDER BY `order` ASC', array('name' => $iaExtra->itemData['name']), iaCore::getConfigTable()))
						{
							$items['config'] = array(
								'url' => $extraConfig['config_group'],
								'anchor' => $extraConfig['name']
							);
						}

						if ($alias = $iaDb->one_bind('alias', '`name` = :name', array('name' => $iaExtra->itemData['name'] . '_manage'), 'admin_pages'))
						{
							$items['manage'] = $alias;
						}

						if ($buttons && version_compare($iaExtra->itemData['info']['version'], $existPackages[$iaExtra->itemData['name']], '>')
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

				$packages[] = array(
					'title' => $iaExtra->itemData['info']['title'],
					'version' => $iaExtra->itemData['info']['version'],
					'description' => $iaExtra->itemData['info']['title'],
					'contributor' => $iaExtra->itemData['info']['contributor'],
					'compatibility' => $iaExtra->itemData['compatibility'],
					'author' => $iaExtra->itemData['info']['author'],
					'summary' => $iaExtra->itemData['info']['summary'],
					'date' => $iaExtra->itemData['info']['date'],
					'name' => $iaExtra->itemData['name'],
					'buttons' => $buttons,
					'url' => $iaExtra->itemData['url'],
					'preview' => $preview,
					'screenshots' => $screenshots,
					'file' => $file,
					'items' => $items,
					'status' => $status,
					'date_updated' => ($status != 'notinstall') ? $dates[$iaExtra->itemData['name']] : false,
					'install' => true
				);
			}
		}
	}

	$iaView->assign('packages_list', $packages);

	$iaDb->resetTable();
}

function change_default($url = '', $package = '')
{
	$iaCore = iaCore::instance();
	$iaDb = &$iaCore->iaDb;

	$defaultPackage = $iaCore->get('default_package', false);

	if ($defaultPackage != $package)
	{
		if ($defaultPackage)
		{
			$oldExtras = $iaCore->factory('extra', iaCore::ADMIN);

			$oldExtras->setUrl(trim($url, IA_URL_DELIMITER) . IA_URL_DELIMITER);
			$oldExtras->setXml(file_get_contents(IA_PACKAGES . $defaultPackage . IA_DS . iaExtra::INSTALL_FILE_NAME));
			$oldExtras->parse();
			$oldExtras->checkValidity();

			$iaDb->update(array('url' => $oldExtras->getUrl()), "`name` = '$defaultPackage'", null, iaExtra::getTable());

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
		$iaDb->update(array('url' => IA_URL_DELIMITER), "`name` = '$package'", null, iaExtra::getTable());
		$iaCore->set('default_package', $package, true);

		$iaDb->setTable('hooks');
		$iaDb->update(array('status' => iaCore::STATUS_INACTIVE), "`name` = 'phpCoreGetUrlBeforeParseUrl'");
		$iaDb->update(array('status' => iaCore::STATUS_ACTIVE), "`name` = 'phpCoreGetUrlBeforeParseUrl' AND `extras` = '$package'");
		$iaDb->resetTable();
	}
}