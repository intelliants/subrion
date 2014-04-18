<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

class iaAcl extends abstractUtil
{
	const ACTION_READ = 'read';

	const SEPARATOR = ':';
	const DELIMITER = '--';

	const USER = 'user';
	const GROUP = 'group';
	const PLAN = 'plan';

	protected $_dbTableObjects = 'acl_objects';
	protected $_dbTablePrivileges = 'acl_privileges';

	protected $_permissions = array();
	protected $_groups = array();
	protected $_objects = array();
	protected $_actions = array();

	protected $_planId = 0;

	protected $_lastStep = 0;

	/**
	 * Set default user and group
	 * @param bool $user
	 * @param bool $group
	 * @return void
	 */
	public function init($user = false, $group = false)
	{
		parent::init();

		$this->iaCore->factory('users');

		if ($user === false)
		{
			$user = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0;
		}
		if ($group === false)
		{
			$group = iaUsers::hasIdentity() ? iaUsers::getIdentity()->usergroup_id : 4;
		}

		$objects = array();
		$actions = array();

		$rows = $this->iaCore->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, 0, null, $this->_dbTableObjects);
		foreach ($rows as $row)
		{
			if ($row['object'] != $row['pre_object'])
			{
				$row['object'] = $row['pre_object'] . '-' . $row['object'];
			}
			$key = $row['object'];
			isset($actions[$key]) || $actions[$key] = array();
			$actions[$key][] = $row['action'];

			$objects[$row['object'] . self::DELIMITER . $row['action']] = $row['access'];
		}

		$this->_actions = $actions;
		$this->_objects = $objects;

