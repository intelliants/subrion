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

class iaUsers extends abstractCore
{
    const SESSION_KEY = 'user';
    const SESSION_FAVORITES_KEY = 'favorites';

    const MEMBERSHIP_REGULAR = 8;
    const MEMBERSHIP_GUEST = 4;
    const MEMBERSHIP_MODERATOR = 2;
    const MEMBERSHIP_ADMINISTRATOR = 1;

    const STATUS_UNCONFIRMED = 'unconfirmed';
    const STATUS_SUSPENDED = 'suspended';

    const METHOD_NAME_GET_LISTINGS = 'fetchMemberListings';
    const METHOD_NAME_GET_FAVORITES = 'getFavorites';

    const AUTO_LOGIN_COOKIE_NAME = '_utcpl';
    const USE_OBSOLETE_AUTH = false;

    protected static $_table = 'members';
    protected static $_itemName = 'member';

    protected static $_usergroupTable = 'usergroups';
    protected static $_providersTable = 'members_auth_providers';

    public $dashboardStatistics = true;

    public $coreSearchOptions = [
        'regularSearchFields' => ['username', 'fullname']
    ];


    public static function getItemName()
    {
        return self::$_itemName;
    }

    public static function getUsergroupsTable()
    {
        return self::$_usergroupTable;
    }

    public static function getProvidersTable()
    {
        return self::$_providersTable;
    }

    public function getUrl(array $itemData)
    {
        return $this->url(null, $itemData);
    }

    public function url($action, array $listingData)
    {
        $patterns = [
            'edit' => 'profile/',
            'default' => 'member/:username.html',
        ];

        return IA_URL . iaDb::printf(isset($patterns[$action]) ? $patterns[$action] : $patterns['default'],
                [
                    'action' => $action,
                    'username' => $listingData['username']
                ]
            );
    }

    /* IDENTITY STORAGE MECH */
    // currently uses the standard PHP session

    /**
     * User authorization
     * Registers new user when authorizing via HybridAuth
     * Updates user details while logging via HybridAuth when email matches a registered user
     */
    public function authorize()
    {
        $this->iaCore->startHook('phpCoreBeforeAuth');

        $authorized = 0;

        if (isset($_POST['register'])) {
            $login = '';
        } elseif (isset($_POST['username'])) {
            $login = $_POST['username'];
            $authorized++;
        } else {
            $login = '';
        }

        if (isset($_POST['register'])) {
            $pass = '';
        } elseif (isset($_POST['password'])) {
            $pass = $_POST['password'];
            $authorized++;
        } else {
            $pass = '';
        }

        $isBackend = (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType());

        if (defined('IA_EXIT') && $authorized != 2) {
            // use this hook to logout
            $this->iaCore->startHook('phpUserLogout', ['userInfo' => iaUsers::getIdentity(true)]);

            if ($this->iaCore->get('hybrid_enabled')) {
                require_once IA_INCLUDES . 'hybrid/Auth.php';

                Hybrid_Auth::logoutAllProviders();
            }

            iaUsers::clearIdentity();

            if (strpos($_SERVER['HTTP_REFERER'], $this->iaView->domainUrl) === 0) {
                if ($isBackend) {
                    $_SESSION['IA_EXIT'] = true;
                }
                $url = $isBackend ? IA_ADMIN_URL : IA_URL;
                header('Location: ' . $url);
            } else {
                header('Location: ' . $this->iaView->domainUrl . ($isBackend ? $this->iaCore->get('admin_page') . IA_URL_DELIMITER : ''));
            }
            exit();
        } elseif ($authorized == 2 && $login && $pass) {
            $auth = (bool)$this->getAuth(null, $login, $pass, isset($_POST['remember']));

            $this->iaCore->startHook('phpUserLogin', ['userInfo' => iaUsers::getIdentity(true), 'password' => $pass]);

            if (!$auth) {
                if ($isBackend) {
                    $this->iaView->assign('error_login', true);
                } else {
                    $this->iaView->setMessages(iaLanguage::get('error_login'));
                    $this->iaView->name('login');
                }
            } else {
                if (isset($_SESSION['referrer'])) { // this variable is set by Login page handler
                    header('Location: ' . $_SESSION['referrer']);
                    unset($_SESSION['referrer']);
                    exit();
                } else {
                    if ($isBackend) {
                        $this->iaCore->factory('log')->write(iaLog::ACTION_LOGIN,
                            ['ip' => $this->iaCore->util()->getIp(false)]);
                    }
                }
            }
        } elseif (2 == $authorized) {
            if ($isBackend) {
                $this->iaView->assign('empty_login', true);
            } else {
                $this->iaView->setMessages(iaLanguage::get('empty_login'));
                $this->iaView->name('login');
            }
        } elseif (isset($_COOKIE[self::AUTO_LOGIN_COOKIE_NAME])) {
            $this->_checkAutoLoginCookie();
        }
    }

