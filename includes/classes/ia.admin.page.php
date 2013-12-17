<?php
//##copyright##

class iaPage extends abstractPlugin
{
	protected static $_table = 'pages';

	protected static $_adminTable = 'admin_pages';
	protected static $_adminGroupsTable = 'admin_pages_groups';


	public static function getAdminTable()
	{
		return self::$_adminTable;
	}

	public static function getAdminGroupsTable()
	{
		return self::$_adminGroupsTable;
	}

	public function insert($page)
	{
		$iaDb = &$this->iaDb;

		if (empty($page))
		{
			$this->setMessage(iaLanguage::get('page_parameters_is_empty'));
			return false;
		}

		$order = $iaDb->getMaxOrder(self::getTable()) + 1;
		$page['order'] = $order ? $order : 1;

		$existPage = false;
		if ($iaDb->exists('`name` = :name', array('name' => $page['name'])) && empty($page['alias']))
		{
			$existPage = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $page['name']));
		}

		$title = $page['titles'][$this->iaView->language];
		foreach (array('title', 'content') as $type)
		{
			if (isset($page[$type . 's']) && is_array($page[$type . 's']))
			{
				foreach ($page[$type . 's'] as $lngcode => $lngvalue)
				{
					iaLanguage::addPhrase('page_' . $type . '_' . $page['name'], $lngvalue, $lngcode, '', iaLanguage::CATEGORY_PAGE);
				}
				unset($page[$type . 's']);
			}
		}

		if (isset($page['menus']))
		{
			$page['menus'] = implode(',', $page['menus']);
		}

		if ($existPage)
		{
			if (iaCore::STATUS_DRAFT == $page['status'])
			{
				$iaDb->update($page, iaDb::convertIds($existPage['id']));
				return $existPage['id'];
			}
			else
			{
				if (iaCore::STATUS_DRAFT == $existPage['status'])
				{
					$iaDb->update($page, iaDb::convertIds($existPage['id']));
					return $existPage['id'];
				}
				else
				{
					$this->setMessage(iaLanguage::get('page_exists'));
					return false;
				}
			}
		}

		$id = $iaDb->insert($page, array('last_updated' => iaDb::FUNCTION_NOW));

		if ($id)
		{
			$this->iaCore->factory('log')->write(iaLog::ACTION_CREATE, array('item' => 'page', 'name' => $title, 'id' => $id));
		}

		return $id;
	}

	public function update(array $itemData, $id)
	{
		if (empty($id))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'ID')));

			return false;
		}

		$extras = empty($itemData['extras']) ? '' : $itemData['extras'];

		foreach (array('title', 'content') as $type)
		{
			if (isset($itemData[$type . 's']) && is_array($itemData[$type . 's']))
			{
				foreach ($itemData[$type . 's'] as $lngcode => $lngvalue)
				{
					iaLanguage::addPhrase('page_' . $type . '_' . $itemData['name'], $lngvalue, $lngcode, $extras, iaLanguage::CATEGORY_PAGE);
				}
				isset($title) || $title = $itemData[$type . 's'][$this->iaCore->iaView->language];
				unset($itemData[$type . 's']);
			}
		}

		if (isset($itemData['menus']))
		{
			$itemData['menus'] = implode(',', $itemData['menus']);
		}

		$stmt = iaDb::convertIds($id);
		$result = (bool)$this->iaDb->update($itemData, $stmt, array('last_updated' => iaDb::FUNCTION_NOW), self::getTable());

		if ($result && isset($title))
		{
			$this->iaCore->factory('log')->write(iaLog::ACTION_UPDATE, array('item' => 'page', 'name' => $title, 'id' => $id));
		}

		return $result;
	}

	public function delete($id)
	{
		if (empty($id))
		{
			$this->setMessage(iaLanguage::getf('key_parameter_is_empty', array('key' => 'ID')));

			return false;
		}

		$result = false;

		$iaDb = &$this->iaDb;

		$iaDb->setTable(self::getTable());

		if ($row = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`id` = :id', array('id' => $id)))
		{
			$result = (bool)$iaDb->delete(iaDb::convertIds($id));

			if ($result)
			{
				$pageName = $row['name'];

				$iaDb->delete("`key` IN ('page_title_{$pageName}', 'page_content_{$pageName}')", iaLanguage::getTable());

				$this->iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('item' => 'page', 'name' => iaLanguage::get('page_title_' . $pageName), 'id' => (int)$id));
			}
		}

		$iaDb->resetTable();

		return $result;
	}

	public function getNonServicePages(array $exclude)
	{
		$sql =
			"SELECT DISTINCTROW p.*, IF(t.`value` IS NULL, p.`name`, t.`value`) `title`
			FROM `" . self::getTable(true) . "` p
				LEFT JOIN `" . $this->iaDb->prefix . iaLanguage::getTable() . "` t
					ON `key` = CONCAT('page_title_', p.`name`) AND t.`code` = '" . $this->iaView->language . "'
			WHERE p.`status` = 'active'
				AND p.`service` = 0 " . ($exclude ? "AND !FIND_IN_SET(p.`name`, '" . implode(',', $exclude) . "') " : ' ') .
			'ORDER BY t.`value`';

		return $this->iaDb->getAll($sql);
	}

	public function getGroups()
	{
		$stmt = '`status` = :status AND `service` = 0';
		$this->iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE));

		$pages = array();
		$result = array();

		$rows = $this->iaDb->all(array('id', 'name', 'group'), $stmt, null, null, self::getTable());
		foreach ($rows as $page)
		{
			$page['group'] || $page['group'] = 1;
			$pages[$page['group']][$page['id']] = iaLanguage::get('page_title_' . $page['name']);
		}

		$rows = $this->iaDb->all(array('title', 'id', 'name'), null, null, null, self::getAdminGroupsTable());
		foreach ($rows as $row)
		{
			if (isset($pages[$row['id']]))
			{
				$result[$row['id']] = array(
					'title' => $row['title'],
					'children' => $pages[$row['id']]
				);
			}
		}

		return $result;
	}

	public function getUrlByName($pageName)
	{
		static $pagesToUrlMap;

		if (is_null($pagesToUrlMap))
		{
			$iaDb = &iaCore::instance()->iaDb;

			$pagesToUrlMap = $iaDb->keyvalue(array('name', 'alias'), null, self::getAdminTable());
		}

		if (isset($pagesToUrlMap[$pageName]))
		{
			return $pagesToUrlMap[$pageName]
				? $pagesToUrlMap[$pageName]
				: $pageName;
		}

		return null;
	}

	public function getByName($pageName, $lookupThroughBackend = true)
	{
		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $pageName), $lookupThroughBackend ? self::getAdminTable() : self::getTable());
	}

	public function getById($pageId)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($pageId), self::getTable());
	}


	public function gridRead($params, $columns, array $filterParams = array(), array $persistentConditions = array())
	{
		if (isset($params['extras']) && iaCore::CORE == strtolower($params['extras']))
		{
			$params['extras'] = '';
		}

		$result = parent::gridRead($params, $columns, $filterParams, $persistentConditions);

		if ($result['data'])
		{
			$this->iaDb->setTable(iaLanguage::getTable());
			$pageTitles = $this->iaDb->keyvalue(array('key', 'value'), "`key` LIKE('page_title_%') AND `category` = 'page' AND `code` = '" . $this->iaView->language . "'");
			$pageContents = $this->iaDb->keyvalue(array('key', 'value'), "`key` LIKE('page_content_%') AND `category` = 'page' AND `code` = '" . $this->iaView->language . "'");
			$this->iaDb->resetTable();

			$defaultPage = $this->iaCore->get('home_page');

			foreach ($result['data'] as &$page)
			{
				$page['title'] = isset($pageTitles["page_title_{$page['name']}"]) ? $pageTitles["page_title_{$page['name']}"] : 'No title';
				$page['content'] = isset($pageContents["page_content_{$page['name']}"]) ? $pageContents["page_content_{$page['name']}"] : 'No content';

				if ($defaultPage == $page['name'])
				{
					$page['default'] = true;
				}
			}
		}

		return $result;
	}
}