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


	public function setRequest(iaApiRequest $request)
	{
		$this->_request = $request;
	}

	public function getRequest()
	{
		return $this->_request;
	}

	public function setResponse(iaApiResponse $response)
	{
		$this->_response = $response;
	}

	public function getResponse()
	{
		return $this->_response;
	}

	public function listResources($start, $limit)
	{
		return $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, $start, $limit, self::getTable());
	}

	public function getResource($id)
	{
		$row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

		if (!$row)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		return $row;
	}

	public function deleteResource($id)
	{
		$row = $this->getOne($id);

		if (!$this->iaDb->delete(iaDb::convertIds($row['id']), self::getTable()))
		{
			throw new Exception('Could not delete a resource', iaApiResponse::INTERNAL_ERROR);
		}
	}

	public function updateResource(array $data, $id)
	{
		$this->iaDb->update($data, iaDb::convertIds($id), null, self::getTable());

		if (0 != $this->_iaDb->getErrorNumber())
		{
			throw new Exception('Could not update a resource', iaApiResponse::INTERNAL_ERROR);
		}
	}

	public function addResource(array $data)
	{
		$id = $this->iaDb->insert($data, null, self::getTable());

		$this->getResponse()->setCode($id ? iaApiResponse::CREATED : iaApiResponse::INTERNAL_ERROR);

		if ($id)
		{
			$this->getResponse()->setBody(array('id' => $id));
		}
	}
}