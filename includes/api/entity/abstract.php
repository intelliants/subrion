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

	public function apiUpdate(array $data, $id)
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
}