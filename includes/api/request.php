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

class iaApiRequest
{
	const METHOD_GET = 'GET';
	const METHOD_POST = 'POST';
	const METHOD_PUT = 'PUT';
	const METHOD_DELETE = 'DELETE';

	const FORMAT_RAW = 'raw';
	const FORMAT_JSON = 'json';

	protected $_method;
	protected $_format = self::FORMAT_RAW;
	protected $_endpoint;
	protected $_params = array();


	public function __construct(array $requestPath)
	{
		$this->_method = $this->_fetchMethod();

		$this->_endpoint = array_shift($requestPath);
		$this->_params = $requestPath;

		if (iaView::REQUEST_JSON == iaCore::instance()->iaView->getRequestType())
		{
			$this->_format = self::FORMAT_JSON;
		}
	}

	private function _fetchMethod()
	{
		$method = $_SERVER['REQUEST_METHOD'];

		if (isset($_SERVER['HTTP_X_HTTP_METHOD']) && self::METHOD_POST == $method)
		{
			if (self::METHOD_DELETE == $_SERVER['HTTP_X_HTTP_METHOD'] || self::METHOD_PUT == $_SERVER['HTTP_X_HTTP_METHOD'])
			{
				$method = $_SERVER['HTTP_X_HTTP_METHOD'];
			}
		}

		return $method;
	}

	// getters
	public function getMethod()
	{
		return $this->_method;
	}

	public function getEndpoint()
	{
		return $this->_endpoint;
	}

	public function getFormat()
	{
		return $this->_format;
	}

	public function getParams()
	{
		return $this->_params;
	}
}