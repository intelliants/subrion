<?php
//##copyright##

class Smarty_Resource_Extra extends Smarty_Resource_Custom
{
	private static $_extraTypes;

	private $_commonFilesPath = array(
		'plugins' => 'front',
		'packages' => 'common'
	);


	protected function fetch($name, &$source, &$modifiedTime)
	{
		$path = $this->_translateName($name);

		$source = @file_get_contents($path);
		$modifiedTime = @filemtime($path);
	}

	protected function fetchTimestamp($name)
	{
		return filemtime($this->_translateName($name));
	}


	private function _translateName($name)
	{
		$array = explode('/', $name);

		$extraName = array_shift($array);
		$extraType = $this->_getExtraType($extraName) . 's';
		$templateName = implode('.', $array) . iaView::TEMPLATE_FILENAME_EXT;

		$filePath = sprintf('templates/%s/%s/%s/%s', iaCore::instance()->get('tmpl'), $extraType, $extraName, $templateName);
		is_file($filePath) || $filePath = sprintf('%s/%s/templates/%s/%s', $extraType, $extraName, $this->_commonFilesPath[$extraType], $templateName);

		return IA_HOME . $filePath;
	}

	private function _getExtraType($extraName)
	{
		if (is_null(self::$_extraTypes))
		{
			$iaCore = iaCore::instance();

			$iaCore->factory('item');

			self::$_extraTypes = $iaCore->iaDb->keyvalue(array('name', 'type'),
				iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), iaItem::getExtrasTable());
		}

		return isset(self::$_extraTypes[$extraName]) ? self::$_extraTypes[$extraName] : null;
	}
}