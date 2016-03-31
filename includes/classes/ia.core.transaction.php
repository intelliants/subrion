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

class iaTransaction extends abstractCore
{
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PASSED = 'passed';
	const PENDING = 'pending';

	const TRANSACTION_MEMBER_BALANCE = 'funds';

	const GATEWAY_CALLBACK_NAME = 'emailNotification';

	protected static $_table = 'payment_transactions';
	protected $_tableGateways = 'payment_gateways';
	protected $_tableIpnLog = 'payment_gateways_ipn_log';

	protected $_gateways;

	public $dashboardStatistics = true;


	/**
	 * Return array of installed payment gateways
	 *
	 * @return string
	 */
	public function getTableGateways()
	{
		return $this->_tableGateways;
	}

	public function getTableIpnLog()
	{
		return $this->_tableIpnLog;
	}

	/**
	 * Returns installed payment gateways
	 *
	 * @return array
	 */
	public function getPaymentGateways()
	{
		if (is_null($this->_gateways))
		{
			$stmt = "`name` IN ('" . implode("','", $this->iaCore->get('extras')) . "')";
			$this->_gateways = $this->iaDb->keyvalue(array('name', 'title'), $stmt, $this->getTableGateways());
		}

		return $this->_gateways;
	}

	/**
	 * Checks if payment gateway installed
	 *
	 * @param string $gatewayName payment gateway name
	 *
	 * @return boolean
	 */
	public function isPaymentGateway($gatewayName)
	{
		return $this->iaDb->exists('`name` = :name', array('name' => $gatewayName), $this->getTableGateways());
	}

	/**
	 * Update transaction record
	 *
	 * @param array $transactionData transaction data
	 * @param int $id transaction id
	 *
	 * @return bool
	 */
	public function update(array $transactionData, $id)
	{
		$result = false;

		if ($transaction = $this->getById($id))
		{
				$transactionData['date_updated'] = date(iaDb::DATETIME_FORMAT);
				!(self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status'])
					|| $transactionData['date_paid'] = date(iaDb::DATETIME_FORMAT);
				$result = (bool)$this->iaDb->update($transactionData, iaDb::convertIds($id), null, self::getTable());

			if ($result && isset($transactionData['status']))
			{
				$operation = empty($transactionData['item']) ? $transaction['item'] : $transactionData['item'];

				if (self::TRANSACTION_MEMBER_BALANCE == $operation)
				{
					$itemId = empty($transactionData['item_id']) ? $transaction['item_id'] : $transactionData['item_id'];
					$amount = empty($transactionData['amount']) ? $transaction['amount'] : $transactionData['amount'];

					if (self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status'])
					{
						$result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), array('funds' => '`funds` + ' . $amount), iaUsers::getTable());
					}
					elseif (self::PASSED != $transactionData['status'] && self::PASSED == $transaction['status'])
					{
						$result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), array('funds' => '`funds` - ' . $amount), iaUsers::getTable());
					}
				}

				if (self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status'])
				{
					$transaction = $this->getById($id);

					$this->_sendEmailNotification($transaction);
					empty($transaction['member_id']) || $this->_createInvoice($transaction);
				}
			}
		}

