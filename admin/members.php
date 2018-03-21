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
    protected $_name = 'members';

    protected $_gridColumns = ['username', 'fullname', 'usergroup_id', 'email', 'status', 'date_reg', 'date_logged'];
    protected $_gridFilters = ['status' => self::EQUAL, 'usergroup_id' => self::EQUAL, 'id' => self::EQUAL];
    protected $_gridSorting = ['usergroup' => 'usergroup_id'];

    protected $_phraseAddSuccess = 'member_added';
    protected $_phraseGridEntryDeleted = 'member_deleted';

    private $_itemName;
    private $_userGroups;

    private $_password;


    public function __construct()
    {
        parent::__construct();

        $iaUsers = $this->_iaCore->factory('users');
        $this->setHelper($iaUsers);

        $this->_itemName = $iaUsers->getItemName();
        $this->_userGroups = $iaUsers->getUsergroups();
    }

    protected function _indexPage(&$iaView)
    {
        if (2 == count($this->_iaCore->requestPath) && 'login' == $this->_iaCore->requestPath[0]) {
            $this->getHelper()->clearIdentity();
            $this->getHelper()->getAuth($this->_iaCore->requestPath[1]);

            iaUtil::go_to(IA_URL);

            return;
        }

        parent::_indexPage($iaView);
    }

    protected function _gridRead($params)
    {
        if (1 == count($this->_iaCore->requestPath) && 'registration-email' == $this->_iaCore->requestPath[0]) {
            return $this->_resendRegistrationEmail();
        }

        return parent::_gridRead($params);
    }

    protected function _entryDelete($entryId)
    {
        $stmt = '`id` = :id AND `id` != :user';
        $stmt = iaDb::printf($stmt, ['id' => (int)$entryId, 'user' => (int)iaUsers::getIdentity()->id]);

        return $this->getHelper()->delete($stmt);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['name'])) {
            $conditions[] = "CONCAT(`username`, `fullname`, `email`) LIKE '%" . iaSanitize::sql($params['name']) . "%'";
        }
    }

    protected function _gridModifyOutput(array &$entries)
    {
        $userId = iaUsers::getIdentity()->id;

        foreach ($entries as &$entry) {
            $entry['usergroup'] = isset($this->_userGroups[$entry['usergroup_id']]) ? iaLanguage::get('usergroup_' . $this->_userGroups[$entry['usergroup_id']]) : '';
            $entry['permissions'] = $entry['config'] = $entry['update'] = true;
            $entry['delete'] = ($entry['id'] != $userId);
            $entry['login'] = (iaCore::STATUS_ACTIVE == $entry['status']);
        }
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $entryData['item'] = $this->_itemName;

        if (iaCore::ACTION_EDIT == $iaView->get('action')) {
            $adminsCount = $this->_iaDb->one_bind(iaDb::STMT_COUNT_ROWS,
                '`usergroup_id` = :group AND `status` = :status',
                ['group' => iaUsers::MEMBERSHIP_ADMINISTRATOR, 'status' => iaCore::STATUS_ACTIVE]);
            $iaView->assign('admin_count', $adminsCount);

            if (1 == $adminsCount && iaUsers::MEMBERSHIP_ADMINISTRATOR == $entryData['usergroup_id']) {
                unset($entryData['status']);
            }
        }

        $this->_iaCore->startHook('editItemSetSystemDefaults', ['item' => &$entryData]);

        $iaPlan = $this->_iaCore->factory('plan');
        $plans = $iaPlan->getPlans($this->_itemName);
        foreach ($plans as &$plan) {
            list(, $plan['defaultEndDate']) = $iaPlan->calculateDates($plan['duration'], $plan['unit']);
        }

        $iaField = $this->_iaCore->factory('field');

        $sections = $iaField->getGroups($this->_itemName);
        $iaField->unwrapItemValues($this->_itemName, $entryData);

        foreach ($sections[0]['fields'] as &$field) {
            if ('email_language' == $field['name']) {
                $field['type'] = iaField::RADIO;
                $field['default'] = iaLanguage::getMasterLanguage()->iso;
                $field['values'] = [];
                foreach ($this->_iaCore->languages as $iso => $language) {
                    $field['values'][$iso] = $language['title'];
                }
                break;
            }
        }

        unset($this->_userGroups[iaUsers::MEMBERSHIP_GUEST]);

        $iaView->assign('plans', $plans);
        $iaView->assign('item_sections', $sections);
        $iaView->assign('statuses', $this->getHelper()->getStatuses());
        $iaView->assign('usergroups', $this->_userGroups);
    }

    protected function _setDefaultValues(array &$entry)
    {
        if ($_POST) {
            $entry = $_POST;
        } else {
            $entry = [
                'featured' => false,
                'sponsored' => false,
                'sponsored_plan_id' => 0,
                'sponsored_end' => '',
                'status' => iaCore::STATUS_ACTIVE,
                'usergroup_id' => iaUsers::MEMBERSHIP_REGULAR
            ];
        }
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $this->_iaCore->startHook('adminAddMemberValidation');

        $iaAcl = $this->_iaCore->factory('acl');
        $iaField = $this->_iaCore->factory('field');

        // below is the hacky way to force the script to upload files to the appropriate user's folder
        // FIXME
        $activeUser = iaUsers::getIdentity(true);
        $_SESSION[iaUsers::SESSION_KEY] = ['id' => $this->getEntryId(), 'username' => $data['username']];
        list($entry, $error, $this->_messages) = $iaField->parsePost($this->_itemName, $entry);
        $_SESSION[iaUsers::SESSION_KEY] = $activeUser;
        //

        if ($iaAcl->isAccessible($this->getName(), 'usergroup')) {
            if (isset($data['usergroup_id'])) {
                $entry['usergroup_id'] = array_key_exists($data['usergroup_id'],
                    $this->_userGroups) ? $data['usergroup_id'] : iaUsers::MEMBERSHIP_REGULAR;
            }
        } elseif (iaCore::ACTION_ADD == $action) {
            $entry['usergroup_id'] = iaUsers::MEMBERSHIP_REGULAR;
        }

        if ($error) {
            return false;
        }

        $stmt = '`email` = :email';
        if (iaCore::ACTION_EDIT == $action) {
            if (isset($entry['status']) && $entry['status'] == $this->_iaDb->one('status',
                    iaDb::convertIds((int)$this->getEntryId()))
            ) {
                unset($entry['status']);
            }

            $stmt .= ' AND `id` != ' . (int)$this->getEntryId();
        }

        if ($this->_iaDb->exists($stmt, $entry)) {
            $this->addMessage('error_duplicate_email');
        }

        if ($this->_iaDb->exists('`username` = :username AND `id` != :id',
            ['username' => $entry['username'], 'id' => $this->getEntryId()])
        ) {
            $this->addMessage('username_already_taken');
        }

        if ($iaAcl->isAccessible($this->getName(), 'password') || iaCore::ACTION_ADD == $action) {
            $this->_password = trim($data['_password']);
            if ($this->_password || !empty($data['_password2'])) {
                $entry['password'] = $this->getHelper()->encodePassword($this->_password);

                iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

                if (empty($entry['password'])) {
                    $this->addMessage('error_password_empty');
                } elseif (!utf8_is_ascii($entry['password'])) {
                    $this->addMessage(iaLanguage::get('password') . ': ' . iaLanguage::get('ascii_required'));
                } elseif (!password_verify($data['_password2'], $entry['password'])) {
                    $this->addMessage('error_password_match');
                }
            }
        }

        if (empty($data['_password']) && iaCore::ACTION_ADD == $action) {
            $this->addMessage('error_password_empty');
        }

        return !$this->getMessages();
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->_iaCore->startHook('phpItemSaved', [
            'action' => $action,
            'itemId' => $this->getEntryId(),
            'itemData' => $entry,
            'itemName' => $this->_itemName
        ]);

        if (iaCore::ACTION_ADD == $action) {
            $iaMailer = $this->_iaCore->factory('mailer');

            if ($iaMailer->loadTemplate('member_registration_notification')) {
                $iaMailer->addAddressByMember($entry);
                $iaMailer->setReplacements([
                    'fullname' => $entry['fullname'],
                    'username' => $entry['username'],
                    'password' => $this->_password,
                    'email' => $entry['email']
                ]);

                $iaMailer->send();
            }

            if (iaUsers::MEMBERSHIP_ADMINISTRATOR == $entry['usergroup_id']) {
                $this->_phraseAddSuccess = 'administrator_added';
            }
        }

        $iaLog = $this->_iaCore->factory('log');

        $actionCode = (iaCore::ACTION_ADD == $action) ? iaLog::ACTION_CREATE : iaLog::ACTION_UPDATE;
        $params = [
            'item' => 'member',
            'name' => $entry['fullname'],
            'id' => $this->getEntryId()
        ];

        $iaLog->write($actionCode, $params);
    }

    protected function _entryAdd(array $entryData)
    {
        return $this->getHelper()->insert($entryData);
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        $result = $this->getHelper()->update($entryData, iaDb::convertIds($entryId),
            ['date_update' => iaDb::FUNCTION_NOW]);

        if ($result && $entryId == iaUsers::getIdentity()->id) {
            iaUsers::reloadIdentity();
        }

        return $result;
    }

    protected function _gridUpdate($params)
    {
        if (isset($params['id']) && is_array($params['id'])
            && 2 == count($params) && isset($params['status'])
        ) {
            $currentUserId = iaUsers::getIdentity()->id;
            if (in_array($currentUserId, $params['id'])) {
                $totalAdminsCount = (int)$this->_iaDb->one_bind(iaDb::STMT_COUNT_ROWS,
                    '`usergroup_id` = :group AND `status` = :status AND `id` != :id', [
                        'group' => iaUsers::MEMBERSHIP_ADMINISTRATOR,
                        'status' => iaCore::STATUS_ACTIVE,
                        'id' => $currentUserId
                    ]);

                if (0 == $totalAdminsCount && $params['status'] != iaCore::STATUS_ACTIVE) {
                    return [
                        'result' => false,
                        'message' => iaLanguage::get('action_not_allowed_since_you_only_admin')
                    ];
                }
            }
        }

        return parent::_gridUpdate($params);
    }

    private function _resendRegistrationEmail()
    {
        $output = ['message' => iaLanguage::get('invalid_params'), 'result' => false];

        if (isset($_POST['id']) && is_numeric($_POST['id'])) {
            $member = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($_POST['id']));

            if ($member && iaUsers::STATUS_UNCONFIRMED == $member['status']) {
                $password = $this->getHelper()->createPassword();
                $passwordHash = $this->getHelper()->encodePassword($password);

                if ($this->_iaDb->update(['password' => $passwordHash], iaDb::convertIds($member['id']))) {
                    $this->getHelper()->sendRegistrationEmail($member['id'], $password, $member);

                    $output['message'] = iaLanguage::get('registration_email_resent');
                    $output['result'] = true;
                }
            }
        }

        return $output;
    }
}