    public static function getAuthProviders()
    {
        if (!(iaCore::instance()->get('hybrid_enabled'))) {
            return false;
        }

        require_once IA_INCLUDES . 'hybrid/Auth.php';
        new Hybrid_Auth(IA_INCLUDES . 'hybridauth.inc.php');

        if (empty(Hybrid_Auth::$config["providers"])) {
            return false;
        }

        $output = [];
        foreach (Hybrid_Auth::$config["providers"] as $key => $provider) {
            if ($provider['enabled']) {
                $output[$key] = $provider;

                if ('Facebook' == $key) {
                    require_once IA_INCLUDES . 'hybrid/thirdparty/Facebook/autoload.php';
                }
            }
        }

        return $output;
    }

    /**
     * Checks if the current user is signed in as a member
     *
     * @return bool
     */
    public static function hasIdentity()
    {
        return (bool)(isset($_SESSION[self::SESSION_KEY]) && is_array($_SESSION[self::SESSION_KEY]));
    }

    /**
     * Returns the data associated with currently logged in user
     *
     * @param bool $plainArray true - return the result in array format
     *
     * @return object
     */
    public static function getIdentity($plainArray = false)
    {
        if (isset($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY]) {
            return $plainArray
                ? $_SESSION[self::SESSION_KEY]
                : (object)$_SESSION[self::SESSION_KEY];
        }

        return null;
    }

    /**
     * Drops the data assigned to currently logged user
     *
     * @return void
     */
    public static function clearIdentity()
    {
        unset($_COOKIE[self::AUTO_LOGIN_COOKIE_NAME]);
        setcookie(self::AUTO_LOGIN_COOKIE_NAME, '', -1, '/');

        self::_setIdentity(null);
    }

    public static function reloadIdentity()
    {
        $iaDb = iaCore::instance()->iaDb;

        $sql = <<<SQL
SELECT u.*, g.`name` `usergroup` 
  FROM `:prefix_:table_users` u 
LEFT JOIN `:prefix_:table_groups` g ON (g.`id` = u.`usergroup_id`) 
WHERE u.`id` = :id AND u.`status` = ':status' 
LIMIT 1
SQL;
        $sql = iaDb::printf($sql, [
            'prefix_' => $iaDb->prefix,
            'table_users' => self::getTable(),
            'table_groups' => self::getUsergroupsTable(),
            'id' => self::getIdentity()->id,
            'status' => iaCore::STATUS_ACTIVE
        ]);

        $row = $iaDb->getRow($sql);

        iaCore::instance()->factory('users')->_processValues($row, true);
        self::_setIdentity($row);

        return (bool)$row;
    }

    private static function _setIdentity($identityInfo)
    {
        $_SESSION[self::SESSION_KEY] = $identityInfo;
    }

    //

