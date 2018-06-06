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

class iaDebug
{
    const STATE_OPENED = 'opened';
    const STATE_CLOSED = 'closed';

    protected static $_data = [];

    protected static $_fileHandle;

    protected static $_fileName = null;


    public function __construct()
    {
        iaSystem::renderTime('end');

        $debug = self::STATE_OPENED;
        if (isset($_COOKIE['debug'])) {
            if (self::STATE_CLOSED == $_COOKIE['debug']) {
                $debug = self::STATE_CLOSED;
            }
        }

        $this->_debugCss();
        echo '<div id="debug-toggle"><a href="#" class="' . $debug . '"></a></div>';
        echo '<div id="debug" class="' . $debug . ' clearfix">';

        $this->_box('info');
        $this->_box('hooks');
        $this->_box('sql');
        $this->_box('timer');

        empty(self::$_data['error']) || $this->_box('error');
        empty(self::$_data['debug']) || $this->_box('debug', self::STATE_OPENED);
    }

    protected function _debugCss()
    {
        $assetsUrl = defined('INSTALL') ? URL_HOME : iaCore::instance()->iaView->assetsUrl;
        echo <<<OUTPUT


<!-- START DEBUG MODE -->
<link href="{$assetsUrl}js/debug/hl.css" type="text/css" rel="stylesheet">
<link href="{$assetsUrl}js/debug/debug.css" type="text/css" rel="stylesheet">

<script type="text/javascript" src="{$assetsUrl}js/debug/hl.js"></script>
<script type="text/javascript" src="{$assetsUrl}js/debug/debug.js"></script>


OUTPUT;
    }

    protected function _box($type = 'info', $debug = 'none')
    {
        $capitalized = ucfirst($type);
        $functionName = '_debug' . $capitalized;

        ob_start();
        $text = $this->$functionName();
        if ($output = ob_get_clean()) {
            $output = <<<OUTPUT
<div class="debug-modal" id="dtext-{$type}">
    <a class="debug-btn-close" data-toggle="{$type}">&times;</a>
    <div class="debug-text">{$output}</div>
</div>
<a class="debug-btn" data-toggle="{$type}"> {$capitalized} {$text} </a>
OUTPUT;
        }

        echo $output;
    }

    protected function _debugInfo()
    {
        $iaCore = iaCore::instance();
        $iaCore->factory('users');

        self::dump(iaCore::ACCESS_FRONT == $iaCore->getAccessType() ? iaCore::FRONT : iaCore::ADMIN, 'Access Type');
        self::dump($iaCore->iaView->getParams(), 'Page', 'info');
        self::dump($iaCore->iaView->get('action'), 'Action', 'info');
        self::dump($iaCore->iaView->get('filename'), 'Module');
        self::dump($iaCore->iaView->language, 'Language');
        self::dump(iaUsers::hasIdentity() ? iaUsers::getIdentity(true) : null, 'Identity');
        self::dump();

        // process blocks
        $blocks = [];
        if ($blocksData = $iaCore->iaView->blocks) {
            foreach ($blocksData as $position => $blocksList) {
                $blocks[$position] = [];
                foreach ($blocksList as $block) {
                    $blocks[$position][] = $block['name'];
                }
            }
        }

        // process constants
        $constantsList = get_defined_constants(true);
        foreach ($constantsList['user'] as $key => $value) {
            if (strpos($key, 'IA_') === 0 && 'IA_SALT' != $key) {
                $constants[$key] = $value;
            }
        }

        self::dump($iaCore->requestPath, 'URL Params');
        self::dump($blocks, 'Blocks List');
        self::dump($iaCore->modulesData, 'Installed Packages');
        self::dump($iaCore->getConfig(), 'Configuration Params');
        self::dump($constants, 'Constants List');

        if (!empty(self::$_data['info'])) {
            foreach (self::$_data['info'] as $key => $val) {
                self::dump($val, (!is_int($key) ? $key : ''));
            }
        }

        self::dump();
        self::dump($_POST, '$_POST');
        self::dump($_FILES, '$_FILES');
        self::dump($_GET, '$_GET');

        self::dump();
        self::dump(PHP_VERSION, 'PHP version');
        self::dump($_SERVER, '$_SERVER');
        self::dump($_SESSION, '$_SESSION');
        self::dump($_COOKIE, '$_COOKIE');

        return '[' . $iaCore->iaView->get('name', 'no name') . ']';
    }

