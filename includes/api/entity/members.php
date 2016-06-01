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

	protected $_table = 'members';


	public function apiGet($id)
	{
		if (self::KEYWORD_SELF == $id)
		{
			if (!iaUsers::hasIdentity())
			{
				throw new Exception('Not authenticated', iaApiResponse::FORBIDDEN);
			}

			return iaUsers::getIdentity(true);
		}

		return parent::apiGet($id);
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
		}

		return parent::apiUpdate($data, $id, $params);
	}

	public function apiInsert(array $data)
	{ return 19;
		if (iaUsers::hasIdentity())
		{
			throw new Exception('Unable to register member being logged in', iaApiResponse::FORBIDDEN);
		}

		$iaUsers = $this->_iaCore->factory('users');

		if (empty($data['username']))
		{
			throw new Exception('No username specified', iaApiResponse::BAD_REQUEST);
		}
		elseif ($this->_iaDb->exists(iaDb::convertIds($data['username'], 'username'), null, iaUsers::getTable()))
		{
			throw new Exception('Username already taken', iaApiResponse::CONFLICT);
		}

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
}