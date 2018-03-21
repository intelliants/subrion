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

class iaSitemap extends abstractCore
{
    const FILENAME = 'sitemap.xml';

    const GETTER_METHOD_NAME = 'getSitemapEntries';
    
    const LINKS_SET_CORE = 1;
    const LINKS_SET_PACKAGES = 2;
    const LINKS_SET_PLUGINS = 3;

    protected $_xmlWrapper = '<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
 xmlns:xhtml="http://www.w3.org/1999/xhtml">:content:</urlset>
 ';

    protected $_xmlEntry = '<url><loc>:url</loc>:langs</url>';
    protected $_xmlLangEntry = '<xhtml:link rel="alternate" hreflang=":lang" href=":url" />';

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

        if (!$fh) {
            return false;
        }

        // write file header
        $marker = ':content:';
        $offset = stripos($this->_xmlWrapper, $marker);
        $content = substr($this->_xmlWrapper, 0, $offset);

        fwrite($fh, $content);

        $sets = [self::LINKS_SET_CORE, self::LINKS_SET_PACKAGES, self::LINKS_SET_PLUGINS]; // priority
        foreach ($sets as $set) {
            $urls = $this->_getUrls($set);

            foreach ($urls as $i => &$url) {
                $this->_validate($url);
            }

            if ($this->_multilingual) {
                $this->_fixLanguageCode($urls);
            }

            $xml = $this->_xmlify($urls);

            fwrite($fh, $xml);
        }

        // write XML footer
        fwrite($fh, substr($this->_xmlWrapper, $offset + strlen($marker)));

        fclose($fh);

        return true;
    }

    /**
     * Returns array of sitemap entries
     *
     * @return array
     */
    protected function _getUrls($setType)
    {
        $iaItem = $this->iaCore->factory('item');

        $result = [];
        
        switch ($setType) {
            case self::LINKS_SET_CORE:
                $modulesList = $this->iaDb->keyvalue(['name', 'type'], iaDb::convertIds(iaCore::STATUS_ACTIVE, 'status'), $iaItem::getModulesTable());
                $homePageName = $this->iaCore->get('home_page');

                $stmt = '`nofollow` = 0 && `service` = 0 && `status` = :status && `passw` = :password ORDER BY `order`';
                $this->iaDb->bind($stmt, ['status' => iaCore::STATUS_ACTIVE, 'password' => '']);

                $pages = $this->iaDb->all(['name', 'alias', 'custom_url', 'module'], $stmt, null, null, 'pages');
                foreach ($pages as $page) {
                    if (empty($page['module']) || isset($modulesList[$page['module']])) {
                        switch (true) {
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
                foreach ($iaItem->getModuleItems() as $itemName => $module) {
                    if (iaCore::CORE != $module) {
                        $itemClassInstance = $this->iaCore->factoryItem($itemName);

                        if (method_exists($itemClassInstance, self::GETTER_METHOD_NAME)) {
                            $entries = call_user_func([$itemClassInstance, self::GETTER_METHOD_NAME]);
                            if (is_array($entries) && $entries) {
                                $result = array_merge($result, $entries);
                            }
                        }
                    }
                }

                break;

            case self::LINKS_SET_PLUGINS:
                $itemsList = [];

                $this->iaCore->startHook('sitemapGeneration', ['items' => &$itemsList]);

                if (is_array($itemsList) && $itemsList) {
                    foreach ($itemsList as $item) {
                        $array = explode(':', $item);
                        $pluginInstance = $this->iaCore->factoryModule($array[0], isset($array[1]) ? $array[1] : null, iaCore::ADMIN);

                        if (method_exists($pluginInstance, self::GETTER_METHOD_NAME)) {
                            $entries = call_user_func([$pluginInstance, self::GETTER_METHOD_NAME]);
                            if (is_array($entries) && $entries) {
                                $result = $entries;
                            }
                        }
                    }
                }
        }

        return $result;
    }

    protected function _validate(&$url)
    {
        $url = trim($url);

        $url = (false === stripos($url, 'http://') && false === stripos($url, 'https://'))
            ? IA_CLEAR_URL . $url
            : $url;

        if (false !== stripos($url, IA_URL)) {
            $url = str_replace(IA_URL, IA_CLEAR_URL, $url);
        }
    }

    protected function _fixLanguageCode(array &$urls)
    {
        $masterLangCode = iaLanguage::getMasterLanguage()->iso;

        foreach ($urls as &$url) {
            $entry = [self::_injectLangCode($url, $masterLangCode)];
            foreach ($this->iaCore->languages as $iso => $language) {
                $entry[$iso] = self::_injectLangCode($url, $iso);
            }

            $url = $entry;
        }
    }

    protected static function _injectLangCode($url, $isoCode)
    {
        return str_replace(IA_CLEAR_URL, IA_CLEAR_URL . $isoCode . IA_URL_DELIMITER, $url);
    }

    protected function _xmlify(array $urls)
    {
        $output = '';

        foreach ($urls as $url) {
            $langs = '';
            if ($this->_multilingual) {
                $locUrls = $url;
                $url = array_shift($locUrls);
                foreach ($locUrls as $iso => $locUrl) {
                    $langs .= str_replace([':url', ':lang'], [$locUrl, $iso], $this->_xmlLangEntry);
                }
            }

            $output .= str_replace([':url', ':langs'], [$url, $langs], $this->_xmlEntry);
        }

        return $output;
    }
}
