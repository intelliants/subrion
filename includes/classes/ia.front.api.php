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

$basePath = IA_INCLUDES . 'api/';

require_once $basePath . 'request.php';
require_once $basePath . 'response.php';
require_once $basePath . 'renderer/interface.php';
require_once $basePath . 'renderer/abstract.php';
require_once $basePath . 'renderer/raw.php';
require_once $basePath . 'renderer/json.php';


class iaApi
{
	const ENDPOINT_AUTH = 'auth';


	public function init() {}

	public function process(array $requestPath)
	{
		$request = new iaApiRequest($requestPath);

		$renderer = $this->_loadRenderer($request->getFormat());

		$response = new iaApiResponse();
		$response->setRenderer($renderer);

		try
		{
			if (self::ENDPOINT_AUTH == $request->getEndpoint())
			{
				if (iaApiRequest::METHOD_POST != $request->getMethod())
				{
					throw new Exception('Method not allowed', iaApiResponse::NOT_ALLOWED);
				}

				$this->_auth();
			}
			else
			{
				$this->_checkAccessToken();

				$entity = $this->_loadEntity($request->getEndpoint());
				$entity->setRequest($request);
				$entity->setResponse($response);

				$result = $this->_callMethod($entity, $request);

				$response->setBody($result);
			}
		}
		catch (Exception $e)
		{
			$response->setCode($e->getCode());
			$response->setBody(array(
				'error' => $e->getMessage()
			));
		}

		$response->emit();
	}

	protected function _callMethod(iaApiEntityAbstract $entity, iaApiRequest $request)
	{
		switch ($request->getMethod())
		{
			case iaApiRequest::METHOD_GET:
				$params = $request->getParams();
				if (1 == count($params))
				{
					return $entity->getOne($params[0]);
				}
				else
				{
					return $entity->get();
				}

			case iaApiRequest::METHOD_PUT:
				return $entity->put();

			case iaApiRequest::METHOD_POST:
				return $entity->post();

			case iaApiRequest::METHOD_DELETE:
				return $entity->delete();

			default:
				throw new Exception(null, iaApiResponse::NOT_ALLOWED);
		}
	}

	protected function _loadRenderer($name)
	{
		$className = 'iaApiRenderer' . ucfirst($name);

		return new $className();
	}

	protected function _loadEntity($name)
	{
		require_once IA_INCLUDES . 'api/entity/abstract' . iaSystem::EXECUTABLE_FILE_EXT;

		$fileName = IA_INCLUDES . 'api/entity/' . $name . iaSystem::EXECUTABLE_FILE_EXT;

		if (is_file($fileName))
		{
			include_once $fileName;

			$className = 'iaApiEntity' . ucfirst($name);

			if (class_exists($className))
			{
				return new $className();
			}
		}

		throw new Exception('Invalid resource', iaApiResponse::BAD_REQUEST);
	}

	protected function _auth()
	{

	}

	protected function _checkAccessToken()
	{
		// TODO: implement authorization
		// pseusocode
		if (!isset($_GET['token']))
		{
			throw new Exception('No access token provided', iaApiResponse::UNAUTHORIZED);
		}

		if ($_GET['token'] != 'test')
		{
			throw new Exception('Access token is invalid', iaApiResponse::UNAUTHORIZED);
		}
	}
}