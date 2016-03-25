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
	const VERSION = '1';

	const ENDPOINT_AUTH = 'auth';


	public function init() {}

	public function process(array $requestPath)
	{
		$request = new iaApiRequest($requestPath);

		$response = new iaApiResponse();

		$renderer = $this->_loadRenderer($request->getFormat());
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
		$params = $request->getParams();

		switch ($request->getMethod())
		{
			case iaApiRequest::METHOD_GET:
				if (!$params)
				{
					list($start, $limit) = $this->_paginate($_GET);

					return $entity->listResources($start, $limit);
				}
				elseif (1 == count($params))
				{
					return $entity->getResource($params[0]);
				}
				else
				{
					throw new Exception('Invalid request', iaApiResponse::BAD_REQUEST);
				}

			case iaApiRequest::METHOD_PUT:
				if (1 != count($params))
				{
					throw new Exception('Resource ID must be specified', iaApiResponse::BAD_REQUEST);
				}
				if (!$_POST)
				{
					throw new Exception('Empty data', iaApiResponse::BAD_REQUEST);
				}

				return $entity->updateResource($_POST, $params[0]);

			case iaApiRequest::METHOD_POST:
				if (!$_POST)
				{
					throw new Exception('Empty data', iaApiResponse::BAD_REQUEST);
				}

				return $entity->addResource($_POST);

			case iaApiRequest::METHOD_DELETE:
				if (1 != count($params))
				{
					throw new Exception('Resource ID must be specified', iaApiResponse::BAD_REQUEST);
				}

				return $entity->deleteResource($params[0]);

			default:
				throw new Exception('Invalid request method', iaApiResponse::NOT_ALLOWED);
		}
	}

	protected function _loadRenderer($name)
	{
		require_once IA_INCLUDES . 'api/entity/abstract' . iaSystem::EXECUTABLE_FILE_EXT;

		$className = 'iaApiRenderer' . ucfirst($name);

		return new $className();
	}

	protected function _loadEntity($name)
	{
		$extras = iaCore::instance()->factory('item')->getPackageByItem($name);

		if (!$extras)
		{
			throw new Exception('Invalid resource', iaApiResponse::BAD_REQUEST);
		}

		$entity = (iaCore::CORE == $extras)
			? $this->_loadCoreEntity($name)
			: $this->_loadPackageEntity($extras, $name);

		if (!$entity)
		{
			throw new Exception('Invalid resource', iaApiResponse::BAD_REQUEST);
		}

		return $entity;
	}

	private function _loadCoreEntity($name)
	{
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

		return false;
	}

	private function _loadPackageEntity($packageName, $name)
	{
		require_once IA_CLASSES . iaSystem::CLASSES_PREFIX . 'base.package.front.api' . iaSystem::EXECUTABLE_FILE_EXT;

		return iaCore::instance()->factoryPackage('item', $packageName, iaCore::FRONT, $name);
	}

	protected function _paginate(array $params)
	{
		$start = null;
		$limit = null;

		if (isset($params['count']))
		{
			$limit = (int)$params['count'];
		}

		if (isset($params['cursor']))
		{
			$start = (int)$params['cursor'];
		}

		if (isset($params['page']))
		{
			$page = max(($params['page']), 1);
			$start = ($page - 1) * ($limit ? $limit : 20);
		}

		return array($start, $limit);
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