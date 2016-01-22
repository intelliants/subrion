<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

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

		if (iaCore::ACCESS_ADMIN == iaCore::instance()->getAccessType())
		{
			$filePath = sprintf('%s/%s/templates/admin/%s', $extraType, $extraName, $templateName);
			return IA_HOME . $filePath;
		}

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