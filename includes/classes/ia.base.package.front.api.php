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

abstract class abstractPackageFrontApiResponder extends abstractPackageFront
{
	protected $_request;
	protected $_response;

	public $apiFilters = array();
	public $apiSorters = array();


	public function setRequest(iaApiRequest $request)
	{
		$this->_request = $request;
	}

	public function setResponse(iaApiResponse $response)
	{
		$this->_response = $response;
	}

	public function apiList($start, $limit, $where, $order)
	{
		$rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where . $order, $start, $limit, self::getTable());

		return $this->_unpackImageFields($rows);
	}

	public function apiGet($id)
	{
		$row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

		if ($row)
		{
			$row = $this->_unpackImageFields(array($row));
			$row = array_shift($row);
		}

		return $row;
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

		return (bool)$this->iaDb->delete(iaDb::convertIds($id), self::getTable());
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

		$this->iaDb->update($data, iaDb::convertIds($id), null, self::getTable());

		return (0 == $this->iaDb->getErrorNumber());
	}

	public function apiInsert(array $data)
	{
		if (!iaUsers::hasIdentity())
		{
			throw new Exception('Guests not allowed to post data', iaApiResponse::UNAUTHORIZED);
		}

		$data['member_id'] = iaUsers::getIdentity()->id;

		return $this->iaDb->insert($data, null, self::getTable());
	}

	// utility
	protected function _unpackImageFields($rows)
	{
		if (!$rows || !is_array($rows))
		{
			return array();
		}

		$fields = $this->iaCore->factory('field')->getImageFields($this->getItemName());

		if (!$fields)
		{
			return $rows;
		}

		foreach ($rows as &$row)
		{
			foreach ($fields as $fieldName)
			{
				if (empty($row[$fieldName])) continue;

				$array = unserialize($row[$fieldName]);
				if ($array && is_array($array))
				{
					if (isset($array['path'])) // single image field
					{
						$array['path'] = self::_pathToUploads($array['path']);
					}
					else // multiple image upload
					{
						foreach ($array as &$entry)
						{
							$entry['path'] = self::_pathToUploads($entry['path']);
						}
					}
				}

				$row[$fieldName] = $array;
			}
		}

		return $rows;
	}

	protected static function _pathToUploads($filePath)
	{
		return IA_CLEAR_URL . 'uploads/' . $filePath;
	}
}