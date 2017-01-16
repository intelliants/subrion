<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
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

	protected $_authEndpoints = array('token', 'auth');

	protected $_authServer;

	protected $_request;
	protected $_response;

	protected $_mobilePush;


	public function init() {}

	public function process(array $requestPath)
	{
		$this->_request = new iaApiRequest($requestPath);
		$this->_response = new iaApiResponse();

		$renderer = $this->_loadRenderer($this->_getRequest()->getFormat());
		$this->_getResponse()->setRenderer($renderer);

		try
		{
			if (iaApiRequest::METHOD_OPTIONS == $this->_getRequest()->getMethod())
			{
				$this->_getResponse()->setHeader('Allow', 'GET,POST,PUT,DELETE,OPTIONS');
				$this->_getResponse()->setHeader('Access-Control-Allow-Headers', 'X-Auth-Token');
			}
			elseif (in_array($this->_getRequest()->getEndpoint(), $this->_authEndpoints))
			{
				$this->_auth();
			}
			else
			{
				$this->_checkPrivileges();

				$result = $this->_action($this->_loadEntity($this->_getRequest()->getEndpoint()));

				$this->_getResponse()->setBody($result);
			}
		}
		catch (Exception $e)
		{
			$this->_getResponse()->setCode($e->getCode());
			$this->_getResponse()->setBody(array('error' => $e->getMessage()));
		}

		$this->_getResponse()->emit();
	}

	protected function _action(iaApiEntityAbstract $entity)
	{
		$params = $this->_getRequest()->getParams();

		switch ($this->_getRequest()->getMethod())
		{
			case iaApiRequest::METHOD_GET:
				if (!$params)
				{
					list($start, $limit) = $this->_paginate($_GET);
					list($where, $order) = $this->_filter($_GET, $entity);

					return $this->listResources($entity, $start, $limit, $where, $order);
				}
				elseif (1 == count($params))
				{
					return $this->getResource($entity, $params[0]);
				}
				else
				{
					throw new Exception('Invalid request', iaApiResponse::BAD_REQUEST);
				}

			case iaApiRequest::METHOD_PUT:
				if (!$params)
				{
					throw new Exception('Resource ID must be specified', iaApiResponse::BAD_REQUEST);
				}

				$resourceId = array_shift($params);

				if (!$this->_getRequest()->getContent() && !$params)
				{
					throw new Exception('Empty data', iaApiResponse::BAD_REQUEST);
				}

				return $this->updateResource($entity, $resourceId, $params);

			case iaApiRequest::METHOD_POST:
				if (!$this->_getRequest()->getContent())
				{
					throw new Exception('Empty data', iaApiResponse::BAD_REQUEST);
				}

				return $this->addResource($entity);

			case iaApiRequest::METHOD_DELETE:
				if (1 != count($params))
				{
					throw new Exception('Resource ID must be specified', iaApiResponse::BAD_REQUEST);
				}

				return $this->deleteResource($entity, $params[0]);

			default:
				throw new Exception('Invalid request method', iaApiResponse::NOT_ALLOWED);
		}
	}

	protected function _getRequest()
	{
		return $this->_request;
	}

	protected function _getResponse()
	{
		return $this->_response;
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

		$entity->setRequest($this->_getRequest());
		$entity->setResponse($this->_getResponse());

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
			$page = max($params['page'], 1);
			$start = ($page - 1) * ($limit ? $limit : 20);
		}

		return array($start, $limit);
	}

	protected function _filter(array $params, $entity)
	{
		// where
		$where = iaDb::EMPTY_CONDITION;

		foreach ($entity->apiFilters as $filterName)
		{
			if (isset($params[$filterName][0]) && is_string($params[$filterName]))
			{
				$where.= sprintf(" AND `%s` = '%s'", $filterName, iaSanitize::sql($params[$filterName]));
			}
		}

		// order
		$sorting = 'id';
		$order = 'desc';

		if (isset($params['sorting']) && in_array($params['sorting'], $entity->apiSorters))
		{
			$sorting = iaSanitize::paranoid($params['sorting']);
		}

		if (isset($params['order']) && in_array($params['order'], array('asc', 'desc')))
		{
			$order = strtoupper($params['order']);
		}

		$order = sprintf(' ORDER BY `%s` %s', $sorting, $order);

		return array($where, $order);
	}

	// oauth2
	protected function _getAuthServer()
	{
		if (is_null($this->_authServer))
		{
/*			require IA_INCLUDES . 'OAuth2/Autoloader.php';
			require IA_INCLUDES . 'api/storage.php';

			OAuth2\Autoloader::register();

			$storage = new iaApiStorage();

			$this->_authServer = new OAuth2\Server($storage);

			$this->_authServer->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
			$this->_authServer->addGrantType(new OAuth2\GrantType\AuthorizationCode($storage));
			$this->_authServer->addGrantType(new OAuth2\GrantType\UserCredentials($storage));
			$this->_authServer->addGrantType(new OAuth2\GrantType\RefreshToken($storage));*/

			require  IA_INCLUDES . 'api/auth.php';

			$this->_authServer = new iaApiAuth();
		}

		return $this->_authServer;
	}

	protected function _auth()
	{
		/*require_once IA_INCLUDES . 'OAuth2/RequestInterface.php';
		require_once IA_INCLUDES . 'OAuth2/Request.php';
		require_once IA_INCLUDES . 'OAuth2/ResponseInterface.php';
		require_once IA_INCLUDES . 'OAuth2/Response.php';

		$authRequest = OAuth2\Request::createFromGlobals();
		$authResponse = new OAuth2\Response();*/

		switch ($this->_getRequest()->getEndpoint())
		{
			case 'auth':
				$this->_getRequest()->getParams() || $this->_checkPrivileges();
				$this->_getAuthServer()->authorize($this->_getRequest(), $this->_getResponse());

				break;

				/*if (!$this->_getAuthServer()->validateAuthorizeRequest($authRequest, $authResponse))
				{
					throw new Exception($authResponse->getParameter('error_description'), $authResponse->getStatusCode());
				}

				if (!$_POST)
				{
					$_SESSION['oauth_referrer'] = $_SERVER['HTTP_REFERER'];

					exit('
<form method="post">
<label>Do You Authorize TestClient?</label><br />
<input type="submit" name="authorized" value="yes">
<input type="submit" name="authorized" value="no">
</form>');
				}

				$authorized = (isset($_POST['authorized']) && 'yes' === $_POST['authorized']);

				$this->_getAuthServer()->handleAuthorizeRequest($authRequest, $authResponse, $authorized);

				if (!$authorized)
				{
					throw new Exception($authResponse->getParameter('error_description'), $authResponse->getStatusCode());
				}

				return $authResponse->getHttpHeader('Location');*/

			case 'token':
				//$this->_getAuthServer()->handleTokenRequest($authRequest)->send();
				$this->_getAuthServer()->handleTokenRequest($this->_getRequest(), $this->_getResponse());
		}
	}

	protected function _checkPrivileges()
	{
		if ($this->_getAuthServer()->verifyResourceRequest($this->_getRequest()))
		{
			if ($tokenInfo = $this->_getAuthServer()->getAccessTokenData($this->_getRequest()))
			{
				if ($tokenInfo['member_id'])
				{
					$iaUsers = iaCore::instance()->factory('users');

					$member = $iaUsers->getInfo($tokenInfo['member_id'], 'username');

					empty($member) || $iaUsers->getAuth($member['id']);
				}

				empty($tokenInfo['session']) || $this->_getAuthServer()->setSession($tokenInfo);

				return;
			}
		}

		throw new Exception('Invalid access token', iaApiResponse::FORBIDDEN);
	}

	// action methods
	protected function listResources($entity, $start, $limit, $where, $order)
	{
		return $entity->apiList($start, $limit, $where, $order);
	}

	public function getResource($entity, $id)
	{
		$resource = $entity->apiGet($id);

		if (!$resource)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		return $resource;
	}

	public function deleteResource($entity, $id)
	{
		if (!$entity->apiDelete($id))
		{
			throw new Exception('Could not delete a resource', iaApiResponse::INTERNAL_ERROR);
		}

		return null;
	}

	public function updateResource($entity, $id, array $params)
	{
		$result = $entity->apiUpdate($this->_getRequest()->getContent(), $id, $params);

		if (is_bool($result))
		{
			if (!$result)
			{
				throw new Exception('Could not update a resource', iaApiResponse::INTERNAL_ERROR);
			}

			$this->_getResponse()->setCode(iaApiResponse::OK);

			return null;
		}

		return $result;
	}

	public function addResource($entity)
	{
		$id = $entity->apiInsert($this->_getRequest()->getContent());

		$this->_getResponse()->setCode($id ? iaApiResponse::CREATED : iaApiResponse::CONFLICT);

		return empty($id) ? '' : $id;
	}

	// Mobile Push
	public function getMobilePush()
	{
		if (is_null($this->_mobilePush))
		{
			require_once IA_INCLUDES . 'api/push' . iaSystem::EXECUTABLE_FILE_EXT;

			$this->_mobilePush = new iaApiPush();
		}

		return $this->_mobilePush;
	}

	public function pushSendMembers($title, $message = '', array $params = array())
	{
		$this->getMobilePush()->sendMembers($title, $message, $params);
	}

	public function pushSendUsergroup($usergroupId, $title, $message = '', array $params = array())
	{
		$this->getMobilePush()->sendUsergroup($usergroupId, $title, $message, $params);
	}

	public function pushSendAdministrators($title, $message = '', array $params = array())
	{
		$this->getMobilePush()->sendAdministrators($title, $message, $params);
	}
}