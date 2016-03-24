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
	protected $_request;
	protected $_response;

	protected $_table;

	protected $_iaCore;
	protected $_iaDb;


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

	public function setRequest(iaApiRequest $request)
	{
		$this->_request = $request;
	}

	public function setResponse(iaApiResponse $response)
	{
		$this->_response = $response;
	}

	public function get()
	{
		return $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, $this->getTable());
	}

	public function getOne($id)
	{

	}

	public function delete()
	{

	}

	public function put()
	{

	}

	public function post()
	{

	}
}