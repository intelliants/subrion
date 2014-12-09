<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

class iaHelper
{
	const PLUGINS_LIST_SOURCE = 'http://tools.subrion.org/list/plugin/%s';
	const PLUGINS_DOWNLOAD_SOURCE = 'http://tools.subrion.org/install/%s/%s';

	const USER_AGENT = 'Subrion CMS Bot';

	const HTTP_STATUS_OK = 200;

	const INSTALLATION_FILE_NAME = 'install.xml';


	public static function isAjaxRequest()
	{
		return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
			&& strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
	}

	public static function getIniSetting($name)
	{
		return ini_get($name) == '1' ? 'ON' : 'OFF';
	}

	public static function cleanUpDirectoryContents($directory, $removeFolder = false)
	{
		$directory = substr($directory, -1) == IA_DS
			? substr($directory, 0, -1)
			: $directory;
		if (!file_exists($directory) || !is_dir($directory))
		{
			return false;
		}
		elseif (is_readable($directory))
		{
			$handle = opendir($directory);
			while ($item = readdir($handle))
			{
				if (!in_array($item, array('.', '..', '.htaccess')))
				{
					$path = $directory . IA_DS . $item;
					if (is_dir($path))
					{
						self::cleanUpDirectoryContents($path, true);
					}
					else
					{
						unlink($path);
					}
				}
			}
			closedir($handle);
			if ($removeFolder)
			{
				if (!rmdir($directory))
				{
					return false;
				}
			}
		}

		return true;
	}

	public static function loadCoreClass($name, $type = 'admin')
	{
		if (!class_exists('iaCore'))
		{
			define('IA_INCLUDES', IA_HOME . 'includes' . IA_DS);
			define('IA_SMARTY', IA_INCLUDES . 'smarty' . IA_DS);
			define('IA_CLASSES', IA_INCLUDES . 'classes' . IA_DS);
			define('IA_PACKAGES', IA_HOME . 'packages' . IA_DS);
			define('IA_PLUGINS', IA_HOME . 'plugins' . IA_DS);
			define('IA_TMP', IA_HOME . 'tmp' . IA_DS);
			define('IA_CACHEDIR', IA_TMP . 'cache' . IA_DS);

			if (file_exists(IA_INCLUDES . 'config.inc.php'))
			{
				include_once IA_INCLUDES . 'config.inc.php';
			}
			else
			{
				define('INTELLI_CONNECT', 'mysql');
				define('INTELLI_DBHOST', self::getPost('dbhost', 'localhost'));
				define('INTELLI_DBPORT', self::getPost('dbport', 3306));
				define('INTELLI_DBUSER', self::getPost('dbuser'));
				define('INTELLI_DBPASS', self::getPost('dbpwd'));
				define('INTELLI_DBNAME', self::getPost('dbname'));
				define('INTELLI_DBPREFIX', self::getPost('prefix', '', false));
				define('INTELLI_DEBUG', false);
			}

			set_include_path(IA_CLASSES);

			require_once 'ia.system.php';

			if (function_exists('spl_autoload_register') && function_exists('spl_autoload_unregister'))
			{
				spl_autoload_register(array('iaSystem', 'autoload'));
			}

			require_once IA_INCLUDES . 'function.php';
			require_once 'ia.interfaces.php';

			$iaCore = iaCore::instance();

			iaSystem::setDebugMode();

			$iaCore->factory(array('sanitize', 'validate'));
			$iaCore->iaDb = $iaCore->factory('db');
			$iaCore->factory('language');
			$iaCore->iaView = $iaCore->factory('view');

			$config = array('baseurl', 'languages', 'timezone');
			$config = $iaCore->iaDb->keyvalue(array('name', 'value'), "`name` IN ('" . implode("','", $config) . "')", iaCore::getConfigTable());

			$languages = empty($config['languages']) ? array('en' => 'English') : unserialize($config['languages']);
			$iaCore->languages = $languages;
			$iaCore->iaView->language = 'en';

			date_default_timezone_set($config['timezone']);

			define('IA_CLEAR_URL', $config['baseurl']);
			define('IA_URL', IA_CLEAR_URL);
			define('IA_FRONT_TEMPLATES', IA_HOME . 'templates' . IA_DS);
			define('IA_TEMPLATES', IA_FRONT_TEMPLATES);
		}

		return iaCore::instance()->factory($name, $type);
	}

	public static function hasAccessToRemote()
	{
		if (extension_loaded('curl'))
		{
			return true;
		}

		if (ini_get('allow_url_fopen'))
		{
			if (function_exists('fsockopen'))
			{
				return true;
			}
			if (function_exists('stream_get_meta_data') && in_array('http', stream_get_wrappers()))
			{
				return true;
			}
		}

		return false;
	}

