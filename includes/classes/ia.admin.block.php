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
 * @package Subrion\Admin
 * @link https://subrion.org/
 * @author https://intelliants.com/ <support@subrion.org>
 * @license https://subrion.org/license.html
 *
 ******************************************************************************/

class iaBlock extends abstractCore
{
    const TYPE_MENU = 'menu';
    const TYPE_PHP = 'php';
    const TYPE_PLAIN = 'plain';
    const TYPE_HTML = 'html';
    const TYPE_SMARTY = 'smarty';

    const DEFAULT_MENU_TEMPLATE = 'render-menu.tpl';

    const LANG_PATTERN_TITLE = 'block_title_';
    const LANG_PATTERN_CONTENT = 'block_content_';

    protected static $_table = 'blocks';
    protected static $_pagesTable = 'objects_pages';
    protected static $_menusTable = 'menus';
    protected static $_positionsTable = 'positions';

    protected $_types = [self::TYPE_PLAIN, self::TYPE_MENU, self::TYPE_HTML, self::TYPE_SMARTY, self::TYPE_PHP];

    protected $_positions;


    public static function getPagesTable()
    {
        return self::$_pagesTable;
    }

    public static function getMenusTable()
    {
        return self::$_menusTable;
    }

    public function getTypes()
    {
        return $this->_types;
    }

    public function getPositions()
    {
        if (is_null($this->_positions)) {
            $this->_positions = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, self::$_positionsTable);
        }

        return $this->_positions;
    }

    protected function _preparePages($pagesList)
    {
        if (is_string($pagesList)) {
            $pagesList = explode(',', $pagesList);
            array_map('trim', $pagesList);
        }

        return $pagesList;
    }

    /**
     * Insert block
     * @param array $blockData
     *
     * @return bool|int
     */
    public function insert(array $blockData)
    {
        if (empty($blockData['name'])) {
            $blockData['name'] = 'block_' . mt_rand(1000, 9999);
        }

        if (!isset($blockData['type']) || !in_array($blockData['type'], $this->getTypes())) {
            $blockData['type'] = self::TYPE_PLAIN;
        }

        if (self::TYPE_MENU == $blockData['type']) {
            $blockData['tpl'] = self::DEFAULT_MENU_TEMPLATE;
        }

        empty($blockData['filename']) || $blockData['external'] = true;
        isset($blockData['header']) || $blockData['header'] = true;

        if (empty($blockData['order'])) {
            $blockData['order'] = (int)$this->iaDb->getMaxOrder(self::getTable()) + 1;
        }

        $bundle = $this->_fetchBundledData($blockData);

        if ($id = $this->iaDb->insert($blockData, null, self::getTable())) {
            $this->_saveBundle($id, $blockData, $bundle);
        }

        return $id;
    }

    public function update(array $itemData, $id)
    {
        $bundle = $this->_fetchBundledData($itemData);

        if (isset($itemData['name'])) {
            unset($itemData['name']);
        }

        $this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());
        $result = (0 == $this->iaDb->getErrorNumber());
        if ($result) {
            $this->_saveBundle($id, $itemData, $bundle);

            $title = $this->iaDb->one_bind('value', '`code` = :lang AND `key` = :key',
                ['lang' => $this->iaView->language, 'key' => self::LANG_PATTERN_TITLE . $id], iaLanguage::getTable());
            $type = $this->iaDb->one('`type`', iaDb::convertIds($id), self::getTable());

            $this->iaCore->factory('log')->write(iaLog::ACTION_UPDATE, [
                'item' => (self::TYPE_MENU == $type) ? 'menu' : 'block',
                'name' => $title,
                'id' => $id
            ]);
        }

        return $result;
    }

    protected function _fetchBundledData(array &$block)
    {
        $result = [];

        if (isset($block['pages'])) {
            $result['pages'] = $this->_preparePages($block['pages']);
            unset($block['pages']);
        }

        if (isset($block['title'])) {
            $result['title'] = $block['title'];
            unset($block['title']);
        }

        if (isset($block['content'])) {
            if (($block['type'] == self::TYPE_PHP || $block['type'] == self::TYPE_SMARTY)) {
                $block['contents'] = $block['content'];
            } else {
                $result['content'] = $block['content'];
            }

            unset($block['content']);
        }

        return $result;
    }

    protected function _saveBundle($id, array $block, array $bundle)
    {
        if (isset($bundle['title']) || isset($bundle['content'])) {
            iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

            if (isset($bundle['title'])) {
                foreach ($this->iaCore->languages as $iso => $language) {
                    $title = is_array($bundle['title'])
                        ? $bundle['title'][$iso]
                        : $bundle['title'];

                    utf8_is_valid($title) || $title = utf8_bad_replace($title);

                    iaLanguage::addPhrase(self::LANG_PATTERN_TITLE . $id, $title, $iso, '', iaLanguage::CATEGORY_FRONTEND);
                }
            }

            if (isset($bundle['content'])) {
                foreach ($this->iaCore->languages as $iso => $language) {
                    $content = is_array($bundle['content'])
                        ? $bundle['content'][$iso]
                        : $bundle['content'];

                    if ($block['type'] != self::TYPE_HTML && !utf8_is_valid($content)) {
                        $content = utf8_bad_replace($content);
                    }

                    iaLanguage::addPhrase(self::LANG_PATTERN_CONTENT . $id, $content, $iso, '', iaLanguage::CATEGORY_FRONTEND);
                }
            }
        }

        if (isset($bundle['pages']) && is_array($bundle['pages'])) {
            $this->setVisibility($id, $block['sticky'], $this->_preparePages($bundle['pages']));
        }
    }

    public function delete($id, $log = true)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

        $title = self::LANG_PATTERN_TITLE . $id;
        $title = iaLanguage::exists($title) ? iaLanguage::get($title) : $row['title'];

        $this->iaCore->startHook('beforeBlockDelete', ['block' => &$row]);

        $result = (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());
        if ($result) {
            $this->iaDb->delete('`object_type` = :object AND `object` = :id', self::getPagesTable(),
                ['id' => $id, 'object' => 'blocks']);
            $this->iaDb->delete('`key` = :title OR `key` = :content', iaLanguage::getTable(),
                ['title' => self::LANG_PATTERN_TITLE . $id, 'content' => self::LANG_PATTERN_CONTENT . $id]);

            if ($log) {
                $this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, ['item' => 'block', 'name' => $title, 'id' => $id]);
            }
        }

        $this->iaCore->startHook('afterBlockDelete', ['block' => &$row]);

        return $result;
    }

    public function setVisibility($blockId, $visibility, array $pages = [], $reset = true)
    {
        $this->iaDb->setTable(self::getPagesTable());

        if ($reset) {
            $this->iaDb->delete("`object_type` = 'blocks' && " . iaDb::convertIds($blockId, 'object'));

            // set global visibility for non-sticky blocks
            if (!$visibility) {
                $this->iaDb->insert(['object_type' => 'blocks', 'object' => $blockId, 'page_name' => '', 'access' => 0]);
            }
        }

        if ($pages) {
            $entry = [
                'object_type' => 'blocks',
                'object' => $blockId,
                'access' => $reset ? !$visibility : $visibility
            ];

            foreach ($pages as $pageName) {
                if ($pageName = trim($pageName)) {
                    $entry['page_name'] = $pageName;
                    $this->iaDb->insert($entry);
                }
            }
        }

        $this->iaDb->resetTable();
    }
}
