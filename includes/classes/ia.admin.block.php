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

class iaBlock extends abstractPlugin
{
	const TYPE_MENU = 'menu';
	const TYPE_PHP = 'php';
	const TYPE_PLAIN = 'plain';
	const TYPE_HTML = 'html';
	const TYPE_SMARTY = 'smarty';

	const DEFAULT_MENU_TEMPLATE = 'render-menu.tpl';

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
		if (empty($blockData['lang']) || !array_key_exists($blockData['lang'], $this->iaCore->languages))
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

		if (isset($blockData['filename']) && $blockData['filename'])
		{
			$blockData['external'] = 1;
		}

		$order = $this->iaDb->getMaxOrder(self::getTable());
		$blockData['order'] = ($order) ? $order + 1 : 1;

		if (isset($blockData['pages']))
		{
			$pagesList = $this->_preparePages($blockData['pages']);
			unset($blockData['pages']);
		}

		if (isset($blockData['multi_language']))
		{
			if (!$blockData['multi_language'] && isset($blockData['block_languages']))
			{
				$languages = $blockData['block_languages'];
				$title = $blockData['title'];
				$contents = $blockData['contents'];

				unset($blockData['block_languages'], $blockData['title'], $blockData['contents']);
			}
		}

		$id = parent::insert($blockData);

		if ($id)
		{
			if (isset($languages))
			{
				foreach ($languages as $language)
				{
					iaLanguage::addPhrase('block_title_blc' . $id, $title[$language], $language);
					iaLanguage::addPhrase('block_content_blc' . $id, $contents[$language], $language);
				}
			}

			if (isset($pagesList))
			{
				$this->setVisiblePages($id, $pagesList, $blockData['sticky']);
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
			$pagesList = $this->_preparePages($itemData['pages']);
			unset($itemData['pages']);
		}

		if (isset($itemData['multi_language']) && !$itemData['multi_language'])
		{
			if (isset($itemData['block_languages']))
			{
				$languages = $itemData['block_languages'];
				$title = $itemData['title'];
				$contents = $itemData['contents'];

				unset($itemData['block_languages'], $itemData['title'], $itemData['contents']);
			}
		}

		if (isset($itemData['name']))
		{
			unset($itemData['name']);
		}

		$result = parent::update($itemData, $id);

		$this->setVisiblePages($id, $pagesList, $itemData['sticky']);

		if (isset($itemData['multi_language']) && !$itemData['multi_language'])
		{
			if (isset($languages))
			{
				$languageContent_where = array();
				$languageContent = array();

				foreach ($languages as $block_language)
				{
					$languageContent[] = array(
						'key' => 'block_title_blc' . $id,
						'value' => $title[$block_language],
						'category' => iaLanguage::CATEGORY_COMMON,
						'code' => $block_language
					);

					$languageContent[] = array(
						'key' => 'block_content_blc' . $id,
						'value' => $contents[$block_language],
						'category' => iaLanguage::CATEGORY_COMMON,
						'code' => $block_language
					);

					$languageContent_where[] = 'block_title_blc' . $id;
					$languageContent_where[] = 'block_content_blc' . $id;
				}

				$iaDb->setTable(iaLanguage::getTable());
				$iaDb->delete("`key` IN ('" . implode("','", $languageContent_where) . "')");
				$iaDb->insert($languageContent);
				$iaDb->resetTable();
			}
		}
		else
		{
			$iaDb->delete("`key` IN ('block_title_blc{$id}', 'block_content_blc{$id}')", iaLanguage::getTable());
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

	public function delete($id)
	{
		$iaDb = &$this->iaDb;

		$row = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
		$title = 'block_title_blc' . $id;
		$title = iaLanguage::exists($title) ? iaLanguage::get($title) : $row['title'];

		$this->iaCore->startHook('beforeBlockDelete', array('block' => &$row));

		$result = parent::delete($id);

		if ($result)
		{
			$iaDb->delete('`object_type` = :object && `object` = :id', self::getPagesTable(), array('id' => $id, 'object' => 'blocks'));
			$iaDb->delete("`key` = 'block_title_blc{$id}' OR `key` = 'block_content_blc{$id}'", iaLanguage::getTable());

			$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('item' => 'block', 'name' => $title, 'id' => $id));
		}

		$this->iaCore->startHook('afterBlockDelete', array('block' => &$row));

		return $result;
	}

	public function setVisiblePages($blockId, array $pagesList, $accessLevel = 1)
	{
		$this->iaDb->setTable(self::getPagesTable());

		$this->iaDb->delete("`object_type` = 'blocks' && " . iaDb::convertIds($blockId, 'object'));

		// set global visibility for disabled blocks
		if (!$accessLevel)
		{
			$this->iaDb->insert(array('object_type' => 'blocks', 'object' => $blockId, 'page_name' => '', 'access' => '0'));
		}

		if ($pagesList)
		{
			$rows = array();
			foreach ($pagesList as $pageName)
			{
				$rows[] = array('object_type' => 'blocks', 'object' => $blockId, 'page_name' => $pageName, 'access' => !$accessLevel);
			}
			$this->iaDb->insert($rows);
		}

		$this->iaDb->resetTable();
	}
}