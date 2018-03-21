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

final class iaSystem
{
    const CLASSES_PREFIX = 'ia.';
    const EXECUTABLE_FILE_EXT = '.php';

    private static $_halt = false;

    public static $timer = [];


    public static function autoload($className)
    {
        $systemClasses = [
            'abstractCore' => 'ia.interfaces',
            'abstractUtil' => 'ia.interfaces',
            // interfaces
            'iaInterfaceDbAdapter' => 'ia.base.db',
            // core
            'iaCore' => 'ia.core',
            'iaDebug' => 'ia.debug',
            // items
            'itemModelAdmin' => 'ia.base.item.admin',
            'itemModelFront' => 'ia.base.item.front',
            // modules
            'abstractModuleAdmin' => 'ia.base.module.admin',
            'abstractModuleFront' => 'ia.base.module.front',
            'abstractModuleFrontApiResponder' => 'ia.base.module.front.api',
            // backend controllers
            'iaAbstractControllerBackend' => 'ia.base.controller.admin',
            'iaAbstractControllerModuleBackend' => 'ia.base.controller.module.admin'
        ];

        $helperClasses = [
            'iaAbstractHelperCategoryFlat' => 'ia.category.flat',
            'iaAbstractFrontHelperCategoryFlat' => 'ia.category.front.flat'
        ];

        if (isset($systemClasses[$className])) {
            $fileName = $systemClasses[$className] . self::EXECUTABLE_FILE_EXT;

            if (include_once IA_CLASSES . $fileName) {
                iaDebug::debug('<b>autoload:</b> ' . $fileName . ' (' . self::byteView(filesize(IA_CLASSES . $fileName)) . ')', 'Initialized Classes List', 'info');
                return true;
            }
        } elseif (isset($helperClasses[$className])) {
            $filePath = IA_INCLUDES . 'helpers/';
            $fileName = $helperClasses[$className] . self::EXECUTABLE_FILE_EXT;

            if (include_once $filePath . $fileName) {
                iaDebug::debug('<b>autoload:</b> ' . $fileName . ' (' . self::byteView(filesize($filePath . $fileName)) . ')', 'Initialized Classes List', 'info');
                return true;
            }
        }

        return false;
    }

    public static function output($output)
    {
        $escapedScriptPath = str_replace('/', '\/', preg_quote(IA_HOME));
        $matches = [];
        $filteredContent = strip_tags($output);

        preg_match('#Parse error\: (.+) in ' . $escapedScriptPath . '(.+?) on line (\d+)#i', $filteredContent, $matches);
        if (empty($matches)) {
            preg_match('#Fatal error\: (.+) in ' . $escapedScriptPath . '(.+?) on line (\d+)#i', $filteredContent, $matches);
            if (empty($matches)) {
                return false; // return false in order to output the original string
            }
        };
        self::$_halt = true;
        iaDebug::debug(self::error(0, $matches[1], $matches[2], $matches[3], true), null, 'error');

        return '';
    }

    public static function shutdown()
    {
        ob_end_flush();

        if (self::$_halt) {
            include_once IA_CLASSES . self::CLASSES_PREFIX . 'debug' . self::EXECUTABLE_FILE_EXT;
            new iaDebug();

            exit('Aborting...');
        }
    }

    public static function error($errno = 0, $errstr = '', $errfile = '', $errline = 0)
    {
        $exit = false;
        $errfile = str_replace(IA_HOME, '', $errfile);
        $errortype = [
            0 => 'Parsing Error',
            E_ERROR => 'Fatal Error',
            2048 => 'Error', // E_STRICT
            E_WARNING => 'Warning',
            E_PARSE => 'Parsing Error',
            E_NOTICE => 'Notice',
            E_CORE_ERROR => 'Core Error',
            E_CORE_WARNING => 'Core Warning',
            E_COMPILE_ERROR => 'Compile Error',
            E_COMPILE_WARNING => 'Compile Warning',
            E_USER_ERROR => 'User Error',
            E_USER_WARNING => 'User Warning',
            E_USER_NOTICE => 'User Notice'
        ];
        $error = ' ' . $errstr . ' <i><br> ' . ($errline != 0 ? 'on line <b>' . $errline . '</b>' : '')
            . ' in file <span style="font-weight:bold; text-shadow: 1px 1px 1px white; text-decoration: underline;">' . $errfile . '</span></i>';
        switch ($errno) {
            case 2048:
                $text = '';
                break;
            case 0:
            case E_COMPILE_ERROR:
            case E_PARSE:
            case E_ERROR:
            case E_USER_ERROR:
                $text = '<span style="font-weight:bold;color:red;">' . $errortype[$errno] . ':</span> ' . $error . '<br>';
                $exit = true;
                break;

            case E_WARNING:
            case E_USER_WARNING:
                $text = '<span class="e_warning">' . $errortype[$errno] . ':</span> ' . $error;
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                $text = '<span class="e_notice">' . $errortype[$errno] . ':</span> ' . $error;
                break;

            default:
                $text = (!isset($errortype[$errno]) ? 'Unknown error type [' . $errno . ']:' : $errortype[$errno]) . ' ' . $error;
        }
        $backTrace = debug_backtrace();
        $traceList = [];

        if ($errno == 0) {
            iaDebug::debug($backTrace, 'Backtrace<span style="display:none;">' . (mt_rand(10000, 99999)) . '</span>', 'error');
            return $text;
        } else {
            if ($backTrace) {
                foreach ($backTrace as $v) {
                    $file = '';
                    if (isset($v['line'])) {
                        $file .= 'on line [' . $v['line'] . '] ';
                    }
                    if (isset($v['file'])) {
                        $file .= 'in file [' . str_replace(IA_HOME, '', $v['file']) . '] ';
                    }
                    $trace = '';
                    if (isset($v['class'])) {
                        $trace .= 'in class ' . $v['class'] . '::' . $v['function'] . '(';
                        if (isset($v['args'])) {
                            $separator = '';
                            foreach ($v['args'] as $argument) {
                                $trace .= $separator . htmlspecialchars(self::_getArgument($argument));
                                $separator = ', ';
                            }
                        }
                        $trace .= ')';
                    } elseif (isset($v['function'])) {
                        $trace .= 'in function ' . $v['function'] . '(';
                        if (isset($v['args'])) {
                            $separator = '';
                            foreach ($v['args'] as $argument) {
                                $trace .= $separator . htmlspecialchars(self::_getArgument($argument));
                                $separator = ', ';
                            }
                        }
                        $trace .= ')';
                    }
                    if ($file) {
                        $trace = '<b style="color: #F00;">' . $trace . '</b><br><b>' . $file . '</b><hr>';
                    }
                    $traceList[] = $trace;
                }

                unset($traceList[count($traceList) - 1]);
                $traceList = array_reverse($traceList);
            }
        }

        if ($text) {
            iaDebug::debug($text, null, 'error');
            iaDebug::debug($traceList, 'Backtrace<span style="display:none;">' . (mt_rand(10000, 99999)) . '</span>', 'error');
            iaDebug::debug('<div class="hr">&nbsp;</div>', null, 'error');
        }

        if ($exit) {
            exit('Aborting...');
        }

        return true;
    }

