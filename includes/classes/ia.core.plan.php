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

class iaPlan extends abstractCore
{
	const SECONDS_PER_DAY = 86400;

	const SPONSORED = 'sponsored';
	const SPONSORED_DATE_START = 'sponsored_start';
	const SPONSORED_DATE_END = 'sponsored_end';
	const SPONSORED_PLAN_ID = 'sponsored_plan_id';

	const METHOD_POST_PAYMENT = 'postPayment';
	const METHOD_CANCEL_PLAN = 'planCancelling';

	const UNIT_HOUR = 'hour';
	const UNIT_DAY = 'day';
	const UNIT_WEEK = 'week';
	const UNIT_MONTH = 'month';
	const UNIT_YEAR = 'year';

	const TYPE_FEE = 'fee';
	const TYPE_SUBSCRIPTION = 'subscription';

	protected static $_table = 'payment_plans';

	protected static $_tableOptions = 'payment_plans_options';
	protected static $_tableOptionValues = 'payment_plans_options_values';

	protected static $_options;

	protected $_item;
	protected $_plans = array();


	public static function getTableOptions()
	{
		return self::$_tableOptions;
	}

	public static function getTableOptionValues()
	{
		return self::$_tableOptionValues;
	}

	public function getOptionsValues($itemName, $itemId)
	{
		$iaItem = $this->iaCore->factory('item');

		$item = $this->iaDb->row(array('sponsored', 'sponsored_plan_id'),
			iaDb::convertIds($itemId), $iaItem->getItemTable($itemName));

		return (!empty($item['sponsored']) && !empty($item['sponsored_plan_id']))
			? $this->_getOptionValuesByPlanId($item['sponsored_plan_id'])
			: array();
	}

