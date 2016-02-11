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
	protected $_name = 'blocks';

	protected $_gridColumns = array('title', 'contents', 'position', 'extras', 'type', 'status', 'order', 'multilingual', 'delete' => 'removable');
	protected $_gridFilters = array('status' => 'equal', 'title' => 'like', 'type' => 'equal', 'position' => 'equal');

	protected $_phraseAddSuccess = 'block_created';

	protected $_permissionsEdit = true;

	private $_multilingualContent;


	public function __construct()
	{
		parent::__construct();

		$iaBlock = $this->_iaCore->factory('block', iaCore::ADMIN);
		$this->setHelper($iaBlock);
	}

	protected function _entryAdd(array $entryData)
	{
		return $this->getHelper()->insert($entryData);
	}

	protected function _entryDelete($entryId)
	{
		return $this->getHelper()->delete($entryId);
	}

	protected function _entryUpdate(array $entryData, $entryId)
	{
		if (isset($entryData['type']))
		{
			if (iaBlock::TYPE_MENU == $entryData['type']
				|| iaBlock::TYPE_MENU == $this->_iaDb->one('`type`', iaDb::convertIds($entryId)))
			{
				return false;
			}
		}

		return $this->getHelper()->update($entryData, $entryId);
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (isset($_GET['pos']) && $_GET['pos'])
		{
			$conditions[] = '`position` = :position';
			$values['position'] = $_GET['pos'];
		}

		$conditions[] = "`type` != 'menu'";
	}

	protected function _modifyGridResult(array &$entries)
	{
		$currentLanguage = $this->_iaCore->iaView;

		foreach ($entries as &$entry)
		{
			$entry['contents'] = iaSanitize::tags($entry['contents']);

			if (!$entry['multilingual'])
			{
				if ($titleLanguages = $this->_iaDb->keyvalue(array('code', 'value'), "`key` = 'block_title_blc{$entry['id']}'", iaLanguage::getTable()))
				{
					if ($titleLanguages[$currentLanguage])
					{
						$entry['title'] = $titleLanguages[$currentLanguage];
					}
					else
					{
						unset($titleLanguages[$currentLanguage]);

						foreach ($titleLanguages as $languageTitle)
						{
							if ($languageTitle)
							{
								$entry['title'] = $languageTitle;
								break;
							}
						}
					}
				}
			}
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry = array(
			'status' => iaCore::STATUS_ACTIVE,
			'type' => iaBlock::TYPE_HTML,
			'collapsed' => false,
			'pages' => array(),
			'title' => '',
			'contents' => ''
		);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$this->_iaCore->startHook('adminAddBlockValidation');

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		// validate block name
		if (iaCore::ACTION_ADD == $action)
		{
			if (empty($data['name']))
			{
				$entry['name'] = 'block_' . mt_rand(1000, 9999);
			}
			else
			{
				$entry['name'] = strtolower(iaSanitize::paranoid($data['name']));
				if (!iaValidate::isAlphaNumericValid($entry['name']))
				{
					$this->addMessage('error_block_name');
				}
				elseif ($this->_iaDb->exists('`name` = :name', array('name' => $entry['name'])))
				{
					$this->addMessage('error_block_name_duplicate');
				}
			}
		}

		$entry['classname'] = $data['classname'];
		$entry['position'] = $data['position'];
		$entry['type'] = $data['type'];
		$entry['status'] = isset($data['status']) ? (in_array($data['status'], array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)) ? $data['status'] : iaCore::STATUS_ACTIVE) : iaCore::STATUS_ACTIVE;
		$entry['header'] = (int)$data['header'];
		$entry['collapsible'] = (int)$data['collapsible'];
		$entry['collapsed'] = (int)$data['collapsed'];
		$entry['multilingual'] = (int)$data['multilingual'];
		$entry['sticky'] = (int)$data['sticky'];
		$entry['external'] = (int)$data['external'];
		$entry['filename'] = $data['filename'];
		$entry['pages'] = isset($data['pages']) ? $data['pages'] : array();
		$entry['title'] = $data['title'];
		$entry['contents'] = $data['content'];

		if ($entry['multilingual'])
		{
			if (empty($entry['title']))
			{
				$this->addMessage('title_is_empty');
			}
			elseif (!utf8_is_valid($entry['title']))
			{
				$entry['title'] = utf8_bad_replace($entry['title']);
			}

			if (empty($entry['contents']) && !$entry['external'])
			{
				$this->addMessage('error_contents');
			}
			elseif (empty($entry['filename']) && $entry['external'])
			{
				$this->addMessage('error_filename');
			}

			if (iaBlock::TYPE_HTML != $entry['type'])
			{
				if (!utf8_is_valid($entry['contents']))
				{
					$entry['contents'] = utf8_bad_replace($entry['contents']);
				}
			}
		}
		else
		{
			$this->_multilingualContent = $data['content'];

			if (isset($data['languages']) && $data['languages'])
			{
				$entry['languages'] = $data['languages'];
				$entry['titles'] = $data['titles'];
				$entry['contents'] = $data['contents'];

				foreach ($entry['languages'] as $langCode)
				{
					if (isset($entry['titles'][$langCode]))
					{
						if (empty($entry['titles'][$langCode]))
						{
							$this->addMessage(iaLanguage::getf('error_lang_title', array('lang' => $this->_iaCore->languages[$langCode]['title'])), false);
						}
						elseif (!utf8_is_valid($entry['titles'][$langCode]))
						{
							$entry['titles'][$langCode] = utf8_bad_replace($entry['titles'][$langCode]);
						}
					}

					if (isset($entry['contents'][$langCode]))
					{
						if (empty($entry['contents'][$langCode]))
						{
							$this->addMessage(iaLanguage::getf('error_lang_contents', array('lang' => $this->_iaCore->languages[$langCode]['title'])), false);
						}

						if (iaBlock::TYPE_HTML != $entry['type'])
						{
							if (!utf8_is_valid($entry['contents'][$langCode]))
							{
								$entry['contents'][$langCode] = utf8_bad_replace($entry['contents'][$langCode]);
							}
						}
					}
				}
			}
			else
			{
				$this->addMessage('block_languages_empty');
			}
		}

		$this->_iaCore->startHook('phpAdminBlocksEdit', array('block' => &$entry));

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array $entry, array $data, $action)
	{
		if (iaCore::ACTION_ADD == $action)
		{
			$this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, array(
				'item' => 'block',
				'name' => $entry['title'],
				'id' => $this->getEntryId()
			));
		}
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		$groupList = $this->_iaDb->onefield('`group`', '1 = 1 GROUP BY `group`', null, null, 'pages');

		$array = $this->_iaDb->all(array('id', 'name'), null, null, null, 'admin_pages_groups');
		$pagesGroups = array();
		foreach ($array as $row)
		{
			$row['title'] = iaLanguage::get('pages_group_' . $row['name']);
			in_array($row['id'], $groupList) && $pagesGroups[$row['id']] = $row;
		}

		$menuPages = array();

		$entryData['content'] = is_null($this->_multilingualContent) ? $entryData['contents'] : $this->_multilingualContent;

		if (!isset($entryData['titles']) && iaCore::ACTION_EDIT == $iaView->get('action'))
		{
			$this->_iaDb->setTable(iaLanguage::getTable());

			$entryData['titles'] = $this->_iaDb->keyvalue(array('code', 'value'), "`key` = '" . iaBlock::LANG_PATTERN_TITLE . $this->getEntryId() . "'");
			$entryData['contents'] = $this->_iaDb->keyvalue(array('code', 'value'), "`key` = '" . iaBlock::LANG_PATTERN_CONTENT . $this->getEntryId() . "'");

			$entryData['languages'] = empty($entryData['contents']) ? array() : array_keys($entryData['contents']);

			if ($entryData['multilingual'] && empty($entryData['contents']) && iaBlock::TYPE_PHP != $entryData['type'])
			{
				foreach ($this->_iaCore->languages as $code => $language)
				{
					$entryData['titles'][$code] = $entryData['title'];
					$entryData['contents'][$code] = $entryData['content'];
				}
			}

			$this->_iaDb->resetTable();

			$menuPages = $this->_iaDb->onefield('`name`', "FIND_IN_SET('{$entryData['name']}', `menus`)", null, null, 'pages');
		}

		isset($entryData['header']) || $entryData['header'] = true;
		isset($entryData['collapsible']) || $entryData['collapsible'] = true;
		isset($entryData['multilingual']) || $entryData['multilingual'] = true;
		isset($entryData['sticky']) || $entryData['sticky'] = true;
		isset($entryData['external']) || $entryData['external'] = false;
		empty($entryData['subpages']) || $entryData['subpages'] = unserialize($entryData['subpages']);
		isset($entryData['pages']) || $entryData['pages'] = $this->_iaDb->onefield('page_name', "`object_type` = 'blocks' && " . iaDb::convertIds($this->getEntryId(), 'object'), 0, null, iaBlock::getPagesTable());

		$iaView->assign('menuPages', $menuPages);
		$iaView->assign('pagesGroup', $pagesGroups);
		$iaView->assign('pages', $this->_getPagesList($iaView->language));
		$iaView->assign('positions', $this->getHelper()->getPositions());
		$iaView->assign('types', $this->getHelper()->getTypes());
	}

	protected function _gridRead($params)
	{
		return (count($this->_iaCore->requestPath) == 1 && 'positions' == $this->_iaCore->requestPath[0])
			? $this->_getPositions()
			: parent::_gridRead($params);
	}

	private function _getPositions()
	{
		$output = array();
		foreach ($this->getHelper()->getPositions() as $entry)
		{
			$output[] = array('value' => $entry['name'], 'title' => $entry['name']);
		}

		return $output;
	}

	private function _getPagesList($languageCode)
	{
		$iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

		$sql =
			'SELECT DISTINCTROW p.*, IF(l.`value` IS NULL, p.`name`, l.`value`) `title` '
			. 'FROM `:prefix:table_pages` p '
			. 'LEFT JOIN `:prefix:table_phrases` l '
				. "ON (`key` = CONCAT('page_title_', p.`name`) AND l.`code` = ':lang' AND l.`category` = ':category') "
			. "WHERE p.`status` = ':status' AND p.`service` = 0 "
			. 'GROUP BY p.`name` '
			. 'ORDER BY l.`value`';

		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_pages' => $iaPage::getTable(),
			'table_phrases' => iaLanguage::getTable(),
			'status' => iaCore::STATUS_ACTIVE,
			'lang' => $languageCode,
			'category' => iaLanguage::CATEGORY_PAGE
		));

		return $this->_iaDb->getAll($sql);
	}

	// we should prevent editing menus via this controller
	public function getById($id)
	{
		$stmt = '`type` != :type AND `id` = :id';
		$this->_iaDb->bind($stmt, array('type' => iaBlock::TYPE_MENU, 'id' => $id));

		return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $stmt);
	}
}