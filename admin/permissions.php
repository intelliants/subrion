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

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'permissions';

    private $_objects = [];


    public function __construct()
    {
        parent::__construct();

        $iaAcl = $this->_iaCore->factory('acl');
        $this->setHelper($iaAcl);

        $this->_objects = $iaAcl->getObjects();
    }

    private function _getSettings()
    {
        $settings = [
            'target' => 'all',
            'id' => 0,
            'action' => 2,
            'user' => 0,
            'group' => 0,
            'item' => null
        ];

        if (isset($_GET['user'])) {
            $settings['action'] = 0;
            $settings['target'] = iaAcl::USER;
            $settings['user'] = $settings['id'] = (isset($_GET[$settings['target']]) ? (int)$_GET[$settings['target']] : 0);
            $settings['item'] = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($settings['id']),
                iaUsers::getTable());

            if (!empty($settings['item']['usergroup_id'])) {
                $settings['group'] = (int)$settings['item']['usergroup_id'];
            }
        } elseif (isset($_GET['group'])) {
            $settings['action'] = 1;
            $settings['target'] = iaAcl::GROUP;
            $settings['group'] = $settings['id'] = (isset($_GET[$settings['target']]) ? (int)$_GET[$settings['target']] : 0);
            $settings['item'] = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($settings['id']),
                iaUsers::getUsergroupsTable());
        }

        return $settings;
    }

    protected function _gridRead($params)
    {
        return iaVIew::errorPage(iaView::ERROR_NOT_FOUND);
    }

    protected function _gridUpdate($params)
    {
        $output = ['result' => false, 'message' => iaLanguage::get('invalid_parameters')];
        $settings = $this->_getSettings();

        if ($settings['action'] != 2 && $_POST) {
            $params = $_POST;

            if (isset($params['access'])) {
                $result = true;
                is_array($params['action']) || $params['action'] = [$params['action']];
                foreach ($params['action'] as $action) {
                    $set = $this->getHelper()->set($params['object'], $params['id'], $settings['target'],
                        $settings['id'], $params['access'], $action);
                    if (!$set && $result) {
                        $result = false;
                    }
                }
            } else {
                $result = $this->getHelper()->drop($params['object'], $params['id'], $settings['target'],
                    $settings['id'], empty($params['action']) ? null : $params['action']);
            }

            $output['result'] = $result;
            $output['message'] = $output['result']
                ? iaLanguage::get('saved')
                : iaLanguage::get('db_error');
        }

        return $output;
    }

    protected function _indexPage(&$iaView)
    {
        $settings = $this->_getSettings();

        if (in_array($settings['target'], [iaAcl::USER, iaAcl::GROUP])) {
            if (empty($settings['item'])) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            if (iaAcl::USER == $settings['target']) {
                iaBreadcrumb::add(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
                $iaView->title(iaLanguage::getf('permissions_members',
                    ['member' => '"' . $settings['item']['fullname'] . '"']));
            } else {
                iaBreadcrumb::add(iaLanguage::get('usergroups'), IA_ADMIN_URL . 'usergroups/');
                $iaView->title(iaLanguage::getf('permissions_usergroups',
                    ['usergroup' => '"' . iaLanguage::get('usergroup_' . $settings['item']['name']) . '"']));
            }

            $userPermissions = $this->getHelper()->getPermissions($settings['id'], $settings['group']);
            $custom = [
                'user' => $settings['user'],
                'group' => $settings['group'],
                'perms' => $userPermissions
            ];

            $actionCode = 'admin_access--read';
            list($object, ) = explode(iaAcl::DELIMITER, $actionCode);

            $adminAccess = [
                'title' => iaLanguage::get($actionCode, $actionCode),
                'modified' => isset($userPermissions[$this->getHelper()->encodeAction($object, iaCore::ACTION_READ,
                        '0')][$settings['action']]),
                'default' => $this->_objects[$actionCode],
                'access' => (int)$this->getHelper()->checkAccess($object, null, 0, 0, $custom)
            ];

            $iaView->assign('adminAccess', $adminAccess);
            $iaView->assign('pageGroupTitles', $this->_iaDb->keyvalue(['id', 'name'], null, 'admin_pages_groups'));
            $iaView->assign('permissions', $this->_getPermissionsInfo($settings, $userPermissions, $custom));
        } else {
            $this->_list($iaView);
        }
    }

    private function _getPermissionsInfo($settings, $userPermissions, $custom)
    {
        $iaAcl = $this->getHelper();
        $iaPage = $this->_iaCore->factory('page', iaCore::ADMIN);

        $actions = $iaAcl->getActions();
        $groups = [];

        foreach ([iaAcl::OBJECT_PAGE, iaAcl::OBJECT_ADMIN_PAGE] as $i => $pageType) {
            $fieldsList = ['name', 'action', 'group', 'parent'];
            $titles = $iaPage->getTitles(iaAcl::OBJECT_PAGE == $pageType ? iaCore::FRONT : iaCore::ADMIN);
            $pages = $this->_iaDb->all($fieldsList,
                '`' . (1 == $i ? 'readonly' : 'service') . "` = 0 AND `name` != '' ORDER BY `parent` DESC, `id`", null,
                null, $pageType . 's');

            foreach ($pages as $page) {
                if ($page['parent']) {
                    $key = $pageType . '-' . $page['parent'];
                    isset($actions[$key]) || $actions[$key] = [];
                    in_array($page['action'], $actions[$key]) || $actions[$key][] = $page['action'];
                } else {
                    $list = [];
                    $key = $pageType . '-' . $page['name'];

                    $page['group'] || $page['group'] = 1;
                    $page['title'] = isset($titles[$page['name']]) ? $titles[$page['name']] : ucfirst($page['name']);

                    foreach ([
                                 iaCore::ACTION_READ,
                                 iaCore::ACTION_ADD,
                                 iaCore::ACTION_EDIT,
                                 iaCore::ACTION_DELETE
                             ] as $action) {
                        $actionCode = $key . iaAcl::DELIMITER . $action;
                        if (isset($this->_objects[$actionCode]) || iaCore::ACTION_READ == $action) {
                            isset($actions[$key]) || $actions[$key] = [];
                            in_array($action, $actions[$key]) || array_unshift($actions[$key], $action);
                        }
                    }

                    foreach ($actions[$key] as $action) {
                        $actionCode = $key . iaAcl::DELIMITER . $action;
                        $param = $pageType . iaAcl::SEPARATOR . $action;

                        $list[$action] = [
                            'title' => iaLanguage::get($actionCode,
                                iaLanguage::getf('action-' . $action, ['page' => $page['title']])),
                            'modified' => isset($userPermissions[$iaAcl->encodeAction($pageType, $action,
                                    $page['name'])][$settings['action']]),
                            'default' => (int)$iaAcl->checkAccess($param, $page['name'], 0, 0, true),
                            'access' => (int)$iaAcl->checkAccess($param, $page['name'], 0, 0, $custom)
                        ];
                    }

                    if (!isset($groups[$pageType][$page['group']])) {
                        $groups[$pageType][$page['group']] = [];
                    }

                    $groups[$pageType][$page['group']][$page['name']] = [
                        'title' => $page['title'],
                        'list' => $list
                    ];
                }
            }

            ksort($groups[$pageType]);
        }

        return $groups;
    }

    private function _list(&$iaView)
    {
        $iaView->assign('members', $this->_listMembers());
        $iaView->assign('usergroups', $this->_listUsergroups());
    }

    private function _listUsergroups()
    {
        $sql = <<<SQL
SELECT u.`id`, u.`name`, IF(u.`id` = 1, 1, p.`access`) `admin_access` 
  FROM `:prefix:table_usergroups` u 
LEFT JOIN `:prefix:table_privileges` p ON (p.`type_id` = u.`id` AND p.`type` = 'group' AND p.`object` = 'admin_access')
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_usergroups' => iaUsers::getUsergroupsTable(),
            'table_privileges' => 'acl_privileges'
        ]);

        return $this->_iaDb->getAll($sql);
    }

    private function _listMembers()
    {
        $sql = <<<SQL
SELECT m.`id`, m.`fullname`, g.`name` `usergroup`, IF(m.`usergroup_id` = 1, 1, p.`access`) `admin_access` 
  FROM `:prefix:table_members` m 
LEFT JOIN `:prefix:table_groups` g ON (m.`usergroup_id` = g.`id`) 
LEFT JOIN `:prefix:table_privileges` p ON (p.`type_id` = m.`id` AND p.`type` = 'user' AND p.`object` = 'admin_access')
WHERE m.`id` IN (SELECT DISTINCT `type_id` FROM `:prefix:table_privileges` WHERE `type` = 'user')
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_members' => iaUsers::getTable(),
            'table_groups' => iaUsers::getUsergroupsTable(),
            'table_privileges' => 'acl_privileges'
        ]);

        return $this->_iaDb->getAll($sql);
    }
}
