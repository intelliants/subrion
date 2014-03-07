<?php
//##copyright##

class iaUsers extends abstractCore
{
	const SESSION_KEY = 'user';

	const MEMBERSHIP_REGULAR = 8;
	const MEMBERSHIP_GUEST = 4;
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

			$iaMailer->load_template($action);
			$body = $iaMailer->Body;

			$members = $this->iaDb->all(array('email', 'fullname'), $condition);
			foreach ($members as $member)
			{
				$iaMailer->ClearAddresses();
				$iaMailer->AddAddress($member['email']);
				$iaMailer->Body = str_replace('{%FULLNAME%}', $member['fullname'], $body);

				$iaMailer->Send();
			}
		}

		return $this->iaDb->update($values, $condition, $rawValues, self::getTable());
	}

	public function delete($statement = null)
	{
		$actionName = 'member_removal';
		$result = $this->iaDb->delete($statement, self::getTable());

		if ($result && $this->iaCore->get($actionName))
		{
			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->load_template($actionName);
			$body = $iaMailer->Body;

			$iaLog = $this->iaCore->factory('log');

			$members = $this->iaDb->all(array('id', 'email', 'fullname'), $statement, null, null, self::getTable());
			foreach ($members as $member)
			{
				$this->iaCore->startHook('userDelete', array('memberInfo' => $member));

				// send email notification
				$iaMailer->ClearAddresses();
				$iaMailer->AddAddress($member['email']);
				$iaMailer->Body = str_replace('{%FULLNAME%}', $member['fullname'], $body);
				$iaMailer->Send();

				$iaLog->write(iaLog::ACTION_DELETE, array('item' => 'member', 'name' => $member['fullname'], 'id' => $member['id']));
			}
		}

		return $result;
	}

	/**
	 * Updates member password
	 *
	 * @param integer $memberId member id
	 * @param string $password new password
	 *
	 * @return bool
	 */
	public function changePassword($memberId, $password)
	{
		return $this->iaDb->update(array('password' => $this->encodePassword($password)), iaDb::convertIds($memberId), null, self::getTable());
	}

	/**
	 * Updates member password and sends email
	 *
	 * @param array $memberInfo member information
	 *
	 * @return bool
	 */
	public function setNewPassword($memberInfo)
	{
		$pass = $this->_createPassword();
		$this->iaDb->setTable(self::getTable());
		$x = $this->iaDb->update(array(
			'password' => $this->encodePassword($pass),
			'sec_key' => ''
		), "`id`='" . $memberInfo['id'] . "'");
		$this->iaDb->resetTable();

		$iaMailer = $this->iaCore->factory('mailer');
		$iaMailer->load_template('password_changement');
		$iaMailer->AddAddress($memberInfo['email'], $memberInfo['fullname']);
		$iaMailer->replace['{%FULLNAME%}'] = $memberInfo['fullname'];
		$iaMailer->replace['{%USERNAME%}'] = $memberInfo['username'];
		$iaMailer->replace['{%PASSWORD%}'] = $pass;

		$iaMailer->Send();

		return $x;
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

			$iaMailer->load_template($action);
			$iaMailer->AddAddress($memberInfo['email']);
			$iaMailer->replace['{%FULLNAME%}'] = $memberInfo['fullname'];
			$iaMailer->replace['{%EMAIL%}'] = $memberInfo['email'];
			$iaMailer->replace['{%PASSWORD%}'] = $password;
			$iaMailer->replace['{%LINK%}'] = IA_URL . 'confirm/?email=' . $memberInfo['email'] . '&key=' . $memberInfo['sec_key'];

			$iaMailer->Send();
		}

		$this->iaCore->startHook('memberPreAdd', array('member' => &$memberInfo, 'password' => $password));

		$this->iaDb->setTable(self::getTable());
		$memberId = $this->iaDb->one_bind('id', '`username` = :username', array('username' => $memberInfo['username']));
		if (empty($memberId))
		{
			$memberId = $this->iaDb->insert($memberInfo, array('date_reg' => iaDb::FUNCTION_NOW, 'date_update' => iaDb::FUNCTION_NOW));
		}
		$this->iaDb->resetTable();

		$this->iaCore->startHook('memberAdded', array('member' => $memberInfo, 'password' => $password));

		return $memberId;
	}

	private function _generateUserName(array $memberInfo)
	{
		$email = $memberInfo['email'];

		// here we can be pretty sure that email contains @
		$result = substr($email, 0, strpos($email, '@'));
		$result = $result . '@' . time();

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
		$status = $this->iaCore->get('members_autoapproval')
			? iaCore::STATUS_ACTIVE
			: iaCore::STATUS_APPROVAL;
		$condition = iaDb::printf("`email` = ':email' AND `sec_key` = ':key'", array('email' => iaSanitize::sql($email), 'key' => iaSanitize::sql($key)));

		return (bool)$this->iaDb->update(array('sec_key' => '', 'status' => $status), $condition, null, self::getTable());
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

		$session = session_id();

		$iaDb = &$this->iaCore->iaDb;
		$iaDb->setTable('online');
		$count = (int)$iaDb->one_bind(iaDb::STMT_COUNT_ROWS, '`session_id` = :session', array('session' => $session));

		$rawValues = array('date' => iaDb::FUNCTION_NOW);

		if ($count > 0)
		{
			$iaDb->update($entryData, "`session_id` = '$session'", $rawValues);
		}
		else
		{
			$entryData['session_id'] = $session;
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

	public function postPayment($plan, $transaction)
	{
		if (isset($plan['usergroup']) && $plan['usergroup'] > 0)
		{
			$this->iaDb->update(array('usergroup_id' => $plan['usergroup'],'id' => $transaction['item_id']), null, null, self::getTable());
		}

		return true;
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
		$rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status', self::getTable());
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

	public function getOnlineMembers()
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
}