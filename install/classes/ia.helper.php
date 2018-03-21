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

class iaHelper
{
    const PLUGINS_LIST_SOURCE = 'https://tools.subrion.org/list/plugin/%s';
    const PLUGINS_DOWNLOAD_SOURCE = 'https://tools.subrion.org/install/%s/%s';

    const USER_AGENT = 'Subrion CMS Bot';

    const HTTP_STATUS_OK = 200;

    const INSTALLATION_FILE_NAME = 'install.xml';

    const CONFIGURATION_FILE = 'config.inc.php';


    public static function isAjaxRequest()
    {
        return (isset($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
    }

    public static function isScriptInstalled()
    {
        if (!file_exists(IA_HOME . 'includes/' . self::CONFIGURATION_FILE)) {
            return false;
        }

        if (!self::loadCoreClass('users', 'core')) {
            return false;
        }

        return (iaCore::instance()->iaDb->one_bind(iaDb::STMT_COUNT_ROWS,
                '`usergroup_id` = :group AND `date_logged` IS NOT NULL',
                ['group' => iaUsers::MEMBERSHIP_ADMINISTRATOR], iaUsers::getTable()) > 0);
    }

    public static function getIniSetting($name)
    {
        return ini_get($name) == '1' ? 'ON' : 'OFF';
    }

    public static function cleanUpCacheContents()
    {
        self::cleanUpDirectoryContents(IA_CACHEDIR, true);
        file_exists(IA_CACHEDIR) || iaCore::instance()->factory('util')->makeDirCascade(IA_CACHEDIR, 0777);

        $mask = !function_exists('posix_getuid') || function_exists('posix_getuid') && posix_getuid() != fileowner(IA_HOME . 'index' . iaSystem::EXECUTABLE_FILE_EXT) ? 0777 : 0755;
        chmod(IA_CACHEDIR, $mask);

        return true;
    }

    public static function cleanUpDirectoryContents($directory, $removeFolder = false)
    {
        $directory = substr($directory, -1) == IA_DS
            ? substr($directory, 0, -1)
            : $directory;
        if (!file_exists($directory) || !is_dir($directory)) {
            return false;
        } elseif (is_readable($directory)) {
            $handle = opendir($directory);
            while ($item = readdir($handle)) {
                if (!in_array($item, ['.', '..', '.htaccess'])) {
                    $path = $directory . IA_DS . $item;
                    if (is_dir($path)) {
                        self::cleanUpDirectoryContents($path, true);
                    } else {
                        unlink($path);
                    }
                }
            }
            closedir($handle);
            if ($removeFolder) {
                if (!rmdir($directory)) {
                    return false;
                }
            }
        }

        return true;
    }

    public static function loadCoreClass($name, $type = 'admin')
    {
        if (!class_exists('iaCore')) {
            define('IA_INCLUDES', IA_HOME . 'includes/');
            define('IA_SMARTY', IA_INCLUDES . 'smarty/');
            define('IA_CLASSES', IA_INCLUDES . 'classes/');
            define('IA_MODULES', IA_HOME . 'modules/');
            define('IA_TMP', IA_HOME . 'tmp/');
            define('IA_CACHEDIR', IA_TMP . 'cache/');

            if (file_exists(IA_INCLUDES . self::CONFIGURATION_FILE)) {
                include_once IA_INCLUDES . self::CONFIGURATION_FILE;
            } else {
                define('INTELLI_CONNECT', in_array('mysqli', get_loaded_extensions()) && function_exists('mysqli_connect') ? 'mysqli' : 'mysql');
                define('INTELLI_DBHOST', self::getPost('dbhost', 'localhost'));
                define('INTELLI_DBPORT', self::getPost('dbport', 3306));
                define('INTELLI_DBUSER', self::getPost('dbuser'));
                define('INTELLI_DBPASS', self::getPost('dbpwd'));
                define('INTELLI_DBNAME', self::getPost('dbname'));
                define('INTELLI_DBPREFIX', self::getPost('prefix', '', false));
                define('INTELLI_DEBUG', false);

                define('IA_SALT', '#' . strtoupper(substr(md5(IA_HOME), 21, 10)));
            }

            set_include_path(IA_CLASSES);

            require_once 'ia.system.php';

            if (function_exists('spl_autoload_register') && function_exists('spl_autoload_unregister')) {
                spl_autoload_register(['iaSystem', 'autoload']);
            }

            require_once IA_INCLUDES . 'function.php';
            require_once 'ia.interfaces.php';

            $iaCore = iaCore::instance();

            $iaCore->factory(['sanitize', 'validate']);
            $iaCore->iaDb = $iaCore->factory('db');
            $iaCore->factory('language');
            $iaCore->iaView = $iaCore->factory('view');
            $iaCore->iaCache = $iaCore->factory('cache');

            $config = ['baseurl', 'timezone', 'lang'];
            $config = $iaCore->factory('config')->fetch("`name` IN ('" . implode("','", $config) . "')");

            empty($iaCore->languages) && $iaCore->languages = [
                'en' => ['title' => 'English', 'locale' => 'en_US', 'iso' => 'en', 'date_format' => '%b %e, %Y']];
            $iaCore->iaView->language = empty($config['lang']) ? 'en' : $config['lang'];
            $iaCore->language = $iaCore->languages[$iaCore->iaView->language];

            iaSystem::setDebugMode();

            date_default_timezone_set(empty($config['timezone']) ? 'UTC' : $config['timezone']);

            define('IA_CLEAR_URL', (!empty($config['baseurl']) ? $config['baseurl'] : URL_HOME));
            define('IA_URL', IA_CLEAR_URL);
            define('IA_FRONT_TEMPLATES', IA_HOME . 'templates/');
            define('IA_TEMPLATES', IA_FRONT_TEMPLATES);
        }

        return iaCore::instance()->factory($name, $type);
    }

    public static function hasAccessToRemote()
    {
        if (extension_loaded('curl')) {
            return true;
        }

        if (ini_get('allow_url_fopen')) {
            if (function_exists('fsockopen')) {
                return true;
            }
            if (function_exists('stream_get_meta_data') && in_array('http', stream_get_wrappers())) {
                return true;
            }
        }

        return false;
    }

    public static function getPost($name, $default = '', $notEmpty = true)
    {
        if (isset($_POST[$name])) {
            if (empty($_POST[$name]) && $notEmpty) {
                return $default;
            }
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

        if (extension_loaded('curl')) {
            set_time_limit(60);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $sourceUrl);
            curl_setopt($ch, CURLOPT_HEADER, 0);

            if ($savePath) {
                $fh = fopen($savePath, 'w');
                curl_setopt($ch, CURLOPT_FILE, $fh);
            } else {
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            }

            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
            curl_setopt($ch, CURLOPT_USERAGENT, self::USER_AGENT);
            $response = curl_exec($ch);
            if (self::HTTP_STATUS_OK == curl_getinfo($ch, CURLINFO_HTTP_CODE)) {
                $result = $response;
            }
            curl_close($ch);

            if (isset($fh)) {
                fclose($fh);
            }
        } elseif (ini_get('allow_url_fopen')) {
            ini_set('user_agent', self::USER_AGENT);
            $result = @file_get_contents($sourceUrl);
            ini_restore('user_agent');

            if ($result !== false) {
                if ($savePath) {
                    $fh = fopen($savePath, 'w');
                    $result = fwrite($fh, $result);
                    fclose($fh);
                }
            }
        }

        return $result;
    }

    public static function _html($string, $mode = ENT_QUOTES)
    {
        return htmlspecialchars($string, $mode);
    }

    public static function _sql($string, $link)
    {
        if (is_array($string)) {
            foreach ($string as $k => $v) {
                $string[$k] = self::_sql($v, $link);
            }
        } else {
            $string = mysqli_real_escape_string($link, $string);
        }

        return $string;
    }

    protected static function _getInstalledPluginsList()
    {
        self::loadCoreClass('db', 'core');
        $iaDb = iaCore::instance()->iaDb;

        $list = $iaDb->onefield('name', "type = 'plugin'", 0, null, 'modules');

        return empty($list)
            ? []
            : $list;
    }

    public static function getRemotePluginsList($coreVersion, $checkIfInstalled = true)
    {
        $result = false;

        $response = self::getRemoteContent(sprintf(self::PLUGINS_LIST_SOURCE, $coreVersion));
        if ($response !== false) {
            $response = json_decode($response);
            if (isset($response->extensions) && count($response->extensions)) {
                $result = $response->extensions;
            }
        }

        if ($checkIfInstalled) {
            $installedPlugins = self::_getInstalledPluginsList();
            foreach ($installedPlugins as $pluginName) {
                if (isset($result->$pluginName)) {
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

        if ($pluginName) {
            $downloadPath = self::_composePath([IA_HOME, 'tmp', 'modules']);
            if (!is_dir($downloadPath)) {
                mkdir($downloadPath);
            }

            $savePath = $downloadPath . $pluginName . '.plugin';
            if (!self::getRemoteContent(sprintf(self::PLUGINS_DOWNLOAD_SOURCE, $pluginName, IA_VERSION), $savePath)) {
                return false;
            }

            if (is_file($savePath)) {
                $extrasFolder = self::_composePath([IA_HOME, 'modules']);
                if (is_writable($extrasFolder)) {
                    $pluginFolder = self::_composePath([$extrasFolder, $pluginName]);
                    if (is_dir($pluginFolder)) {
                        self::cleanUpDirectoryContents($pluginFolder);
                    } else {
                        mkdir($pluginFolder);
                    }

                    require_once self::_composePath([IA_HOME, 'includes', 'utils']) . 'pclzip.lib.php';
                    $zipSource = new PclZip($savePath);

                    if ($zipSource->extract(PCLZIP_OPT_PATH, $extrasFolder . $pluginName)) {
                        $installationFile = file_get_contents($pluginFolder . self::INSTALLATION_FILE_NAME);
                        if ($installationFile !== false) {
                            $iaModule = self::loadCoreClass('module');

                            $iaModule->setXml($installationFile);
                            $iaModule->parse();

                            if (!$iaModule->getNotes()) {
                                $result = $iaModule->install();
                            }
                        }
                    }
                }

                iaHelper::cleanUpDirectoryContents(IA_HOME . 'tmp/');
            }
        }

        return $result;
    }

    // handy function to create a path
    protected static function _composePath(array $path)
    {
        foreach ($path as $key => $value) {
            $path[$key] = trim($value, IA_DS);
        }
        return (stripos(PHP_OS, 'win') !== false ? '' : IA_DS) . implode(IA_DS, $path) . IA_DS;
    }

    public static function redirect($url)
    {
        header('Location: ' . URL_HOME . 'install/' . $url);
        exit();
    }

    public static function launchCronTasks()
    {
        $iaCore = iaCore::instance();
        $iaCron = $iaCore->factory('cron');

        $tasks = $iaCore->iaDb->all(iaDb::ID_COLUMN_SELECTION, null, null, null, $iaCron::getTable());

        if ($tasks) {
            foreach ($tasks as $task) {
                $iaCron->run($task['id']);
            }
        }
    }
}
