<?php
//##copyright##

class iaDebug
{
	const STATE_OPENED = 'opened';
	const STATE_CLOSED = 'closed';

	protected $_timer;

	protected static $_logger;


	public function __construct()
	{
		iaSystem::renderTime('end');

		$debug = self::STATE_OPENED;
		if (isset($_COOKIE['debug']))
		{
			if (self::STATE_CLOSED == $_COOKIE['debug'])
			{
				$debug = self::STATE_CLOSED;
			}
		}

		$this->_debugCss();
		echo '<div id="debug-toggle"><a href="#" class="' . $debug . '"></a></div>';
		echo '<div id="debug" class="' . $debug . ' clearfix">';

		$this->_box('info');
		$this->_box('sql');
		$this->_box('timer');

		if (isset($_SESSION['error']))
		{
			$this->_box('error');
		}

		if (isset($_SESSION['debug']))
		{
			$this->_box('debug', self::STATE_OPENED);
		}
	}

	public static function logger($destinationDirectory = null)
	{
		if (is_null(self::$_logger))
		{
			require IA_INCLUDES . 'utils' . IA_DS . 'KLogger.php';

			self::$_logger = new KLogger(is_null($destinationDirectory) ? IA_TMP : $destinationDirectory, KLogger::INFO);
		}

		return self::$_logger;
	}

	protected function _debugCss()
	{
		echo '
<link href="' . IA_CLEAR_URL . 'js/debug/hl.css" type="text/css" rel="stylesheet">
<link href="' . IA_CLEAR_URL . 'js/debug/debug.css" type="text/css" rel="stylesheet">
<script type="text/javascript" src="' . IA_CLEAR_URL . 'js/debug/hl.js"></script>
<script type="text/javascript" src="' . IA_CLEAR_URL . 'js/debug/debug.js"></script>
';
	}

	protected function _box($type = 'info', $debug = 'none')
	{
		// FIXME: the $debug variable is unused in the code below
		/* COMMENTED OUT
		if ($debug == 'none' || !in_array($debug, array(self::STATE_OPENED, self::STATE_CLOSED)))
		{
			$debug = self::STATE_CLOSED;
			if (isset($_COOKIE['dtext_' . $type]))
			{
				if ($_COOKIE['dtext_' . $type] == self::STATE_OPENED)
				{
					$debug = self::STATE_OPENED;
				}
			}
		}
		/* ENDOFCM */

		echo
			'<div class="debug-modal" id="dtext-'.$type.'">
			<a class="debug-btn-close" data-toggle="'.$type.'">&times;</a>
			<div class="debug-text">';

		$func = '_debug'.ucfirst($type);
		$text = $this->$func();

		echo '</div></div><a class="debug-btn" data-toggle="'.$type.'">'.ucfirst($type).($text ? ' '.$text.' ' : '').'</a>';
	}

	protected function _debugInfo()
	{
		if (isset($_SESSION['info']))
		{
			foreach ($_SESSION['info'] as $key => $val)
			{
				self::vardump($val, (!is_int($key) ? $key : ''));
			}
			unset($_SESSION['info']);
		}

		$iaCore = iaCore::instance();

		self::vardump();
		self::vardump($iaCore->iaDb->getCount(), 'Queries count');
		self::vardump($iaCore->iaView->getParams(), 'Page Params');

		$blocks = array();
		if ($blocksData = $iaCore->iaView->blocks)
		{
			foreach ($blocksData as $position => $blocksList)
			{
				$blocks[$position] = array();
				foreach ($blocksList as $block)
				{
					$blocks[$position][] = $block['name'];
				}
			}

		}
		self::vardump($blocks, 'Blocks List');

		$iaCore->factory('users');
		if (iaUsers::hasIdentity())
		{
			self::vardump(iaUsers::getIdentity(true), 'User Profile');
		}

		self::vardump();
		self::vardump(array_keys($iaCore->getHooks()), 'Hooks List');
		self::vardump($iaCore->packagesData, 'Installed Packages Data');

		self::vardump();
		self::vardump($iaCore->requestPath, 'URL Params');
		self::vardump($iaCore->getConfig(), 'Configuration Params');

		self::vardump();
		self::vardump($_SERVER, '$_SERVER');
		self::vardump($_SESSION, '$_SESSION');
		self::vardump($_COOKIE, '$_COOKIE');

		self::vardump();
		self::vardump($_POST, '$_POST');
		self::vardump($_FILES, '$_FILES');
		self::vardump($_GET, '$_GET');

		echo '<div id="error_console_log"></div>';

		return '[' . $iaCore->iaView->name() . ']';
	}

