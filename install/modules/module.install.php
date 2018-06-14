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

define('IA_VER', '421');

$iaOutput->layout()->title = 'Installation Wizard';

$iaOutput->steps = [
    'check' => 'Pre-Installation Check',
    'license' => 'Subrion License',
    'configuration' => 'Configuration',
    'finish' => 'Script Installation',
    'plugins' => 'Plugins Installation'
];

$error = false;
$message = '';

$builtinPlugins = ['blog', 'kcaptcha', 'fancybox'];

switch ($step) {
    case 'check':
        $checks = [
            'server' => []
        ];
        $sections = [
            'server' => [
                'title' => 'Server Configuration',
                'desc' => 'If any of these items are highlighted in red then please take actions to correct them. Failure to do so could lead to your installation not functioning correctly.',
            ],
            'recommended' => [
                'title' => 'Recommended Settings',
                'desc' => 'These settings are recommended for PHP in order to ensure full compatibility with Subrion CMS. However, Subrion CMS will still operate if your settings do not quite match the recommended.',
            ],
            'directory' => [
                'title' => 'Directory &amp; File Permissions',
                'desc' => 'In order for Subrion CMS to function correctly it needs to be able to access or write to certain files or directories. If you see "Unwritable" you need to change the permissions on the file or directory to allow Subrion CMS to write to it.',
            ],
        ];

        $checks['server']['mysql_version'] = [
            'required' => function_exists('mysqli_connect'),
            'class' => true,
            'name' => 'Mysql version',
            'value' => function_exists('mysqli_connect')
                ? '<td class="success">' . substr(mysqli_get_client_info(), 0, (false === $pos = strpos(mysqli_get_client_info(), '-')) ? 10 : $pos) . '</td>'
                : '<td class="danger">MySQL 5.x or upper required</td>'
        ];
        $checks['server']['php_version'] = [
            'required' => version_compare('5.6', PHP_VERSION, '<'),
            'class' => true,
            'name' => 'PHP version',
            'value' => version_compare('5.6', PHP_VERSION, '<')
                ? '<td class="success">' . PHP_VERSION . '</td>'
                : '<td class="danger">PHP version is not compatible. PHP 5.6.x needed. (Current version ' . PHP_VERSION . ')</td>'
        ];
        $checks['server']['remote'] = [
            'name' => 'Remote files access support',
            'value' => iaHelper::hasAccessToRemote()
                ? '<td class="success">Available</td>'
                : '<td class="danger">Unavailable (highly recommended to enable "CURL" extension or "allow_url_fopen")</td>'
        ];
        $checks['server']['xml'] = [
            'name' => 'XML support',
            'value' => extension_loaded('xml')
                ? '<td class="success">Available</td>'
                : '<td class="danger">Unavailable (recommended)</td>'
        ];
        $checks['server']['mysql_support'] = [
            'name' => 'MySQL support (MySQLi)',
            'value' => function_exists('mysqli_connect')
                ? '<td class="success">Available</td>'
                : '<td class="danger">Unavailable (required)</td>'
        ];
        $checks['server']['gd'] = [
            'name' => 'GD extension',
            'value' => extension_loaded('gd')
                ? '<td class="success">Available</td>'
                : '<td class="danger">Unavailable (highly recommended)</td>'
        ];
        $checks['server']['mbstring'] = [
            'name' => 'Mbstring extension',
            'value' => extension_loaded('mbstring')
                ? '<td class="success">Available</td>'
                : '<td class="danger">Unavailable (not required) </td>'
        ];

        if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || isset($_SERVER['HTTP_CF_VISITOR'])) {
            $checks['server']['flexiblessl'] = [
                'name' => 'Cloudflare\'s Flexible SSL',
                'value' => '<td class="warning">Cloudflare is in use. In case you want to push your site behind <em>Flexible SSL</em>, there might be issues with URLs</td>'
            ];
        }


        $recommendedSettings = [
            ['File Uploads', 'file_uploads', 'ON'],
            ['Register Globals', 'register_globals', 'OFF']
        ];
        foreach ($recommendedSettings as $item) {
            $checks['recommended'][$item[1]] = [
                'name' => $item[0] . ':</td><td>' . $item[2] . '',
                'value' => (iaHelper::getIniSetting($item[1]) == $item[2] ? '<td class="success">' : '<td class="danger">') . iaHelper::getIniSetting($item[1]) . '</td>',
            ];
        }

        $directory = [
            ['tmp/', '', true],
            ['uploads/', '', true],
            ['backup/', ' (optional)', false],
            ['modules/', ' (optional)', false],
            ['includes/config.inc.php', ' (optional)', false],
        ];

        foreach ($directory as $item) {
            $text = '';
            $isWritable = false;
            if (file_exists(IA_HOME . $item[0])) {
                $text = is_writable(IA_HOME . $item[0]) ? '<td class="success">Writable</td>' : '<td class="' . (empty($item[1]) ? 'danger' : 'optional') . '">Unwritable ' . $item[1] . '</td>';
                $isWritable = is_writable(IA_HOME . $item[0]);
            } else {
                if ($item[0] == 'includes/config.inc.php') {
                    if (!is_writable(IA_HOME . 'includes/')) {
                        $text = '<td class="danger">Does not exist and cannot be created' . $item[1] . '</td>';
                    } else {
                        $text = '<td class="success">Does not exist, but can be created' . $item[1] . '</td>';
                    }
                } else {
                    $text = '<td class="danger">Does not exist' . $item[1] . '</td>';
                }
            }
            $checks['directory'][$item[0]] = [
                'class' => true,
                'name' => $item[0],
                'value' => $text
            ];

            if ($item[2]) {
                $checks['directory'][$item[0]]['required'] = $isWritable;
            }
        }

        $nextButtonEnabled = true;
        foreach ($sections as $section => $items) {
            foreach ($checks[$section] as $key => $check) {
                if (isset($check['required']) && !$check['required']) {
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
        $errorList = [];
        $template = 'default';
        $templates = [];

        $directory = opendir(IA_HOME . 'templates/');
        while ($file = readdir($directory)) {
            if (substr($file, 0, 1) != '.' && '_common' != $file) {
                if (is_dir(IA_HOME . 'templates/' . $file)) {
                    $templates[] = $file;
                }
            }
        }
        closedir($directory);
        sort($templates);

        if (isset($_POST['db_action'])) {
            $requiredFields = ['dbhost', 'dbuser', 'dbname', 'prefix', 'tmpl', 'admin_username', 'admin_password', 'admin_email'];

            foreach ($requiredFields as $fieldName) {
                if (!iaHelper::getPost($fieldName, false)) {
                    $errorList[] = $fieldName;
                }
            }

            if (iaHelper::getPost('admin_password') != iaHelper::getPost('admin_password2')) {
                $errorList[] = 'admin_password2';
            }

            $port = (int)iaHelper::getPost('dbport', 3306);
            if ($port > 65536 || $port <= 0) {
                $_POST['dbport'] = 3306;
            }

            if (!iaHelper::email(iaHelper::getPost('admin_email'))) {
                $errorList[] = 'admin_email';
            }

            if (!preg_match('/^[a-zA-Z0-9._-]*$/i', iaHelper::getPost('admin_username'))) {
                $errorList[] = 'admin_username';
            }

            if (empty($errorList)) {
                $link = @mysqli_connect(iaHelper::getPost('dbhost'), iaHelper::getPost('dbuser'), iaHelper::getPost('dbpwd'), '', iaHelper::getPost('dbport', 3306));
                if (mysqli_connect_errno()) {
                    $error = true;
                    $message = 'MySQL server: ' . mysqli_connect_error() . '<br>';
                }

                if (!$error && !mysqli_select_db($link, iaHelper::getPost('dbname'))) {
                    $error = true;
                    $message = 'Could not select database ' . iaHelper::_html(iaHelper::getPost('dbname')) . ': ' . mysqli_error($link);
                }

                $prefix = iaHelper::getPost('prefix');

                if (!$error && !iaHelper::getPost('delete_tables', false)) {
                    if ($query = mysqli_query($link, 'SHOW TABLES')) {
                        if (mysqli_num_rows($query) > 0) {
                            while ($array = mysqli_fetch_row($query)) {
                                if (strpos($array[0], iaHelper::_sql($prefix, $link)) !== false) {
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

                if (!$error) {
                    $dbOptions = 'ENGINE=MyISAM DEFAULT CHARSET=utf8mb4';
                    $dumpFile = IA_INSTALL . 'dump/install.sql';

                    if (!file_exists($dumpFile)) {
                        $error = true;
                        $message = 'Could not open file with sql instructions: install.sql';
                    }
                }

                if (!$error) {
                    $search = [
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
                    ];
                    $message = $s_sql = '';
                    $counter = 0;
                    $file = file($dumpFile);
                    if (count($file) > 0) {
                        mysqli_query($link, "SET NAMES 'utf8mb4'");

                        foreach ($file as $s) {
                            $s = trim($s);
                            if (isset($s[0]) && ($s[0] == '#' || $s[0] == '' || 0 === strpos($s, '--'))) {
                                continue;
                            }

                            if ($s && $s[strlen($s) - 1] == ';') {
                                $s_sql .= $s;
                            } else {
                                $s_sql .= $s;
                                continue;
                            }

                            $s_sql = str_replace(array_keys($search), array_values($search), $s_sql);

                            if (!mysqli_query($link, $s_sql)) {
                                $error = true;
                                if ($counter == 0) {
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
                    } else {
                        $error = true;
                        $message = 'Mysql dump is empty! Please check the file!';
                    }
                }

                $iaModuleInstaller = iaHelper::loadCoreClass('module');

                $templateInstallationFile = IA_HOME . 'templates/' . iaHelper::getPost('tmpl', '') . IA_DS . 'install.xml';
                $iaModuleInstaller->getFromPath($templateInstallationFile);

                $iaModuleInstaller->parse();
                $iaModuleInstaller->checkValidity(iaHelper::getPost('tmpl', ''));

                if ($notes = $iaModuleInstaller->getNotes()) {
                    $error = true;
                    $errorList[] = 'template';
                    $message = sprintf('Template installation error: %s', implode('<br>', $notes));
                }

                if (!$error) {
                    $config = file_get_contents(IA_INSTALL . 'modules/config.sample');
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
https://subrion.org/forums/
__________________________
The Subrion Support Team
https://subrion.org
https://intelliants.com
HTML;
                    $salt = '#' . strtoupper(substr(md5(IA_HOME), 21, 10));
                    $params = [
                        '{version}' => IA_VERSION,
                        '{date}' => (new \DateTime())->format('d F Y H:i:s'),
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
                        '{url}' => URL_ADMIN_PANEL
                    ];
                    $body = str_replace(array_keys($params), array_values($params), $body);
                    $params['{dbpass}'] = str_replace("'", "\\'", $params['{dbpass}']);
                    $config = str_replace(array_keys($params), array_values($params), $config);

                    @mail(iaHelper::_sql(iaHelper::getPost('admin_email'), $link), 'Subrion CMS Installed', $body, 'From: support@subrion.org');
                    $filename = IA_HOME . 'includes/config.inc.php';
                    $configMsg = '';

                    // session path test, session_save_path might be empty in many configs
                    $testResult = !session_save_path() || is_writable(session_save_path()) ?
                        '' :
                        "session_save_path('" . IA_HOME . "tmp');";

                    $config = str_replace('{sessionpath}', $testResult, $config);
                    //

                    if (is_writable(IA_HOME . 'includes/') || is_writable($filename)) {
                        if (!$handle = fopen($filename, 'w+')) {
                            $configMsg = 'Cannot open file: ' . $filename;
                        }

                        if (fwrite($handle, $config) === false) {
                            $configMsg = 'Cannot write to file: ' . $filename;
                        }

                        fclose($handle);
                    } else {
                        $configMsg = 'Cannot write to folder.';
                    }

                    iaHelper::cleanUpDirectoryContents(IA_HOME . 'tmp/');

                    if (!$error) {
                        $step = 'finish';
                        $iaOutput->step = 'finish';
                    }

                    $iaOutput->config = $config;
                    $iaOutput->description = $configMsg;
                }

                if (!$error) {
                    defined('IA_SALT') || define('IA_SALT', $salt);

                    $iaUsers = iaHelper::loadCoreClass('users', 'core');
                    $iaUsers->changePassword(['id' => 1], iaHelper::getPost('admin_password'), false);

                    iaHelper::cleanUpCacheContents();

                    $iaModuleInstaller->install(iaModule::SETUP_INITIAL);

                    // writing it to the system log
                    $iaLog = iaHelper::loadCoreClass('log', 'core');
                    $iaLog->write(iaLog::ACTION_INSTALL, ['type' => 'app']);
                }

                if (!$error) {
                    $iaModuleInstaller = iaHelper::loadCoreClass('module');

                    $modulesFolder = IA_HOME . 'modules/';
                    foreach ($builtinPlugins as $pluginName) {
                        $installationFile = file_get_contents($modulesFolder . $pluginName . IA_DS . iaHelper::INSTALLATION_FILE_NAME);
                        if ($installationFile !== false) {
                            $iaModuleInstaller->setXml($installationFile);
                            $iaModuleInstaller->parse();

                            if (!$iaModuleInstaller->getNotes()) {
                                $result = $iaModuleInstaller->install();
                            }
                        }
                    }
                }

                if (!$error) {
                    iaHelper::launchCronTasks();
                }
            }

            $template = iaHelper::getPost('tmpl', $template);
        }

        $iaOutput->errorList = $errorList;
        $iaOutput->template = $template;
        $iaOutput->templates = $templates;

        break;

    case 'download':
        if (class_exists('iaCore')) { // iaCore isn't loaded when config file hasn't been written
            iaCore::instance()->iaView->set('nodebug', true);
        }

        header('Content-Type: text/x-delimtext; name="config.inc.php"');
        header('Content-disposition: attachment; filename="config.inc.php"');

        echo $_POST['config_content'];
        exit;

    case 'plugins':
        if (iaHelper::isAjaxRequest()) {
            if (isset($_POST['plugin']) && $_POST['plugin']) {
                echo iaHelper::installRemotePlugin($_POST['plugin'])
                    ? 'installed successfully'
                    : 'installation is not performed';
                exit();
            }
        } else {
            if ($plugins = iaHelper::getRemotePluginsList(IA_VERSION)) {
                $iaOutput->plugins = $plugins;
            } else {
                $message = 'Could not get the list of compatible plugins.';
            }
        }

        break;

    default:
        return;
}

$iaOutput->error = $error;
$iaOutput->message = $message;
