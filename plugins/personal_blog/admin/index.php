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

class iaBackendController extends iaAbstractControllerPluginBackend
{
	protected $_name = 'blog';
	protected $_table = 'blog_entries';

	protected $_pluginName = 'personal_blog';

	protected $_gridColumns = array('title', 'alias', 'date_added', 'status');
	protected $_gridFilters = array('status' => 'equal');

	protected $_phraseAddSuccess = 'blog_entry_added';
	protected $_phraseGridEntryDeleted = 'blog_entry_deleted';


	public function __construct()
	{
		parent::__construct();

		$iaBlog = $this->_iaCore->factoryPlugin($this->getPluginName(), iaCore::ADMIN, $this->getName());
		$this->setHelper($iaBlog);
	}

	protected function _indexPage(&$iaView)
	{
		$iaView->grid('_IA_URL_plugins/' . $this->getPluginName() . '/js/admin/index');
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (!empty($_GET['text']))
		{
			$conditions[] = '(`title` LIKE :text OR `body` LIKE :text)';
			$values['text'] = '%' . iaSanitize::sql($_GET['text']) . '%';
		}
	}

	protected function _gridRead($params)
	{
		return (isset($params['get']) && 'alias' == $params['get'])
			? array('url' => IA_URL . 'blog' . IA_URL_DELIMITER . $this->_iaDb->getNextId() . '-' . $this->getHelper()->titleAlias($params['title']))
			: parent::_gridRead($params);
	}

	protected function _setPageTitle(&$iaView)
	{
		if (in_array($iaView->get('action'), array(iaCore::ACTION_ADD, iaCore::ACTION_EDIT)))
		{
			$iaView->title(iaLanguage::get($iaView->get('action') . '_blog_entry'));
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry['title'] = $entry['body'] = '';
		$entry['lang'] = $this->_iaCore->iaView->language;
		$entry['date_added'] = date(iaDb::DATETIME_FORMAT);
		$entry['status'] = iaCore::STATUS_ACTIVE;
	}

	protected function _entryDelete($entryId)
	{
		return (bool)$this->getHelper()->delete($entryId);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		parent::_preSaveEntry($entry, $data, $action);

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		if (!utf8_is_valid($entry['title']))
		{
			$entry['title'] = utf8_bad_replace($entry['title']);
		}
		if (empty($entry['title']))
		{
			$this->addMessage('title_is_empty');
		}

		if (!utf8_is_valid($entry['body']))
		{
			$entry['body'] = utf8_bad_replace($entry['body']);
		}
		if (empty($entry['body']))
		{
			$this->addMessage('body_is_empty');
		}

		if (empty($entry['date_added']))
		{
			$entry['date_added'] = date(iaDb::DATETIME_FORMAT);
		}

		$entry['alias'] = $this->getHelper()->titleAlias(empty($entry['alias']) ? $entry['title'] : $entry['alias']);

		if ($this->getMessages())
		{
			return false;
		}

		if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'])
		{
			$iaPicture = $this->_iaCore->factory('picture');

			$path = iaUtil::getAccountDir();
			$file = $_FILES['image'];
			$token = iaUtil::generateToken();
			$info = array(
				'image_width' => 1000,
				'image_height' => 750,
				'thumb_width' => 250,
				'thumb_height' => 250,
				'resize_mode' => iaPicture::CROP
			);

			if ($image = $iaPicture->processImage($file, $path, $token, $info))
			{
				if ($entry['image']) // it has an already assigned image
				{
					$iaPicture = $this->_iaCore->factory('picture');
					$iaPicture->delete($entry['image']);
				}

				$entry['image'] = $image;
			}
		}

		return true;
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		$iaLog = $this->_iaCore->factory('log');

		$actionCode = (iaCore::ACTION_ADD == $action)
			? iaLog::ACTION_CREATE
			: iaLog::ACTION_UPDATE;
		$params = array(
			'module' => 'blog',
			'item' => 'blog',
			'name' => $entry['title'],
			'id' => $this->getEntryId()
		);

		$iaLog->write($actionCode, $params);
	}
}