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

class iaApiAuth extends abstractCore
{
	const QUERY_KEY = 'token';

	protected static $_table = 'api_tokens';


	public function __construct()
	{
		$this->init();
	}

	public function handleTokenRequest(iaApiRequest $request, iaApiResponse $response)
	{
		session_regenerate_id(true);

		$entry = array(
			'key' =>  $this->_generateToken(),
			'ip' => iaUtil::getIp(),
			'session' => session_id()
		);

		$this->iaDb->insert($entry, null, self::getTable());

		if ($this->iaDb->getErrorNumber() > 0)
		{
			throw new Exception('Unable to issue a token', iaApiResponse::INTERNAL_ERROR);
		}

		$response->setBody($entry['key']);
	}

	public function verifyResourceRequest(iaApiRequest $request)
	{
		return (null !== $request->getQuery(self::QUERY_KEY));
	}

	public function getAccessTokenData(iaApiRequest $request)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION,
			iaDb::convertIds($request->getQuery(self::QUERY_KEY), 'key'),
			self::getTable());
	}

	public function setSession(array $token)
	{
		session_write_close();
		session_id($token['session']);
		session_start();
	}

	private function _generateToken()
	{
		$ipAddress = iaUtil::getIp();
		$userAgent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

		$token = md5(microtime() . $ipAddress . $userAgent);

		return $token;
	}
}