    public function insert(array $userData)
    {
        return $this->iaDb->insert($userData, ['date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW],
            self::getTable());
    }

    public function update($values, $condition = '', $rawValues = [])
    {
        $action = null;
        $action = (isset($values['status']) && iaCore::STATUS_ACTIVE == $values['status']) ? 'member_approved' : $action;
        $action = (isset($values['status']) && iaCore::STATUS_APPROVAL == $values['status']) ? 'member_disapproved' : $action;

        if ($action && $this->iaCore->get($action)) {
            $condition = $condition ? $condition : "`id`='{$values['id']}'";

            $iaMailer = $this->iaCore->factory('mailer');

            $iaMailer->loadTemplate($action);
            $body = $iaMailer->Body;

            $members = $this->iaDb->all(['email', 'fullname'], $condition);
            foreach ($members as $member) {
                $iaMailer->addAddress($member['email']);
                $iaMailer->Body = str_replace('{%FULLNAME%}', $member['fullname'], $body);

                $iaMailer->send();
            }
        }

        return $this->iaDb->update($values, $condition, $rawValues, self::getTable());
    }

    public function delete($statement = null)
    {
        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $statement, null, null, self::getTable());
        $result = $this->iaDb->delete($statement, self::getTable());

        if ($result) {
            $this->iaCore->factory('util');
            $iaMailer = $this->iaCore->factory('mailer');
            $iaLog = $this->iaCore->factory('log');

            $actionName = 'member_removal';
            $emailNotificationEnabled = $iaMailer->loadTemplate($actionName);

            foreach ($rows as $entry) {
                // delete associated auth providers
                $this->iaDb->delete(iaDb::convertIds($entry['id'], 'member_id'), self::$_providersTable);

                // delete member uploads folder
                $folder = IA_UPLOADS . iaUtil::getAccountDir($entry['username']);
                iaUtil::cascadeDeleteFiles($folder, true) && @rmdir($folder);

                $iaLog->write(iaLog::ACTION_DELETE,
                    ['item' => 'member', 'name' => $entry['fullname'], 'id' => $entry['id']]);

                $this->iaCore->startHook('phpUserDelete', ['userInfo' => $entry]);

                if ($emailNotificationEnabled) {
                    $iaMailer->addAddressByMember($entry);
                    $iaMailer->setReplacements('fullname', $entry['fullname']);

                    $iaMailer->send();
                }
            }
        }

        return $result;
    }

    /**
     * Updates member password and sends email
     *
     * @param array $memberInfo member information
     * @param string $password new password, auto generated if none is passed
     * @param bool $isNotify send notification email on change
     *
     * @return bool
     */
    public function changePassword($memberInfo, $password = '', $isNotify = true)
    {
        // generate password if no password is passed
        if (!$password) {
            $password = $this->createPassword();
        }

        $result = $this->iaDb->update([
            'password' => $this->encodePassword($password),
            'sec_key' => ''
        ], iaDb::convertIds($memberInfo['id']), null, self::getTable());

        if ($result) {
            $this->iaCore->startHook('phpUserPasswordUpdate', ['userInfo' => $memberInfo, 'password' => $password]);
        }

        if ($isNotify) {
            $iaMailer = $this->iaCore->factory('mailer');

            $iaMailer->loadTemplate('password_changement');
            $iaMailer->addAddressByMember($memberInfo);
            $iaMailer->setReplacements([
                'fullname' => $memberInfo['fullname'],
                'username' => $memberInfo['username'],
                'password' => $password
            ]);

            $iaMailer->send();
        }

        return $result;
    }

