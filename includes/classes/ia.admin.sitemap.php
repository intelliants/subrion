<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

class iaSitemap extends abstractCore
{
	const FILENAME = 'sitemap.xml';

	const TYPE_GOOGLE = 'GOOG';
	const TYPE_YAHOO = 'YHOO';
	const TYPE_GOOGLE_IMAGES = 'GOOG-I';

	const GETTER_METHOD_NAME = 'getSitemapEntries';

	protected $_xmlWrappers = array(
		self::TYPE_GOOGLE => '<url><loc>:url</loc></url>',
		self::TYPE_YAHOO => '',
		self::TYPE_GOOGLE_IMAGES => ''
	);

	protected $_xmlContent = '<?xml version="1.0" encoding="UTF-8"?><urlset
		xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">:content</urlset>';


	/**
	 * Writes the sitemap file according to provided type
	 *
	 * @return bool
	 */
	public function generate($type = self::TYPE_GOOGLE)
	{
		set_time_limit(600);

		$entries = $this->_getEntries($type);
		$content = $this->_wrapToXml($entries, $type);

		$output = str_replace(':content', $content, $this->_xmlContent);

		return (bool)file_put_contents(IA_TMP . self::FILENAME, $output);
	}

	/**
	 * Wraps sitemap entries to XML code
	 *
	 * @return string
	 */
	protected function _wrapToXml(array $entries, $type) // type is currently ignored
	{
		$result = '';

		$entryPattern = $this->_xmlWrappers[$type];
		foreach ($entries as $group => $urls)
		{
			foreach ($urls as $url)
			{
				$result .= str_replace(':url', $url, $entryPattern);
			}
		}

		return $result;
	}

	/**
	 * Returns array of sitemap entries
	 *
	 * @return array
	 */
	protected function _getEntries()
	{
		$result = array('pages' => array());

		// 1. first process the core content
		$extrasList = $this->iaDb->keyvalue(array('name', 'type'), "`status` = 'active'", 'extras');
		$homePageName = $this->iaCore->get('home_page');

		$stmt = '`nofollow` = 0 AND `service` = 0 AND `status` = :status AND `passw` = :password ORDER BY `order`';
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'password' => ''));

		$pages = $this->iaDb->all(array('name', 'alias', 'custom_url', 'extras'), $stmt, null, null, 'pages');
		foreach ($pages as $page)
		{
			if (empty($page['extras']) || isset($extrasList[$page['extras']]))
			{
				switch (true)
				{
					case ($page['name'] == $homePageName):
						$url = '';
						break;
					case $page['custom_url']:
						$url = $page['custom_url'];
						break;
					case $page['alias']:
						$url = $page['alias'];
						break;
					default:
						$url = $page['name'] . IA_URL_DELIMITER;
				}

				$result['pages'][] = $url;
			}
		}

		// 2. handle packages then
		$iaItem = $this->iaCore->factory('item');

		foreach ($iaItem->getPackageItems() as $itemName => $package)
		{
			if (iaCore::CORE != $package)
			{
				$itemClassInstance = $this->iaCore->factoryPackage('item', $package, iaCore::ADMIN, $itemName);
				if (empty($itemClassInstance))
				{
					$itemClassInstance = $this->iaCore->factoryPackage('item', $package, 'common', $itemName);
				}

				if (method_exists($itemClassInstance, self::GETTER_METHOD_NAME))
				{
					$entries = $itemClassInstance->{self::GETTER_METHOD_NAME}();
					if (is_array($entries) && $entries)
					{
						$result[$itemName] = $entries;
					}
				}
			}
		}

		// 3. process the rest via hooks
		$itemsList = array();

		$this->iaCore->startHook('sitemapGeneration', array('items' => &$itemsList));

		if (is_array($itemsList) && $itemsList)
		{
			foreach ($itemsList as $item)
			{
				$array = explode(':', $item);
				$pluginInstance = $this->iaCore->factoryPlugin($array[0], iaCore::ADMIN, isset($array[1]) ? $array[1] : null);

				if (method_exists($pluginInstance, self::GETTER_METHOD_NAME))
				{
					$item = isset($array[1]) ? $array[1] : $item;
					$entries = $pluginInstance->{self::GETTER_METHOD_NAME}();
					if (is_array($entries) && $entries)
					{
						$result[$item] = $entries;
					}
				}
			}
		}
		//

		foreach ($result as &$urls)
		{
			foreach ($urls as &$entry)
			{
				$entry = (false === stripos($entry, 'http://') && false === stripos($entry, 'https://'))
					? IA_URL . $entry
					: $entry;
			}
		}

		if ($this->iaCore->get('language_switch') && count($this->iaCore->languages) > 1)
		{
			$installedLanguages = $this->iaCore->languages;
			$currentLanguage = $this->iaView->language;

			foreach ($result as $group => $urls)
			{
				foreach ($urls as $url)
				{
					foreach ($installedLanguages as $code => $title)
					{
						if ($code != $currentLanguage)
						{
							// potentially buggy. replaces all (!) of entries in URL
							$result[$group][] = str_replace(IA_URL_DELIMITER . $currentLanguage . IA_URL_DELIMITER, IA_URL_DELIMITER . $code . IA_URL_DELIMITER, $url);
						}
					}
				}
			}
		}

		return $result;
	}
}