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
 * @package Subrion\Admin
 * @link http://www.subrion.org/
 * @author https://intelliants.com/ <support@subrion.org>
 * @license http://www.subrion.org/license.html
 *
 ******************************************************************************/

class iaBlock extends abstractPlugin
{
	const TYPE_MENU = 'menu';
	const TYPE_PHP = 'php';
	const TYPE_PLAIN = 'plain';
	const TYPE_HTML = 'html';
	const TYPE_SMARTY = 'smarty';

	const DEFAULT_MENU_TEMPLATE = 'render-menu.tpl';

	const LANG_PATTERN_TITLE = 'block_title_blc';
	const LANG_PATTERN_CONTENT = 'block_content_blc';

	protected static $_table = 'blocks';
	protected static $_pagesTable = 'objects_pages';
	protected static $_menusTable = 'menus';
	protected static $_positionsTable = 'positions';

	protected $_types = array(self::TYPE_PLAIN, self::TYPE_MENU, self::TYPE_HTML, self::TYPE_SMARTY, self::TYPE_PHP);

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
		if (is_null($this->_positions))
		{
			$this->_positions = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, self::$_positionsTable);
		}

		return $this->_positions;
	}

	protected function _preparePages($pagesList)
	{
		if (is_string($pagesList))
		{
			$pagesList = explode(',', $pagesList);
			array_map('trim', $pagesList);
		}

		return $pagesList;
	}

	/**
	 * Insert block
	 * @param array $blockData
	 * @return bool|int
	 */
	public function insert(array $blockData)
	{
		if (empty($blockData['lang'])
			|| ($blockData['lang'] && !array_key_exists($blockData['lang'], $this->iaCore->languages)))
		{
			$blockData['lang'] = $this->iaView->language;
		}

		if (!isset($blockData['type']) || !in_array($blockData['type'], $this->getTypes()))
		{
			$blockData['type'] = self::TYPE_PLAIN;
		}
		if (self::TYPE_MENU == $blockData['type'])
		{
			$blockData['tpl'] = self::DEFAULT_MENU_TEMPLATE;
		}

		if (!empty($blockData['filename']))
		{
			$blockData['external'] = 1;
		}

		$order = $this->iaDb->getMaxOrder(self::getTable());
		$blockData['order'] = $order ? $order + 1 : 1;

		if (isset($blockData['pages']))
		{
			$pages = $this->_preparePages($blockData['pages']);
			unset($blockData['pages']);
		}

		if (isset($blockData['multilingual']))
		{
			if (!$blockData['multilingual'] && isset($blockData['languages']))
			{
				$languages = $blockData['languages'];
				$titles = $blockData['titles'];
				$contents = $blockData['contents'];

				unset($blockData['languages'], $blockData['titles'], $blockData['contents']);
			}
		}

		$id = parent::insert($blockData);

		if ($id)
		{
			if (isset($languages))
			{
				foreach ($languages as $language)
				{
					iaLanguage::addPhrase(self::LANG_PATTERN_TITLE . $id, $titles[$language], $language);
					iaLanguage::addPhrase(self::LANG_PATTERN_CONTENT . $id, $contents[$language], $language);
				}
			}

			if (isset($pages))
			{
				$this->setVisibility($id, $blockData['sticky'], $pages);
			}
		}

		return $id;
	}

	public function update(array $itemData, $id)
	{
		$iaDb = &$this->iaDb;

		$row = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

		if (isset($itemData['pages']))
		{
			$pages = $this->_preparePages($itemData['pages']);
			unset($itemData['pages']);
		}

		if (isset($itemData['multilingual']) && !$itemData['multilingual'])
		{
			if (isset($itemData['languages']))
			{
				$languages = $itemData['languages'];
				$titles = $itemData['titles'];
				$contents = $itemData['contents'];

				unset($itemData['languages'], $itemData['titles'], $itemData['contents']);
			}
		}

		if (isset($itemData['name']))
		{
			unset($itemData['name']);
		}

		$result = parent::update($itemData, $id);

		if (isset($pages))
		{
			$this->setVisibility($id, $itemData['sticky'], $pages);
		}

		if (isset($itemData['multilingual']) && !$itemData['multilingual'])
		{
			if (isset($languages))
			{
				$stmt = array();
				$entries = array();

				foreach ($languages as $langCode)
				{
					$entries[] = array(
						'key' => self::LANG_PATTERN_TITLE . $id,
						'value' => $titles[$langCode],
						'category' => iaLanguage::CATEGORY_COMMON,
						'code' => $langCode
					);

					$entries[] = array(
						'key' => self::LANG_PATTERN_CONTENT . $id,
						'value' => $contents[$langCode],
						'category' => iaLanguage::CATEGORY_COMMON,
						'code' => $langCode
					);

					$stmt[] = self::LANG_PATTERN_TITLE . $id;
					$stmt[] = self::LANG_PATTERN_CONTENT . $id;
				}

				$iaDb->setTable(iaLanguage::getTable());
				$iaDb->delete("`key` IN ('" . implode("','", $stmt) . "')");
				$iaDb->insert($entries);
				$iaDb->resetTable();
			}
		}
		else
		{
			// let the user content be kept

			//$iaDb->delete("`key` IN ('" . self::LANG_PATTERN_TITLE . $id . "', '"
			//	. self::LANG_PATTERN_CONTENT . $id . "')", iaLanguage::getTable());
		}

		if ($result)
		{
			$this->iaCore->factory('log')->write(iaLog::ACTION_UPDATE, array(
				'item' => (self::TYPE_MENU == $row['type']) ? 'menu' : 'block',
				'name' => $row['title'],
				'id' => $id
			));
		}

		return $result;
	}

	public function delete($id, $log = true)
	{
		$iaDb = &$this->iaDb;

		$row = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
		$title = self::LANG_PATTERN_TITLE . $id;
		$title = iaLanguage::exists($title) ? iaLanguage::get($title) : $row['title'];

		$this->iaCore->startHook('beforeBlockDelete', array('block' => &$row));

		$result = parent::delete($id);

		if ($result)
		{
			$iaDb->delete('`object_type` = :object && `object` = :id', self::getPagesTable(), array('id' => $id, 'object' => 'blocks'));
			$iaDb->delete('`key` = :title OR `key` = :content', iaLanguage::getTable(), array(
				'title' => self::LANG_PATTERN_TITLE . $id,
				'content' => self::LANG_PATTERN_CONTENT . $id
			));

			if ($log)
			{
				$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('item' => 'block', 'name' => $title, 'id' => $id));
			}
		}

		$this->iaCore->startHook('afterBlockDelete', array('block' => &$row));

		return $result;
	}

	public function setVisibility($blockId, $visibility, array $pages = array(), $reset = true)
	{
		$this->iaDb->setTable(self::getPagesTable());

		if ($reset)
		{
			$this->iaDb->delete("`object_type` = 'blocks' && " . iaDb::convertIds($blockId, 'object'));

			// set global visibility for non-sticky blocks
			if (!$visibility)
			{
				$this->iaDb->insert(array('object_type' => 'blocks', 'object' => $blockId, 'page_name' => '', 'access' => 0));
			}
		}

		if ($pages)
		{
			$entry = array(
				'object_type' => 'blocks',
				'object' => $blockId,
				'access' => $reset ? !$visibility : $visibility
			);

			foreach ($pages as $pageName)
			{
				if ($pageName = trim($pageName))
				{
					$entry['page_name'] = $pageName;
					$this->iaDb->insert($entry);
				}
			}
		}

		$this->iaDb->resetTable();
	}
}