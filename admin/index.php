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
	const UPDATE_TYPE_PATCH = 'patch';
	const UPDATE_TYPE_INFO = 'info';

	const STATISTICS_GETTER_METHOD = 'getDashboardStatistics';

	protected $_name = 'dashboard';

	protected $_processAdd = false;
	protected $_processEdit = false;


	protected function _indexPage(&$iaView)
	{
		$iaView->display('index');

		$iaCore = &$this->_iaCore;
		$iaDb = &$this->_iaDb;

		if (isset($_GET['reset']) || isset($_GET['save']))
		{
			$data = isset($_GET['list']) ? $_GET['list'] : '';
			if ($iaDb->update(array('admin_columns' => $data), iaDb::convertIds(iaUsers::getIdentity()->id), null, iaUsers::getTable()))
			{
				iaUsers::reloadIdentity();
			}

			$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);

			iaUtil::go_to(IA_SELF);
		}

		$disabledWidgets = iaUsers::getIdentity()->admin_columns;
		$disabledWidgets = empty($disabledWidgets) ? array() : explode(',', trim($disabledWidgets, ','));

		$iaView->assign('disabled_widgets', $disabledWidgets);

		$customizationMode = isset($_GET['customize']) && empty($_GET['customize']);
		if ($customizationMode)
		{
			$iaView->setMessages(iaLanguage::get('customization_mode_alert'));
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
				if (method_exists($classInstance, self::STATISTICS_GETTER_METHOD))
				{
					if ($classInstance->dashboardStatistics)
					{
						$data = call_user_func(array($classInstance, self::STATISTICS_GETTER_METHOD));

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

						$issue = preg_replace('/#(\d+)/', '<a href="http://dev.subrion.org/issues/$1" target="_blank">#$1</a>', ltrim($line, '+-* '));
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
			$data = $iaCore->factory('users')->getVisitorsInfo();
			$iaView->assign('online_members', $data);
		}

		if ($iaCore->get('check_for_updates'))
		{
			$this->_checkForUpdates();
		}
	}

	protected function _htmlAction(&$iaView)
	{
		switch ($iaView->name())
		{
			case 'phpinfo':
				$this->_showPhpinfo($iaView);
				$iaView->display('index');
				break;

			case 'clear_cache':
				$this->_clearCache($iaView);
				break;

			case 'sitemap':
				$this->_buildSitemap($iaView);
				break;

			default:
				parent::_htmlAction($iaView);
		}
	}

	protected function _gridRead($params)
	{
		switch ($_POST['action'])
		{
			case 'request';
				$email = $this->_iaCore->get('site_email');
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
				$footer .= 'Script version: ' . $this->_iaCore->get('version') . '<br />' . PHP_EOL;

				$result = (bool)mail('tech@subrion.com', $this->_iaCore->get('site') . ' - ' . $_POST['feedback_subject'], $_POST['feedback_body'] . $footer, 'From: ' . $email);

				return array(
					'result' => $result,
					'message' => iaLanguage::get($result ? 'request_submitted' : 'failed')
				);

				break;

			case 'menu':
				$iaView = &$this->_iaCore->iaView;

				$iaView->loadSmarty(true);

				$page = $this->_iaCore->factory('page', iaCore::ADMIN)->getByName($_POST['page']);

				$core = array('page' => array(
					'info' => array(
						'active_menu' => $page['name'],
						'group' => $page['group'], // trick to get the specified page marked as active
						'menu' => $iaView->getAdminMenu()
					)
				));
				$iaView->iaSmarty->assign('core', $core);

				return array('menus' => $iaView->iaSmarty->fetch('menu.tpl'));
		}
	}

	private function _showPhpinfo(&$iaView)
	{
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
	}

	private function _clearCache(&$iaView)
	{
		$this->_iaCore->iaCache->clearGlobalCache();

		$iaView->setMessages(iaLanguage::get('cache_dropped'), iaView::SUCCESS);

		if (isset($_SERVER['HTTP_REFERER']))
		{
			iaUtil::go_to($_SERVER['HTTP_REFERER']);
		}
	}

	private function _buildSitemap(&$iaView)
	{
		$iaSitemap = $this->_iaCore->factory('sitemap', iaCore::ADMIN);
		$iaSitemap->generate()
			? $iaView->setMessages(iaLanguage::getf('sitemap_regenerated', array('url' => IA_CLEAR_URL . iaSitemap::FILENAME)), iaView::SUCCESS)
			: $iaView->setMessages(iaLanguage::get('sitemap_error'));

		if (isset($_SERVER['HTTP_REFERER']))
		{
			iaUtil::go_to($_SERVER['HTTP_REFERER']);
		}
	}

	private function _checkForUpdates()
	{
		$url = sprintf(iaUtil::REMOTE_TOOLS_URL . 'get/updates/%s/', IA_VERSION);
		$content = iaUtil::getPageContent($url);

		if (!$content)
		{
			return;
		}

		$content = iaUtil::jsonDecode($content);

		if (is_array($content) && $content)
		{
			$messages = array();

			foreach ($content as $entry)
			{
				switch ($entry['type'])
				{
					case self::UPDATE_TYPE_INFO:
						$messages[] = array($entry['id'], $entry['message']);
						break;
					case self::UPDATE_TYPE_PATCH:
						$version = explode('.', $entry['version']);
						if (count($version) > 3)
						{
							if ($this->_iaCore->get('auto_apply_critical_upgrades'))
							{
								$result = iaSystem::forceUpgrade($entry['version']);
								if (is_bool($result) && $result)
								{
									$this->_iaCore->factory('cache')->clearGlobalCache();

									$message = iaLanguage::getf('script_upgraded', array('version' => $entry['version']));
									$this->_iaCore->iaView->setMessages($message, iaView::SUCCESS);

									iaUtil::go_to(IA_SELF);
								}
								else
								{
									iaDebug::debug($result, 'Forced upgrade to the version ' . $entry['version']);
								}
							}
						}
						else
						{
							$url = sprintf('%sinstall/upgrade/check/%s/', IA_CLEAR_URL, $entry['version']);
							$this->_iaCore->iaView->setMessages(iaLanguage::getf('upgrade_available', array('url' => $url, 'version' => $entry['version'])), iaView::SYSTEM);
						}
				}
			}

			$this->_iaCore->iaView->assign('updatesInfo', $messages);
		}
	}
}