    protected function _debugSql()
    {
        $table = '';
        $iaCore = iaCore::instance();

        if ($queries = $iaCore->iaDb->getQueriesList()) {
            $duplicated = [];
            $index = 0;

            foreach ($queries as $query) {
                if (!is_array($query)) {
                    $table .= '<tr><th colspan="3" style="color:green"><div>' . $query . '</div></th></tr>';
                } else {
                    $index++;
                    $double = '';
                    if (in_array($query[0], $duplicated)) {
                        $double = '<span style="color:red;font-weight:bold;">DUPLICATED</span> ';
                    } else {
                        $duplicated[] = $query[0];
                    }
                    $title = $query[0];
                    if (strlen($title) > 80) {
                        $title = substr($title, 0, - strlen($title) + 80) . ' ... ';
                    }
                    $nbsp = '&nbsp;&nbsp;&nbsp;';
                    $search = ["\t",'FROM','SELECT',' AS ',' LIKE ',' ON ',' AND ',' OR ', 'WHERE', 'INNER JOIN', 'RIGHT JOIN', 'LEFT JOIN', 'LEFT OUTER', ' JOIN', 'ORDER BY', 'GROUP BY', 'LIMIT'];
                    $replace = [
                        $nbsp,
                        "<br>{$nbsp}<b>FROM</b>",
                        "<b>SELECT</b>",
                        " <b>AS</b> ",
                        " <b>LIKE</b> ",
                        "<br>{$nbsp}{$nbsp}{$nbsp}<b>ON</b> ",
                        " <b>AND</b> ",
                        " <b>OR</b> ",
                        "<br>{$nbsp}<b>WHERE</b>",
                        "<br>{$nbsp}{$nbsp}<b>INNER</b> <b>JOIN</b>",
                        "<br>{$nbsp}{$nbsp}<b>RIGHT</b> <b>JOIN</b>",
                        "<br>{$nbsp}{$nbsp}<b>LEFT</b> <b>JOIN</b>",
                        "<br>{$nbsp}{$nbsp}<b>LEFT</b> <b>OUTER</b>",
                        " <br>{$nbsp}{$nbsp}<b>JOIN</b>",
                        "<br>{$nbsp}<b>ORDER BY</b>",
                        "<br>{$nbsp}<b>GROUP BY</b>",
                        "<br>{$nbsp}<b>LIMIT</b>",
                    ];
                    $query[0] = str_replace($search, $replace, $query[0]);

                    $table .= '<tr><td class="iterator">' . $index . '.</td><td style="width: 15px; color: '.($query[1] > 0.001 ? 'red' : 'green') . ';">'
                        . ($query[1] * 1000) . '&nbsp;ms</td><td>' . $double.self::dump($query[0], $title, 1, 'sql') . '</td></tr>';
                }
            }
        }
        echo '<table><tr><th width="30">#</th><th>Time</th><th>Query</th></tr>' . $table . '</table>';

        return sprintf('[Queries: %d]', $iaCore->iaDb->getCount());
    }

    protected function _debugDebug()
    {
        foreach (self::$_data['debug'] as $key => $val) {
            self::dump($val, (!is_int($key) ? $key : ''));
        }

        return '[' . count(self::$_data['debug']) . ']';
    }

