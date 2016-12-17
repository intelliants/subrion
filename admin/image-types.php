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
	protected $_name = 'image-types';

	protected $_table = 'image_types';

	protected $_gridColumns = "`id`, `name`, `width`, `height`, `resize_mode`, `cropper`, 1 `update`, 1 `delete`";
	protected $_gridFilters = array('name' => self::LIKE);


	protected function _modifyGridResult(array &$entries)
	{
		foreach ($entries as $key => &$entry)
		{
			// unset($entries[$key]['filetypes']);
		}
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry['title'] = '';
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		$iaView->set('toolbarActionsReplacements', array('id' => $this->getEntryId()));

		list($imageTypes, $entryData['types']) = $this->_getFileTypes($this->getEntryId());
		$iaView->assign('imageTypes', $imageTypes);

		if (isset($_POST['imageTypes']) && $_POST['imageTypes'])
		{
			$entryData['types'] = $_POST['imageTypes'];
		}
	}

	private function _getFileTypes($id = 0)
	{
		$fileTypes = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, '`image` = 1', 0, null, 'file_types');

		// get assigned image types for field
		$assignedTypes = $id ? $this->_iaDb->onefield('`image_type_id`', "`field_id` = {$id}", 0, null, 'fields_image_types') : array();

		return array($fileTypes, $assignedTypes);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry = array(
			'name' => iaSanitize::alias(iaUtil::checkPostParam('name')),
		);

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['name'] = trim(strtolower(iaSanitize::paranoid($entry['name'])));

			if (empty($entry['name']))
			{
				$this->addMessage('field_name_invalid');
			}
		}
		else
		{
			unset($entry['name']);
		}

		if (empty($data['imageTypes']))
		{
			$this->addMessage('field_name_invalid');
		}
		else
		{
			$entry['filetypes'] = implode(',', $data['imageTypes']);
		}

		$entry['width'] = (int)$data['width'];
		$entry['height'] = (int)$data['height'];
		$entry['resize_mode'] = $data['pic_resize_mode'];
		$entry['cropper'] = (int)$data['cropper'];

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		if (iaCore::ACTION_ADD == $action)
		{
			$this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, array(
				'item' => 'image-type',
				'name' => $entry['title'],
				'id' => $this->getEntryId()
			));
		}
	}
}