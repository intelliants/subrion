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

class iaApiEntityMembers extends iaApiEntityAbstract
{
	const KEYWORD_SELF = 'self';

	protected $_name = 'members';

	protected $_table = 'members';


	public function apiGet($id)
	{
		if (self::KEYWORD_SELF == $id)
		{
			if (!iaUsers::hasIdentity())
			{
				throw new Exception('Not authenticated', iaApiResponse::FORBIDDEN);
			}

			$entry = iaUsers::getIdentity(true);
		}
		else
		{
			$entry = parent::apiGet($id);
		}

		if ($entry)
		{
			unset($entry['password'], $entry['sec_key']);
		}

		return $entry;
	}

	public function apiUpdate(array $data, $id, array $params)
	{
		if (self::KEYWORD_SELF == $id)
		{
			if (!iaUsers::hasIdentity())
			{
				throw new Exception('Not authenticated', iaApiResponse::FORBIDDEN);
			}

			$id = iaUsers::getIdentity()->id;

			if (1 == count($params))
			{
				return $this->_apiUpdateField($params[0], $id, $data);
			}

			// restrict update of sensitive data
			unset($data['email'], $data['status'], $data['date_reg'], $data['views_num'],
				$data['sponsored'], $data['sponsored_plan_id'], $data['sponsored_start'], $data['sponsored_end'],
				$data['featured'], $data['featured_start'], $data['featured_end'],
				$data['usergroup_id'], $data['sec_key'], $data['date_update'], $data['date_logged']);
		}
		elseif (!$this->_iaCore->factory('acl')->checkAccess('admin_page:edit', 'members'))
		{
			throw new Exception(iaLanguage::get(iaView::ERROR_FORBIDDEN), iaApiResponse::FORBIDDEN);
		}

		$this->_processFields($data);

		if (isset($data['password']))
		{
			if ($data['password'])
			{
				$data['password'] = $this->_iaCore->factory('users')->encodePassword($data['password']);
			}
			else
			{
				unset($data['password']);
			}
		}

		return $this->_update($data, $id, $params);
	}

	protected function _update(array $data, $id, array $params)
	{
		$resource = $this->apiGet($id);

		if (!$resource)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		$this->_iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

		return (0 === $this->_iaDb->getErrorNumber());
	}

	public function apiInsert(array $data)
	{
		if (iaUsers::hasIdentity())
		{
			throw new Exception('Unable to register member being logged in', iaApiResponse::FORBIDDEN);
		}

		$iaUsers = $this->_iaCore->factory('users');

		if (empty($data['email']))
		{
			throw new Exception('No email specified', iaApiResponse::BAD_REQUEST);
		}
		elseif ($this->_iaDb->exists(iaDb::convertIds($data['email'], 'email'), null, iaUsers::getTable()))
		{
			throw new Exception('Email exists', iaApiResponse::CONFLICT);
		}

		if (empty($data['password']))
		{
			$data['password'] = $iaUsers->createPassword();
		}

		unset($data['disable_fields']);

		return $iaUsers->register($data);
	}

	public function apiDelete($id)
	{
		if (!is_numeric($id))
		{
			throw new Exception('Numeric ID expected', iaApiResponse::NOT_FOUND);
		}

		$resource = $this->apiGet($id);

		if (!$resource)
		{
			throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
		}

		if (!$this->_iaCore->factory('acl')->checkAccess('admin_page:delete', 'members'))
		{
			throw new Exception(iaLanguage::get(iaView::ERROR_FORBIDDEN), iaApiResponse::FORBIDDEN);
		}

		return (bool)$this->_iaDb->delete(iaDb::convertIds($id), $this->getTable());
	}
}