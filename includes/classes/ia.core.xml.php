<?php
//##copyright##

class iaXml extends abstractUtil
{

	private $_foreign = false;

	public function init()
	{
		parent::init();

		if (!function_exists('simplexml_load_string'))
		{
			require_once IA_INCLUDES . 'utils' . IA_DS . 'simplexml.class.php';

			$this->_foreign = new simplexml();
		}
	}

	private function _parse_string($string)
	{
		return $this->_foreign ? $this->_foreign->xml_load_string($string) : simplexml_load_string($string);
	}

	public function parse_file($file)
	{
		return $this->_parse_string(file_get_contents($file));
	}
}