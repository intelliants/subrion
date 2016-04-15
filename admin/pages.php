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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'pages';

	protected $_tooltipsEnabled = true;

	protected $_gridColumns = "`id`, `name`, `status`, `last_updated`, IF(`custom_url` != '', `custom_url`, IF(`alias` != '', `alias`, CONCAT(`name`, '/'))) `url`, `id` `update`, IF(`readonly` = 0, 1, 0) `delete`";
	protected $_gridFilters = array('name' => self::LIKE, 'extras' => self::EQUAL);

	protected $_phraseAddSuccess = 'page_added';


	public function __construct()
	{
		parent::__construct();

		$iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);
		$this->setHelper($iaPage);

		$this->setTable(iaPage::getTable());
	}

	protected function _indexPage(&$iaView)
	{
		if (isset($_POST['preview']))
		{
			$this->_previewPage($iaView->get('action'));
		}

		parent::_indexPage($iaView);
	}

	protected function _gridRead($params)
	{
		$action = (1 == count($this->_iaCore->requestPath)) ? $this->_iaCore->requestPath[0] : null;

		switch ($action)
		{
			case 'url': return $this->_getJsonUrl($params);
			case 'plugins': return $this->_getJsonPlugins();
			default: return parent::_gridRead($params);
		}
	}

	protected function _modifyGridResult(array &$entries)
	{
		$currentLanguage = $this->_iaCore->iaView->language;

		$this->_iaDb->setTable(iaLanguage::getTable());
		$pageTitles = $this->_iaDb->keyvalue(array('key', 'value'), "`key` LIKE('page_title_%') AND `category` = 'page' AND `code` = '$currentLanguage'");
		$pageContents = $this->_iaDb->keyvalue(array('key', 'value'), "`key` LIKE('page_content_%') AND `category` = 'page' AND `code` = '$currentLanguage'");
		$this->_iaDb->resetTable();

		$defaultPage = $this->_iaCore->get('home_page');

		foreach ($entries as &$entry)
		{
			$entry['title'] = isset($pageTitles["page_title_{$entry['name']}"]) ? $pageTitles["page_title_{$entry['name']}"] : 'No title';
			$entry['content'] = isset($pageContents["page_content_{$entry['name']}"]) ? $pageContents["page_content_{$entry['name']}"] : 'No content';

			if ($defaultPage == $entry['name'])
			{
				$entry['default'] = true;
			}
		}
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$this->_iaCore->startHook('phpAdminAddPageValidation', array('entry' => &$entry));

		iaUtil::loadUTF8Functions('ascii', 'bad', 'utf8_to_ascii', 'validation');

		$entry['name'] = preg_replace('#[^a-z0-9-_]#iu', '', strtolower($data['name'] =! utf8_is_ascii($data['name']) ? utf8_to_ascii($data['name']): $data['name']));
		$entry['meta_description'] = utf8_is_valid($data['meta_description']) ? $data['meta_description'] : utf8_bad_replace($data['meta_description']);
		$entry['meta_keywords'] = utf8_is_valid($data['meta_keywords']) ? $data['meta_keywords'] : utf8_bad_replace($data['meta_keywords']);
		$entry['status'] = isset($data['preview']) ? iaCore::STATUS_DRAFT : $data['status'];

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['group'] = 2;
			$entry['filename'] = 'page';
		}

		foreach ($data['titles'] as $key => $title)
		{
			if (empty($title))
			{
				$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('title') . ' (' . $key . ')')), false);
				break;
			}
		}

		if (!isset($data['service']) || !$data['service'])
		{
			$entry['alias'] = empty($data['alias']) ? $data['name'] : $data['alias'];
			$entry['custom_url'] = empty($data['custom_url']) ? '' : $data['custom_url'];
			$entry['passw'] = empty($data['passw']) ? '' : $data['passw'];

			$entry['alias'] = utf8_is_ascii($entry['alias']) ? $entry['alias'] : utf8_to_ascii($entry['alias']);
			$entry['alias'] = empty($entry['alias']) ? '' : iaSanitize::alias($entry['alias']);
			$entry['alias'].= $data['extension'];

			if ($data['parent_id'])
			{
				$parentPage = $this->getById($data['parent_id']);
				$parentAlias = empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias'];

				$entry['parent'] = $parentPage['name'];
				$entry['alias'] = $parentAlias . (IA_URL_DELIMITER == substr($parentAlias, -1, 1) ? '' : IA_URL_DELIMITER) . $entry['alias'];
			}
			else
			{
				$entry['parent'] = '';
			}

			if ($this->_iaDb->exists('`id` != :id AND `alias` = :alias', array('id' => $this->getEntryId(), 'alias' => $entry['alias'])))
			{
				$this->addMessage('page_alias_exists');
			}

			if (isset($data['nofollow']))
			{
				$entry['nofollow'] = (int)$data['nofollow'];
			}

			if (isset($data['new_window']))
			{
				$entry['new_window'] = (int)$data['new_window'];
			}

			// delete custom url
			if (isset($data['unique']) && 0 == $data['unique'])
			{
				$entry['custom_url'] = '';
			}

			if (isset($data['custom_tpl']) && $data['custom_tpl'])
			{
				$entry['custom_tpl'] = (int)$data['custom_tpl'];
				$entry['template_filename'] = $data['template_filename'];

				if (!$data['template_filename'])
				{
					$this->addMessage('page_incorrect_template_filename');
				}
			}
			else
			{
				$entry['custom_tpl'] = 0;
				$entry['template_filename'] = '';
			}
		}

		if (empty($entry['name']))
		{
			$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('name'))), false);
		}
		elseif (iaCore::ACTION_ADD == $action
			&& $this->_iaDb->exists('`name` = :name', array('name' => $entry['name'])))
		{
			$this->addMessage('page_name_exists');
		}

		return !$this->getMessages();
	}

	protected function _modifyGridParams(&$conditions, &$values, array $params)
	{
		if (isset($values['extras']) && iaCore::CORE == strtolower($values['extras']))
		{
			$values['extras'] = '';
		}

		$conditions[] = '`service` = 0';
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'name' => '',
			'parent' => '',
			'filename' => 'page',
			'custom_tpl' => 0,
			'template_filename' => '',
			'alias' => '',
			'extras' => '',
			'readonly' => false,
			'service' => false,
			'nofollow' => false,
			'new_window' => false,
			'meta_description' => '',
			'meta_keywords' => '',
			'status' => iaCore::STATUS_ACTIVE
		);
	}

	protected function _entryAdd(array $entryData)
	{
		$order = $this->_iaDb->getMaxOrder() + 1;

		$entryData['last_updated'] = date(iaDb::DATETIME_FORMAT);
		$entryData['order'] = $order ? $order : 1;

		return parent::_entryAdd($entryData);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		$currentData = $this->getById($entryId);

		$entryData['last_updated'] = date(iaDb::DATETIME_FORMAT);

		$result = parent::_entryUpdate($entryData, $entryId);

		if ($result)
		{
			if (!empty($currentData['alias']) && $entryData['alias'] && $currentData['alias'] != $entryData['alias'])
			{
				$this->_massUpdateAlias($currentData['alias'], $entryData['alias'], $this->getEntryId());
			}
		}

		return $result;
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		// saving selected menus
		$selectedMenus = empty($data['menus']) ? array() : $data['menus'];
		$this->_saveMenus($entry['name'], $selectedMenus);

		// setting as the home page if needed
		if (isset($data['home_page']) && $data['home_page'])
		{
			$iaAcl = $this->_iaCore->factory('acl');
			if ($iaAcl->checkAccess($this->getName() . 'home'))
			{
				$this->_iaCore->set('home_page', $entry['name'], true);
			}
		}

		// saving multilingual content
		$currentLanguage = $this->_iaCore->iaView->language;
		$extras = $data['extras'];

		foreach (array('title', 'content') as $type)
		{
			if (isset($data[$type . 's']) && is_array($data[$type . 's']))
			{
				foreach ($data[$type . 's'] as $languageCode => $value)
				{
					utf8_is_valid($value) || $value = utf8_bad_replace($value);
					iaLanguage::addPhrase('page_' . $type . '_' . $entry['name'], $value, $languageCode, $extras, iaLanguage::CATEGORY_PAGE);
				}
				isset($title) || $title = $data[$type . 's'][$currentLanguage];
			}
		}

		// writing to log
		$iaLog = $this->_iaCore->factory('log');
		$actionCode = (iaCore::ACTION_ADD == $action) ? iaLog::ACTION_CREATE : iaLog::ACTION_UPDATE;
		$iaLog->write($actionCode, array('item' => 'page', 'name' => $title, 'id' => $this->getEntryId()));
	}

	protected function _entryDelete($entryId)
	{
		$result = false;

		if ($row = $this->getById($entryId))
		{
			$result = parent::_entryDelete($entryId);

			if ($result)
			{
				$pageName = $row['name'];

				$this->_iaCore->factory('log')->write(iaLog::ACTION_DELETE, array('item' => 'page',
					'name' => $this->_iaCore->factory('page')->getPageTitle($pageName), 'id' => (int)$entryId));

				// remove associated entries as well
				$this->_iaDb->delete("`key` IN ('page_title_{$pageName}', 'page_content_{$pageName}')", iaLanguage::getTable());

				$this->_iaCore->factory('block', iaCore::ADMIN);
				$this->_iaDb->delete('`page_name` = :page', iaBlock::getMenusTable(), array('page' => $pageName));
				//
			}
		}

		return $result;
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		if (isset($_POST['titles']) && isset($_POST['contents']))
		{
			$entryData['titles'] = $_POST['titles'];
			$entryData['contents'] = $_POST['contents'];
		}
		elseif (iaCore::ACTION_EDIT == $iaView->get('action'))
		{
			$this->_iaDb->setTable(iaLanguage::getTable());
			$entryData['titles'] = $this->_iaDb->keyvalue(array('code', 'value'), "`key` = 'page_title_{$entryData['name']}' AND `category` = 'page'");
			$entryData['contents'] = $this->_iaDb->keyvalue(array('code', 'value'), "`key` = 'page_content_{$entryData['name']}' AND `category` = 'page'");
			$this->_iaDb->resetTable();
		}

		$menus = array(
			array('title' => iaLanguage::get('core_menus', 'Core menus'), 'list' => array()),
			array('title' => iaLanguage::get('custom_menus', 'Custom menus'), 'list' => array())
		);

		$iaAcl = $this->_iaCore->factory('acl');
		if ($iaAcl->checkAccess('admin_page:add', 0, 0, 'menus'))
		{
			$this->_iaCore->factory('block', iaCore::ADMIN);

			$menusList = $this->_iaDb->all(array('id', 'title', 'removable'), "`type` = 'menu'", null, null, iaBlock::getTable());
			foreach ($menusList as $menuEntry)
			{
				$menus[$menuEntry['removable']]['list'][] = $menuEntry;
			}

			ksort($menus[0]['list']);
			ksort($menus[1]['list']);

			$selectedMenus = empty($_POST['menus'])
				? $this->_iaDb->onefield('menu_id', iaDb::convertIds($entryData['name'], 'page_name'), null, null, iaBlock::getMenusTable())
				: $_POST['menus'];
			$iaView->assign('selectedMenus', $selectedMenus);
		}

		$parentAlias = '';
		if ($entryData['parent'])
		{
			$parentAlias = $this->getHelper()->getByName($entryData['parent'], false);
			$parentAlias = empty($parentAlias['alias']) ? $parentAlias['name'] . IA_URL_DELIMITER : $parentAlias['alias'];
		}

		$entryData['extension'] = (false === strpos($entryData['alias'], '.')) ? '' : end(explode('.', $entryData['alias']));
		$entryData['alias'] = substr($entryData['alias'], strlen($parentAlias), -1 - strlen($entryData['extension']));

		if ($entryData['name'] == $entryData['alias'])
		{
			$entryData['alias'] = '';
		}

		$parentPage = $this->getHelper()->getByName($entryData['parent'], false);
		$groups = $this->getHelper()->getGroups(array($this->_iaCore->get('home_page'), $entryData['name']));
		$isHomepage = ($this->_iaCore->get('home_page', iaView::DEFAULT_HOMEPAGE) == $entryData['name']);

		$iaView->assign('isHomePage', $isHomepage);
		$iaView->assign('extensions', $this->getHelper()->extendedExtensions);
		$iaView->assign('menus', $menus);
		$iaView->assign('pages', $this->getHelper()->getNonServicePages(array('index')));
		$iaView->assign('pagesGroup', $groups);
		$iaView->assign('parentPageId', $parentPage['id']);
	}


	private function _previewPage($action)
	{
		if (iaCore::ACTION_ADD == $action)
		{
			$_POST['save'] = true;
		}
		else
		{
			iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

			$newPage = array();
			$name = strtolower($_POST['name'] = !utf8_is_ascii($_POST['name']) ? utf8_to_ascii($_POST['name']) : $_POST['name']);
			if (isset($_POST['contents']) && is_array($_POST['contents']))
			{
				function utf8_validation(&$item)
				{
					$item = !utf8_is_valid($item) ? utf8_bad_replace($item) : $item;
				}

				foreach ($_POST['contents'] as $key => $content)
				{
					utf8_validation($_POST['contents'][$key]);
				}
				$newPage['contents'] = $_POST['contents'];
			}

			$newPage['titles'] = $_POST['titles'];
			$newPage['passw'] = iaSanitize::sql($_POST['passw']);

			isset($_SESSION['preview_pages']) || $_SESSION['preview_pages'] = array();
			$_SESSION['preview_pages'][$name] = $newPage;

			$languagesEnabled = $this->_iaCore->get('language_switch') && count($this->_iaCore->languages);
			$redirectUrl = IA_CLEAR_URL . ($languagesEnabled ? $_POST['language'] . IA_URL_DELIMITER : '') . 'page' . IA_URL_DELIMITER . $name . IA_URL_DELIMITER . '?preview';

			iaUtil::go_to($redirectUrl);
		}
	}

	private function _massUpdateAlias($previous, $new, $entryId)
	{
		$previous = iaSanitize::sql($previous);
		$previous = (IA_URL_DELIMITER == $previous[strlen($previous) - 1]) ? substr($previous, 0, -1) : $previous;

		$new = iaSanitize::sql($new);
		$new = (IA_URL_DELIMITER == $new[strlen($new) - 1]) ? substr($new, 0, -1) : $new;

		$cond = iaDb::printf("`alias` LIKE ':alias%' AND `id` != :id", array('alias' => $previous, 'id' => $entryId));
		$stmt = array('alias' => "REPLACE(`alias`, '$previous', '$new')");

		$this->_iaDb->update(null, $cond, $stmt);
	}

	private function _getJsonPlugins()
	{
		$sql = 'SELECT '
				. "IF(p.`extras` = '', 'core', p.`extras`) `value`, "
				. "IF(p.`extras` = '', 'Core', g.`title`) `title` "
			. 'FROM `:prefix:pages` p '
			. 'LEFT JOIN `:prefixextras` g ON (g.`name` = p.`extras`) '
			. 'GROUP BY p.`extras`';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'pages' => iaPage::getTable()
		));

		return array('data' => $this->_iaDb->getAll($sql));
	}


	private function _saveMenus($entryName, $menus)
	{
		$iaAcl = $this->_iaCore->factory('acl');

		if ($iaAcl->checkAccess('admin_page:add', 'menus'))
		{
			$iaDb = &$this->_iaDb;
			$iaBlock = $this->_iaCore->factory('block', iaCore::ADMIN);

			$iaDb->setTable($iaBlock::getMenusTable());

			$menusList = $iaDb->all(array('id'), iaDb::convertIds('menu', 'type'), null, null, $iaBlock::getTable());
			foreach ($menusList as $item)
			{
				$items = array();
				$add = false;
				if (in_array($item['id'], $menus))
				{
					if (!$iaDb->exists('`menu_id` = :menu AND `page_name` = :page', array('menu' => $item['id'], 'page' => $entryName)))
					{
						$items[] = array(
							'parent_id' => 0,
							'menu_id' => $item['id'],
							'el_id' => $this->getEntryId() . '_' . iaUtil::generateToken(5),
							'level' => 0,
							'page_name' => $entryName
						);
						$add = true;
					}
				}
				else
				{
					$iaDb->delete('`menu_id` = :menu AND `page_name` = :page', null, array('menu' => $item['id'], 'page' => $entryName));
				}

				if ($add)
				{
					$iaDb->insert($items);
				}

				$this->_iaCore->iaCache->remove('menu_' . $item['id']);
			}

			$iaDb->resetTable();
		}
	}

	private function _getJsonUrl(array $params)
	{
		iaUtil::loadUTF8Functions('ascii', 'utf8_to_ascii');

		$name = $params['name'];
		$name = utf8_is_ascii($name) ? $name : utf8_to_ascii($name);
		$name = preg_replace('#[^a-z0-9-_]#iu', '', $name);

		$url = $params['url'];
		$url = utf8_is_ascii($url) ? $url : utf8_to_ascii($url);
		$url = preg_replace('#[^a-z0-9-_]#iu', '', $url);

		$url = $url ? $url : $name;

		if (is_numeric($params['parent']) && $params['parent'])
		{
			$parentPage = $this->getById($params['parent']);
			$parentAlias = empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias'];

			$url = $parentAlias . (IA_URL_DELIMITER == substr($parentAlias, -1, 1) ? '' : IA_URL_DELIMITER) . $url;
		}

		$url.= $params['ext'];

		$exists = $this->_iaDb->exists('`alias` = :url AND `name` != :name', array('url' => $url, 'name' => $name));
		$url = IA_URL . $url;

		return array('url' => $url, 'exists' => $exists);
	}
}