    protected function _debugHooks()
    {
        $output = '';
        $i = 0;
        $total = 0;

        $listLoaded = iaCore::instance()->getHooks();
        $listUnused = $listLoaded; // needs to be pre-populated

        if (empty(self::$_data['hooks'])) {
            return '';
        }

        foreach (self::$_data['hooks'] as $name => $type) {
            $i++;

            if (isset($listLoaded[$name])) { // if this hook has been executed
                unset($listUnused[$name]);

                $hooksContent = [];
                $j = 0;
                foreach ($listLoaded[$name] as $pluginName => $hookData) {
                    $j++;
                    $pluginName = empty($pluginName) ? 'core' : $pluginName;

                    $hooksContent['hooks'][$j]['header'] = '<div style="margin: 10px 0 5px 0;"><b>Type:</b> ' . $hookData['type'] . '<b style="margin-left: 30px;">Extension:</b> ' . $pluginName . '</div>';
                    $hooksContent['hooks'][$j]['type'] = $hookData['type'];
                    $hooksContent['hooks'][$j]['filename'] = $hookData['filename'];
                    $hooksContent['hooks'][$j]['code'] = iaSanitize::html($hookData['code'], 0, 100);
                }

                $name = '<span style="color: green;">(' . count($listLoaded[$name]) . ')</span> ' . $name;

                ob_start();
                self::dump($hooksContent, $name, true);
                $cellContent = ob_get_clean();
            } else {
                $cellContent = '<b>' . $name . '</b>';
            }

            $type = is_array($type) ? $type[0] : $type;

            $output.= '<tr><td class="iterator">' . $i . '</td><td width="60"><i>' . $type . '</i></td><td>' . $cellContent . '</td></tr>';
        }

        foreach ($listLoaded as $hooks) {
            $total += count($hooks);
        }

        empty($listUnused) || self::dump($listUnused, 'Hooks loaded, but weren\'t executed');

        echo '<h4>Hooks List</h4><table>' . $output . '</table>';

        return "[$total/$i]";
    }

    protected function _debugError()
    {
        $count = 0;
        foreach (self::$_data['error'] as $key => $val) {
            if ($val != '<div class="hr">&nbsp;</div>' && strpos($key, 'Backtrace') !== false) {
                $count++;
            }
            self::dump($val, (!is_int($key) ? $key : ''));
        }

        return '[' . $count . ']';
    }

