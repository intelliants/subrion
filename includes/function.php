<?php 
//##copyright##

function _v($val = '<br />', $title = '', $type = 0)
{
	iaDebug::dump($val, $title, $type);
}

function _vc()
{
	echo '<!-- DEBUG OUTPUT STARTED' . PHP_EOL;
	if ($count = func_num_args())
	{
		$count--;
		foreach (func_get_args() as $i => $argument)
		{
			echo PHP_EOL . 'Arg #' . ($i + 1) . ':' . PHP_EOL;
			var_dump($argument);
			echo PHP_EOL . ($i == $count ? '' : '==========');
		}
	}
	echo '-->';
}

function _d($value, $key = null)
{
	if (func_num_args() > 1 && $key != 'debug' && !is_null($key) && !is_string($key)) // treat it as a multiple variables display
	{
		foreach (func_get_args() as $argument)
		{
			iaDebug::debug($argument);
		}
	}
	else
	{
		iaDebug::debug($value, $key);
	}
}

function _t($key = '', $default = null)
{
	if (!class_exists('iaLanguage') || empty($key))
	{
		return false;
	}

	iaDebug::debug($key, 'Deprecated type of obtaining language phrase');

	return iaLanguage::get($key, $default);
}