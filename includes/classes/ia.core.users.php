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

class iaUsers extends abstractCore
{
	const SESSION_KEY = 'user';

	const MEMBERSHIP_REGULAR = 8;
	const MEMBERSHIP_GUEST = 4;
	const MEMBERSHIP_MODERATOR = 2;
	const MEMBERSHIP_ADMINISTRATOR = 1;

	const STATUS_UNCONFIRMED = 'unconfirmed';
	const STATUS_SUSPENDED = 'suspended';

	const METHOD_NAME_GET_LISTINGS = 'fetchMemberListings';
	const METHOD_NAME_GET_FAVORITES = 'getFavorites';

	protected static $_table = 'members';
	protected static $_itemName = 'members';

	protected static $_usergroupTable = 'usergroups';

	public $dashboardStatistics = true;


	public static function getItemName()
	{
		return self::$_itemName;
	}

	public static function getUsergroupsTable()
	{
		return self::$_usergroupTable;
	}

	/* IDENTITY STORAGE MECH */
	// currently uses the standard PHP session

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
		self::_setIdentity(null);
	}

	public static function reloadIdentity()
	{
		$sql =
			'SELECT u.*, g.`title` `usergroup` ' .
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
			$password = $this->_createPassword();
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
			? $this->_createPassword()
			: $memberInfo['password'];

		unset($memberInfo['disable_fields']);

		$password = $memberInfo['password'];

		$memberInfo['usergroup_id'] = self::MEMBERSHIP_REGULAR;
		$memberInfo['sec_key'] = md5($this->_createPassword());
		$memberInfo['status'] = self::STATUS_UNCONFIRMED;
		$memberInfo['password'] = $this->encodePassword($password);

		// according to DB table scheme we have to ensure that username field will contain unique data
		if (empty($memberInfo['username']))
		{
			$memberInfo['username'] = $this->_generateUserName($memberInfo);
		}

		// send email
		$this->iaCore->startHook('memberAddEmailSubmission', array('member' => $memberInfo));

		$action = 'member_registration';
		if ($this->iaCore->get($action) && $memberInfo['email'])
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate($action);
			$iaMailer->addAddress($memberInfo['email']);
			$iaMailer->setReplacements(array(
				'fullname' => $memberInfo['fullname'],
				'email' => $memberInfo['email'],
				'password' => $password,
				'link' => IA_URL . 'confirm/?email=' . $memberInfo['email'] . '&key=' . $memberInfo['sec_key']
			));

			$iaMailer->send(true);
		}

		$this->iaCore->startHook('memberPreAdd', array('member' => &$memberInfo, 'password' => $password));

		$this->iaDb->setTable(self::getTable());
		$memberId = $this->iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`username` = :username', array('username' => $memberInfo['username']));
		if (empty($memberId))
		{
			$memberId = $this->iaDb->insert($memberInfo, array('date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW));
		}
		$this->iaDb->resetTable();

		$this->iaCore->startHook('phpUserRegister', array('userInfo' => $memberInfo, 'password' => $password));

		// sending the admin notification
		$action = 'member_registration_admin';
		if ($this->iaCore->get($action) && $memberInfo['email'])
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate($action);
			$iaMailer->setReplacements(array(
				'id' => $memberId,
				'username' => $memberInfo['username'],
				'fullname' => $memberInfo['fullname'],
				'email' => $memberInfo['email'],
				'password' => $password
			));

			$iaMailer->sendToAdministrators();
		}
		//

		return $memberId;
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

	protected function _createPassword($length = 7)
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

	public function getInfo($id, $key = 'id')
	{
		if ($key != 'id' && $key != 'username')
		{
			$key = 'id';
		}

		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`' . $key . '` = :id AND `status` = :status', array('id' => $id, 'status' => iaCore::STATUS_ACTIVE), self::getTable());
	}

	public function getAuth($userId, $user = null, $password = null)
	{
		$sql =
			'SELECT u.*, g.`title` `usergroup` ' .
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
			$email = iaSanitize::sql($user);
			$user = preg_replace('/[^a-zA-Z0-9.@_-]/', '', $user);
			$condition = sprintf("(u.`username` = '%s' OR u.`email` = '%s') AND u.`password` = '%s'", $user, $email, $password);
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
			$this->iaCore->startHook('phpUserLogin', array('userInfo' => $row, 'password' => $_POST['password']));

			$this->iaDb->update(null, iaDb::convertIds($row['id']), array('date_logged' => iaDb::FUNCTION_NOW), self::getTable());

			$this->_assignItem($row);

			return $row;
		}

		return false;
	}

	public function registerVisitor()
	{
		$entryData = array('status' => iaCore::STATUS_ACTIVE, 'page' => IA_SELF);

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
		$count = (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, iaDb::convertIds($sessionId, 'session_id'));

		$rawValues = array('date' => iaDb::FUNCTION_NOW);

		if ($count > 0)
		{
			$iaDb->update($entryData, "`session_id` = '$sessionId'", $rawValues);
		}
		else
		{
			$entryData['session_id'] = $sessionId;
			$entryData['ip'] = iaUtil::getIp();

			$iaDb->insert($entryData, $rawValues);
		}

		$iaDb->update(array('status' => 'expired'), '`date` < NOW() - INTERVAL 20 MINUTE');
		$iaDb->delete('`date` < NOW() - INTERVAL 2 DAY');
	}

	protected function _assignItem($account)
	{
		if ($salt = $this->_getSalt())
		{
			foreach ($salt['items'] as $item)
			{
				$values = array('salt' => '', 'member_id' => $account['id']);
				$this->iaDb->update($values, "`salt` = '{$salt['salt']}'", null, $item);
			}
		}

		setcookie('salt', '', time() - 3600, '/');
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

	public function encodePassword($rawPassword)
	{
		$factors = array('iaSubrion', 'Y2h1c2hrYW4tc3R5bGU', 'onfr64_qrpbqr');

		$password = $factors && array_reverse($factors);
		$password = array_map(str_rot13($factors[2]), array($factors[1] . chr(0x3d)));
		$password = md5(IA_SALT . substr(reset($password), -15) . $rawPassword);

		return $password;
	}

	public function getUsergroups()
	{
		return $this->iaDb->keyvalue(array('id', 'title'), null, self::getUsergroupsTable());
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
}