    protected function _debugTimer()
    {
        $count = count(iaSystem::$timer) - 1;
        $totalTime = 0;
        $text = '';
        $last[0] = $last[1] = iaSystem::$timer[0]['time'];

        $start = iaSystem::$timer[0]['time'];
        $end = iaSystem::$timer[$count]['time'];
        $totalRealTime = number_format((($end[1] + $end[0]) - ($start[1] + $start[0])), 5, '.', '');

        for ($i = 0; $i < $count; $i++) {
            $memoryUsed = (int)iaSystem::$timer[$i]['bytes'];
            $memoryInPrevIteration = $i ? (int)iaSystem::$timer[$i-1]['bytes'] : 0;
            $start = (float)$last[0][1] + (float)$last[0][0];
            $end = iaSystem::$timer[$i]['time'][1] + iaSystem::$timer[$i]['time'][0];
            $times = number_format((float)$end - $start, 5, '.', '');
            $perc = ceil(($memoryUsed - $memoryInPrevIteration) * 100 / $memoryUsed);
            if ($times > 0.0001) {
                $last[0] = $last[1] = iaSystem::$timer[$i]['time'];
                $totalTime += $times;

                $text .= ('<tr><td class="iterator" rowspan="2">' . $i . '.</td>
					<td rowspan="2" class="noborder">
						<i>' . iaSystem::$timer[$i]['description'] . '</i> <br />
						' . ($perc >= 5 ? '<font color="orange"><i>memory up:</i></font> ' . $perc . '%' : '')
                    . '</td>
					<td><b>Rendering time:</b></td>
					<td>' . ($times > 0.01 ? '<font color="red">' . $times * 1000 . '</font>' : $times * 1000) . ' ms ('.($totalTime).' s)</td>
				</tr>
				<tr>
					<td width="100"><b>Memory usage:</b></td>
					<td width="150">'
                    . iaSystem::byteView($memoryUsed)
                    . ' (' . number_format($memoryUsed, 0, '', ' ')
                    . ')</td>
				</tr>');
            }
        }

        $search = ['START', 'END'];
        $replace = ['<b class="d_green">START</b>', '<b class="d_red">END</b>'];

        $text = str_replace($search, $replace, '<b>Real time render:</b> ' . $totalRealTime . '<br />
			<b>Math time render:</b> ' . $totalTime . '<br />
			<b>Memory usage:</b> ' . iaSystem::byteView($memoryUsed) . '(' . number_format($memoryUsed, 0, '', ' ') . 'b)

			<table>' . $text . '</table>');

        echo $text;

        return '[Time: ' . $totalRealTime . '] [Mem.: ' . iaSystem::byteView($memoryUsed) . ']';
    }

    public static function dump($value = '<br />', $title = '', $type = false, $hl = 'php')
    {
        if (!INTELLI_DEBUG && !INTELLI_QDEBUG) {
            return '';
        }

        if (is_array($value)) {
            if (empty($value)) {
                echo '<div><span style="text-decoration: line-through;color:#464B4D;font-weight:bold;text-shadow: 0 1px 1px white;">'
                    . $title . '</span> <span style="color:red;">Empty array</span></div>';
            } else {
                if ($title) {
                    $name = 'pre_' . mt_rand(1000, 9999);
                    echo '<div style="margin:0;font-size:11px;"><span onclick="document.getElementById(\''.$name.'\').style.display = (document.getElementById(\''.$name.'\').style.display==\'none\' ? \'block\' : \'none\');">
					<b><i style="color:green;cursor:pointer;text-shadow: 0 1px 1px white;">'.$title.'</i></b></span> ['.count($value).']</div>
					<pre style="display:none;font-size:12px;max-height:250px;overflow:auto;margin: 5px 0 10px;" id="'.$name.'">';
                } else {
                    echo '<pre>';
                }

                if (isset($value['hooks'])) {
                    foreach ($value['hooks'] as $hook) {
                        echo $hook['header'];
                        if ($hook['filename']) {
                            echo $hook['filename'];
                        } else {
                            $hl = 'smarty' == $hook['type'] ? 'html' : $hook['type'];
                            echo "<code class=\"$hl\">",
                                htmlentities(print_r($hook['code'], true), ENT_COMPAT, 'UTF-8'),
                                '</code>';
                        }
                    }
                } else {
                    echo "<code class=\"{$hl}\">",
                        htmlentities(print_r($value, true), ENT_COMPAT, 'UTF-8'),
                        '</code>';
                }
                echo '</pre>';
            }
        } else {
            switch (true) {
                case is_bool($value):
                    $value = $value ? '<i style="color:green">true</i>' : '<i style="color:red">false</i>';
                    break;
                case is_null($value):
                    $value = '<i style="color:gray">NULL</i>';
            }

            if ($type) {
                $count = 100;
                if (strlen($value) > $count) {
                    $title = $title ? $title : substr($value, 0, - strlen($value) + $count) . ' ... ';
                    $name = 'val_' . mt_rand(1000, 9999);

                    return '<div onclick="document.getElementById(\''.$name.'\').style.display = (document.getElementById(\''.$name.'\').style.display==\'none\' ? \'block\' : \'none\');">'
                        . '<b><i style="color:black;cursor:pointer;">'.$title.'</i></b></div><div style="display:none;color:#464B4D;" id="'.$name.'"><pre><code class="' . $hl . '">'.$value.'</code></pre></div> ';
                } else {
                    return '<div style="color: black;"><pre><code class="' . $hl . '">' . $value . '</code></pre></div>';
                }
            } else {
                echo '<div>' . ($title != '' ? '<b><i style="color:#464B4D;text-shadow: 0 1px 1px white;">' . $title . ':</i></b> ' : '') . $value . '</div>';
            }
        }

        return '';
    }

    public static function debug($value, $key = null, $type = 'debug')
    {
        if (!INTELLI_DEBUG && defined('INTELLI_QDEBUG') && !INTELLI_QDEBUG) {
            // no need to continue if debug is turned off
            return;
        }

        switch (true) {
            case is_bool($value):
                $value = $value
                    ? '<i style="color:green">true</i>'
                    : '<i style="color:red">false</i>';
                break;
            case is_null($value):
                $value = '<i style="color:gray">NULL</i>';
                break;
            case is_object($value):
                $value = '<i style="color:blue">Object</i>';
        }

        if ('debug' == $type && function_exists('debug_backtrace')) {
            $trace = debug_backtrace();
            if (isset($trace[1])) {
                $trace = $trace[1];
                $key = '<span style="font-size:10px; text-decoration:underline;">' . str_replace(IA_HOME, '', $trace['file'])
                    . ' on line [' . $trace['line'] . ']'
                    . '<span style="display: none">' . mt_rand(10000, 99999) . '</span></span><br>' . ($key == '' ? '-empty title-' : $key);
            }
        }

        if (empty($key)) {
            self::$_data[$type][] = $value;
        } else {
            if (isset(self::$_data[$type][$key])) {
                is_array(self::$_data[$type][$key])
                    ? self::$_data[$type][$key][] = $value
                    : self::$_data[$type][$key] = [self::$_data[$type][$key], $value];
            } else {
                self::$_data[$type][$key] = $value;
            }
        }
    }

    /**
     * Logs
     *
     * @param string $message
     * @param array|object $context
     * @param string $fileName filename to write to
     *
     * @return null
     */
    public static function log($message, $context = null, $fileName = '')
    {
        $message = self::_formatMessage($message, $context);
        self::write($message, $fileName);
    }

    /**
     * Writes a line to the log without prepending a status or timestamp
     *
     * @param string $message Line to write to the log
     * @param string $fileName filename to write to
     *
     * @return bool
     */
    public static function write($message, $fileName = '')
    {
        if (is_null(self::$_fileName)) {
            self::$_fileName = $fileName ? $fileName : date('Y-m-d_H_i_s');
        } elseif ($fileName) {
            self::$_fileName = $fileName;
        }
        if (is_null(self::$_fileHandle)) {
            self::$_fileHandle = fopen(IA_TMP . self::$_fileName . '.txt', 'a');
        }

        return (bool)fwrite(self::$_fileHandle, $message);
    }

    /**
     * Formats the message for logging.
     *
     * @param  string $message The message to log * @param  array  $context The context
     *
     * @return string
     */
    private static function _formatMessage($message, $context = null)
    {
        if (!empty($context)) {
            $message .= PHP_EOL . self::_indent(self::_contextToString($context));
        }

        return '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL . PHP_EOL;
    }

    /**
     * Takes the given context and coverts it to a string.
     *
     * @param  array $context The Context
     * @return string
     */
    private static function _contextToString($context)
    {
        $export = '';
        if (is_object($context)) {
            $context = json_decode(json_encode($context), true);
        }

        if ($context instanceof Traversable) {
            foreach ($context as $key => $value) {
                $export .= "{$key}: ";
                $export .= preg_replace([
                    '/=>\s+([a-zA-Z])/im',
                    '/array\(\s+\)/im',
                    '/^  |\G  /m',
                ], [
                    '=> $1',
                    'array()',
                    '    ',
                ], str_replace('array (', 'array(', var_export($value, true)));
                $export .= PHP_EOL;
            }
        }

        return str_replace(['\\\\', '\\\''], ['\\', '\''], rtrim($export));
    }

    /**
     * Indents the given string with the given indent.
     *
     * @param  string $string The string to indent
     * @param  string $indent What to use as the indent.
     *
     * @return string
     */
    private static function _indent($string, $indent = '    ')
    {
        return $indent.str_replace("\n", "\n" . $indent, $string);
    }
}
