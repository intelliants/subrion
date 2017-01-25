<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'image-types';

	protected $_table = 'image_types';

	protected $_gridColumns = array('name', 'width', 'height', 'resize_mode', 'cropper');
	protected $_gridFilters = array('name' => self::LIKE, 'id' => self::EQUAL);


	protected function _assignValues(&$iaView, array &$entryData)
	{
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
		$assignedTypes = array();
		if ($id)
		{
			$assignedTypes = $this->_iaDb->one('filetypes', iaDb::convertIds($id), $this->getTable());
			$assignedTypes && $assignedTypes = explode(',', $assignedTypes);
		}

		return array($fileTypes, $assignedTypes);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		if (iaCore::ACTION_ADD == $action)
		{
			$entry['name'] = trim(strtolower(iaSanitize::paranoid($data['name'])));

			if (empty($entry['name']))
			{
				$this->addMessage('field_name_invalid');
			}
		}

		if (empty($data['imageTypes']))
		{
			$this->addMessage('error_file_type');
		}
		else
		{
			$entry['filetypes'] = implode(',', $data['imageTypes']);
		}

		$entry['width'] = (int)$data['width'];
		$entry['height'] = (int)$data['height'];
		$entry['resize_mode'] = $data['pic_resize_mode'];
		$entry['cropper'] = (int)$data['cropper'];

		if (!$entry['width'] || !$entry['height'])
		{
			$this->addMessage('error_incorrect_dimensions');
		}

		return !$this->getMessages();
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		if (iaCore::ACTION_ADD == $action)
		{
			$this->_iaCore->factory('log')->write(iaLog::ACTION_CREATE, array(
				'item' => 'image-type',
				'name' => $entry['name'],
				'id' => $this->getEntryId()
			));
		}
	}
}