<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

define('INSTALL', 'install');
define('IA_DS', '/');
define('IA_URL_DELIMITER', '/');
define('IA_HOME', str_replace([INSTALL . IA_DS, '\\'], ['', IA_DS], dirname(__FILE__) . IA_DS));
define('IA_INSTALL', IA_HOME . INSTALL . IA_DS);

// installation files can only be in 'install' directory!
if (false === strpos($_SERVER['SCRIPT_NAME'], IA_URL_DELIMITER . INSTALL . IA_URL_DELIMITER)) {
    die('Access denied');
}

error_reporting(E_STRICT | E_ALL);
ini_set('display_errors', true);

date_default_timezone_set('UTC');

session_name(sprintf('INTELLI_%s', substr(md5(IA_HOME), 0, 10)));
session_start();

include IA_HOME . 'index.php';

$scriptFolder = trim(str_replace(INSTALL . IA_URL_DELIMITER . 'index.php', '', $_SERVER['PHP_SELF']), IA_URL_DELIMITER);
$scriptFolder = empty($scriptFolder) ? '' : $scriptFolder . IA_URL_DELIMITER;
$scriptPort = (80 == $_SERVER['SERVER_PORT'] || 443 == $_SERVER['SERVER_PORT']) ? '' : ':' . $_SERVER['SERVER_PORT'];

$url = '//' . $_SERVER['SERVER_NAME'] . $scriptPort . IA_URL_DELIMITER . $scriptFolder;

define('URL_HOME', 'http' . (isset($_SERVER['HTTPS']) && 'on' == $_SERVER['HTTPS'] ? 's' : '') . ':' . $url);
define('URL_ADMIN_PANEL', URL_HOME . 'panel/');
define('URL_INSTALL', URL_HOME . INSTALL . IA_URL_DELIMITER);
define('URL_ASSETS', $url . INSTALL . IA_URL_DELIMITER);

$url = trim(!isset($_SERVER['REDIRECT_URL']) || $_SERVER['REQUEST_URI'] != $_SERVER['REDIRECT_URL'] ? $_SERVER['REQUEST_URI'] : $_SERVER['REDIRECT_URL'], IA_URL_DELIMITER);
$url = isset($_GET['_p']) ? trim($_GET['_p'], IA_URL_DELIMITER) : substr($url, strlen(trim(INSTALL . $scriptFolder, IA_URL_DELIMITER)) - 1);
$url = explode(IA_URL_DELIMITER, (string)$url);

unset($_GET['_p']);

$module = empty($url[0]) ? 'welcome' : $url[0];
$step = empty($url[1]) ? 'check' : $url[1];
$modules = [];

set_include_path(IA_INSTALL . 'classes');
require_once 'ia.helper.php';
require_once 'ia.output.php';

$modulesPath = IA_INSTALL . 'modules/';
if (is_dir($modulesPath)) {
    if ($directory = opendir($modulesPath)) {
        while ($file = readdir($directory)) {
            $pos = strpos($file, 'module.');
            if ($pos !== false && $pos == 0) {
                list(, $mod, ) = explode('.', $file);
                switch ($mod) {
                    case 'install':
                        $modules[] = $mod;
                        break;
                    case 'upgrade':
                        if (iaHelper::isScriptInstalled()) {
                            $iaUsers = iaHelper::loadCoreClass('users', 'core');
                            if ($mod == $module || iaUsers::hasIdentity() && iaUsers::MEMBERSHIP_ADMINISTRATOR == iaUsers::getIdentity()->usergroup_id) {
                                $modules[] = $mod;
                            }
                        }
                        break;
                    default:
                        $modules[] = $mod;
                }
            }
        }
        closedir($directory);
    }
}

if (empty($modules)) {
    header('HTTP/1.0 403');
    exit('Forbidden.');
}

if (1 == count($modules)) {
    $module = $modules[0];
}

if ('welcome' == $module) {
    $url = URL_HOME . 'install' . IA_URL_DELIMITER;
    $url .= iaHelper::isScriptInstalled() ? 'upgrade' : 'install';
    $url .= IA_URL_DELIMITER;
    header('Location: ' . $url);
    exit();
}

if (!file_exists(IA_HOME . 'includes/config.inc.php')) {
    // disallow upgrade module if no config file exists
    $modules = array_diff($modules, ['upgrade']);

    // set active module
    $module = 'install';
}

$iaOutput = new iaOutput(IA_INSTALL . 'templates/');

$iaOutput->module = $module;
$iaOutput->modules = $modules;
$iaOutput->step = $step;

require $modulesPath . 'module.' . $module . '.php';

if (!iaHelper::isAjaxRequest()) {
    if ($iaOutput->isRenderable($template = $module . '.' . $step)) {
        echo $iaOutput->render($template);
    } else {
        header('HTTP/1.0 500');
        die('Internal Server Error.');
    }
}
