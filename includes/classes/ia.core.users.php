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

	protected static $_table = 'members';
	protected static $_itemName = 'members';

	protected static $_usergroupTable = 'usergroups';
	protected static $_providersTable = 'members_auth_providers';

	public $dashboardStatistics = true;

	public $coreSearchOptions = array(
		'regularSearchStatements' => array("`username` LIKE '%:query%' OR `fullname` LIKE '%:query%'")
	);


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

		if (isset($_POST['register']))
		{
			$login = '';
		}
		elseif (isset($_POST['username']))
		{
			$login = $_POST['username'];
			$authorized++;
		}
		else
		{
			$login = '';
		}

		if (isset($_POST['register']))
		{
			$pass = '';
		}
		elseif (isset($_POST['password']))
		{
			$pass = $_POST['password'];
			$authorized++;
		}
		else
		{
			$pass = '';
		}

		$isBackend = (iaCore::ACCESS_ADMIN == $this->iaCore->getAccessType());

		if (IA_EXIT && $authorized != 2)
		{
			// use this hook to logout
			$this->iaCore->startHook('phpUserLogout', array('userInfo' => iaUsers::getIdentity(true)));

			if ($this->iaCore->get('hybrid_enabled'))
			{
				require_once IA_INCLUDES . 'hybrid/Auth.php';

				Hybrid_Auth::logoutAllProviders();
			}

			iaUsers::clearIdentity();

			unset($_SESSION['_achkych']);
			if (strpos($_SERVER['HTTP_REFERER'], $this->iaView->domainUrl) === 0)
			{
				if ($isBackend)
				{
					$_SESSION['IA_EXIT'] = true;
				}
				$url = $isBackend ? IA_ADMIN_URL : IA_URL;
				header('Location: ' . $url);
			}
			else
			{
				header('Location: ' . $this->iaView->domainUrl . ($isBackend ? $this->iaCore->get('admin_page') . IA_URL_DELIMITER : ''));
			}
			exit();
		}
		elseif ($authorized == 2 && $login && $pass)
		{
			$auth = (bool)$this->getAuth(null, $login, $pass, isset($_POST['remember']));

			$this->iaCore->startHook('phpUserLogin', array('userInfo' => iaUsers::getIdentity(true), 'password' => $pass));

			if (!$auth)
			{
				if ($isBackend)
				{
					$this->iaView->assign('error_login', true);
				}
				else
				{
					$this->iaView->setMessages(iaLanguage::get('error_login'));
					$this->iaView->name('login');
				}
			}
			else
			{
				unset($_SESSION['_achkych']);
				if (isset($_SESSION['referrer'])) // this variable is set by Login page handler
				{
					header('Location: ' . $_SESSION['referrer']);
					unset($_SESSION['referrer']);
					exit();
				}
				else
				{
					if ($isBackend)
					{
						$this->iaCore->factory('log')->write(iaLog::ACTION_LOGIN, array('ip' => $this->iaCore->util()->getIp(false)));
					}
				}
			}
		}
		elseif (2 == $authorized)
		{
			if ($isBackend)
			{
				$this->iaView->assign('empty_login', true);
			}
			else
			{
				$this->iaView->setMessages(iaLanguage::get('empty_login'));
				$this->iaView->name('login');
			}
		}
		elseif (isset($_COOKIE[self::AUTO_LOGIN_COOKIE_NAME]))
		{
			$this->_checkAutoLoginCookie();
		}

		$this->iaCore->getSecurityToken() || $_SESSION[iaCore::SECURITY_TOKEN_MEMORY_KEY] = $this->iaCore->factory('util')->generateToken(92);
	}

	public static function getAuthProviders()
	{
		if (!(iaCore::instance()->get('hybrid_enabled')))
		{
			return false;
		}

		require_once IA_INCLUDES . 'hybrid/Auth.php';
		new Hybrid_Auth(IA_INCLUDES . 'hybridauth.inc.php');

		if (empty(Hybrid_Auth::$config["providers"]))
		{
			return false;
		}

		$output = array();
		foreach (Hybrid_Auth::$config["providers"] as $key => $provider)
		{
			if ($provider['enabled'])
			{
				$output[$key] = $provider;
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
		if (isset($_SESSION[self::SESSION_KEY]) && $_SESSION[self::SESSION_KEY])
		{
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
		$sql =
			'SELECT u.*, g.`name` `usergroup` ' .
			'FROM `:prefix_:table_users` u ' .
			'LEFT JOIN `:prefix_:table_groups` g ON (g.`id` = u.`usergroup_id`) ' .
			"WHERE u.`id` = :id AND u.`status` = ':status' " .
			'LIMIT 1';

		$iaDb = iaCore::instance()->iaDb;
		$sql = iaDb::printf($sql, array(
			'prefix_' => $iaDb->prefix,
			'table_users' => self::getTable(),
			'table_groups' => self::getUsergroupsTable(),
			'id' => self::getIdentity()->id,
			'status' => iaCore::STATUS_ACTIVE
		));

		$row = $iaDb->getRow($sql);

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
		return $this->iaDb->insert($userData, array('date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW), self::getTable());
	}

	public function update($values, $condition = '', $rawValues = array())
	{
		$action = null;
		$action = (isset($values['status']) && iaCore::STATUS_ACTIVE == $values['status']) ? 'member_approved' : $action;
		$action = (isset($values['status']) && iaCore::STATUS_APPROVAL == $values['status']) ? 'member_disapproved' : $action;

		if ($action && $this->iaCore->get($action))
		{
			$condition = $condition ? $condition : "`id`='{$values['id']}'";

			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate($action);
			$body = $iaMailer->Body;

			$members = $this->iaDb->all(array('email', 'fullname'), $condition);
			foreach ($members as $member)
			{
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

		if ($result)
		{
			$actionName = 'member_removal';
			$emailNotificationEnabled = $this->iaCore->get($actionName);

			$iaMailer = $this->iaCore->factory('mailer');
			$iaLog = $this->iaCore->factory('log');

			foreach ($rows as $entry)
			{
				// delete associated auth providers
				$this->iaDb->delete(iaDb::convertIds($entry['id'], 'member_id'), self::$_providersTable);

				// delete member uploads folder
				$folder = IA_UPLOADS . iaUtil::getAccountDir($entry['username']);
				iaUtil::cascadeDeleteFiles($folder, true) && @rmdir($folder);

				$iaLog->write(iaLog::ACTION_DELETE, array('item' => 'member', 'name' => $entry['fullname'], 'id' => $entry['id']));

				$this->iaCore->startHook('phpUserDelete', array('userInfo' => $entry));

				if ($emailNotificationEnabled)
				{
					$iaMailer->loadTemplate($actionName);
					$iaMailer->addAddress($entry['email'], $entry['fullname']);
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
		if (!$password)
		{
			$password = $this->createPassword();
		}

		$result = $this->iaDb->update(array(
			'password' => $this->encodePassword($password),
			'sec_key' => ''
		), iaDb::convertIds($memberInfo['id']), null, self::getTable());

		if ($result)
		{
			$this->iaCore->startHook('phpUserPasswordUpdate', array('userInfo' => $memberInfo, 'password' => $password));
		}

		if ($isNotify)
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate('password_changement');
			$iaMailer->addAddress($memberInfo['email'], $memberInfo['fullname']);
			$iaMailer->setReplacements(array(
				'fullname' => $memberInfo['fullname'],
				'username' => $memberInfo['username'],
				'password' => $password
			));

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

		unset($memberInfo['disable_fields']);

		$password = $memberInfo['password'];

		$memberInfo['usergroup_id'] = self::MEMBERSHIP_REGULAR;
		$memberInfo['sec_key'] = md5($this->createPassword());
		$memberInfo['status'] = self::STATUS_UNCONFIRMED;
		$memberInfo['password'] = $this->encodePassword($password);

		// according to DB table scheme we have to ensure that username field will contain unique data
		if (empty($memberInfo['username']))
		{
			$memberInfo['username'] = $this->_generateUserName($memberInfo);
		}

		$this->iaCore->startHook('phpUserPreRegister', array('member' => &$memberInfo, 'password' => $password));

		$this->iaDb->setTable(self::getTable());
		$memberId = $this->iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`username` = :username', array('username' => $memberInfo['username']));
		if (empty($memberId))
		{
			$memberId = $this->iaDb->insert($memberInfo, array('date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW));

			if ($memberId)
			{
				$this->iaCore->startHook('memberAddEmailSubmission', array('member' => $memberInfo));

				// send email to a registered member
				$this->sendRegistrationEmail($memberId, $password, $memberInfo);
			}
		}
		$this->iaDb->resetTable();

		$this->iaCore->startHook('phpUserRegister', array('userInfo' => $memberInfo, 'password' => $password));

		return $memberId;
	}

	public function sendRegistrationEmail($id, $password, array $memberInfo)
	{
		$iaMailer = $this->iaCore->factory('mailer');

		$action = 'member_registration';
		if ($this->iaCore->get($action) && $memberInfo['email'])
		{
			$iaMailer->loadTemplate($action);
			$iaMailer->addAddress($memberInfo['email']);
			$iaMailer->setReplacements(array(
				'fullname' => $memberInfo['fullname'],
				'username' => $memberInfo['username'],
				'email' => $memberInfo['email'],
				'password' => $password,
				'link' => IA_URL . 'confirm/?email=' . $memberInfo['email'] . '&key=' . $memberInfo['sec_key']
			));

			$iaMailer->send(true);
		}

		$action = 'member_registration_admin';
		if ($this->iaCore->get($action) && $memberInfo['email'])
		{
			$iaMailer->loadTemplate($action);
			$iaMailer->setReplacements(array(
				'id' => $id,
				'username' => $memberInfo['username'],
				'fullname' => $memberInfo['fullname'],
				'email' => $memberInfo['email'],
				'password' => $password
			));

			$iaMailer->sendToAdministrators();
		}
	}

	private function _generateUserName(array $memberInfo)
	{
		$email = $memberInfo['email'];

		// here we can be pretty sure that email contains @
		$result = substr($email, 0, strpos($email, '@'));
		if ($this->getInfo($result, 'username'))
		{
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

		for ($i = 0; $i < $length; $i++)
		{
			$num = rand() % 33;
			$password .= $chars[$num];
		}

		return $password;
	}

	public function confirmation($email, $key)
	{
		$status = $this->iaCore->get('members_autoapproval') ? iaCore::STATUS_ACTIVE : iaCore::STATUS_APPROVAL;

		$stmt = '`email` = :email AND `sec_key` = :key';
		$this->iaDb->bind($stmt, array('email' => $email, 'key' => $key));

		return (bool)$this->iaDb->update(array('sec_key' => '', 'status' => $status), $stmt, array('date_update' => iaDb::FUNCTION_NOW), self::getTable());
	}

	public function getById($id)
	{
		return $this->getInfo($id);
	}

	public function getInfo($id, $key = 'id')
	{
		if ($key != 'id' && $key != 'username' && $key != 'email')
		{
			$key = 'id';
		}

		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`' . $key . '` = :id AND `status` = :status', array('id' => $id, 'status' => iaCore::STATUS_ACTIVE), self::getTable());
	}

	public function getAuth($userId, $user = null, $password = null, $remember = false)
	{
		$sql =
			'SELECT u.*, g.`name` `usergroup` ' .
			'FROM `:prefix_:table_users` u ' .
			'LEFT JOIN `:prefix_:table_groups` g ON (g.`id` = u.`usergroup_id`) ' .
			'WHERE :condition ' .
			'LIMIT 1';

		if ((int)$userId)
		{
			$condition = sprintf('u.`id` = %d', $userId);
		}
		else
		{
			$condition = '(u.`username` = :username OR u.`email` = :email) AND u.`password` = :password';
			$this->iaDb->bind($condition, array(
				'username' => preg_replace('/[^a-zA-Z0-9.@_-]/', '', $user),
				'email' => $user,
				'password' => $this->encodePassword($password)
			));
		}

		$sql = iaDb::printf($sql, array(
			'prefix_' => $this->iaDb->prefix,
			'table_users' => self::getTable(),
			'table_groups' => self::getUsergroupsTable(),
			'condition' => $condition
		));
		$row = $this->iaDb->getRow($sql);

		if (iaCore::STATUS_ACTIVE == $row['status'])
		{
			self::_setIdentity($row);

			$this->iaDb->update(null, iaDb::convertIds($row['id']), array('date_logged' => iaDb::FUNCTION_NOW), self::getTable());

			$this->_assignItem($row, $remember);

			$this->_assignFavorites();

			return $row;
		}

		return false;
	}

	public function registerVisitor()
	{
		$entryData = array('status' => iaCore::STATUS_ACTIVE, 'page' => IA_SELF, 'date' => date(iaDb::DATETIME_FORMAT));

		if (self::hasIdentity())
		{
			$entryData['username'] = self::getIdentity()->username;
			$entryData['fullname'] = self::getIdentity()->fullname;
		}
		else
		{
			if (isset($_SERVER['HTTP_USER_AGENT']))
			{
				$signatures = array('bot', 'spider', 'crawler', 'wget', 'curl', 'validator');
				foreach ($signatures as $signature)
				{
					if (stripos($_SERVER['HTTP_USER_AGENT'], $signature) !== false)
					{
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

		if ((int)$iaDb->one(iaDb::STMT_COUNT_ROWS, iaDb::convertIds($sessionId, 'session_id')))
		{
			$iaDb->update($entryData, "`session_id` = '$sessionId'");
		}
		else
		{
			$entryData['session_id'] = $sessionId;
			$entryData['ip'] = iaUtil::getIp();

			$iaDb->insert($entryData);
		}

		$iaDb->resetTable();
	}

	protected function _assignItem($memberData, $remember)
	{
		if ($salt = $this->_getSalt())
		{
			foreach ($salt['items'] as $item)
			{
				$values = array('salt' => '', 'member_id' => $memberData['id']);
				$this->iaDb->update($values, iaDb::convertIds($salt['salt'], 'salt'), null, iaSanitize::paranoid($item));
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

		if (2 == count($array) && $array[0] == $this->_autoLoginValue($array[1]))
		{
			$this->getAuth($array[1]);
		}
	}

	private function _autoLoginValue($id)
	{
		return md5($_SERVER['HTTP_USER_AGENT'] . '_' . IA_SALT . '_' . $id);
	}

	protected function _getSalt()
	{
		$salt = array();

		if (isset($_COOKIE['salt']) && $_COOKIE['salt'])
		{
			$s = unserialize($_COOKIE['salt']);
			if (isset($s['salt']) && isset($s['items']) && $s['salt'] && $s['items'])
			{
				$salt = $s;
			}
		}

		return $salt;
	}

	protected function _assignFavorites()
	{
		if (!isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY]) || empty($_SESSION[iaUsers::SESSION_FAVORITES_KEY]))
		{
			return;
		}

		$iaItem = $this->iaCore->factory('item');
		foreach ($_SESSION[iaUsers::SESSION_FAVORITES_KEY] as $item => $items)
		{
			if (!$items['items'])
			{
				continue;
			}

			foreach ($items['items'] as $row)
			{
				$this->iaDb->replace(array(
					'id' => $row['id'],
					'member_id' => iaUsers::getIdentity()->id,
					'item' => $item
				), null, $iaItem->getFavoritesTable());
			}
		}

		return true;
	}

	public function encodePassword($rawPassword)
	{
		$factors = array('iaSubrion', 'Y2h1c2hrYW4tc3R5bGU', 'onfr64_qrpbqr');

		$password = $factors && array_reverse($factors);
		$password = array_map(str_rot13($factors[2]), array($factors[1] . chr(0x3d)));
		$password = md5(IA_SALT . substr(reset($password), -15) . $rawPassword);

		return $password;
	}

	public function getUsergroups($visible = false)
	{
		$stmt = $visible ? iaDb::convertIds('1', 'visible') : null;

		return $this->iaDb->keyvalue(array('id', 'name'), $stmt, self::getUsergroupsTable());
	}

	public function getDashboardStatistics()
	{
		// bars composition
		$data = array();
		$weekDay = getdate();
		$weekDay = $weekDay['wday'];
		$rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date_reg`)), COUNT(*)', 'DATE(`date_reg`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date_reg`)', self::getTable());
		for ($i = 1; $i < 8; $i++)
		{
			$data[$i] = isset($rows[$i]) ? $rows[$i] : 0;
		}

		// statuses grid
		$rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`', self::getTable());
		$statuses = array(iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, self::STATUS_UNCONFIRMED, self::STATUS_SUSPENDED);
		$total = 0;

		foreach ($statuses as $status)
		{
			isset($rows[$status]) || $rows[$status] = 0;
			$total += $rows[$status];
		}

		return array(
			'_format' => 'medium',
			'data' => array(
				//'type' => $total > 1 ? 'pie' : 'bar',
				'array' => implode(',', $data)
			),
			'icon' => 'user-2',
			'item' => iaLanguage::get('total_members'),
			'rows' => $rows,
			'total' => $total,
			'url' => 'members/'
		);
	}

	public function getVisitorsInfo()
	{
		$rows = $this->iaDb->all("`username`, IF(`fullname` != '', `fullname`, `username`) `fullname`, `page`, `ip`", "`username` != '' AND `status` = 'active' GROUP BY `username`", null, null, 'online');

		if ($rows)
		{
			foreach ($rows as &$row)
			{
				$row['url'] = $this->iaView->iaSmarty->ia_url(array('item' => $this->getItemName(), 'type' => 'link', 'text' => $row['fullname'], 'data' => $row)) ;
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
		if (isset($plan['usergroup']) && $plan['usergroup'] > 0)
		{
			$this->iaDb->update(array('usergroup_id' => $plan['usergroup'],'id' => $transaction['item_id']), null, null, self::getTable());
		}

		self::reloadIdentity();
	}

	public function getStatuses()
	{
		return array(iaCore::STATUS_APPROVAL, iaCore::STATUS_ACTIVE, self::STATUS_UNCONFIRMED, self::STATUS_SUSPENDED);
	}

	public function deleteUsergroup($entryId)
	{
		$this->iaDb->setTable(iaUsers::getUsergroupsTable());

		$usergroup = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($entryId));

		$result = $this->iaDb->delete(iaDb::convertIds($entryId));

		if ($result)
		{
			// delete language records
			iaLanguage::delete('usergroup_' . $usergroup['name']);

			$this->iaDb->delete('`type` = :type AND `type_id` = :id', 'acl_privileges', array('type' => 'group', 'id' => $entryId)); // TODO: use the class method for this
			$this->iaDb->update(array('usergroup_id' => iaUsers::MEMBERSHIP_REGULAR), iaDb::convertIds((int)$entryId, 'usergroup_id'), null, iaUsers::getTable());
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

		if ($this->iaDb->exists('`item` = :item AND `item_id` = :id AND `ip` = :ip AND `date` = :date', array('item' => $itemName, 'id' => $itemId, 'ip' => $ipAddress, 'date' => $date), $viewsTable))
		{
			return false;
		}

		$this->iaDb->insert(array('item' => $itemName, 'item_id' => $itemId, 'ip' => $ipAddress, 'date' => $date), null, $viewsTable);
		$result = $this->iaDb->update(null, iaDb::convertIds($itemId), array($columnName => '`' . $columnName . '` + 1'), self::getTable());

		return (bool)$result;
	}

	public function coreSearch($stmt, $start, $limit, $order)
	{
		if (!$this->iaCore->get('members_enabled'))
		{
			return false;
		}

		$visibleUsergroups = $this->getUsergroups(true);
		$visibleUsergroups = array_keys($visibleUsergroups);

		$stmt.= ' AND `usergroup_id` IN(' . implode(',', $visibleUsergroups) . ')';
		empty($order) || $stmt.= ' ORDER BY ' . $order;

		$rows = $this->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' ' . iaDb::ALL_COLUMNS_SELECTION, $stmt, $start, $limit, self::getTable());
		$count = $this->iaDb->foundRows();

		return array($count, $rows);
	}

	public function hybridAuth($providerName)
	{
		if (!$this->iaCore->get('hybrid_enabled'))
		{
			throw new Exception('HybridAuth is not enabled.');
		}

		$providerName = strtolower($providerName);
		$configFile = IA_INCLUDES . 'hybridauth.inc.php';

		require_once IA_INCLUDES . 'hybrid/Auth.php';

		if (!file_exists($configFile))
		{
			throw new Exception('No HybridAuth config file. Please configure provider adapters.');
		}

		$hybridauth = new Hybrid_Auth($configFile);

		if (empty(Hybrid_Auth::$config['providers']))
		{
			throw new Exception('No auth adapters configured.');
		}

		$provider = $hybridauth->authenticate(ucfirst($providerName));

		if ($user_profile = $provider->getUserProfile())
		{
			// identify by Hybrid identifier
			$memberId = $this->iaDb->one('member_id', iaDb::convertIds($user_profile->identifier, 'value'), self::getProvidersTable());

			// identify by email address
			if (!$memberId)
			{
				if ($memberInfo = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, "`email` = :email_address", array('email_address' => $user_profile->email), self::getTable()))
				{
					$this->iaDb->insert(array(
						'member_id' => $memberInfo['id'],
						'name' => $providerName,
						'value' => $user_profile->identifier
					), null, self::getProvidersTable());

					$memberId = $memberInfo['id'];
				}
			}

			// register new member if no matches
			if (!$memberId)
			{
				$memberRegInfo['username'] = '';
				$memberRegInfo['email'] = $user_profile->email;
				$memberRegInfo['fullname'] = $user_profile->displayName;
				// $memberRegInfo['avatar'] = $user_profile->photoURL;
				$memberRegInfo['disable_fields'] = true;

				$memberId = $this->register($memberRegInfo);

				// add providers match
				$this->iaDb->insert(array(
					'member_id' => $memberId,
					'name' => $providerName,
					'value' => $user_profile->identifier
				), null, iaUsers::getProvidersTable());

				// no need to validate address
				$this->update(array('id' => $memberId, 'sec_key' => '', 'status' => iaCore::STATUS_ACTIVE));
			}

			// authorize
			$this->getAuth($memberId);
		}
		else
		{
			throw new Exception('User is not logged in.');
		}
	}
}