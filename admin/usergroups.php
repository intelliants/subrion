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
    protected $_name = 'usergroups';

    protected $_processEdit = false;
    protected $_tooltipsEnabled = true;

    protected $_phraseAddSuccess = 'usergroup_added';
    protected $_phraseGridEntryDeleted = 'usergroup_deleted';

    protected $_iaUsers;


    public function __construct()
    {
        parent::__construct();

        $this->setTable(iaUsers::getUsergroupsTable());

        $this->_iaUsers = $this->_iaCore->factory('users');
    }

    protected function _gridRead($params)
    {
        return ($this->_iaCore->requestPath && 'store' == end($this->_iaCore->requestPath))
            ? $this->_getUsergroups()
            : parent::_gridRead($params);
    }

    protected function _entryAdd(array $entryData)
    {
        $entryData['order'] = $this->_iaDb->getMaxOrder() + 1;

        return parent::_entryAdd($entryData);
    }

    protected function _entryDelete($entryId)
    {
        return $this->_iaUsers->deleteUsergroup($entryId);
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql = <<<SQL
SELECT u.*, IF(u.`id` = 1, 0, u.`id`) `permissions`, u.`id` `config`, IF(u.`system` = 1, 0, 1) `delete`, 
  IF(u.`id` = 1, 1, p.`access`) `admin`, 
  (SELECT GROUP_CONCAT(m.`fullname` SEPARATOR ', ') FROM `:table_members` m WHERE m.`usergroup_id` = u.`id` GROUP BY m.`usergroup_id` LIMIT 10) `members`,
  (SELECT COUNT(m.`id`) FROM `:table_members` m WHERE m.`usergroup_id` = u.`id` GROUP BY m.`usergroup_id`) `count`
  FROM `:table_usergroups` u
LEFT JOIN `:table_privileges` p ON (p.`type` = 'group' AND p.`type_id` = u.`id` AND `object` = 'admin_access' AND `action` = 'read')
WHERE :conditions
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'table_members' => iaUsers::getTable(true),
            'table_usergroups' => $this->_iaDb->prefix . iaUsers::getUsergroupsTable(),
            'table_privileges' => $this->_iaDb->prefix . 'acl_privileges',
            'conditions' => $where,
            'order' => $order,
            'start' => $start,
            'limit' => $limit
        ]);

        $usergroups = $this->_iaDb->getAll($sql);
        foreach ($usergroups as &$usergroup) {
            $usergroup['title'] = iaLanguage::get('usergroup_' . $usergroup['name']);
        }

        return $usergroups;
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['assignable'] = (int)$data['visible'];
        $entry['visible'] = (int)$data['visible'];
        $entry['order'] = (int)$data['order'];

        if (iaCore::ACTION_ADD == $action) {
            if (empty($data['name'])) {
                $this->addMessage('error_usergroup_incorrect');
            } else {
                $entry['name'] = strtolower(iaSanitize::paranoid($data['name']));
                if (!iaValidate::isAlphaNumericValid($entry['name'])) {
                    $this->addMessage('error_usergroup_incorrect');
                } elseif ($this->_iaDb->exists('`name` = :name', ['name' => $entry['name']])) {
                    $this->addMessage('error_usergroup_exists');
                }
            }
        }

        foreach ($this->_iaCore->languages as $code => $language) {
            if (empty($data['title'][$code])) {
                $this->addMessage(iaLanguage::getf('error_lang_title', ['lang' => $language['title']]), false);
            }
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

        foreach ($this->_iaCore->languages as $code => $language) {
            $title = iaSanitize::tags($data['title'][$code]);
            utf8_is_valid($title) || $title = utf8_bad_replace($title);
            iaLanguage::addPhrase('usergroup_' . $entry['name'], $title, $code);
        }

        // copy privileges
        if ($data['copy_from']) {
            $this->_iaDb->setTable('acl_privileges');

            $where = '`type_id` = :id AND `type` = :type';
            $this->_iaDb->bind($where, ['id' => (int)$data['copy_from'], 'type' => 'group']);

            $rows = $this->_iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where);

            foreach ($rows as $key => &$row) {
                $row['type_id'] = $this->getEntryId();
                unset($rows[$key]['id']);
            }

            $this->_iaDb->insert($rows);

            $this->_iaDb->resetTable();
        }
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        iaBreadcrumb::replaceEnd(iaLanguage::get('add_usergroup'), IA_SELF);

        $usergroupList = $this->_iaDb->keyvalue(['id', 'name'], iaDb::convertIds(iaUsers::MEMBERSHIP_ADMINISTRATOR, 'id', false));

        $iaView->assign('groups', $usergroupList);
    }

    private function _getUsergroups()
    {
        $result = ['data' => []];

        foreach ($this->_iaUsers->getUsergroups() as $id => $name) {
            $result['data'][] = ['value' => $id, 'title' => iaLanguage::get('usergroup_' . $name)];
        }

        return $result;
    }
}