		if (empty($this->_permissions) || $user !== false || $group !== false)
		{
			$this->_permissions = $this->getPermissions($user, $group);
		}
	}

	/**
	 * Insert new privileges in database
	 * @param array $fields
	 * @return bool
	 */
	public function insert($fields)
	{
		$iaDb = &$this->iaCore->iaDb;

		if (empty($fields))
		{
			return false;
		}

		$chain = array();
		if ($fields)
		{
			foreach ($fields as $field => $value)
			{
				if ($field != 'access')
				{
					$chain[] = sprintf("`%s` = '%s'", $field, iaSanitize::sql($value));
				}
			}
		}
		$where = implode(' AND ', $chain);
		if ($iaDb->exists($where))
		{
			$iaDb->delete($where);
		}

		return $iaDb->insert($fields);
	}

	public function setPlan($planId = 0)
	{
		$this->_planId = (int)$planId;
	}

	public function resetPlan()
	{
		$this->setPlan();
	}

	public function getAccess($id = 0, $type = 'plan')
	{
		$stmt = sprintf("`type` = '%s' AND `type_id` = %d", $type, $id);
		$all = $this->iaCore->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt, 0, null, $this->_dbTablePrivileges);

		return $all;
	}

	public function getPlanFieldList($plan_id = 0, $item = '')
	{
		$fields = array();
		/*
		// TODO: check sql query
		$all = $this->iaCore->iaDb->all('*', "`type` = '$type' AND `type_id` = '$id' AND `object` = 'field' AND `object_id` != '0'", 0, null, 'acl_privileges');
		if (!$all)
		{
			return false;
		}
		foreach ($all as $row)
		{
			list($obj_item, $obj_name) = explode('--', $row['object_id']);
			if ($item == '')
			{
				if (!isset($fields[$obj_item]))
				{
					$fields[$obj_item] = array();
				}

				$fields[$obj_item][] = $obj_name;
			}
			elseif ($item == $obj_item)
			{
				$fields[] = $obj_name;
			}
		}*/
		return $fields;
	}

	/**
	 * Check access for admin
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->checkAccess('admin_login', 0, 1);
	}

	/**
	 * Check page
	 * @alias checkAccess
	 * @param string $params
	 * @param int $owner_user
	 * @param int $owner_group
	 * @param bool $object_id
	 * @return bool
	 */
	public function checkPage($params = '', $ownerUser = 0, $ownerGroup = 0, $objectId = false)
	{
		if (!$this->checkAccess($params, $ownerUser, $ownerGroup, $objectId))
		{
			return iaView::accessDenied();
		}

		return true;
	}

	/**
	 * Checks access for user and groups
	 * @param string $params
	 * @param int $userId
	 * @param int $groupId
	 * @param bool $objectId
	 * @param bool $custom
	 * @return bool
	 */
	public function checkAccess($params = '', $userId = 0, $groupId = 0, $objectId = null, $custom = false)
	{
		$array = explode(self::SEPARATOR, $params);
		$object = $array[0];
		$action = self::ACTION_READ;

		if (isset($array[1]))
		{
			$action = $array[1];
		}

		if ($custom === false)
		{
			$this->iaCore->factory('users');

			$user = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0;
			$group = iaUsers::hasIdentity() ? iaUsers::getIdentity()->usergroup_id : 0;
			$perms = $this->_permissions;
		}
		else
		{
			$user = isset($custom['user']) ? $custom['user'] : 0;
			$group = isset($custom['group']) ? $custom['group'] : 0;
			if (isset($custom['perms']))
			{
				$perms = $custom['perms'];
			}
			elseif ($custom)
			{
				$perms = array();
			}
			else
			{
				$perms = $this->getPermissions($user, $group);
			}
		}

		// 1. Administrators
		if (iaUsers::MEMBERSHIP_ADMINISTRATOR == $group)
		{
			$this->_lastStep = 1;
			return true;
		}
		// 2. Owner (user)
		if ($userId != 0 && $userId == $user)
		{
			$this->_lastStep = 2;
			return true;
		}
		// 3. Owner (user)
		if ($groupId != 0 && $groupId == $group)
		{
			$this->_lastStep = 3;
			return true;
		}

		if ($objectId)
		{
			$name = $this->encodeAction($object, $action, $objectId);
			if (isset($perms[$name]))
			{
				$perms = $perms[$name];
				ksort($perms);
				// 4. Object privileges check (user = 0)
				// 5. Object privileges check (group = 1)
				// 6. Object privileges check (plan = 2)
				foreach ($perms as $type => $values)
				{
					$this->_lastStep = 4 + $type;
					if ($type == 2)
					{
						if ($values['type_id'] == $this->_planId)
						{
							return (bool)$values['access'];
						}
					}
					else
					{
						return (bool)$values['access'];
					}
				}
			}
		}
		// 7. All privileges check (user = 0)
		// 8. All privileges check (group = 1)
		// 9. All privileges check (plan = 2)
		$name = $object . self::DELIMITER . $action . self::DELIMITER . '0';
		if (isset($perms[$name]))
		{
			$perms = $perms[$name];
			ksort($perms);
			foreach ($perms as $type => $values)
			{
				$this->_lastStep = 7 + $type;
				if ($type == 2)
				{
					if ($values['type_id'] == $this->_planId)
					{
						return (bool)$values['access'];
					}
				}
				else
				{
					return (bool)$values['access'];
				}
			}
		}

		// 10. Default object value
		if (isset($this->_objects[$object . self::DELIMITER . $action]))
		{
			$this->_lastStep = 10;
			return (bool)$this->_objects[$object . self::DELIMITER . $action];
		}

		$this->_lastStep = 11;
		// 11. Default value from core
		return ($action == self::ACTION_READ);
	}

	/**
	 * Return last step, when access granted, for debug
	 * @return string
	 */
	public function getLastStep()
	{
		$messages = array(
			'none',
			'Administrators',
			'Owner (user)',
			'Owner (user)',
			'Object privileges check (user = 0)',
			'Object privileges check (group = 1)',
			'Object privileges check (plan = 2)',
			'All privileges check (user = 0)',
			'All privileges check (group = 1)',
			'All privileges check (plan = 2)',
			'Default object value',
			'Default core value'
		);

		return '(' . $this->_lastStep . ') ' . $messages[$this->_lastStep];
	}

	/**
	 * Get permissions from database
	 * @param $user
	 * @param $group
	 * @return array
	 */
	public function getPermissions($user, $group)
	{
		$permissions = array();

		// get permissions
		$stmt = sprintf("(`type` = '%s' AND `type_id` = '%d') OR (`type` = '%s' AND `type_id` = '%d')",
			self::USER,
			$user,
			self::GROUP,
			$group
		);
		$entries = $this->iaCore->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt, 0, null, $this->_dbTablePrivileges);

		foreach ($entries as $entry)
		{
			if (empty($entry['object_id']))
			{
				$entry['object_id'] = 0;
			}

			switch($entry['type'])
			{
				case self::USER:
					$entry['type'] = 0;
					break;
				case self::GROUP:
					$entry['type'] = 1;
					break;
				case self::PLAN:
					$entry['type'] = 2;
					break;
			}
			$name = $this->encodeAction($entry['object'], $entry['action'], $entry['object_id']);
			if (!isset($permissions[$name]))
			{
				$permissions[$name] = array();
			}
			$permissions[$name][$entry['type']] = array(
				'access' => (int)$entry['access'],
				'type_id' => $entry['type_id'],
				'id' => $entry['id']
			);
		}
		return $permissions;
	}

	/**
	 * Get permissions only for group
	 * @alias getPermissions
	 * @param int $group
	 * @return array
	 */
	public function getPermissionsForUsergroup($group = 0)
	{
		return $this->getPermissions(0, $group);
	}

	/**
	 * Get permissions only for user
	 * @alias getPermissions
	 * @param int $user
	 * @return array
	 */
	public function getPermissionsForUser($user = 0)
	{
		return $this->getPermissions($user, 0);
	}

	/**
	 * Encode permissions actions
	 * @param string $object
	 * @param string $action
	 * @param int $object_id
	 * @return string
	 */
	public function encodeAction($object, $action = self::ACTION_READ, $objectId = 0)
	{
		return $object . self::DELIMITER . $action . self::DELIMITER . $objectId;
	}

	/**
	 * decode permissions actions
	 * @param $action
	 * @return array
	 */
	public function decodeAction($action)
	{
		return explode(self::DELIMITER, $action);
	}

	/**
	 * User groups
	 *
	 * @param bool $refresh
	 * @return array
	 */
	public function getGroups($refresh = false)
	{
		if ($refresh)
		{
			$this->_groups = $this->iaCore->iaDb->keyvalue(array('title', 'id'), '1 ORDER BY `title`', 'usergroups');
		}
		return $this->_groups;
	}

	/**
	 * Return free usergroup ID, 0 if no free ID
	 * @return int
	 */
	public function obtainFreeId()
	{
		$id = 0;
		$max_groups = 28; // be careful with overflow
		$this->getGroups(true);
		for ($i = 0; $i <= $max_groups; $i++)
		{
			if (!in_array(1 << $i, $this->_groups))
			{
				$id = 1 << $i;
				break;
			}
		}
		return $id;
	}

	// TODO: review
	public function getObjects()
	{
		return $this->_objects;
	}

	public function getActions()
	{
		return $this->_actions;
	}
	//
}