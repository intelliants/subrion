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

define('IA_VERSION', '4.0.5');

if (isset($ia_version))
{
	return IA_VERSION;
}

if (version_compare('5.3', PHP_VERSION, '>'))
{
	exit('Subrion ' . IA_VERSION . ' requires PHP 5.3 or higher to run properly.');
}
if (function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules()))
{
	exit('Subrion ' . IA_VERSION . ' requires the mod_rewrite module to run properly.');
}

// enable errors display
ini_set('display_errors', true);
error_reporting(E_ALL | E_STRICT | E_DEPRECATED);

// define system constants
define('IA_DS', '/');
define('IA_URL_DELIMITER', '/');
define('IA_HOME', str_replace('\\', IA_DS, dirname(__FILE__)) . IA_DS);
define('IA_INCLUDES', IA_HOME . 'includes' . IA_DS);
define('IA_CLASSES', IA_INCLUDES . 'classes' . IA_DS);
define('IA_PLUGINS', IA_HOME . 'plugins' . IA_DS);
define('IA_PACKAGES', IA_HOME . 'packages' . IA_DS);
define('IA_UPLOADS', IA_HOME . 'uploads' . IA_DS);
define('IA_SMARTY', IA_INCLUDES . 'smarty' . IA_DS);
define('IA_TMP', IA_HOME . 'tmp' . IA_DS);
define('IA_CACHEDIR', IA_TMP . 'cache' . IA_DS);
define('IA_FRONT', IA_HOME . 'front' . IA_DS);
define('IA_ADMIN', IA_HOME . 'admin' . IA_DS);
define('FOLDER', trim(str_replace(IA_DS . 'index.php', '', $_SERVER['PHP_SELF']), IA_URL_DELIMITER));
define('FOLDER_URL', FOLDER != '' ? trim(str_replace(IA_DS, IA_URL_DELIMITER, FOLDER), IA_URL_DELIMITER) . IA_URL_DELIMITER : '');

// process stripslashes if magic_quotes is enabled on the server
if (function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc())
{
	$in = array(&$_GET, &$_POST, &$_COOKIE, &$_SERVER);
	while (list($k, $v) = each($in))
	{
		foreach ($v as $key => $val)
		{
			if (!is_array($val))
			{
				$in[$k][$key] = stripslashes($val);
				continue;
			}
			$in[] = & $in[$k][$key];
		}
	}
	unset($in);
}

$performInstallation = false;

if (file_exists(IA_INCLUDES . 'config.inc.php'))
{
	include IA_INCLUDES . 'config.inc.php';
	defined('INTELLI_DEBUG') || $performInstallation = true;
}
else
{
	$performInstallation = true;
}

// redirect to installation
if ($performInstallation)
{
	if (file_exists(IA_HOME . 'install/index.php'))
	{
		header('Location: ' . str_replace('index.php', 'install/', $_SERVER['SCRIPT_NAME']));
		return;
	}

	exit('Install directory was not found!');
}

$domain = explode(':', $_SERVER['HTTP_HOST']);
$domain = reset($domain);

if (strpos($domain, '.') && !filter_var($domain, FILTER_VALIDATE_IP))
{
	$chunks = array_reverse(explode('.', $domain));
	if (count($chunks) > 2)
	{
		if (!in_array($chunks[1], array('co', 'com', 'net', 'org', 'gov', 'ltd', 'ac', 'edu')))
		{
			$domain = implode('.', array($chunks[1], $chunks[0]));

			if ($chunks[2] != 'www')
			{
				$domain = implode('.', array($chunks[2], $chunks[1], $chunks[0]));
			}
		}
	}
	$domain = '.' . $domain;
}

ini_set('session.gc_maxlifetime', 1800); // 30 minutes
//session_set_cookie_params(1800, '/', $domain, false, true);
session_name('INTELLI_' . substr(md5(IA_HOME), 0, 10));
session_start();
setcookie(session_name(), session_id(), time() + 1800);

require_once IA_CLASSES . 'ia.system.php';
require_once IA_INCLUDES . 'function.php';

if (function_exists('spl_autoload_register'))
{
	spl_autoload_register(array('iaSystem', 'autoload'));
}

iaSystem::renderTime('start');

if (INTELLI_DEBUG)
{
	register_shutdown_function(array('iaSystem', 'shutdown'));
	ob_start(array('iaSystem', 'output'));
}
else
{
	error_reporting(0);
}

set_error_handler(array('iaSystem', 'error'));

iaSystem::renderTime('Core started');

iaCore::instance()->init();