	public static function getPost($name, $default = '', $notEmpty = true)
	{
		if (isset($_POST[$name]))
		{
			if (empty($_POST[$name]) && $notEmpty) return $default;
			return $_POST[$name];
		}

		return $default;
	}

	public static function email($email)
	{
		return (bool)preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email);
	}

	public static function getRemoteContent($sourceUrl, $savePath = null)
	{
		$result = false;

		if (extension_loaded('curl'))
		{
			set_time_limit(60);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $sourceUrl);
			curl_setopt($ch, CURLOPT_HEADER, 0);

			if ($savePath)
			{
				$fh = fopen($savePath, 'w');
				curl_setopt($ch, CURLOPT_FILE, $fh);
			}
			else
			{
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			}

			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
			curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
			$response = curl_exec($ch);
			if (self::HTTP_STATUS_OK == curl_getinfo($ch, CURLINFO_HTTP_CODE))
			{
				$result = $response;
			}
			curl_close($ch);

			if (isset($fh))
			{
				fclose($fh);
			}
		}
		elseif (ini_get('allow_url_fopen'))
		{
			ini_set('user_agent', self::USER_AGENT);
			$result = @file_get_contents($sourceUrl);
			ini_restore('user_agent');

			if ($result !== false)
			{
				if ($savePath)
				{
					$fh = fopen($savePath, 'w');
					$result = fwrite($fh, $result);
					fclose($fh);
				}
			}
		}

		return $result;
	}

	public static function _html ($string, $mode = ENT_QUOTES)
	{
		return htmlspecialchars($string, $mode);
	}

	public static function _sql ($string)
	{
		if (is_array($string))
		{
			foreach ($string as $k => $v)
			{
				$string[$k] = self::_sql($v);
			}
		}
		else
		{
			$string = mysql_real_escape_string($string);
		}
		return $string;
	}

	protected static function _getInstalledPluginsList()
	{
		self::loadCoreClass('db', 'core');
		$iaDb = iaCore::instance()->iaDb;

		$list = $iaDb->onefield('name', "type = 'plugin'", 0, null, 'extras');

		return empty($list)
			? array()
			: $list;
	}

	public static function getRemotePluginsList($coreVersion, $checkIfInstalled = true)
	{
		$result = false;

		$response = self::getRemoteContent(sprintf(self::PLUGINS_LIST_SOURCE, $coreVersion));
		if ($response !== false)
		{
			$response = json_decode($response);
			if (isset($response->extensions) && count($response->extensions))
			{
				$result = $response->extensions;
			}
		}

		if ($checkIfInstalled)
		{
			$installedPlugins = self::_getInstalledPluginsList();
			foreach ($installedPlugins as $pluginName) {
				if (isset($result->$pluginName))
				{
					$result->$pluginName->installed = 1;
				}
			}
		}

		return $result;
	}

	// performs complete plugin installation
	public static function installRemotePlugin($pluginName)
	{
		$result = false;

		if ($pluginName)
		{
			$downloadPath = self::_composePath(array(IA_HOME, 'tmp', 'plugins'));
			if (!is_dir($downloadPath))
			{
				mkdir($downloadPath);
			}

			$savePath = $downloadPath . $pluginName . '.plugin';
			if (!self::getRemoteContent(sprintf(self::PLUGINS_DOWNLOAD_SOURCE, $pluginName, IA_VERSION), $savePath))
			{
				return false;
			}

			if (is_file($savePath))
			{
				$extrasFolder = self::_composePath(array(IA_HOME, 'plugins'));
				if (is_writable($extrasFolder))
				{
					$pluginFolder = self::_composePath(array($extrasFolder, $pluginName));
					if (is_dir($pluginFolder))
					{
						self::cleanUpDirectoryContents($pluginFolder);
					}
					else
					{
						mkdir($pluginFolder);
					}

					require_once self::_composePath(array(IA_HOME, 'includes', 'utils')) . 'pclzip.lib.php';
					$zipSource = new PclZip($savePath);

					if ($zipSource->extract(PCLZIP_OPT_PATH, $extrasFolder . $pluginName))
					{
						$installationFile = file_get_contents($pluginFolder . self::INSTALLATION_FILE_NAME);
						if ($installationFile !== false)
						{
							$iaExtra = self::loadCoreClass('extra');

							$iaExtra->setXml($installationFile);
							$iaExtra->parse();

							if (!$iaExtra->getNotes())
							{
								$result = $iaExtra->install();

							}
						}
					}
				}

				iaHelper::cleanUpDirectoryContents(IA_HOME . 'tmp' . IA_DS);
			}
		}

		return $result;
	}

	// handy function to create a path
	protected static function _composePath (array $path)
	{
		foreach ($path as $key => $value)
		{
			$path[$key] = trim($value, IA_DS);
		}
		return (stripos(PHP_OS, 'win') !== false ? '' : IA_DS) . implode(IA_DS, $path) . IA_DS;
	}
}