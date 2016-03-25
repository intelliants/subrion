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

class iaApiResponse
{
	const OK = 200;
	const CREATED = 201;

	const BAD_REQUEST = 400;
	const UNAUTHORIZED = 401;
	const NOT_FOUND = 404;
	const NOT_ALLOWED = 405;
	const CONFLICT = 409;

	const INTERNAL_ERROR = 500;

	protected $_code = self::OK;
	protected $_body;

	protected $_renderer;


	public function setCode($code)
	{
		$this->_code = (int)$code;
	}

	public function setBody($body)
	{
		$this->_body = $body;
	}

	public function setRenderer(iaApiRenderer $renderer)
	{
		$this->_renderer = $renderer;
	}

	public function emit()
	{
		header('HTTP/1.1 ' . $this->_code);
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Methods: *');

		$this->_renderer->setResultCode($this->_code);
		$this->_renderer->setData($this->_body);

		$this->_renderer->sendHeaders();

		echo $this->_renderer->render();
	}
}