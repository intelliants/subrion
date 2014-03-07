<?php 
//##copyright##

function _v($val = '<br />', $title = '', $type = 0)
{
	iaDebug::vardump($val, $title, $type);
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