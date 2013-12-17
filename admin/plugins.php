<?php
//##copyright##

$iaExtra = $iaCore->factory('extra', iaCore::ADMIN);

$iaDb->setTable(iaExtra::getTable());

$pluginsFolder = IA_PLUGINS;

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
			$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
			$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
			$dir = in_array($_GET['dir'], array('ASC', 'DESC')) ? $_GET['dir'] : 'ASC';
			$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

			switch ($_GET['type'])
			{
				case 'available':
					$output = array();
					$mode = isset($_GET['mode']) && $_GET['mode'] ? $_GET['mode'] : '';

					// get array of installed plugins
					$installedExtras = $iaDb->keyvalue(array('name', 'version'), "`type` = '" . iaExtra::TYPE_PLUGIN . "'");

					if ('remote' == $mode)
					{
						// get plugins list from cache, cache lives for 1 hour
						$iaCache = $iaCore->factory('cache');

						$pluginsArray = array();
						if ($cachedData = $iaCache->get('subrion_plugins', 3600, true))
						{
							$pluginsArray = $cachedData;
						}
						else
						{
							if ($response = $iaCore->util()->getPageContent('http://tools.subrion.com/plugins-list/?version=' . IA_VERSION))
							{
								$response = $iaCore->util()->jsonDecode($response);
								if (!empty($response['error']))
								{
									$output['msg'][] = $response['error'];
									$output['error'] = true;
								}
								elseif ($response['total'] > 0)
								{
									if (isset($response['plugins']) && is_array($response['plugins']))
									{
										foreach ($response['plugins'] as $entry)
										{
											$pluginInfo = (array)$entry;

											$pluginInfo['type'] = 'remote';
											$pluginInfo['install'] = 0;

											// exclude installed plugins
											if (!array_key_exists($pluginInfo['name'], $installedExtras))
											{
												if (isset($pluginInfo['compatibility']) && version_compare($pluginInfo['compatibility'], IA_VERSION, '<='))
												{
													$pluginInfo['install'] = 1;
												}
												$pluginInfo['date'] = gmdate("Y-m-d H:i:s", $pluginInfo['date']);
												$pluginInfo['file'] = $pluginInfo['name'];
												$pluginInfo['readme'] = false;
												$pluginInfo['reinstall'] = false;
												$pluginInfo['uninstall'] = false;
												$pluginInfo['remove'] = false;
												$pluginInfo['removable'] = false;

												$pluginsArray[] = $pluginInfo;
											}
										}

										// cache well-formed results
										$iaCache->write('subrion_plugins', $pluginsArray);
									}
									else
									{
										$output['message'][] = iaLanguage::get('error_incorrect_format_from_subrion');
										$output['error'] = true;
									}
								}
							}
							else
							{
								$output['message'][] = iaLanguage::get('error_incorrect_response_from_subrion');
								$output['error'] = true;
							}
						}

						if ($pluginsArray)
						{
							$output['total'] = count($pluginsArray);
							$output['data'] = array_slice($pluginsArray, $start, $limit);
						}
					}
					elseif ('local' == $mode)
					{
						$total = 0;
						$pluginsList = array();
						$pluginsData = array();

						$directory = opendir($pluginsFolder);
						while ($file = readdir($directory))
						{
							if (substr($file, 0, 1) != '.' && is_dir($pluginsFolder . $file))
							{
								if (is_file($installationFile = $pluginsFolder . $file . IA_DS . iaExtra::INSTALL_FILE_NAME))
								{
									if ($fileContent = file_get_contents($installationFile))
									{
										$iaExtra->setXml($fileContent);
										$iaExtra->parse(true);

										$installationPossible = false;
										if (!$iaExtra->getNotes())
										{
											$version = explode('-', $iaExtra->itemData['compatibility']);
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

										if (!array_key_exists($iaExtra->itemData['name'], $installedExtras))
										{
											$notes = $iaExtra->getNotes();
											if ($notes)
											{
												$notes = implode(PHP_EOL, $notes);
												$notes.= PHP_EOL . PHP_EOL . iaLanguage::get('installation_impossible');
											}

											$pluginsList[$file] = $iaExtra->itemData['info']['title'];
											$pluginsData[$file] = array(
												'title' => $iaExtra->itemData['info']['title'],
												'version' => $iaExtra->itemData['info']['version'],
												'compatibility' => $iaExtra->itemData['compatibility'],
												'description' => $iaExtra->itemData['info']['summary'],
												'author' => $iaExtra->itemData['info']['author'],
												'date' => $iaExtra->itemData['info']['date'],
												'file' => $file,
												'notes' => $notes,
												'type' => 'local',
												'info' => true,
												'install' => true
											);

											$total++;
										}
									}
								}
							}
						}

						closedir($directory);

						$output['total'] = $total;

						if ($pluginsList) // sort plugins
						{
							natcasesort($pluginsList);
							('DESC' != $dir) || $pluginsList = array_reverse($pluginsList, true);

							if ($filter)
							{
								foreach ($pluginsList as $pluginName => $pluginTitle)
								{
									if (false === stripos($pluginName . $pluginTitle, $filter))
									{
										unset($pluginsList[$pluginName]);
									}
								}

								$output['total'] = count($pluginsList);
							}

							$pluginsList = array_splice($pluginsList, $start, $limit);

							foreach ($pluginsList as $pluginName => $pluginTitle)
							{
								$output['data'][] = $pluginsData[$pluginName];
							}
						}
					}
					break;
				case 'installed':
					$where = "`type` = '" . iaExtra::TYPE_PLUGIN . "'" . (empty($filter) ? '' : " AND `title` LIKE '%{$filter}%'");
					$order = ($sort && $dir) ? " ORDER BY `{$sort}` {$dir}" : '';

					$output = array(
						'data' => $iaDb->all(array('id', 'name', 'title', 'version', 'status', 'author', 'summary', 'removable', 'date'), $where . $order, $start, $limit),
						'total' => $iaDb->one(iaDb::STMT_COUNT_ROWS, $where)
					);

					if ($output['data'])
					{
						foreach ($output['data'] as &$entry)
						{
							if ($row = $iaDb->row_bind(array('name', 'config_group'), '`extras` = :plugin ORDER BY `order` ASC', array('plugin' => $entry['name']), iaCore::getConfigTable()))
							{
								$entry['config'] = $row['config_group'] . '/#' . $row['name'] . '';
							}

							if ($alias = $iaDb->one_bind('alias', '`name` = :name', array('name' => $entry['name']), 'admin_pages'))
							{
								$entry['manage'] = $alias;
							}

							$entry['file'] = $entry['name'];
							$entry['info'] = true;
							$entry['reinstall'] = true;
							$entry['uninstall'] = $entry['removable'];
							$entry['remove'] = $entry['removable'];

							if (is_dir(IA_PLUGINS . $entry['name']))
							{
								$installationFile = IA_PLUGINS . $entry['name'] . IA_DS . iaExtra::INSTALL_FILE_NAME;

								if (file_exists($installationFile))
								{
									$fileContent = file_get_contents($installationFile);

									$iaExtra->setXml($fileContent);
									$iaExtra->parse();

									if (($iaExtra->itemData['compatibility'] && version_compare(IA_VERSION, $iaExtra->itemData['compatibility'], '>=')) && version_compare($iaExtra->itemData['info']['version'], $entry['version'], '>'))
									{
										$entry['upgrade'] = $entry['name'];
									}
								}
							}
						}
					}
			}

			switch ($_GET['get'])
			{
				case 'info':
					$plugin = $_GET['name'];

					if (file_exists($documentationPath = IA_PLUGINS . $plugin . IA_DS . 'docs' . IA_DS))
					{
						$docs = scandir($documentationPath);

						foreach ($docs as $doc)
						{
							if (substr($doc, 0, 1) != '.' && is_file($documentationPath . $doc))
							{
								if (!is_null($contents = file_get_contents($documentationPath . $doc)))
								{
									$contents = str_replace('{IA_URL}', IA_CLEAR_URL, $contents);
									$n = substr($doc, 0, count($doc) - 6);
									$output['tabs'][] = array(
										'title' => iaLanguage::get('extra_' . $n, $n),
										'html' => ('changelog' == $tab ? preg_replace('/#(\d+)/', '<a href="http://dev.subrion.com/issues/$1" target="_blank">#$1</a>', $contents) : $contents),
										'cls' => 'extension-docs'
									);
								}
							}
						}

						$iaExtra->setXml(file_get_contents(IA_PLUGINS . $plugin . IA_DS . iaExtra::INSTALL_FILE_NAME));
						$iaExtra->parse();

						$search = array(
							'{icon}',
							'{name}',
							'{author}',
							'{contributor}',
							'{version}',
							'{date}',
							'{compatibility}'
						);

						$icon = file_exists(IA_PLUGINS . $plugin . IA_DS . 'docs' . IA_DS . 'img' . IA_DS . 'icon.png')
							? '<tr><td class="plugin-icon"><img src="' . IA_CLEAR_URL . 'plugins/' . $plugin . '/docs/img/icon.png" alt="" /></td></tr>'
							: '';

						$replace = array(
							$icon,
							$iaExtra->itemData['info']['title'],
							$iaExtra->itemData['info']['author'],
							$iaExtra->itemData['info']['contributor'],
							$iaExtra->itemData['info']['version'],
							$iaExtra->itemData['info']['date'],
							$iaExtra->itemData['compatibility']
						);

						$template = file_get_contents(IA_ADMIN . 'templates' . IA_DS . $iaCore->get('admin_tmpl') . IA_DS . 'extra_information.tpl');

						$output['info'] = str_replace($search, $replace, $template);
					}
			}

			break;

		case iaCore::ACTION_EDIT:
			$iaAcl->checkPage($permission . 'update');

			$output = array(
				'result' => false,
				'message' => iaLanguage::get('invalid_parameters')
			);

			if (isset($_POST['id']) && count($_POST) > 1)
			{
				$ids = is_array($_POST['id']) ? $_POST['id'] : array($_POST['id']);
				$stmt = sprintf('`removable` = 1 AND `id` IN (%s)', implode(',', $ids));

				unset($_POST['id']);

				$output['result'] = ($iaDb->update($_POST, $stmt) != -1);

				if ($output['result'])
				{
					$iaCore->getConfig(true);
					$output['message'] = iaLanguage::get('saved');
				}
				else
				{
					$output['message'] = iaLanguage::get('plugin_status_may_not_be_changed');
				}
			}
	}

	switch ($_POST['action'])
	{
		case 'install':
		case 'reinstall':
			$iaAcl->checkPage($permission . $_POST['action']);

			$output = array('error' => true);
			$pluginFolder = isset($_POST['name']) ? $_POST['name'] : '';

			if (isset($_POST['mode']) && 'remote' == $_POST['mode'] && $pluginFolder)
			{
				$pluginsTempFolder = IA_TMP . 'plugins' . IA_DS;
				if (!is_dir($pluginsTempFolder))
				{
					mkdir($pluginsTempFolder);
				}

				$filePath = $pluginsTempFolder . $pluginFolder;
				$fileName = $filePath . '.zip';

				// save remote plugin file
				$iaCore->util()->downloadRemoteContent('http://tools.subrion.com/download-plugin/?plugin=' . $pluginFolder . '&version=' . IA_VERSION, $fileName);
	
				if (file_exists($fileName))
				{
					if (is_writable(IA_PLUGINS))
					{
						// delete previous folder
						if (is_dir(IA_PLUGINS . $pluginFolder))
						{
							unlink(IA_PLUGINS . $pluginFolder);
						}

						include_once (IA_INCLUDES . 'utils' . IA_DS . 'pclzip.lib.php');

						$pclZip = new PclZip($fileName);
						$files = $pclZip->extract(PCLZIP_OPT_PATH, IA_PLUGINS);
					}
					else
					{
						$output['message'] = iaLanguage::get('upload_plugin_error');
					}
				}
			}

			$installationFile = $pluginsFolder . $pluginFolder . IA_DS . iaExtra::INSTALL_FILE_NAME;
			if (!file_exists($installationFile))
			{
				$output['message'] = iaLanguage::get('file_doesnt_exist');
			}
			else
			{
				$iaExtra->setXml(file_get_contents($installationFile));
				$output['error'] = false;
			}

			$iaExtra->parse();

			$installationPossible = false;
			$version = explode('-', $iaExtra->itemData['compatibility']);
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
				$output['message'] = iaLanguage::get('incompatible');
				$output['error'] = true;
			}
	
			if (!$output['error'])
			{
				$iaExtra->doAction(iaExtra::ACTION_INSTALL);
				if ($iaExtra->error)
				{
					$output['message'] = $iaExtra->getMessage();
					$output['error'] = true;
				}
				else
				{
					$iaLog = $iaCore->factory('log');

					if ($iaExtra->isUpgrade)
					{
						$output['message'] = iaLanguage::get('plugin_updated');

						$iaLog->write(iaLog::ACTION_UPGRADE, array('type' => iaExtra::TYPE_PLUGIN, 'name' => $iaExtra->itemData['info']['title'], 'to' => $iaExtra->itemData['info']['version']));
					}
					else
					{
						$output['message'] = ('install' == $_POST['action'])
							? iaLanguage::getf('plugin_installed', array('name' => $iaExtra->itemData['info']['title']))
							: iaLanguage::getf('plugin_reinstalled', array('name' => $iaExtra->itemData['info']['title']));

						$iaLog->write(iaLog::ACTION_INSTALL, array('type' => iaExtra::TYPE_PLUGIN, 'name' => $iaExtra->itemData['info']['title']));
					}

					empty($iaExtra->itemData['notes']) || $output['message'][] = $iaExtra->itemData['notes'];

					$iaCore->getConfig(true);
				}
			}

			$output['result'] = !$output['error'];
			unset($output['error']);

			break;

		case 'uninstall':
			$iaAcl->checkPage($permission . $_POST['action']);
			$output = array('result' => false, 'message' => iaLanguage::get('invalid_parameters'));

			if (isset($_POST['name']) && $_POST['name'])
			{
				$pluginName = $_POST['name'];

				if ($iaDb->exists('`name` = :plugin AND `type` = :type AND `removable` = 1', array('plugin' => $pluginName, 'type' => iaExtra::TYPE_PLUGIN)))
				{
					$installationFile = $pluginsFolder . $pluginName . IA_DS . iaExtra::INSTALL_FILE_NAME;

					if (!file_exists($installationFile))
					{
						$output['message'] = array(iaLanguage::get('plugin_files_physically_missed'));
					}

					$iaExtra->uninstall($pluginName);

					is_array($output['message'])
						? $output['message'][] = iaLanguage::get('plugin_uninstalled')
						: $output['message'] = iaLanguage::get('plugin_uninstalled');

					$output['result'] = true;

					// log this event
					$iaCore->factory('log')->write(iaLog::ACTION_UNINSTALL, array('type' => iaExtra::TYPE_PLUGIN, 'name' => $pluginName));
					//

					$iaCore->getConfig(true);
				}
				else
				{
					$output['message'] = iaLanguage::get('plugin_may_not_be_removed');
				}
			}
	}

	if (isset($output))
	{
		$iaView->assign($output);
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->grid('admin/plugins');
	$iaView->display('plugins');
}

$iaDb->resetTable();