    public static function phpSyntaxCheck($phpCode)
    {
        return @eval('return true;' . $phpCode);
    }

    public static function renderTime($section, $description = null)
    {
        $size = '-';

        if (function_exists('memory_get_peak_usage')) {
            $size = memory_get_peak_usage(1);
        } elseif (function_exists('memory_get_usage')) {
            $size = memory_get_usage(1);
        }

        self::$timer[] = [
            'time' => explode(' ', microtime()),
            'description' => is_null($description)
                ? $section
                : sprintf('<b>%s</b> - %s', $section, $description),
            'bytes' => $size
        ];
    }

    protected static function _getArgument($argument)
    {
        switch (strtolower(gettype($argument))) {
            case 'string':
                return '"' . str_replace("\n", '', $argument) . '"';
            case 'boolean':
                return $argument ? 'true' : 'false';
            case 'object':
                return 'object(' . get_class($argument) . ')';
            case 'array':
                return 'array()';
            case 'resource':
                return 'resource(' . get_resource_type($argument) . ')';
            default:
                return $argument;
        }
    }

    public static function forceUpgrade($version)
    {
        iaCore::instance()->factory('util');

        $patchUrl = iaUtil::REMOTE_TOOLS_URL . 'get/patch/%s/%s/';
        $patchUrl = sprintf($patchUrl, IA_VERSION, $version);

        $filePath = IA_TMP . 'patch.iap';

        iaUtil::downloadRemoteContent($patchUrl, $filePath);

        if ($contents = file_get_contents($filePath)) {
            require_once IA_HOME . 'install/classes/ia.patch.parser.php';
            require_once IA_HOME . 'install/classes/ia.patch.applier.php';

            try {
                $iaPatchParser = new iaPatchParser($contents);
                $patch = $iaPatchParser->patch;

                $iaPatchApplier = new iaPatchApplier(IA_HOME, [
                    'host' => INTELLI_DBHOST,
                    'port' => INTELLI_DBPORT,
                    'database' => INTELLI_DBNAME,
                    'user' => INTELLI_DBUSER,
                    'password' => INTELLI_DBPASS,
                    'prefix' => INTELLI_DBPREFIX
                ], true);

                $result = $iaPatchApplier->process($patch, $version);

                $logFile = 'upgrade-log-' . $patch['info']['version_to'] . '_' . date('d-m-y-Hi') . '.txt';
                if ($fh = fopen(IA_UPLOADS . $logFile, 'wt')) {
                    fwrite($fh, $iaPatchApplier->getLog());
                    fclose($fh);
                }

                $logParams = ['type' => 'app-forced', 'from' => IA_VERSION, 'to' => $version, 'file' => $logFile];

                $iaLog = iaCore::instance()->factory('log');
                $iaLog->write(iaLog::ACTION_UPGRADE, $logParams);

                return $result;
            } catch (Exception $e) {
                return $e->getMessage();
            }
        }

        return false;
    }

    public static function byteView($num = 0)
    {
        $text = '';
        $num = (int)$num;
        $list = ['Kb', 'Mb', 'Gb', 'Pb'];

        $i = 0;
        while ($num > 0 && $i < 10) {
            if (isset($list[$i])) {
                $temp = ($num / 1024);
                if (floor($temp) > 0) {
                    $num = number_format($temp, 5, '.', '');
                    $text = number_format($num, 2, '.', ' ') . $list[$i];
                }
            } else {
                $num = 0;
            }
            $i++;
        }

        return $text;
    }

    public static function setDebugMode()
    {
        if ($debuggerPassword = iaCore::instance()->get('debug_pass')) {
            if (isset($_GET['debugger']) && $debuggerPassword == $_GET['debugger']) {
                $_SESSION['debugger'] = $_GET['debugger'];
            }
            if (isset($_SESSION['debugger']) && $debuggerPassword == $_SESSION['debugger']) {
                define('INTELLI_QDEBUG', true);
            }
        }

        defined('INTELLI_QDEBUG') || define('INTELLI_QDEBUG', false);
    }
}