	protected function _debugSql()
	{
		$table = '';
		$iaCore = iaCore::instance();

		if ($queries = $iaCore->iaDb->getQueriesList())
		{
			array_unshift($queries, 'Core Queries');
			$duplicated = array();
			$index = 0;

			foreach ($queries as $query)
			{
				if (!is_array($query))
				{
					$table .= '<tr><th colspan="3" style="color:green"><div>'.$query.'</div></th></tr>';
				}
				else
				{
					$index++;
					$double = '';
					if (in_array($query[0], $duplicated))
					{
						$double = '<span style="color:red;font-weight:bold;">DUPLICATED</span> ';
					}
					else
					{
						$duplicated[] = $query[0];
					}
					$title = $query[0];
					if (strlen($title) > 80)
					{
						$title = substr($title, 0, - strlen($title) + 80) . ' ... ';
					}
					$nbsp = '&nbsp;&nbsp;&nbsp;';
					$search = array("\t",'FROM','SELECT',' AS ',' LIKE ',' ON ',' AND ',' OR ', 'WHERE', 'INNER JOIN', 'RIGHT JOIN', 'LEFT JOIN', 'LEFT OUTER', ' JOIN', 'ORDER BY', 'GROUP BY', 'LIMIT');
					$replace = array(
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
					);
					$query[0] = str_replace($search, $replace, $query[0]);

					$table .= '<tr><td style="width:15px;color:'.($query[1] > 0.001 ? 'red' : 'green') . ';">'
						. ($query[1] * 1000) . '&nbsp;ms</td><td style="width:30px;text-align:center;">' . $index . '.</td><td>'
						. $double.self::vardump($query[0], $title, 1) . '</td></tr>';
				}
			}
		}
		echo '<table cellspacing="0" cellpadding="0" width="100%"><tr><th width="30">Time</th><th></th><th>Sql</th></tr>' . $table . '</table>';

		return sprintf('[Queries: %d]', $iaCore->iaDb->getCount());
	}

	protected function _debugDebug()
	{
		foreach ($_SESSION['debug'] as $key => $val)
		{
			self::vardump($val, (!is_int($key) ? $key : ''));
		}
		$return = '[Count: ' . count($_SESSION['debug']) . ']';
		unset($_SESSION['debug']);

		return $return;
	}