    /**
     * Inserts new member and sends email
     *
     * @param array $memberInfo member information
     *
     * @return bool|int
     */
    public function register(array $memberInfo)
    {
        $memberInfo['password'] = (isset($memberInfo['disable_fields']) && $memberInfo['disable_fields'])
            ? $this->createPassword()
            : $memberInfo['password'];

        $socialProvider = null;
        if (isset($memberInfo['social_provider'])) {
            $socialProvider = $memberInfo['social_provider'];
            unset($memberInfo['social_provider']);
        }

        unset($memberInfo['disable_fields']);

        $password = $memberInfo['password'];

        $memberInfo['usergroup_id'] = self::MEMBERSHIP_REGULAR;
        $memberInfo['sec_key'] = md5($this->createPassword());
        $memberInfo['status'] = self::STATUS_UNCONFIRMED;
        $memberInfo['password'] = $this->encodePassword($password);
        $memberInfo['email_language'] = $this->iaCore->language['iso'];

        // according to DB table scheme we have to ensure that username field will contain unique data
        if (empty($memberInfo['username'])) {
            $memberInfo['username'] = $this->_generateUserName($memberInfo);
        }

        $this->iaCore->startHook('phpUserPreRegister', ['member' => &$memberInfo, 'password' => $password]);

        $this->iaDb->setTable(self::getTable());

        $memberId = $this->iaDb->one(iaDb::ID_COLUMN_SELECTION, iaDb::convertIds($memberInfo['email'], 'email'));

        if (empty($memberId)) {
            $memberId = $this->iaDb->insert($memberInfo,
                ['date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW]);

            if ($memberId) {
                $this->iaCore->startHook('memberAddEmailSubmission', ['member' => $memberInfo]);

                // send email to a registered member
                $this->sendRegistrationEmail($memberId, $password, $memberInfo, $socialProvider);
            }
        }

        $this->iaDb->resetTable();

        $this->iaCore->startHook('phpUserRegister', ['userInfo' => $memberInfo, 'password' => $password]);

        return $memberId;
    }

    public function sendRegistrationEmail($id, $password, array $memberInfo, $socialProvider = null)
    {
        $iaMailer = $this->iaCore->factory('mailer');

        $action = is_null($socialProvider)
            ? 'member_registration'
            : 'member_registration_social';

        if ($iaMailer->loadTemplate($action) && $memberInfo['email']) {
            $iaMailer->addAddressByMember($memberInfo);
            $iaMailer->setReplacements([
                'fullname' => $memberInfo['fullname'],
                'username' => $memberInfo['username'],
                'email' => $memberInfo['email'],
                'password' => $password,
                'link' => IA_URL . 'confirm/?email=' . $memberInfo['email'] . '&key=' . $memberInfo['sec_key']
            ]);

            if ($socialProvider) {
                $iaMailer->setReplacements('provider', $socialProvider);
            }

            $iaMailer->send();
        }

        $action = 'member_registration_admin';
        if ($iaMailer->loadTemplate($action) && $memberInfo['email']) {
            $iaMailer->setReplacements([
                'id' => $id,
                'username' => $memberInfo['username'],
                'fullname' => $memberInfo['fullname'],
                'email' => $memberInfo['email'],
                'password' => $password
            ]);

            $iaMailer->sendToAdministrators();
        }
    }

    private function _generateUserName(array $memberInfo)
    {
        $email = $memberInfo['email'];

        // here we can be pretty sure that email contains @
        $result = substr($email, 0, strpos($email, '@'));
        if ($this->getInfo($result, 'username')) {
            $this->iaCore->factory('util');
            $result = $result . '_' . iaUtil::generateToken(5);
        }

        return $result;
    }

    public function createPassword($length = 7)
    {
        $chars = 'abcdefghijkmnopqrstuvwxyz023456789';
        $password = '';
        srand((double)microtime() * 1000000);

        for ($i = 0; $i < $length; $i++) {
            $num = rand() % 33;
            $password .= $chars[$num];
        }

        return $password;
    }

    public function sendPasswordResetEmail(array $member)
    {
        if (empty($member['email']) || empty($member['id']) || empty($member['fullname'])) {
            return false;
        }

        $email = $member['email'];
        $token = $this->iaCore->factory('util')->generateToken();

        $this->iaDb->update(['id' => $member['id'], 'sec_key' => $token], null, null, self::getTable());

        $confirmationUrl = IA_URL . "forgot/?email={$email}&code={$token}";

        $iaMailer = $this->iaCore->factory('mailer');

        $iaMailer->loadTemplate('password_restoration');
        $iaMailer->addAddressByMember($member);
        $iaMailer->setReplacements([
            'fullname' => $member['fullname'],
            'url' => $confirmationUrl,
            'code' => $token,
            'email' => $email
        ]);

        return $iaMailer->send();
    }

    public function confirmation($email, $key)
    {
        $status = $this->iaCore->get('members_autoapproval') ? iaCore::STATUS_ACTIVE : iaCore::STATUS_APPROVAL;

        $stmt = '`email` = :email AND `sec_key` = :key';
        $this->iaDb->bind($stmt, ['email' => $email, 'key' => $key]);

        $result = (bool)$this->iaDb->update(['sec_key' => '', 'status' => $status], $stmt,
            ['date_update' => iaDb::FUNCTION_NOW], self::getTable());
        if ($result) {
            $member = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($email, 'email'), self::getTable());
            $iaPlan = $this->iaCore->factory('plan');
            if ($member['sponsored_plan_id']) {
                $plan = $iaPlan->getById($member['sponsored_plan_id']);
                if ($plan['id'] && empty(floatval($plan['cost']))) {
                    $iaPlan->assignFreePlan($plan['id'], self::getItemName(), $member);
                }
            }
        }

        return $result;
    }

    public function getById($id)
    {
        return $this->getInfo($id);
    }

    public function getInfo($id, $key = 'id')
    {
        in_array($key, ['id', 'username', 'email', 'sec_key']) || $key = 'id';

        $row = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`' . $key . '` = :id AND `status` = :status',
            ['id' => $id, 'status' => iaCore::STATUS_ACTIVE], self::getTable());

        $this->_processValues($row, true);

        return $row;
    }

