<?php
//##copyright##

class iaBlock extends abstractPlugin
{
	const TYPE_MENU = 'menu';
	const TYPE_PHP = 'php';
	const TYPE_PLAIN = 'plain';
	const TYPE_HTML = 'html';
	const TYPE_SMARTY = 'smarty';

	const DEFAULT_MENU_TEMPLATE = 'render-menu.tpl';

	protected static $_table = 'blocks';
	protected static $_pagesTable = 'blocks_pages';
	protected static $_menusTable = 'menus';

	protected $_types = array(self::TYPE_PLAIN, self::TYPE_MENU, self::TYPE_HTML, self::TYPE_SMARTY, 'php');

	protected $_positions = array();


	public function init()
	{
		parent::init();
		$this->_positions = explode(',', $this->iaCore->get('block_positions'));
	}

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
		return $this->_positions;
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
			$blockData['lang'] = IA_LANGUAGE;
		}

		if (!isset($blockData['type']) || !in_array($blockData['type'], $this->getTypes()))
		{
			$blockData['type'] = self::TYPE_PLAIN;
		}
		if (self::TYPE_MENU == $blockData['type'])
		{
			$blockData['tpl'] = self::DEFAULT_MENU_TEMPLATE;
		}

		$order = $this->iaDb->getMaxOrder(self::getTable());
		$blockData['order'] = ($order) ? $order + 1 : 1;

		if (isset($blockData['visible_on_pages']))
		{
			$visibleOn = $blockData['visible_on_pages'];
			unset($blockData['visible_on_pages']);
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

			if (isset($visibleOn))
			{
				$this->setVisiblePages($id, $visibleOn);
			}
		}

		return $id;
	}

	public function delete($id)
	{
		$iaDb = &$this->iaDb;

		$row = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
		$title = 'block_title_blc' . $id;
		$title = iaLanguage::exists($title)
			? iaLanguage::get($title)
			: $row['title'];

		$this->iaCore->startHook('beforeBlockDelete', array('block' => &$row));

		$result = parent::delete($id);

		if ($result)
		{
			$iaDb->delete('`block_id` = :id', self::getPagesTable(), array('id' => $id));
			$iaDb->delete("`key` = 'block_title_blc{$id}' OR `key` = 'block_content_blc{$id}'", iaLanguage::getTable());

			$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('item' => 'block', 'name' => $title, 'id' => (int)$_POST['id']));
		}

		$this->iaCore->startHook('afterBlockDelete', array('block' => &$row));

		return $result;
	}

	public function update(array $itemData, $id)
	{
		$iaDb = &$this->iaDb;

		$row = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

		if (isset($itemData['visible_on_pages']))
		{
			$pagesList = $itemData['visible_on_pages'];
			unset($itemData['visible_on_pages']);
		}

		if (self::TYPE_MENU == $itemData['type'])
		{
			$sql = "UPDATE `{$iaDb->prefix}pages` SET `menus` = REPLACE(`menus`, '{$row['name']}', '')";
			$iaDb->query($sql);
			if ($_POST['pages'])
			{
				$sql = "UPDATE `{$iaDb->prefix}pages` SET `menus` = CONCAT(`menus`, '{$row['name']}')";
				$sql .= sprintf("WHERE `name` IN('%s')", implode("', '", $_POST['pages']));
				$iaDb->query($sql);
			}
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

		if (isset($pagesList))
		{
			$this->setVisiblePages($id, $pagesList);
		}

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
			$this->iaCore->factory('log')->write(iaLog::ACTION_UPDATE, array('item' => 'block', 'name' => $row['title'], 'id' => $id));
		}

		return $result;
	}



	public function gridRead($params, $columns, array $filterParams = array(), array $persistentConditions = array())
	{
		$result = parent::gridRead($params, $columns, $filterParams, $persistentConditions);

		if ($result['data'])
		{
			foreach ($result['data'] as &$block)
			{
				$block['contents'] = htmlspecialchars($block['contents']);
				if (!$block['multi_language'])
				{
					if ($titleLanguages = $this->iaDb->keyvalue(array('code', 'value'), "`key` = 'block_title_blc{$block['id']}'", iaLanguage::getTable()))
					{
						if ($titleLanguages[IA_LANGUAGE])
						{
							$block['title'] = $titleLanguages[IA_LANGUAGE];
						}
						else
						{
							unset($titleLanguages[IA_LANGUAGE]);

							foreach ($titleLanguages as $languageTitle)
							{
								if ($languageTitle)
								{
									$block['title'] = $languageTitle;
									break;
								}
							}
						}
					}
				}
			}
		}

		return $result;
	}

	public  function setVisiblePages($blockId, array $pagesList)
	{
		$this->iaDb->setTable(self::getPagesTable());

		$this->iaDb->delete(iaDb::convertIds($blockId, 'block_id'));

		$rows = array();
		foreach ($pagesList as $pageName)
		{
			$rows[] = array('block_id' => $blockId, 'page_name' => $pageName);
		}

		$this->iaDb->insert($rows);

		$this->iaDb->resetTable();
	}
}