	protected function _getOptionValuesByPlanId($planId)
	{
		$sql = 'SELECT o.`name`, v.`value` '
			. 'FROM `:prefix:table_option_values` v '
			. 'LEFT JOIN `:prefix:table_options` o ON (v.`option_id` = o.`id`) '
			. 'WHERE v.`plan_id` = :plan';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table_option_values' => self::getTableOptionValues(),
			'table_options' => self::getTableOptions(),
			'plan' => (int)$planId
		));

		$rows = $this->iaDb->getKeyValue($sql);

		return $rows ? $rows : array();
	}

	public function getPlanOptions($planId)
	{
		$result = array();

		$values = $this->iaDb->assoc(array('option_id', 'price', 'value'), iaDb::convertIds($planId, 'plan_id'), self::getTableOptionValues());

		if (is_null(self::$_options))
		{
			self::$_options = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, self::getTableOptions());
		}

		foreach (self::$_options as $option)
		{
			isset($values[$option['id']]) && $option['price'] = $values[$option['id']]['price'];
			$option['value'] = isset($values[$option['id']]) ? $values[$option['id']]['value'] : $option['default_value'];

			$result[] = $option;
		}

		return $result;
	}

	/**
	 * Payment pre-processing actions
	 *
	 * @param $itemName item name
	 * @param $itemData current item data, id field is mandatory
	 * @param $planId plan id to be paid for
	 * @param string $returnUrl post payment return url
	 *
	 * @return bool|string
	 */
	public function prePayment($itemName, $itemData, $planId, $returnUrl = IA_URL)
	{
		if (!$planId || !isset($this->_plans[$planId]))
		{
			return $returnUrl;
		}

		if (empty($itemData))
		{
			return false;
		}

		$cost = $this->_plans[$planId]['cost'];

		if ('members' != $itemName && !empty($itemData[self::SPONSORED]))
		{
			/*
			$rdbmsDate = $this->iaDb->one('CURDATE()');
			$daysLeft = strtotime($itemData[self::SPONSORED_DATE_END]) - strtotime($rdbmsDate);
			$daysLeft = $daysLeft > 0 ? $daysLeft / 86400 : 0;
			$cost -= round($daysLeft * ($itemData['cost'] / $itemData['days']), 2);
			*/
		}

		$iaTransaction = $this->iaCore->factory('transaction');
		$paymentId = $iaTransaction->create(null, $cost, $itemName, $itemData, $returnUrl, $planId, true);

		return IA_URL . 'pay' . IA_URL_DELIMITER . $paymentId . IA_URL_DELIMITER;
	}

	/**
	 * Return plan information
	 *
	 * @param integer $planId plan id
	 *
	 * @return null|array
	 */
	public function getById($planId)
	{
		$plan = null;

		if (!is_array($planId))
		{
			$plan = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`status` = :status AND `id` = :id', array('status' => iaCore::STATUS_ACTIVE, 'id' => (int)$planId), self::getTable());
			if ($plan)
			{
				$plan['title'] = iaLanguage::get('plan_title_' . $plan['id']);
				$plan['description'] = iaLanguage::get('plan_description_' . $plan['id']);
			}
		}

		return $plan;
	}

	/**
	 * Returns an array of available plans
	 *
	 * @param null $itemName option item name
	 *
	 * @return array
	 */
	public function getPlans($itemName, $options = true)
	{
		if (!$this->_item || $this->_item != $itemName)
		{
			$where = '`item` = :item AND `status` = :status ORDER BY `order` ASC';
			$this->iaDb->bind($where, array('item' => $itemName, 'status' => iaCore::STATUS_ACTIVE));

			if ($rows = $this->iaDb->all(array('id', 'duration', 'unit', 'cost', 'data'), $where, null, null, self::getTable()))
			{
				foreach ($rows as $row)
				{
					$row['data'] = unserialize($row['data']);
					$row['fields'] = isset($row['data']['fields']) ? implode(',', $row['data']['fields']) : '';
					$row['options'] = $this->getPlanOptions($row['id']);

					$this->_plans[$row['id']] = $row;
				}
			}

			$this->_item = $itemName;
		}

		return $this->_plans;
	}

	/**
	 * Write funds off from member balance
	 *
	 * @param array $transactionData data about transaction
	 *
	 * @return bool true on success
	 */
	public function extractFunds(array $transactionData)
	{
		if (!iaUsers::hasIdentity())
		{
			return false;
		}

		$iaUsers = $this->iaCore->factory('users');
		$iaTransaction = $this->iaCore->factory('transaction');

		$userInfo = $iaUsers->getInfo(iaUsers::getIdentity()->id);

		$remainingBalance = $userInfo['funds'] - $transactionData['amount'];
		if ($remainingBalance >= 0)
		{
			$result = (bool)$iaUsers->update(array('funds' => $remainingBalance), iaDb::convertIds($userInfo['id']));

			if ($result)
			{
				iaUsers::reloadIdentity();

				$updatedValues = array(
					'status' => iaTransaction::PASSED,
					'gateway' => iaTransaction::TRANSACTION_MEMBER_BALANCE,
					'reference_id' => date('YmdHis'),
					'member_id' => iaUsers::getIdentity()->id
				);

				$iaTransaction->update($updatedValues, $transactionData['id']);
			}

			return $result;
		}

		return false;
	}


	public function setUnpaid($itemName, $itemId) // unassigns paid plan
	{
		// first, try to update DB record
		$tableName = $this->iaCore->factory('item')->getItemTable($itemName);
		$stmt = iaDb::convertIds($itemId);

		$fields = array(self::SPONSORED, self::SPONSORED_PLAN_ID);
		'members' == $itemName || $fields[] = 'member_id';

		$entry = $this->iaDb->row($fields, $stmt, $tableName);
		if (empty($entry) || !$entry[self::SPONSORED])
		{
			return false;
		}

		$values = array(
			self::SPONSORED => 0,
			self::SPONSORED_PLAN_ID => 0,
			self::SPONSORED_DATE_START => null,
			self::SPONSORED_DATE_END => null
		);

		$plan = $this->getById($entry[self::SPONSORED_PLAN_ID]);

		if (!empty($plan['expiration_status']))
		{
			$values['status'] = $plan['expiration_status'];
		}

		$result = $this->iaDb->update($values, $stmt, null, $tableName);

		if (isset($entry['member_id']) && $entry['member_id'])
		{
			$this->_sendEmailNotification('expired', $plan, $entry['member_id']);
		}

		// then, try to call class' helper
		$this->_runClassMethod($itemName, self::METHOD_CANCEL_PLAN, array($itemId));

		return $result;
	}

	public function setPaid($transaction) // updates item's sponsored record
	{
		if (!is_array($transaction))
		{
			return false;
		}

		$result = false;

		$item = $transaction['item'];
		$plan = $this->getById($transaction['plan_id']);

		if ($plan && $item && !empty($transaction['item_id']))
		{
			list($dateStarted, $dateFinished) = $this->calculateDates($plan['duration'], $plan['unit']);

			$values = array(
				self::SPONSORED => 1,
				self::SPONSORED_PLAN_ID => $transaction['plan_id'],
				self::SPONSORED_DATE_START => $dateStarted,
				self::SPONSORED_DATE_END => $dateFinished,
				'status' => iaCore::STATUS_ACTIVE
			);

			$iaItem = $this->iaCore->factory('item');
			$result = $this->iaDb->update($values, iaDb::convertIds($transaction['item_id']), null, $iaItem->getItemTable($item));
		}

		$this->_sendEmailNotification('activated', $plan, $transaction['member_id']);

		// perform item specific actions
		$this->_runClassMethod($item, self::METHOD_POST_PAYMENT, array($plan, $transaction));

		return $result;
	}

	public function calculateDates($duration, $unit)
	{
		switch ($unit)
		{
			case self::UNIT_HOUR:
			case self::UNIT_DAY:
			case self::UNIT_WEEK: // use pre-calculated data
				$unitDurationInSeconds = array(self::UNIT_HOUR => 3600, self::UNIT_DAY => 86400, self::UNIT_WEEK => 604800);
				$base = $unitDurationInSeconds[$unit];

				break;

			case self::UNIT_MONTH:
				$days = date('t');
				$base = self::SECONDS_PER_DAY * $days;

				break;

			case self::UNIT_YEAR:
				$date = getdate();
				$days = date('z', mktime(0, 0, 0, 12, 31, $date['year'])) + 1;
				$base = self::SECONDS_PER_DAY * $days;
		}

		$dateStarted = time();
		$dateFinished = $dateStarted + ($base * $duration);

		return array(
			date(iaDb::DATETIME_FORMAT, $dateStarted),
			date(iaDb::DATETIME_FORMAT, $dateFinished)
		);
	}

	protected function _sendEmailNotification($type, array $plan, $memberId)
	{
		$notificationType = 'plan_' . $type;

		if (empty($plan) || empty($memberId) || !$this->iaCore->get($notificationType))
		{
			return false;
		}

		$iaUsers = $this->iaCore->factory('users');

		$member = $iaUsers->getById($memberId);

		if (!$member)
		{
			return false;
		}

		$iaMailer = $this->iaCore->factory('mailer');

		$iaMailer->loadTemplate($notificationType);
		$iaMailer->addAddress($member['email']);

		$iaMailer->setReplacements($plan);
		$iaMailer->setReplacements(array(
			'email' => $member['email'],
			'username' => $member['username'],
			'fullname' => $member['fullname'],
			'plan' => iaLanguage::get('plan_title_' . $plan['id']),
			'currency' => $this->iaCore->get('currency'),
		));

		return $iaMailer->send();
	}

	private function _runClassMethod($itemName, $method, array $args = array())
	{
		$iaItem = $this->iaCore->factory('item');

		$className = ucfirst(substr($itemName, 0, -1));
		$itemClassInstance = ($itemName == 'members')
			? $this->iaCore->factory('users')
			: $this->iaCore->factoryPackage($className, $iaItem->getPackageByItem($itemName));

		if ($itemClassInstance && method_exists($itemClassInstance, $method))
		{
			return call_user_func_array(array($itemClassInstance, $method), $args);
		}

		return false;
	}
}