		return $result;
	}

	/**
	 * Delete transaction record
	 *
	 * @param int $transactionId transaction id
	 *
	 * @return bool
	 */
	public function delete($transactionId)
	{
		$result = false;
		if ($transactionId)
		{
			$result = (bool)$this->iaDb->delete('`id` = :id AND `status` != :status', self::getTable(), array('id' => (int)$transactionId, 'status' => self::PASSED));
			empty($result) || $this->iaCore->factory('invoice')->deleteCorrespondingInvoice($transactionId);
		}

		return $result;
	}

	/**
	 * Return transaction details by id
	 *
	 * @param int $transactionId transaction id
	 *
	 * @return mixed
	 */
	public function getById($transactionId)
	{
		return $this->getBy(iaDb::ID_COLUMN_SELECTION, $transactionId);
	}

	public function getBy($key, $id)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id, $key), self::getTable());
	}

	public function createIpn($transaction)
	{
		unset($transaction['id'], $transaction['date_created']);

		$this->iaDb->insert($transaction, array('date_created' => iaDb::FUNCTION_NOW), self::getTable());

		return $this->iaDb->getInsertId();
	}

	/**
	 * Generates invoice for an item
	 *
	 * @param string $title plan title
	 * @param double $cost plan cost
	 * @param string $itemName item name
	 * @param array $itemData item details
	 * @param string $returnUrl return URL
	 * @param int $planId plan id
	 * @param bool $return true redirects to invoice payment URL
	 *
	 * @return string
	 */
	public function create($title, $cost, $itemName = 'members', $itemData = array(), $returnUrl = '', $planId = 0, $return = false)
	{
		if (!isset($itemData['id']))
		{
			$itemData['id'] = 0;
		}

		$title = empty($title) ? iaLanguage::get('plan_title_' . $planId) : $title;
		$title .= ($itemData['id']) ? ' - #' . $itemData['id'] : '';

		$transactionId = uniqid('t');
		$transaction = array(
			'member_id' => (int)(isset($itemData['member_id']) && $itemData['member_id'] ? $itemData['member_id'] : iaUsers::getIdentity()->id),
			'item' => $itemName,
			'item_id' => $itemData['id'],
			'amount' => $cost,
			'currency' => $this->iaCore->get('currency'),
			'sec_key' => $transactionId,
			'status' => self::PENDING,
			'plan_id' => $planId,
			'return_url' => $returnUrl,
			'operation' => $title,
			'date_created' => date(iaDb::DATETIME_FORMAT)
		);

		$result = $this->iaDb->insert($transaction, null, $this->getTable());
		$result && $this->iaCore->startHook('phpTransactionCreated', array('id' => $result, 'transaction' => $transaction));
		$return || iaUtil::go_to(IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER);

		return $result ? $transactionId : false;
	}

	protected function _createInvoice($transaction)
	{
		$iaInvoice = $this->iaCore->factory('invoice');

		return $iaInvoice->create($transaction);
	}

	/**
	 * Filling admin dashboard statistics with the financial information
	 *
	 * @return array
	 */
	public function getDashboardStatistics()
	{
		$this->iaDb->setTable(self::getTable());

		$currenciesToSymbolMap = array('USD' => '$', 'EUR' => '€', 'RMB' => '¥', 'CNY' => '¥');

		$currency = strtoupper($this->iaCore->get('currency'));
		$currency = isset($currenciesToSymbolMap[$currency]) ? $currenciesToSymbolMap[$currency] : '';


		$data = array();
		$weekDay = getdate();
		$weekDay = $weekDay['wday'];
		$stmt = '`status` = :status AND DATE(`date_paid`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date_paid`)';
		$this->iaDb->bind($stmt, array('status' => self::PASSED));
		$rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date_paid`)), SUM(`amount`)', $stmt);
		for ($i = 0; $i < 7; $i++)
		{
			$data[$i] = isset($rows[$i]) ? $rows[$i] : 0;
		}

		$statuses = array(self::PASSED, self::PENDING, self::REFUNDED, self::FAILED);
		$rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`');

		foreach ($statuses as $status)
		{
			isset($rows[$status]) || $rows[$status] = 0;
		}

		$total = $this->iaDb->one_bind('ROUND(SUM(`amount`)) `total`', '`status` = :status', array('status' => self::PASSED));
		$total || $total = 0;

		$this->iaDb->resetTable();


		return array(
			'_format' => 'medium',
			'data' => array('array' => implode(',', $data)),
			'icon' => 'banknote',
			'item' => iaLanguage::get('total_income'),
			'rows' => $rows,
			'total' => $currency . $total,
			'url' => 'transactions/'
		);
	}

	public function addIpnLogEntry($gateway, $data, $result)
	{
		$entry = array(
			'gateway' => $gateway,
			'data' => var_export($data, true),
			'result' => $result
		);

		return (bool)$this->iaDb->insert($entry, array('date' => iaDb::FUNCTION_NOW), $this->getTableIpnLog());
	}

	protected function _sendEmailNotification(array $transaction)
	{
		// first, do check if gateway has its own email submission
		if ($result = $this->_doGatewayCallback($transaction['gateway'], $transaction))
		{
			return $result;
		}

		$result1 = true;

		$notification = 'transaction_paid';
		if ($this->iaCore->get($notification))
		{
			$iaUsers = $this->iaCore->factory('users');

			$member = $iaUsers->getById($transaction['member_id']);

			if (!$member)
			{
				return false;
			}

			$iaMailer = $this->iaCore->factory('mailer');

			$iaMailer->loadTemplate('transaction_paid');
			$iaMailer->addAddress($member['email']);

			$iaMailer->setReplacements($transaction);
			$iaMailer->setReplacements(array(
				'email' => $member['username'],
				'username' => $member['username'],
				'fullname' => $member['fullname']
			));

			$result1 = $iaMailer->send();
		}

		// notify admin
		$result2 = true;

		$notification.= '_admin';
		if ($this->iaCore->get($notification))
		{
			$iaMailer->loadTemplate($notification);
			$iaMailer->addAddress($this->iaCore->get('site_email'));
			$iaMailer->setReplacements(array(
				'username' => iaUsers::getIdentity()->username,
				'amount' => $transaction['amount'],
				'operation' => $transaction['operation']
			));

			$result2 = $iaMailer->send();
		}

		return $result1 && $result2;
	}

	protected function _doGatewayCallback($gatewayName, array $transaction)
	{
		if (!$gatewayName)
		{
			return false;
		}

		$gatewayInstance = $this->iaCore->factoryPlugin($gatewayName, 'common');

		if ($gatewayInstance && method_exists($gatewayInstance, self::GATEWAY_CALLBACK_NAME))
		{
			return call_user_func(array($gatewayInstance, self::GATEWAY_CALLBACK_NAME), $transaction);
		}

		return false;
	}
}