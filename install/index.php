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

define('INSTALL', 'install');
define('IA_DS', '/');
define('IA_URL_DELIMITER', '/');
define('IA_HOME', str_replace(array(INSTALL . IA_DS, '\\'), array('', IA_DS), dirname(__FILE__) . IA_DS));
define('IA_INSTALL', IA_HOME . INSTALL . IA_DS);

// installation files can only be in 'install' directory!
if (false === strpos($_SERVER['SCRIPT_NAME'], IA_URL_DELIMITER . INSTALL . IA_URL_DELIMITER))
{
	die('Access denied');
}

error_reporting (E_STRICT | E_ALL);
ini_set('display_errors', true);

date_default_timezone_set('UTC');

session_name(sprintf('INTELLI_%s', substr(md5(IA_HOME), 0, 10)));
session_start();

if (version_compare(PHP_VERSION, '5.3.0', '<') && function_exists('set_magic_quotes_runtime'))
{
	@set_magic_quotes_runtime(0);
}

$scriptFolder = trim(str_replace(INSTALL . IA_URL_DELIMITER . 'index.php', '', $_SERVER['PHP_SELF']), IA_URL_DELIMITER);
$scriptFolder = empty($scriptFolder) ? '' : $scriptFolder . IA_URL_DELIMITER;

define('URL_HOME', 'http://' . $_SERVER['SERVER_NAME'] . IA_URL_DELIMITER . $scriptFolder);
define('URL_INSTALL', URL_HOME . INSTALL . IA_URL_DELIMITER);

$url = trim(!isset($_SERVER['REDIRECT_URL']) || $_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'] ? $_SERVER['REQUEST_URI'] : $_SERVER['REDIRECT_URL'], IA_URL_DELIMITER);
$url = isset($_GET['_p']) ? trim($_GET['_p'], IA_URL_DELIMITER) : substr($url, strlen(trim(INSTALL . $scriptFolder, IA_URL_DELIMITER)));
$url = explode(IA_URL_DELIMITER, $url);

unset($_GET['_p']);

$step = 'check';
$module = 'welcome';
$modules = array();

$modulesPath = IA_INSTALL . 'modules' . IA_DS;
if (is_dir($modulesPath))
{
	if ($directory = opendir($modulesPath))
	{
		while ($file = readdir($directory))
		{
			$pos = strpos($file, 'module.');
			if ($pos !== false && $pos == 0)
			{
				list(, $mod, $type) = explode('.', $file);
				if (empty($module))
				{
					$module = $mod;
				}
				$modules[] = $mod;
			}
		}
		closedir($directory);
	}
}

if (empty($modules))
{
	exit('Access denied.');
}

foreach ($url as $index => $chunk)
{
	if (trim($chunk))
	{
		switch ($index)
		{
			case 0: // module name
				if (in_array($chunk, $modules))
				{
					$module = $chunk;
				}

				break;

			case 1: // step name
				$step = $chunk;
		}
	}
}

if (1 == count($modules))
{
	$module = $modules[0];
}

if ('welcome' == $module)
{
	$url = URL_HOME . 'install' . IA_URL_DELIMITER;
	$url .= file_exists(IA_HOME . 'includes' . IA_DS . 'config.inc.php') ? 'upgrade' : 'install';
	$url .= IA_URL_DELIMITER;
	header('Location: ' . $url);
	exit();
}

if (!file_exists(IA_HOME . 'includes' . IA_DS . 'config.inc.php'))
{
	// disallow upgrade module if no config file exists
	$modules = array_diff($modules, array('upgrade'));

	// set active module
	$module = 'install';
}

set_include_path(IA_INSTALL . 'classes');

require_once 'ia.helper.php';
require_once 'ia.output.php';

$iaOutput = new iaOutput(IA_INSTALL . 'templates/');

$iaOutput->module = $module;
$iaOutput->modules = $modules;
$iaOutput->step = $step;

require $modulesPath . 'module.' . $module . '.php';

if (!iaHelper::isAjaxRequest())
{
	if ($iaOutput->isRenderable($template = $module . '.' . $step))
	{
		echo $iaOutput->render($template);
	}
	else
	{
		header('HTTP/1.0 500');
		die('Internal Server Error.');
	}
}