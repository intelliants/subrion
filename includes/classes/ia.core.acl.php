<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

class iaAcl extends abstractUtil
{
    const SEPARATOR = ':';
    const DELIMITER = '--';

    const USER = 'user';
    const GROUP = 'group';
    const PLAN = 'plan';

    const OBJECT_PAGE = 'page';
    const OBJECT_ADMIN_PAGE = 'admin_page';

    protected $_dbTableObjects = 'acl_objects';
    protected $_dbTablePrivileges = 'acl_privileges';

    protected $_permissions = [];
    protected $_objects = [];
    protected $_actions = [];

    protected $_lastStep = 0;

    /**
     * Set default user and group
     * @param bool $user
     * @param bool $group
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->iaCore->factory('users');

        $user = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0;
        $group = iaUsers::hasIdentity() ? iaUsers::getIdentity()->usergroup_id : iaUsers::MEMBERSHIP_GUEST;

        $objects = [];
        $actions = [];

        $rows = $this->iaCore->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, $this->_dbTableObjects);
        foreach ($rows as $row) {
            if ($row['object'] != $row['pre_object']) {
                $row['object'] = $row['pre_object'] . '-' . $row['object'];
            }
            $key = $row['object'];
            isset($actions[$key]) || $actions[$key] = [];
            $actions[$key][] = $row['action'];

            $objects[$row['object'] . self::DELIMITER . $row['action']] = (int)$row['access'];
        }

        $this->_actions = $actions;
        $this->_objects = $objects;

        if (empty($this->_permissions)) {
            $this->_permissions = $this->getPermissions($user, $group);
        }
    }

    /**
     * Check access for admin
     * @return bool
     */
    public function isAdmin()
    {
        return $this->checkAccess('admin_access');
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
    public function checkAccess($params = '', $objectId = null, $userId = 0, $groupId = 0, $custom = false)
    {
        $array = explode(self::SEPARATOR, $params);
        $object = $array[0];
        $action = iaCore::ACTION_READ;

        if (isset($array[1])) {
            $action = $array[1];
        }

        if (false === $custom) {
            $this->iaCore->factory('users');

            $user = iaUsers::hasIdentity() ? iaUsers::getIdentity()->id : 0;
            $group = iaUsers::hasIdentity() ? iaUsers::getIdentity()->usergroup_id : 0;
            $perms = $this->_permissions;
        } else {
            $user = isset($custom['user']) ? $custom['user'] : 0;
            $group = isset($custom['group']) ? $custom['group'] : 0;
            if (isset($custom['perms'])) {
                $perms = $custom['perms'];
            } elseif ($custom) {
                $perms = [];
            } else {
                $perms = $this->getPermissions($user, $group);
            }
        }

        // 1. Administrators
        if (iaUsers::MEMBERSHIP_ADMINISTRATOR == $group) {
            $this->_lastStep = 1;
            return true;
        }
        // 2. Owner (user)
        if ($userId != 0 && $userId == $user) {
            $this->_lastStep = 2;
            return true;
        }
        // 3. Owner (user)
        if ($groupId != 0 && $groupId == $group) {
            $this->_lastStep = 3;
            return true;
        }

        if ($objectId) {
            $name = $this->encodeAction($object, $action, $objectId);
            if (isset($perms[$name])) {
                $perms = $perms[$name];
                ksort($perms);
                // 4. Object privileges check (user = 0)
                // 5. Object privileges check (group = 1)
                // 6. Object privileges check (plan = 2)
                foreach ($perms as $type => $values) {
                    $this->_lastStep = 4 + $type;
/*					if ($type == 2)
                    {
                        if ($values['type_id'] == $this->_planId)
                        {
                            return (bool)$values['access'];
                        }
                    }
                    else
                    {*/
                        return (bool)$values['access'];
//					}
                }
            }
        }

        // 7. All privileges check (user = 0)
        // 8. All privileges check (group = 1)
        // 9. All privileges check (plan = 2)
        $name = $object . self::DELIMITER . $action . self::DELIMITER . '0';
        if (isset($perms[$name])) {
            $perms = $perms[$name];
            ksort($perms);
            foreach ($perms as $type => $values) {
                $this->_lastStep = 7 + $type;
/*				if ($type == 2)
                {
                    if ($values['type_id'] == $this->_planId)
                    {
                        return (bool)$values['access'];
                    }
                }
                else
                {*/
                    return (bool)$values['access'];
//				}
            }
        }

        // 10. Default object value
        $key = $object . ($objectId ? '-' . $objectId : '') . self::DELIMITER . $action;
        if (isset($this->_objects[$key])) {
            $this->_lastStep = 10;
            return (bool)$this->_objects[$key];
        }

        $this->_lastStep = 11;
        // 11. Default value from core
        return ($action == iaCore::ACTION_READ);
    }

    public function isAccessible($objectId, $action = iaCore::ACTION_READ, $object = null)
    {
        if (is_null($object)) {
            $object = (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType()) ? self::OBJECT_ADMIN_PAGE : self::OBJECT_PAGE;
        }

        if (in_array($object, [self::OBJECT_PAGE, self::OBJECT_ADMIN_PAGE])) {
            static $parentPagesMap = [];

            if (!isset($parentPagesMap[$object])) {
                $parentPagesMap[$object] = $this->_getPagesParents($object);
            }
            if (!empty($parentPagesMap[$object][$objectId])) {
                $objectId = $parentPagesMap[$object][$objectId];
            }
        }

        return $this->checkAccess($object . self::SEPARATOR . $action, $objectId);
    }

    private function _getPagesParents($side)
    {
        $rows = $this->iaCore->iaDb->keyvalue(['name', 'parent'], null, $side . 's');

        function recursiveFindFirstParent($rows, $page)
        {
            while (true) {
                if (!$rows[$page]) {
                    return $page;
                }
                $page = $rows[$page];
            }
        }

        // here we do shift the parents to the very first parent
        foreach ($rows as $name => &$parent) {
            empty($parent) || $parent = recursiveFindFirstParent($rows, $parent);
        }

        return $rows;
    }

    /**
     * Return latest step when access has been granted
     * @return string
     */
    public function getLastStep()
    {
        $messages = [
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
        ];

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
        $permissions = [];

        // get permissions
        $stmt = sprintf("(`type` = '%s' AND `type_id` = '%d') OR (`type` = '%s' AND `type_id` = '%d')",
            self::USER,
            $user,
            self::GROUP,
            $group
        );
        $entries = $this->iaCore->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $stmt, null, null, $this->_dbTablePrivileges);

        foreach ($entries as $entry) {
            if (empty($entry['object_id'])) {
                $entry['object_id'] = 0;
            }

            switch ($entry['type']) {
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

            isset($permissions[$name]) || $permissions[$name] = [];
            $permissions[$name][$entry['type']] = [
                'access' => (int)$entry['access'],
                'type_id' => $entry['type_id']
            ];
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
    public function encodeAction($object, $action = iaCore::ACTION_READ, $objectId = 0)
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

    public function drop($object, $objectId, $type = null, $typeId = null, $action = null)
    {
        $iaDb = &$this->iaCore->iaDb;

        $stmt = '`object` = :object';
        empty($objectId) || $stmt .= ' AND `object_id` = :oid';
        is_null($type) || $stmt .= ' AND `type` = :type';
        is_null($typeId) || $stmt .= ' AND `type_id` = :tid';
        is_null($action) || $stmt .= ' AND `action` = :action';

        $params = [
            'type' => $type,
            'tid' => (int)$typeId,
            'object' => $object,
            'oid' => $objectId,
            'action' => $action
        ];

        $iaDb->delete($stmt, $this->_dbTablePrivileges, $params);

        return !$iaDb->getErrorNumber();
    }

    public function set($object, $objectId, $type, $typeId, $access, $action = iaCore::ACTION_READ, $extras = '')
    {
        $iaDb = &$this->iaCore->iaDb;

        $stmt = '`object` = :object AND `object_id` = :oid AND `type` = :type AND `type_id` = :tid AND `action` = :action';
        $iaDb->bind($stmt, ['object' => $object, 'oid' => $objectId, 'type' => $type, 'tid' => (int)$typeId, 'action' => $action]);

        $row = $iaDb->row(['access'], $stmt, $this->_dbTablePrivileges);
        if ($row && $row['access'] == $access) {
            return true;
        }

        return $row
            ? (bool)$iaDb->update(['access' => (int)$access], $stmt, null, $this->_dbTablePrivileges)
            : (bool)$iaDb->insert(['object' => $object, 'object_id' => $objectId, 'type' => $type, 'type_id' => (int)$typeId, 'action' => $action, 'access' => (int)$access, 'module' => $extras], null, $this->_dbTablePrivileges);
    }
}
