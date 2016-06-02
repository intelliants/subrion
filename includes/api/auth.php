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

	protected function _coreAuth($params)
	{
		if (empty($params['login']) || empty($params['password']))
		{
			throw new Exception('Empty credentials', iaApiResponse::BAD_REQUEST);
		}

		$remember = (isset($params['remember']) && 1 == $params['remember']);

		$iaUsers = $this->iaCore->factory('users');

		if (!$iaUsers->getAuth(null, $params['login'], $params['password'], $remember))
		{
			throw new Exception('Invalid credentials', iaApiResponse::FORBIDDEN);
		}
	}

	protected function _hybridAuth($providerName)
	{
		$iaUsers = $this->iaCore->factory('users');

		$iaUsers->hybridAuth($providerName);
	}

	public function authorize(iaApiRequest $request, iaApiResponse $response)
	{
		if (!$this->_checkRateLimiting())
		{
			throw new Exception(null, iaApiResponse::TOO_MANY_REQUESTS);
		}

		$params = $request->getParams();

		if (empty($params))
		{
			$this->_coreAuth($request->getContent());
		}
		elseif ('logout' == $params[0])
		{
			iaUsers::clearIdentity();
		}
		elseif (1 == count($params))
		{
			$this->_hybridAuth($params[0]);
		}
		else
		{
			throw new Exception(null, iaApiResponse::NOT_FOUND);
		}
	}

	public function handleTokenRequest(iaApiRequest $request, iaApiResponse $response)
	{
		session_regenerate_id(true);

		$entry = array(
			'key' =>  $this->_generateToken($request),
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
		return (bool)$request->getServer('HTTP_X_AUTH_TOKEN');
	}

	public function getAccessTokenData(iaApiRequest $request)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION,
			iaDb::convertIds($request->getServer('HTTP_X_AUTH_TOKEN'), 'key'),
			self::getTable());
	}

	public function setSession(array $token)
	{
		session_write_close();
		session_id($token['session']);
		session_start();
	}

	private function _checkRateLimiting()
	{
		// TODO: implement

		return true;
	}

	private function _generateToken(iaApiRequest $request)
	{
		$ipAddress = iaUtil::getIp();
		$userAgent = $request->getServer('HTTP_USER_AGENT');

		$token = md5(microtime() . $ipAddress . $userAgent);

		return $token;
	}
}