    public function getAuth($userId = null, $usernameOrEmail = null, $password = null, $remember = false)
    {
        if (!is_null($userId)) {
            $condition = sprintf('u.`id` = %d', $userId);
        } else {
            $condition = '(u.`username` = :username OR u.`email` = :email)' .
                (self::USE_OBSOLETE_AUTH ? ' AND u.`password` = :password' : '');
            $this->iaDb->bind($condition, [
                'username' => preg_replace('/[^a-zA-Z0-9.@_-]/', '', $usernameOrEmail),
                'email' => $usernameOrEmail,
                'password' => $this->encodePassword($password)
            ]);
        }

        $sql = <<<SQL
SELECT u.*, g.`name` `usergroup` 
	FROM `:prefix_:table_users` u 
LEFT JOIN `:prefix_:table_groups` g ON (g.`id` = u.`usergroup_id`) 
WHERE :condition 
LIMIT 1
SQL;
        $sql = iaDb::printf($sql, [
            'prefix_' => $this->iaDb->prefix,
            'table_users' => self::getTable(),
            'table_groups' => self::getUsergroupsTable(),
            'condition' => $condition
        ]);

        $row = $this->iaDb->getRow($sql);

        if (!$row
            || iaCore::STATUS_ACTIVE != $row['status']
            || (!self::USE_OBSOLETE_AUTH && $password && !password_verify($password, $row['password']))) {
            return false;
        }

        $this->_processValues($row, true);

        self::_setIdentity($row);

        $this->iaDb->update(null, iaDb::convertIds($row['id']), ['date_logged' => iaDb::FUNCTION_NOW], self::getTable());

        $this->_assignItem($row, $remember);
        $this->_assignFavorites();

        return $row;
    }

    public function registerVisitor()
    {
        $entryData = ['status' => iaCore::STATUS_ACTIVE, 'page' => IA_SELF, 'date' => date(iaDb::DATETIME_FORMAT)];

        if (self::hasIdentity()) {
            $entryData['username'] = self::getIdentity()->username;
            $entryData['fullname'] = self::getIdentity()->fullname;
        } else {
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                $signatures = ['bot', 'spider', 'crawler', 'wget', 'curl', 'validator'];
                foreach ($signatures as $signature) {
                    if (stripos($_SERVER['HTTP_USER_AGENT'], $signature) !== false) {
                        $entryData['is_bot'] = 1;
                        $entryData['fullname'] = $_SERVER['HTTP_USER_AGENT'];
                        break;
                    }
                }
            }
        }

        $sessionId = session_id();

        $iaDb = &$this->iaCore->iaDb;
        $iaDb->setTable('online');

        if ((int)$iaDb->one(iaDb::STMT_COUNT_ROWS, iaDb::convertIds($sessionId, 'session_id'))) {
            $iaDb->update($entryData, "`session_id` = '$sessionId'");
        } else {
            $entryData['session_id'] = $sessionId;
            $entryData['ip'] = iaUtil::getIp();

            $iaDb->insert($entryData);
        }

