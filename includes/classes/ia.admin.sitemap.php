<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2015 Intelliants, LLC <http://www.intelliants.com>
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

	const GETTER_METHOD_NAME = 'getSitemapEntries';
	
	const LINKS_SET_CORE = 1;
	const LINKS_SET_PACKAGES = 2;
	const LINKS_SET_PLUGINS = 3;

	protected $_xmlEntry = '<url><loc>:url</loc></url>';

	protected $_xmlContent = '<?xml version="1.0" encoding="UTF-8"?><urlset
		xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
		xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
		xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">:content:</urlset>';

	protected $_multilingual = false;


	public function init()
	{
		parent::init();

		$this->_multilingual = ($this->iaCore->get('language_switch') && count($this->iaCore->languages) > 1);
	}

	/**
	 * Writes the sitemap file
	 *
	 * @return bool
	 */
	public function generate()
	{
		set_time_limit(600);
		
		$fh = fopen(IA_TMP . self::FILENAME, 'w');

		if (!$fh)
		{
			return false;
		}

		// write file header
		$marker = ':content:';
		$offset = stripos($this->_xmlContent, $marker);
		$content = substr($this->_xmlContent, 0, $offset);

		fwrite($fh, $content);
		//
		

		$sets = array(self::LINKS_SET_CORE, self::LINKS_SET_PACKAGES, self::LINKS_SET_PLUGINS); // priority
		foreach ($sets as $set)
		{
			foreach ($this->_getEntries($set) as $url)
			{
				fwrite($fh, $this->_validate($url));
			}
		}

		// write XML footer
		fwrite($fh, substr($this->_xmlContent, $offset + strlen($marker)));

		fclose($fh);

		return true;
	}

	/**
	 * Returns array of sitemap entries
	 *
	 * @return array
	 */
	protected function _getEntries($setType)
	{
		$result = array();
		
		switch ($setType)
		{
			case self::LINKS_SET_CORE:
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

						$result[] = $url;
					}
				}

				break;

			case self::LINKS_SET_PACKAGES:
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
								$result = $entries;
							}
						}
					}
				}

				break;

			case self::LINKS_SET_PLUGINS:
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
							$entries = $pluginInstance->{self::GETTER_METHOD_NAME}();
							if (is_array($entries) && $entries)
							{
								$result = $entries;
							}
						}
					}
				}
		}

		return $result;
	}

	protected function _validate($url)
	{
		if (empty($url))
		{
			return '';
		}

		$url = (false === stripos($url, 'http://') && false === stripos($url, 'https://'))
			? IA_URL . $url
			: $url;

		$result = str_replace(':url', $url, $this->_xmlEntry);

		if ($this->_multilingual)
		{
			$currentLanguage = $this->iaView->language;

			foreach ($this->iaCore->languages as $code => $title)
			{
				if ($code != $currentLanguage)
				{
					// potentially buggy. replaces all (!) of entries in URL
					$result.= str_replace(IA_URL_DELIMITER . $currentLanguage . IA_URL_DELIMITER, IA_URL_DELIMITER . $code . IA_URL_DELIMITER, $url);
				}
			}
		}

		return $result;
	}
}