	protected function _debugError()
	{
		$count = 0;
		foreach ($_SESSION['error'] as $key => $val)
		{
			if ($val != '<div class="hr">&nbsp;</div>' && strpos($key, 'Backtrace') !== false)
			{
				$count++;
			}
			self::vardump($val, (!is_int($key) ? $key : ''));
		}

		unset($_SESSION['error']);

		return '[Count: '.$count.']';
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

		for ($i = 0; $i < $count; $i++)
		{
			$memoryUsed = (int)iaSystem::$timer[$i]['bytes'];
			$memoryInPrevIteration = $i ? (int)iaSystem::$timer[$i-1]['bytes'] : 0;
			$start = (float)$last[0][1] + (float)$last[0][0];
			$end = iaSystem::$timer[$i]['time'][1] + iaSystem::$timer[$i]['time'][0];
			$times = number_format((float)$end - $start, 5, '.', '');
			$perc = ceil(($memoryUsed - $memoryInPrevIteration) * 100 / $memoryUsed);
			if ($times > 0.0001)
			{
				$last[0] = $last[1] = iaSystem::$timer[$i]['time'];
				$totalTime += $times;

				$text .= ('<tr><td width="1">' . $i . '.</td><td colspan="3" width="100%"><div class="hr">&nbsp;</div></td></tr>
				<tr>
					<td rowspan="2">&nbsp;</td>
					<td rowspan="2" width="60%">
						<i>' . iaSystem::$timer[$i]['description'] . '</i> <br />
						' . ( $perc >= 5 ? '<font color="orange"><i>memory up:</i></font> ' . $perc . '%' : '' )
					. '</td>
					<td><b>Rendering time:</b></td>
					<td>' . ( $times > 0.01 ? '<font color="red">' . $times * 1000 . '</font>' : $times * 1000 ) . ' ms ('.($totalTime).' s)</td>
				</tr>
				<tr>
					<td><b>Memory usage:</b></td>
					<td>'
					. iaSystem::byteView($memoryUsed)
					. ' (' . number_format($memoryUsed, 0, '', ' ')
					. ')</td>
				</tr>');
			}
		}

		$search = array('START', 'END');
		$replace = array('<b class="d_green">START</b>', '<b class="d_red">END</b>');

		$text = str_replace($search, $replace, '<b>Real time render:</b> ' . $totalRealTime . '<br />
			<b>Math time render:</b> ' . $totalTime . '<br />
			<b>Memory usage without gz compress:</b> ' . iaSystem::byteView($memoryUsed) . '(' . number_format($memoryUsed, 0, '', ' ') . 'b)
			<table border="0" cellspacing="2" cellpadding="2" width="100%">' . $text . '</table>');

		echo $text;

		return '[Time: '.$totalRealTime.'] [Mem.: '.iaSystem::byteView($memoryUsed).']';
	}

	public static function vardump($val = '<br />', $title = '', $type = 0)
	{
		if (is_array($val))
		{
			if (empty($val))
			{
				echo '<div><span style="text-decoration: line-through;color:#464B4D;font-weight:bold;text-shadow: 0 1px 1px white;">'
					. $title . '</span> <span style="color:red;">Array is empty</span></div>';
			}
			else
			{
				if ($title)
				{
					$name = 'pre_' . mt_rand(1000, 9999);
					echo '<div style="margin:0px;font-size:11px;"><span onclick="document.getElementById(\''.$name.'\').style.display = (document.getElementById(\''.$name.'\').style.display==\'none\' ? \'block\' : \'none\');">
					<b><i style="color:green;cursor:pointer;text-shadow: 0 1px 1px white;">'.$title.'</i></b></span> ['.count($val).']</div>
					<pre style="display:none;font-size:12px;max-height:250px;overflow:auto;margin:5px;" id="'.$name.'">';
				}
				else
				{
					echo '<pre>';
				}
				print_r($val);
				echo '</pre>';
			}
		}
		else
		{
			if (is_bool($val))
			{
				$val = $val
					? '<i style="color:green">true</i>'
					: '<i style="color:red">false</i>';
			}

			if ($type == 1)
			{
				$count = 50;
				if (strlen($val) > $count)
				{
					$title = $title ? $title : substr($val, 0, - strlen($val) + $count) . ' ... ';
					$name = 'val_' . mt_rand(1000, 9999);
					return '<div onclick="document.getElementById(\''.$name.'\').style.display = (document.getElementById(\''.$name.'\').style.display==\'none\' ? \'block\' : \'none\');">'
						. '<b><i style="color:black;cursor:pointer;">'.$title.'</i></b></div><div style="display:none;color:#464B4D;" id="'.$name.'"><pre><code class="sql">'.$val.'</code></pre></div> ';
				}
				else
				{
					return '<div style="color:black;"><pre><code class="sql">'.$val.'</code></pre></div>';
				}
			}
			else
			{
				echo '<div>' . ($title != '' ? '<b><i style="color:#464B4D;text-shadow: 0 1px 1px white;">' . $title . ':</i></b> ' : '') . $val . '</div>';
			}
		}

		return '';
	}

	public static function debug($value, $key = null, $type = 'debug')
	{
		switch (true)
		{
			case is_bool($value):
				$value = $value
					? '<i style="color:green">true</i>'
					: '<i style="color:red">false</i>';
				break;
			case is_null($value):
				$value = '<i style="color:gray">NULL</i>';
		}

		if ('debug' == $type && function_exists('debug_backtrace'))
		{
			$trace = debug_backtrace();
			if (isset($trace[1]))
			{
				$trace = $trace[1];
				$key = '<span style="font-size:10px; text-decoration:underline;">' . str_replace(IA_HOME, '', $trace['file'])
					. ' on line [' . $trace['line'] . ']'
					. '<span style="display: none">' . mt_rand(10000, 99999) . '</span></span><br>' . ($key == '' ? '-empty title-' : $key);
			}
		}

		if (empty($key))
		{
			$_SESSION[$type][] = $value;
		}
		else
		{
			if (isset($_SESSION[$type][$key]))
			{
				if (is_array($_SESSION[$type][$key]))
				{
					$_SESSION[$type][$key][] = $value;
				}
				else
				{
					$_SESSION[$type][$key] = array($_SESSION[$type][$key], $value);
				}
			}
			else
			{
				$_SESSION[$type][$key] = $value;
			}
		}
	}
}