        $iaDb->resetTable();
    }

    protected function _assignItem($memberData, $remember)
    {
        if ($salt = $this->_getSalt()) {
            foreach ($salt['items'] as $item) {
                $values = ['salt' => '', 'member_id' => $memberData['id']];
                $this->iaDb->update($values, iaDb::convertIds($salt['salt'], 'salt'), null,
                    iaSanitize::paranoid($item));
            }
        }

        setcookie('salt', '', time() - 3600, '/');
        empty($remember) || $this->_setAutoLoginCookie($memberData);
    }

    private function _setAutoLoginCookie(array $member)
    {
        $time = time() + (60 * 60 * 24 * 30);
        $value = $this->_autoLoginValue($member['id']) . 's' . $member['id'];

        setcookie(self::AUTO_LOGIN_COOKIE_NAME, $value, $time, '/');
    }

    private function _checkAutoLoginCookie()
    {
        $array = explode('s', $_COOKIE[self::AUTO_LOGIN_COOKIE_NAME]);

        if (2 == count($array) && $array[0] == $this->_autoLoginValue($array[1])) {
            $this->getAuth($array[1]);
        }
    }

    private function _autoLoginValue($id)
    {
        return md5($_SERVER['HTTP_USER_AGENT'] . '_' . IA_SALT . '_' . $id);
    }

    protected function _getSalt()
    {
        $salt = [];

        if (isset($_COOKIE['salt']) && $_COOKIE['salt']) {
            $s = json_decode($_COOKIE['salt'], true);
            if (isset($s['salt']) && isset($s['items']) && $s['salt'] && $s['items']) {
                $salt = $s;
            }
        }

        return $salt;
    }

    protected function _assignFavorites()
    {
        if (!isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY]) || empty($_SESSION[iaUsers::SESSION_FAVORITES_KEY])) {
            return;
        }

        $iaItem = $this->iaCore->factory('item');
        foreach ($_SESSION[iaUsers::SESSION_FAVORITES_KEY] as $item => $items) {
            if (!$items['items']) {
                continue;
            }

            foreach ($items['items'] as $row) {
                $this->iaDb->replace([
                    'id' => $row['id'],
                    'member_id' => iaUsers::getIdentity()->id,
                    'item' => $item
                ], null, $iaItem->getFavoritesTable());
            }
        }

        return true;
    }

    public function encodePassword($rawPassword)
    {
        if (self::USE_OBSOLETE_AUTH) {
            $factors = ['iaSubrion', 'Y2h1c2hrYW4tc3R5bGU', 'onfr64_qrpbqr'];

            $password = $factors && array_reverse($factors);
            $password = array_map(str_rot13($factors[2]), [$factors[1] . chr(0x3d)]);
            $password = md5(IA_SALT . substr(reset($password), -15) . $rawPassword);
        } else {
            $password = password_hash($rawPassword, PASSWORD_BCRYPT);
        }

        return $password;
    }

    public function getUsergroups($visible = false)
    {
        $stmt = $visible ? iaDb::convertIds('1', 'visible') : iaDb::EMPTY_CONDITION;

        return $this->iaDb->keyvalue(['id', 'name'], $stmt . ' ORDER BY `order`', self::getUsergroupsTable());
    }

    public function getUsergroupByName($name)
    {
        return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($name, 'name'), $this->getUsergroupsTable());
    }

    public function getDashboardStatistics()
    {
        // bars composition
        $data = [];
        $weekDay = getdate();
        $weekDay = $weekDay['wday'];
        $rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date_reg`)), COUNT(*)',
            'DATE(`date_reg`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date_reg`)',
            self::getTable());
        for ($i = 1; $i < 8; $i++) {
            $data[$i] = isset($rows[$i]) ? $rows[$i] : 0;
        }

        // statuses grid
        $rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`', self::getTable());
        $statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, self::STATUS_UNCONFIRMED, self::STATUS_SUSPENDED];
        $total = 0;

        foreach ($statuses as $status) {
            isset($rows[$status]) || $rows[$status] = 0;
            $total += $rows[$status];
        }

        return [
            '_format' => 'medium',
            'data' => [
                //'type' => $total > 1 ? 'pie' : 'bar',
                'array' => implode(',', $data)
            ],
            'icon' => 'user-2',
            'item' => iaLanguage::get('total_members'),
            'rows' => $rows,
            'total' => $total,
            'url' => 'members/'
        ];
    }

    public function getVisitorsInfo()
    {
        if ($rows = $this->iaDb->all("`username`, IF(`fullname` != '', `fullname`, `username`) `fullname`, `page`, `ip`",
            "`username` != '' AND `status` = 'active' GROUP BY `username`", null, null, 'online')
        ) {
            foreach ($rows as &$row) {
                $row['link'] = $this->url('view', $row);
            }
        }

        return $rows;
    }

    // this called by core when paid plan cancelled
    public function planCancelling($itemId)
    {
        self::reloadIdentity();
    }

    // called by core when subscription has been paid
    public function postPayment($plan, $transaction)
    {
        if (isset($plan['usergroup']) && $plan['usergroup'] > 0) {
            $this->iaDb->update(['usergroup_id' => $plan['usergroup'], 'id' => $transaction['item_id']], null, null,
                self::getTable());
        }

        self::reloadIdentity();
    }

    public function getStatuses()
    {
        return [iaCore::STATUS_APPROVAL, iaCore::STATUS_ACTIVE, self::STATUS_UNCONFIRMED, self::STATUS_SUSPENDED];
    }

    public function deleteUsergroup($entryId)
    {
        $this->iaDb->setTable(iaUsers::getUsergroupsTable());

        $usergroup = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($entryId));

        $result = $this->iaDb->delete(iaDb::convertIds($entryId));

        if ($result) {
            // delete language records
            iaLanguage::delete('usergroup_' . $usergroup['name']);

            $this->iaDb->delete('`type` = :type AND `type_id` = :id', 'acl_privileges',
                ['type' => 'group', 'id' => $entryId]); // TODO: use the class method for this
            $this->iaDb->update(['usergroup_id' => iaUsers::MEMBERSHIP_REGULAR],
                iaDb::convertIds((int)$entryId, 'usergroup_id'), null, iaUsers::getTable());
        }
        $this->iaDb->resetTable();

        return $result;
    }

    /**
     * Increments the number of views for a specified item
     *
     * Application should ensure if an item is in active status
     * and provide appropriate DB column name if differs from "views_num"
     */
    public function incrementViewsCounter($itemId, $columnName = 'views_num')
    {
        $viewsTable = 'views_log';

        $itemName = $this->getItemName();
        $ipAddress = $this->iaCore->util()->getIp();
        $date = date(iaDb::DATE_FORMAT);

        if ($this->iaDb->exists('`item` = :item AND `item_id` = :id AND `ip` = :ip AND `date` = :date',
            ['item' => $itemName, 'id' => $itemId, 'ip' => $ipAddress, 'date' => $date], $viewsTable)
        ) {
            return false;
        }

        $this->iaDb->insert(['item' => $itemName, 'item_id' => $itemId, 'ip' => $ipAddress, 'date' => $date], null,
            $viewsTable);
        $result = $this->iaDb->update(null, iaDb::convertIds($itemId), [$columnName => '`' . $columnName . '` + 1'],
            self::getTable());

        return (bool)$result;
    }

    public function coreSearch($stmt, $start, $limit, $order)
    {
        if (!$this->iaCore->get('members_enabled')) {
            return false;
        }

        $visibleUsergroups = $this->getUsergroups(true);
        $visibleUsergroups = array_keys($visibleUsergroups);

        $stmt .= ' AND `usergroup_id` IN(' . implode(',', $visibleUsergroups) . ')';
        empty($order) || $stmt .= ' ORDER BY ' . $order;

        $rows = $this->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' ' . iaDb::ALL_COLUMNS_SELECTION, $stmt, $start, $limit,
            self::getTable());
        $count = $this->iaDb->foundRows();

        $this->_processValues($rows);

        return [$count, $rows];
    }

    public function hybridAuth($providerId)
    {
        if (!$this->iaCore->get('hybrid_enabled')) {
            throw new Exception('HybridAuth is not enabled.');
        }

        $providerName = strtolower($providerId);
        $configFile = IA_INCLUDES . 'hybridauth.inc.php';

        require_once IA_INCLUDES . 'hybrid/Auth.php';

        if (!file_exists($configFile)) {
            throw new Exception('No HybridAuth config file. Please configure provider adapters.');
        }

        $hybridauth = new Hybrid_Auth($configFile);

        if (empty(Hybrid_Auth::$config['providers'])) {
            throw new Exception('No auth adapters configured.');
        }

        $provider = $hybridauth->authenticate(ucfirst($providerName));

        if ($user_profile = $provider->getUserProfile()) {
            if (empty($user_profile->email)) {
                throw new Exception('Email is not given by provider');
            }
            // identify by Hybrid identifier
            $memberId = $this->iaDb->one('member_id', iaDb::convertIds($user_profile->identifier, 'value'),
                self::getProvidersTable());

            // identify by email address
            if (!$memberId) {
                if ($memberInfo = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, "`email` = :email_address",
                    ['email_address' => $user_profile->email], self::getTable())
                ) {
                    $this->iaDb->insert([
                        'member_id' => $memberInfo['id'],
                        'name' => $providerName,
                        'value' => $user_profile->identifier
                    ], null, self::getProvidersTable());

                    $memberId = $memberInfo['id'];
                }
            }

            // register new member if no matches
            if (!$memberId) {
                $memberRegInfo['username'] = '';
                $memberRegInfo['email'] = $user_profile->email;
                $memberRegInfo['fullname'] = $user_profile->displayName;
                // $memberRegInfo['avatar'] = $user_profile->photoURL;
                $memberRegInfo['disable_fields'] = true;
                $memberRegInfo['social_provider'] = $providerId;

                $memberId = $this->register($memberRegInfo);

                // add providers match
                $this->iaDb->insert([
                    'member_id' => $memberId,
                    'name' => $providerName,
                    'value' => $user_profile->identifier
                ], null, iaUsers::getProvidersTable());

                // no need to validate address
                $this->update(['id' => $memberId, 'sec_key' => '', 'status' => iaCore::STATUS_ACTIVE]);
            }

            // authorize
            $this->getAuth($memberId);
        } else {
            throw new Exception('User is not logged in.');
        }
    }

    /**
     * Used to process member fields
     *
     * @param array $rows items array
     * @param boolean $singleRow true when item is passed as one row
     * @param array $fieldNames list of custom serialized fields
     */
    protected function _processValues(&$rows, $singleRow = false, $fieldNames = [])
    {
        if (!$rows) {
            return;
        }

        $iaField = $this->iaCore->factory('field');
        $iaItem = $this->iaCore->factory('item');

        $serializedFields = array_merge($fieldNames, $iaField->getSerializedFields($this->getItemName()));
        $multilingualFields = $iaField->getMultilingualFields($this->getItemName());

        $singleRow && $rows = [$rows];

        $rows = $iaItem->updateItemsFavorites($rows, $this->getItemName());

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                break;
            }

            $iaField->filter($this->getItemName(), $row);

            foreach ($serializedFields as $fieldName) {
                if (isset($row[$fieldName])) {
                    $row[$fieldName] = $row[$fieldName] ? unserialize($row[$fieldName]) : [];
                }
            }

            $currentLangCode = $this->iaCore->language['iso'];
            foreach ($multilingualFields as $fieldName) {
                if (isset($row[$fieldName . '_' . $currentLangCode]) && !isset($row[$fieldName])) {
                    $row[$fieldName] = $row[$fieldName . '_' . $currentLangCode];
                }
            }

            $row['item'] = self::getItemName();
            $row['link'] = $this->url('view', $row);
        }

        $singleRow && $rows = array_shift($rows);
    }

    /**
     * Returns listings for Favorites page
     *
     * @param $ids
     *
     * @return mixed
     */
    public function getFavorites($ids)
    {
        $where = iaDb::printf("`id` IN (:ids) AND `status` = ':status'",
            ['ids' => implode(',', $ids), 'status' => iaCore::STATUS_ACTIVE]);

        return $this->coreSearch($where, 0, 50, null)[1];
    }
}
