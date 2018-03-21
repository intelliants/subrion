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

class iaApiEntityMember extends iaApiEntityAbstract
{
    const KEYWORD_SELF = 'self';

    protected static $_table = 'members';

    protected $hiddenFields = ['password', 'sec_key'];

    protected $protectedFields = [
        'email', 'status', 'date_reg', 'sec_key', 'views_num', 'sponsored',
        'sponsored_plan_id', 'sponsored_start', 'sponsored_end', 'featured',
        'featured_start', 'featured_end', 'usergroup_id', 'date_update', 'date_logged'
    ];

    protected $helper;


    public function init()
    {
        parent::init();

        $this->helper = $this->iaCore->factory('users');
    }

    public function apiList($start, $limit, $where, $order)
    {
        $order = str_replace(' ORDER BY', '', $order);
        $rows = $this->helper->coreSearch($where, $start, $limit, $order)[1];

        $this->_filterHiddenFields($rows);

        return $rows;
    }

    public function apiGet($id)
    {
        if (self::KEYWORD_SELF == $id) {
            if (!iaUsers::hasIdentity()) {
                throw new Exception('Not authorized', iaApiResponse::UNAUTHORIZED);
            }

            $entry = iaUsers::getIdentity(true);
            $this->_filterHiddenFields($entry, true);

            return $entry;
        }

        return parent::apiGet($id);
    }

    public function apiUpdate($data, $id, array $params)
    {
        if (self::KEYWORD_SELF == $id) {
            if (!iaUsers::hasIdentity()) {
                throw new Exception('Not authorized', iaApiResponse::UNAUTHORIZED);
            }

            $id = iaUsers::getIdentity()->id;
        } elseif (!$this->iaCore->factory('acl')->checkAccess('admin_page:edit', 'members')) {
            throw new Exception(iaLanguage::get(iaView::ERROR_FORBIDDEN), iaApiResponse::FORBIDDEN);
        }

        $resource = $this->apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (1 == count($params)) {
            return $this->_apiUpdateSingleField($params[0], $id, $data);
        }

        $this->_apiProcessFields($data);

        if (isset($data['password'])) {
            if ($data['password']) {
                $data['password'] = $this->helper->encodePassword($data['password']);
            } else {
                unset($data['password']);
            }
        }

        $this->iaDb->update($data, iaDb::convertIds($id), null, $this->getTable());

        $result = (0 === $this->iaDb->getErrorNumber());

        if ($id == iaUsers::getIdentity()->id && $result) {
            iaUsers::reloadIdentity();
        }

        return $result;
    }

    public function apiInsert($data)
    {
        if (iaUsers::hasIdentity()) {
            throw new Exception('Unable to register member being logged in', iaApiResponse::FORBIDDEN);
        }

        if (empty($data['email'])) {
            throw new Exception('No email specified', iaApiResponse::BAD_REQUEST);
        } elseif ($this->iaDb->exists(iaDb::convertIds($data['email'], 'email'), null, iaUsers::getTable())) {
            throw new Exception('Email exists', iaApiResponse::CONFLICT);
        }

        if (empty($data['password'])) {
            $data['password'] = $this->helper->createPassword();
        }

        unset($data['disable_fields']);

        return $this->helper->register($data);
    }

    public function apiDelete($id, array $params)
    {
        if (self::KEYWORD_SELF == $id) {
            if (!iaUsers::hasIdentity()) {
                throw new Exception('Not authorized', iaApiResponse::UNAUTHORIZED);
            }

            $id = iaUsers::getIdentity()->id;
        } elseif (!$this->iaCore->factory('acl')->checkAccess('admin_page:delete', 'members')) {
            throw new Exception(iaLanguage::get(iaView::ERROR_FORBIDDEN), iaApiResponse::FORBIDDEN);
        }

        $resource = parent::apiGet($id);

        if (!$resource) {
            throw new Exception('Resource does not exist', iaApiResponse::NOT_FOUND);
        }

        if (1 == count($params)) {
            $result = $this->_apiResetSingleField($params[0], $id);

            if ($result && iaUsers::getIdentity()->id == $id) {
                iaUsers::reloadIdentity();
            }

            return $result;
        }

        return (bool)$this->helper->delete(iaDb::convertIds($id));
    }
}
