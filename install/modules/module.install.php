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

define('IA_VER', '405');

$iaOutput->layout()->title = 'Installation Wizard';

$iaOutput->steps = array(
	'check' => 'Pre-Installation Check',
	'license' => 'Subrion License',
	'configuration' => 'Configuration',
	'finish' => 'Script Installation',
	'plugins' => 'Plugins Installation'
);


$error = false;
$message = '';

$builtinPlugins = array('kcaptcha', 'fancybox', 'personal_blog', 'elfinder');

switch ($step)
{
	case 'check':
		$checks = array(
			'server' => array()
		);
		$sections = array(
			'server' => array(
				'title' => 'Server Configuration',
				'desc' => 'If any of these items are highlighted in red then please take actions to correct them. Failure to do so could lead to your installation not functioning correctly.',
			),
			'recommended' => array(
				'title' => 'Recommended Settings',
				'desc' => 'These settings are recommended for PHP in order to ensure full compatibility with Subrion CMS. However, Subrion CMS will still operate if your settings do not quite match the recommended.',
			),
			'directory' => array(
				'title' => 'Directory &amp; File Permissions',
				'desc' => 'In order for Subrion CMS to function correctly it needs to be able to access or write to certain files or directories. If you see "Unwritable" you need to change the permissions on the file or directory to allow Subrion CMS to write to it.',
			),
		);

		$checks['server']['mysql_version'] = array(
			'required' => function_exists('mysqli_connect'),
			'class' => true,
			'name' => 'Mysql version',
			'value' => function_exists('mysqli_connect')
				? '<td class="success">' . substr(mysqli_get_client_info(), 0, (false === $pos = strpos(mysqli_get_client_info(), '-')) ? 10 : $pos) . '</td>'
				: '<td class="danger">MySQL 5.x or upper required</td>'
		);
		$checks['server']['php_version'] = array(
			'required' => version_compare('5.0', PHP_VERSION, '<'),
			'class' => true,
			'name' => 'PHP version',
			'value' => version_compare('5.0', PHP_VERSION, '<')
				? '<td class="success">' . PHP_VERSION . '</td>'
				: '<td class="danger">PHP version is not compatible. PHP 5.x needed. (Current version ' . PHP_VERSION . ')</td>'
		);
		$checks['server']['remote'] = array(
			'name' => 'Remote files access support',
			'value' => iaHelper::hasAccessToRemote()
				? '<td class="success">Available</td>'
				: '<td class="danger">Unavailable (highly recommended to enable "CURL" extension or "allow_url_fopen")</td>'
		);
		$checks['server']['xml'] = array(
			'name' => 'XML support',
			'value' => extension_loaded('xml')
				? '<td class="success">Available</td>'
				: '<td class="danger">Unavailable (recommended)</td>'
		);
		$checks['server']['mysql_support'] = array(
			'name' => 'MySQL support (MySQLi)',
			'value' => function_exists('mysqli_connect')
				? '<td class="success">Available</td>'
				: '<td class="danger">Unavailable (required)</td>'
		);
		$checks['server']['gd'] = array(
			'name' => 'GD extension',
			'value' => extension_loaded('gd')
				? '<td class="success">Available</td>'
				: '<td class="danger">Unavailable (highly recommended)</td>'
		);
		$checks['server']['mbstring'] = array(
			'name' => 'Mbstring extension',
			'value' => extension_loaded('mbstring')
				? '<td class="success">Available</td>'
				: '<td class="danger">Unavailable (not required) </td>'
		);

		$recommendedSettings = array(
			array ('File Uploads', 'file_uploads', 'ON'),
			array ('Magic Quotes GPC', 'magic_quotes_gpc', 'OFF'),
			array ('Register Globals', 'register_globals', 'OFF')
		);
		foreach ($recommendedSettings as $item)
		{
			$checks['recommended'][$item[1]] = array(
				'name' => $item[0] . ':</td><td>' . $item[2] . '',
				'value' => (iaHelper::getIniSetting($item[1]) == $item[2] ? '<td class="success">' : '<td class="danger">' ) . iaHelper::getIniSetting($item[1]) . '</td>',
			);
		}

		$directory = array(
			array('tmp' . IA_DS, '', true),
			array('uploads' . IA_DS, '', true),
			array('backup' . IA_DS, ' (optional)', false),
			array('plugins' . IA_DS, ' (optional)', false),
			array('includes' . IA_DS . 'config.inc.php', ' (optional)', false),
		);

		foreach ($directory as $item)
		{
			$text = '';
			$isWritable = false;
			if (file_exists(IA_HOME . $item[0]))
			{
				$text = is_writable(IA_HOME . $item[0]) ? '<td class="success">Writable</td>' : '<td class="' . (empty($item[1]) ? 'danger' : 'optional') . '">Unwritable ' . $item[1] . '</td>';
				$isWritable = is_writable(IA_HOME . $item[0]);
			}
			else
			{
				if ($item[0] == 'includes' . IA_DS . 'config.inc.php')
				{
					if (!is_writable(IA_HOME . 'includes' . IA_DS))
					{
						$text = '<td class="danger">Does not exist and cannot be created' . $item[1] . '</td>';
					}
					else
					{
						$text = '<td class="success">Does not exist, but can be created' . $item[1] . '</td>';
					}
				}
				else
				{
					$text = '<td class="danger">Does not exist' . $item[1] . '</td>';
				}
			}
			$checks['directory'][$item[0]] = array(
				'class' => true,
				'name' => $item[0],
				'value' => $text
			);

			if ($item[2])
			{
				$checks['directory'][$item[0]]['required'] = $isWritable;
			}
		}

		$nextButtonEnabled = true;
		foreach ($sections as $section => $items)
		{
			foreach ($checks[$section] as $key => $check)
			{
				if (isset($check['required']) && !$check['required'])
				{
					$nextButtonEnabled = false;
					break 2;
				}
			}
		}

		$iaOutput->nextButton = $nextButtonEnabled;
		$iaOutput->sections = $sections;
		$iaOutput->checks = $checks;

		break;

	case 'license':
		// EULA step. do nothing
		break;

	case 'configuration':
	case 'finish':
		$step = 'configuration';
		$errorList = array();
		$template = 'default';
		$templates = array();

		$templatesFolder = IA_HOME . 'templates/';
		$directory = opendir($templatesFolder);
		while ($file = readdir($directory))
		{
			if (substr($file, 0, 1) != '.' && 'common' != $file)
			{
				if (is_dir($templatesFolder . $file))
				{
					$templates[] = $file;
				}
			}
		}
		closedir($directory);
		sort($templates);

		if (isset($_POST['db_action']))
		{
			$requiredFields = array('dbhost', 'dbuser', 'dbname', 'prefix', 'tmpl', 'admin_username', 'admin_password', 'admin_email');

			foreach ($requiredFields as $fieldName)
			{
				if (!iaHelper::getPost($fieldName, false))
				{
					$errorList[] = $fieldName;
				}
			}

			if (iaHelper::getPost('admin_password') != iaHelper::getPost('admin_password2'))
			{
				$errorList[] = 'admin_password2';
			}

			$port = (int)iaHelper::getPost('dbport', 3306);
			if ($port > 65536 || $port <= 0)
			{
				$_POST['dbport'] = 3306;
			}

			if (!iaHelper::email(iaHelper::getPost('admin_email')))
			{
				$errorList[] = 'admin_email';
			}

			if (!preg_match('/^[a-zA-Z0-9._-]*$/i', iaHelper::getPost('admin_username')))
			{
				$errorList[] = 'admin_username';
			}

			if (empty($errorList))
			{
				$link = @mysqli_connect(iaHelper::getPost('dbhost'), iaHelper::getPost('dbuser'), iaHelper::getPost('dbpwd'), '', iaHelper::getPost('dbport', 3306));
				if (mysqli_connect_errno())
				{
					$error = true;
					$message = 'MySQL server: ' . mysqli_connect_error() . '<br>';
				}

				if (!$error && !mysqli_select_db($link, iaHelper::getPost('dbname')))
				{
					$error = true;
					$message = 'Could not select database ' . iaHelper::_html(iaHelper::getPost('dbname')) . ': ' . mysqli_error($link);
				}

				$prefix = iaHelper::getPost('prefix');

				if (!$error && !iaHelper::getPost('delete_tables', false))
				{
					if ($query = mysqli_query($link, 'SHOW TABLES'))
					{
						if (mysqli_num_rows($query) > 0)
						{
							while ($array = mysqli_fetch_row($query))
							{
								if (strpos($array[0], iaHelper::_sql($prefix, $link)) !== false)
								{
									$error = true;
									$message = 'Tables with prefix "' . $prefix . '" already exist.';
									$errorList[] = 'prefix';

									break;
								}
							}
						}
					}
					unset($query);
				}

				if (!$error)
				{
					$dbOptions = 'ENGINE=MyISAM DEFAULT CHARSET=utf8';
					$dumpFile = IA_INSTALL . 'dump' . IA_DS . 'install.sql';

					if (!file_exists($dumpFile))
					{
						$error = true;
						$message = 'Could not open file with sql instructions: install.sql';
					}
				}

				if (!$error)
				{
					$search = array(
						'{install:dir}' => trim(IA_HOME, '/'),
						'{install:base}' => IA_HOME,
						'{install:base_url}' => URL_HOME,
						'{install:tmpl}' => iaHelper::_sql(iaHelper::getPost('tmpl'), $link),
						'{install:lang}' => 'en',
						'{install:admin_username}' => iaHelper::_sql(iaHelper::getPost('admin_username'), $link),
						'{install:email}' => iaHelper::_sql(iaHelper::getPost('admin_email'), $link),
						'{install:db_options}' => $dbOptions,
						'{install:version}' => IA_VERSION,
						'{install:drop_tables}' => ('on' == iaHelper::getPost('delete_tables')) ? '' : '#',
						'{install:prefix}' => iaHelper::_sql(iaHelper::getPost('prefix', '', false), $link)
					);
					$message = $s_sql = '';
					$counter = 0;
					$file = file($dumpFile);
					if (count($file) > 0)
					{
						mysqli_query($link, "SET NAMES 'utf8'");

						foreach ($file as $s)
						{
							$s = trim ($s);
							if (isset($s[0]) && ($s[0] == '#' || $s[0] == '' || 0 === strpos($s, '--')))
							{
								continue;
							}

							if ($s && $s[strlen($s) - 1] == ';')
							{
								$s_sql .= $s;
							}
							else
							{
								$s_sql .= $s;
								continue;
							}

							$s_sql = str_replace(array_keys($search), array_values($search), $s_sql);

							if (!mysqli_query($link, $s_sql))
							{
								$error = true;
								if ($counter == 0)
								{
									$counter++;
									$message .= '<div class="db_errors">';
								}
								$errorCode = function_exists('mysqli_errno') ? mysqli_errno($link) . ' ' : '';
								$message .= "<div class=\"qerror\">'" . $errorCode . mysqli_error($link)
									. "' during the following query:</div> <div class=\"query\"><pre>{$s_sql}</pre></div>";
							}
							$s_sql = '';
						}
						$message .= $message ? '</div>' : '';
					}
					else
					{
						$error = true;
						$message = 'Mysql dump is empty! Please check the file!';
					}
				}

				$iaTemplate = iaHelper::loadCoreClass('template');

				$templateInstallationFile = IA_HOME . 'templates' . IA_DS . iaHelper::getPost('tmpl', '') . IA_DS . 'install.xml';
				$iaTemplate->getFromPath($templateInstallationFile);

				$iaTemplate->parse();
				$iaTemplate->check();

				if ($notes = $iaTemplate->getNotes())
				{
					$error = true;
					$errorList[] = 'template';
					$message = sprintf('&quot;%s&quot; template error: %s', $iaTemplate->title, implode('<br>', $notes));
				}

				if (!$error)
				{
					$config = file_get_contents(IA_INSTALL . 'modules' . IA_DS . 'config.sample');
					$body = <<<HTML
Congratulations,

You have successfully installed Subrion CMS ({version}) on your server.

This e-mail contains important information regarding your installation and
should be kept for reference. Your password has been securely stored in our
database and cannot be retrieved. In the event that it is forgotten, you
will be able to reset it using the email address associated with your
account.

----------------------------
Site configuration
----------------------------
 Username: {username}
 Password: {password}
 Board URL: {url}
----------------------------
Mysql configuration
----------------------------
 Hostname: {dbhost}:{dbport}
 Database: {dbname}
 Username: {dbuser}
 Password: {dbpass}
 Prefix: {dbprefix}
----------------------------

Useful information regarding the Subrion CMS can be found in Subrion User Forums -
http://www.subrion.org/forums/
__________________________
The Subrion Support Team
http://www.subrion.org
https://intelliants.com
HTML;
					$salt = '#' . strtoupper(substr(md5(IA_HOME), 21, 10));
					$params = array(
						'{version}' => IA_VERSION,
						'{date}' => date('d F Y H:i:s'),
						'{dbconnector}' => in_array('mysqli', get_loaded_extensions()) && function_exists('mysqli_connect') ? 'mysqli' : 'mysql',
						'{dbhost}' => iaHelper::getPost('dbhost'),
						'{dbuser}' => iaHelper::getPost('dbuser'),
						'{dbpass}' => iaHelper::getPost('dbpwd', '', false),
						'{dbname}' => iaHelper::getPost('dbname'),
						'{dbport}' => iaHelper::getPost('dbport'),
						'{dbprefix}' => iaHelper::getPost('prefix'),
						'{salt}' => $salt,
						'{debug}' => iaHelper::getPost('debug', 0, false),
						'{username}' => iaHelper::_sql(iaHelper::getPost('admin_username'), $link),
						'{password}' => iaHelper::_sql(iaHelper::getPost('admin_password'), $link),
						'{url}' => URL_HOME . 'admin/'
					);
					$body = str_replace(array_keys($params), array_values($params), $body);
					$params['{dbpass}'] = str_replace("'", "\\'", $params['{dbpass}']);
					$config = str_replace(array_keys($params), array_values($params), $config);

					@mail(iaHelper::_sql(iaHelper::getPost('admin_email')), 'Subrion CMS Installed', $body, 'From: tech@subrion.com');
					$filename = IA_HOME . 'includes' . IA_DS . 'config.inc.php';
					$configMsg = '';

					// session path test
					$testResult = false;
					$filePath = session_save_path() . IA_DS . 'foo';
					if ($fh = @fopen($filePath, 'w'))
					{
						$testResult = fwrite($fh, 'bar');
						fclose($fh);
					}

					$testResult = $testResult ? '' : "session_save_path('" . IA_HOME . "tmp');";
					$config = str_replace('{sessionpath}', $testResult, $config);
					//

					if (is_writable(IA_HOME . 'includes' . IA_DS) || is_writable($filename))
					{
						if (!$handle = fopen($filename, 'w+'))
						{
							$configMsg = 'Cannot open file: ' . $filename;
						}

						if (fwrite($handle, $config) === false)
						{
							$configMsg = 'Cannot write to file: ' . $filename;
						}

						fclose($handle);
					}
					else
					{
						$configMsg = 'Cannot write to folder.';
					}

					iaHelper::cleanUpDirectoryContents(IA_HOME . 'tmp' . IA_DS);

					if (!$error)
					{
						$step = 'finish';
						$iaOutput->step = 'finish';
					}

					$iaOutput->config = $config;
					$iaOutput->description = $configMsg;
				}

				if (!$error)
				{
					defined('IA_SALT') || define('IA_SALT', $salt);

					$iaUsers = iaHelper::loadCoreClass('users', 'core');
					$iaUsers->changePassword(array('id' => 1), iaHelper::getPost('admin_password'), false);

					iaHelper::cleanUpCacheContents();

					$iaTemplate->install(iaTemplate::SETUP_INITIAL);

					// writing it to the system log
					$iaLog = iaHelper::loadCoreClass('log', 'core');
					$iaLog->write(iaLog::ACTION_INSTALL, array('type' => 'app'));
				}

				if (!$error && $builtinPlugins)
				{
					$pluginsFolder = IA_HOME . 'plugins' . IA_DS;
					foreach ($builtinPlugins as $pluginName)
					{
						$installationFile = file_get_contents($pluginsFolder . $pluginName . IA_DS . iaHelper::INSTALLATION_FILE_NAME);
						if ($installationFile !== false)
						{
							$iaExtrasInstaller = iaHelper::loadCoreClass('extra');

							$iaExtrasInstaller->setXml($installationFile);
							$iaExtrasInstaller->parse();

							if (!$iaExtrasInstaller->getNotes())
							{
								$result = $iaExtrasInstaller->install();
							}
						}
					}
				}
			}

			$template = iaHelper::getPost('tmpl', $template);
		}

		$iaOutput->errorList = $errorList;
		$iaOutput->template = $template;
		$iaOutput->templates = $templates;

		break;

	case 'download':
		if (class_exists('iaCore')) // iaCore isn't loaded when config file hasn't been written
		{
			iaCore::instance()->iaView->set('nodebug', true);
		}

		header('Content-Type: text/x-delimtext; name="config.inc.php"');
		header('Content-disposition: attachment; filename="config.inc.php"');

		echo get_magic_quotes_gpc() ? stripslashes($_POST['config_content']) : $_POST['config_content'];
		exit;

	case 'plugins':
		if (iaHelper::isAjaxRequest())
		{
			if (isset($_POST['plugin']) && $_POST['plugin'])
			{
				echo iaHelper::installRemotePlugin($_POST['plugin'])
					? 'installed successfully'
					: 'installation is not performed';
				exit();
			}
		}
		else
		{
			if ($plugins = iaHelper::getRemotePluginsList(IA_VERSION))
			{
				$iaOutput->plugins = $plugins;
			}
			else
			{
				$message = 'Could not get the list of plugins.';
			}
		}

		break;

	default:
		return;
}

$iaOutput->error = $error;
$iaOutput->message = $message;