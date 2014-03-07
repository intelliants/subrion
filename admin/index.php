<?php
//##copyright##

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($_POST['action'])
	{
		case 'request';
			$email = $iaCore->get('site_email');
			if (isset($_POST['feedback_email']) && iaValidate::isEmail($_POST['feedback_email']))
			{
				$email = $_POST['feedback_email'];
			}
			$footer = PHP_EOL;
			$footer .= '<br />------<br />' . PHP_EOL;
			$footer .= 'Site: ' . IA_URL . '<br />' . PHP_EOL;
			if (isset($_POST['feedback_fullname']))
			{
				$footer .= 'Full Name: ' . $_POST['feedback_fullname'] . '<br />' . PHP_EOL;
			}
			$footer .= 'Email: ' . $email . '<br />' . PHP_EOL;
			$footer .= 'Script version: ' . $iaCore->get('version') . '<br />' . PHP_EOL;

			$result = (bool)mail('tech@subrion.com', $iaCore->get('site') . ' - ' . $_POST['feedback_subject'], $_POST['feedback_body'] . $footer, 'From: ' . $email);

			$iaView->assign(array(
				'result' => $result,
				'message' => iaLanguage::get($result ? 'request_submitted' : 'failed')
			));

			break;

		case 'menu':
			$iaView->loadSmarty(true);

			$page = $iaCore->factory('page', iaCore::ADMIN)->getByName($_POST['page']);

			$iaView->iaSmarty->assign('menu', $iaView->getAdminMenu());
			$iaView->iaSmarty->assign('page', array('active_menu' => $page['name'], 'group' => $page['group'])); // trick to get the specified page marked as active

			$iaView->assign('menus', $iaView->iaSmarty->fetch('menu.tpl'));
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->display('index');

	switch ($iaView->name())
	{
		case 'phpinfo':
			ob_start();
			phpinfo();
			$content = ob_get_contents();
			ob_end_clean();

			$content = preg_replace('#.*<body>(.*)</body>.*#ms', '$1', $content);

			$search = array(
				'<td class="e">',
				'<td class="v">',
				'<th colspan="2">',
				'<!DOCTYPE', '<body>',
				'</body></html>',
				'<table border="0" cellpadding="3" width="600">',
			);

			$replace = array(
				'<td style="text-align: right; width: 20%;">',
				'<td style="overflow: visible; width: 80%; word-wrap: break-word;">',
				'<th colspan="2" style="text-align: center; font-weight: bold;">',
				'<!-- <!DOCTYPE', '<body> -->',
				'<!-- </body></html> -->',
				'<table class="table table-bordered table-condensed table-striped">',
			);

			$content = str_replace($search, $replace, $content);
			$content = preg_replace('#<h2><a name="module_.+?">(.*?)<\/a><\/h2>#i', '<h3>$1</h3>', $content);
			$content = preg_replace('#<a href="http:\/\/www.php.net\/"><img border="0" src=".+?" alt="PHP Logo" \/><\/a>#i', '', $content);

			$iaView->assign('text_content', $content);

			break;

		case 'clear_cache':
			$iaCore->factory('cache')->clearGlobalCache();

			$iaView->setMessages(iaLanguage::get('cache_dropped'), iaView::SUCCESS);

			if (isset($_SERVER['HTTP_REFERER']))
			{
				iaCore::util()->go_to($_SERVER['HTTP_REFERER']);
			}

			break;

		case 'sitemap':
			$iaSitemap = $iaCore->factory('sitemap', iaCore::ADMIN);

			$iaSitemap->generate()
				? $iaView->setMessages(iaLanguage::getf('sitemap_regenerated', array('url' => IA_CLEAR_URL . iaSitemap::FILENAME)), iaView::SUCCESS)
				: $iaView->setMessages(iaLanguage::get('sitemap_error'), iaView::ERROR);

			if (isset($_SERVER['HTTP_REFERER']))
			{
				$iaCore->factory('util')->go_to($_SERVER['HTTP_REFERER']);
			}

			break;

		default:
			if (isset($_GET['reset']) || isset($_GET['save']))
			{
				$data = isset($_GET['list']) ? $_GET['list'] : '';
				$stmt = '`id` = :id';
				$iaDb->bind($stmt, array('id' => iaUsers::getIdentity()->id));
				if ($iaDb->update(array('admin_columns' => $data), $stmt, null, iaUsers::getTable()))
				{
					$iaCore->factory('users')->getAuth(iaUsers::getIdentity()->id);
				}

				$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);

				iaUtil::go_to(IA_SELF);
			}

			$disabledWidgets = iaUsers::getIdentity()->admin_columns;
			$disabledWidgets = empty($disabledWidgets) ? array() : explode(',', $disabledWidgets);

			$iaView->assign('disabled_widgets', $disabledWidgets);

			$customizationMode = isset($_GET['customize']) && empty($_GET['customize']);
			if ($customizationMode)
			{
				$iaView->setMessages(iaLanguage::get('customization_mode_alert'), iaView::ERROR);
				$iaView->assign('customization_mode', true);
			}


			// populate statistics
			$iaItem = $iaCore->factory('item');
			$itemsList = $iaItem->getPackageItems();
			$validSizes = array('small', 'medium', 'package');

			$iaCore->startHook('adminDashboardStatistics', array('items' => &$itemsList));

			natcasesort($itemsList);
			$statistics = array();

			foreach ($validSizes as $size)
			{
				$statistics[$size] = array();
			}

			foreach ($itemsList as $itemName => $pluginType)
			{
				$itemName = substr($itemName, 0, -1);
				switch ($pluginType)
				{
					case 'core':
						$classInstance = $iaCore->factory('member' == $itemName ? 'users' : $itemName);
						break;
					case 'plugin':
						$array = explode(':', $itemName);
						$itemName = isset($array[1]) ? $array[1] : $itemName;
						$classInstance = $iaCore->factoryPlugin($array[0], iaCore::ADMIN, isset($array[1]) ? $array[1] : null);
						break;
					default:
						$classInstance = $iaCore->factoryPackage($itemName, $pluginType, iaCore::ADMIN);
				}

				if (!$customizationMode && in_array($itemName, $disabledWidgets))
				{
					continue;
				}

				if ($classInstance)
				{
					if (method_exists($classInstance, 'getDashboardStatistics'))
					{
						if ($classInstance->dashboardStatistics)
						{
							$data = $classInstance->getDashboardStatistics();

							isset($data['icon']) || $data['icon'] = $itemName;
							isset($data['caption']) || $data['caption'] = $itemName;

							$data['caption'] = iaLanguage::get($data['caption'], $data['caption']);

							$widgetFormat = isset($data['_format']) && in_array($data['_format'], $validSizes)
								? $data['_format']
								: $validSizes[0];
							$statistics[$widgetFormat][$itemName] = $data;
						}
					}
				}
			}

			$iaView->assign('statistics', $statistics);
			//

			if (($customizationMode || !in_array('changelog', $disabledWidgets)) && $iaCore->get('display_changelog') && is_file(IA_HOME . 'changelog.txt'))
			{
				$index = 0;
				$class = 'undefined';
				$log = array();
				$titles = array();
				$lines = file(IA_HOME . 'changelog.txt');

				foreach ($lines as $line_num => $line)
				{
					$line = trim($line);
					if ($line)
					{
						if ($line[0] == '>')
						{
							$index++;
							$log[$index] = array(
								'title' => trim($line, '<> '),
								'added' => '',
								'modified' => '',
								'bugfixes' => '',
								'other' => '',
							);
							$titles[trim($line, '<> ')] = $index;
						}
						elseif ($index > 0)
						{
							switch ($line[0])
							{
								case '+':
									$class = 'added';
									break;
								case '-':
									$class = 'bugfixes';
									break;
								case '*':
									$class = 'modified';
									break;
								default:
									$class = 'other';
							}

							$issue = preg_replace('/#(\d+)/', '<a href="http://dev.subrion.com/issues/$1" target="_blank">#$1</a>', ltrim($line, '+-* '));
							$log[$index][$class] .= '<li>' . $issue . '</li>';
						}
					}
				}

				unset($log[0]);
				ksort($titles);
				$titles = array_reverse($titles);

				$iaView->assign('changelog_titles', $titles);
				$iaView->assign('changelog', $log);
			}

			// twitter widget
			if ($customizationMode || !in_array('twitter', $disabledWidgets))
			{
				$data = iaUtil::getPageContent('http://tools.intelliants.com/timeline/');
				$iaView->assign('timeline', iaUtil::jsonDecode($data));
			}

			if ($customizationMode || !in_array('recent-activity', $disabledWidgets))
			{
				$data = $iaCore->factory('log')->get();
				$iaView->assign('activity_log', $data);
			}

			if ($customizationMode || !in_array('website-visits', $disabledWidgets))
			{
				$data = $iaCore->factory('users')->getOnlineMembers();
				$iaView->assign('online_members', $data);
			}

			if ($iaCore->get('check_for_updates'))
			{
				if ($content = iaUtil::getPageContent('http://tools.subrion.com/download/patch/'))
				{
					$content = iaUtil::jsonDecode($content);
					if (is_array($content) && $content)
					{
						foreach ($content as $versionFrom => $versionTo)
						{
							if (version_compare($versionFrom, IA_VERSION) === 0 && version_compare($versionTo, IA_VERSION))
							{
								$version = explode('.', $versionTo);
								if (count($version) > 3)
								{
									if ($iaCore->get('auto_apply_critical_upgrades'))
									{
										$result = iaSystem::forceUpgrade($versionTo);
										if (is_bool($result) && $result)
										{
											$message = iaLanguage::getf('script_upgraded', array('version' => $versionTo));
											$iaView->setMessages($message, iaView::SUCCESS);

											$iaCore->factory('cache')->clearGlobalCache();
										}
										else
										{
											iaDebug::debug($result, 'Forced upgrade to the version ' . $versionTo);
										}
									}
								}
								else
								{
									$url = sprintf('%sinstall/upgrade/check/%s/', IA_CLEAR_URL, $versionTo);
									$iaView->setMessages(iaLanguage::getf('upgrade_available', array('url' => $url, 'version' => $versionTo)), iaView::SYSTEM);
								}
							}
						}
					}
				}
			}

			if (false === strtotime(iaUsers::getIdentity()->date_logged))
			{
				$iaCore->factory('sitemap', iaCore::ADMIN)->generate();
			}
	}
}