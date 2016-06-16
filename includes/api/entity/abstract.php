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

abstract class iaApiEntityAbstract
{
	protected $_table;

	protected $_iaCore;
	protected $_iaDb;


	public function init(){}

	public function __construct()
	{
		$iaCore = iaCore::instance();

		$this->_iaCore = $iaCore;
		$this->_iaDb = &$iaCore->iaDb;
	}

	public function getTable()
	{
		return $this->_table;
	}

	// actions
	public function apiList($start, $limit, $where, $order)
	{
		return $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . ' ' . $order, $start, $limit, $this->getTable());
	}

	public function apiGet($id)
	{
		return $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), $this->getTable());
	}

	public function apiDelete($id)
	{
		$resource = $this->apiGet($id);

		if (!$resource)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		if (!isset($resource['member_id']) || $resource['member_id'] != iaUsers::getIdentity()->id)
		{
			throw new Exception('Resource may be removed by owner only', iaApiResponse::FORBIDDEN);
		}

		return (bool)$this->_iaDb->delete(iaDb::convertIds($id), $this->getTable());
	}

	public function apiUpdate(array $data, $id, array $params)
	{
		$resource = $this->apiGet($id);

		if (!$resource)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		if (!isset($resource['member_id']) || $resource['member_id'] != iaUsers::getIdentity()->id)
		{
			throw new Exception('Resource may be edited by owner only', iaApiResponse::FORBIDDEN);
		}

		$this->_iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

		return (0 == $this->_iaDb->getErrorNumber());
	}

	public function apiInsert(array $data)
	{
		if (!iaUsers::hasIdentity())
		{
			throw new Exception('Guests not allowed to post data', iaApiResponse::UNAUTHORIZED);
		}

		$data['member_id'] = iaUsers::getIdentity()->id;

		return $this->_iaDb->insert($data, null, $this->getTable());
	}

	protected function _apiUpdateField($fieldName, $entryId, $content)
	{
		$iaField = $this->_iaCore->factory('field');

		$fieldParams = $this->_iaDb->row(array('type', 'required', 'image_width', 'image_height', 'thumb_width',
			'thumb_height', 'resize_mode'), iaDb::convertIds($fieldName, 'name'), $iaField::getTable());

		if (!$fieldParams)
		{
			throw new Exception('No field to update', iaApiResponse::NOT_FOUND);
		}

		if ($fieldParams['required'] && !$content)
		{
			throw new Exception('Empty value is not accepted', iaApiResponse::UNPROCESSABLE_ENTITY);
		}

		switch ($fieldParams['type'])
		{
			case iaField::IMAGE:
				$content = $this->_processImageField($content, $fieldParams);
				break;
			case iaField::PICTURES:
				$content = $this->_processImageField($content, $fieldParams);
				break;
			case iaField::STORAGE:
				$content = $this->_processStorageField($content, $fieldParams);
		}

		$this->_iaDb->update(array($fieldName => $content), iaDb::convertIds($entryId), null, $this->getTable());

		return 0 === $this->_iaDb->getErrorNumber();
	}

	protected function _processImageField($content, array $field)
	{
		$tempFile = self::_getTempFileName();
		file_put_contents($tempFile, $content);

		// processing image
		$iaPicture = $this->_iaCore->factory('picture');

		$file = array(
			'type' => $_SERVER['CONTENT_TYPE'],
			'tmp_name' => $tempFile
		);

		$path = iaUtil::getAccountDir();
		$name = self::_generateFileName();

		$imagePath = $iaPicture->processImage($file, $path, $name, $field);

		if (!$imagePath)
		{
			throw new Exception('Error processing image: ' . $iaPicture->getMessage(), iaApiResponse::INTERNAL_ERROR);
		}

		$result = array('path' => $imagePath, 'title' => '');
		$result = serialize($result);

		return $result;
	}

	protected function _processPicturesField($content, array $field)
	{
		return $content; // TODO: implement
	}

	protected function _processStorageField($content, array $field)
	{
		return $content; // TODO: implement
	}

	protected static function _generateFileName()
	{
		if (empty($filename))
		{
			return iaUtil::generateToken();
		}

		$extension = '';
		if (false !== strpos($filename, '.'))
		{
			$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
			$filename = pathinfo($filename, PATHINFO_FILENAME);

			if (false !== strpos($filename, '.'))
			{
				$filename = str_replace(array('.', '~'), '-', $filename);
			}
		}

		$filename = iaSanitize::alias($filename) . '_'. iaUtil::generateToken(5);

		return $filename . '.' . $extension;
	}

	protected static function _getTempFileName()
	{
		return tempnam(sys_get_temp_dir(), 'api');
	}
}