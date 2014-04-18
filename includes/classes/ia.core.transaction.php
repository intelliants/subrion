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

class iaTransaction extends abstractCore
{
	const CANCELED = 'canceled';
	const FAILED = 'failed';
	const REFUNDED = 'refunded';
	const PASSED = 'passed';
	const PENDING = 'pending';

	const TRANSACTION_MEMBER_BALANCE = 'balance';

	protected static $_table = 'transactions';
	protected $_tableGateways = 'payment_gateways';

	protected $_gateways;

	public $dashboardStatistics = true;


	public function getTableGateways()
	{
		return $this->_tableGateways;
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
			$this->_gateways = $this->iaDb->keyvalue(array('name', 'gateway'), null, $this->getTableGateways());
		}

		return $this->_gateways;
	}

	/**
	 * Checks if payment gateway installed
	 *
	 * @param string $aGateway payment gateway name
	 *
	 * @return boolean
	 */
	public function isPaymentGateway($gatewayName)
	{
		return $this->iaDb->exists('`name` = :gateway', array('gateway' => $gatewayName), $this->getTableGateways());
	}

	/**
	 * Return payment gateways
	 *
	 * @param string $aGateway payment gateway name
	 *
	 * @return string
	 */
	public function getPaymentGatewayByName($gatewayName)
	{
		return $this->one_bind('gateway', '`name` = :gateway', array('gateway' => $gatewayName), $this->getTableGateways());
	}

	public function update(array $transactionData, $id)
	{
		$result = false;

		if ($transaction = $this->getById($id))
		{
			$result = (bool)$this->iaDb->update($transactionData, iaDb::convertIds($id), array('date' => iaDb::FUNCTION_NOW), self::getTable());

			if ($result && !empty($transactionData['status']))
			{
				$operation = empty($transactionData['item']) ? $transaction['item'] : $transactionData['item'];
				if (self::TRANSACTION_MEMBER_BALANCE == $operation)
				{
					$itemId = empty($transactionData['item_id']) ? $transaction['item_id'] : $transactionData['item_id'];
					$amount = empty($transactionData['total']) ? $transaction['total'] : $transactionData['total'];

					if (self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status'])
					{
						$result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), array('funds' => '`funds` + ' . $amount), iaUsers::getTable());
					}
					elseif (self::PASSED != $transactionData['status'] && self::PASSED == $transaction['status'])
					{
						$result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), array('funds' => '`funds` - ' . $amount), iaUsers::getTable());
					}
				}
			}
		}

		return $result;
	}

	public function delete($aId)
	{
		$res = false;
		if ($aId)
		{
			$res = $this->iaDb->delete("`id` = '{$aId}' AND `status` != 'passed'", self::getTable());
		}

		return $res;
	}

	public function getById($transactionId)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($transactionId), self::getTable());
	}

	public function getBy($key, $id)
	{
		return $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`' . $key . '` = :id', array('id' => $id), self::getTable());
	}

	public function createIpn($transaction)
	{
		unset($transaction['id'], $transaction['date']);

		$this->iaDb->insert($transaction, array('date' => iaDb::FUNCTION_NOW), self::getTable());

		return $this->iaDb->getInsertId();
	}

	/*
	 * Generates invoice for an item
	 *
	 * @param string $title     plan title
	 * @param double $cost      plan cost
	 * @param string $aItem     item name
	 * @param array $aItemInfo  item details
	 * @param string $returnUrl return URL
	 * @param integer $aPlan    plan id
	 * @param boolean $return   true redirects to invoice payment URL
	 *
	 * @return string invoice unique ID
	 */
	public function createInvoice($title, $cost, $itemName = 'members', $itemData = array(), $returnUrl = '', $planId = 0, $return = false)
	{
		if (!isset($itemData['id']))
		{
			$itemData['id'] = 0;
		}
		$title = empty($title) ? iaLanguage::get('plan_title_' . $planId) : $title;
		$transactionId = uniqid('t');
		$pay = array(
			'member_id' => (int)(isset($itemData['member_id']) && $itemData['member_id'] ? $itemData['member_id'] : iaUsers::getIdentity()->id),
			'item' => $itemName,
			'item_id' => $itemData['id'],
			'total' => $cost,
			'currency' => $this->iaCore->get('currency'),
			'sec_key' => $transactionId,
			'status' => self::PENDING,
			'plan_id' => $planId,
			'return_url' => $returnUrl,
			'operation_name' => $title
		);
		$this->iaDb->insert($pay, array('date' => iaDb::FUNCTION_NOW), self::getTable());

		if (!$return)
		{
			iaUtil::go_to(IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER);
		}

		return $transactionId;
	}


	public function getDashboardStatistics()
	{
		$this->iaDb->setTable(self::getTable());

		$currenciesToSymbolMap = array('USD' => '$', 'EUR' => '€', 'RMB' => '¥', 'CNY' => '¥');

		$currency = strtoupper($this->iaCore->get('currency'));
		$currency = isset($currenciesToSymbolMap[$currency]) ? $currenciesToSymbolMap[$currency] : '';


		$data = array();
		$weekDay = getdate();
		$weekDay = $weekDay['wday'];
		$stmt = '`status` = :status AND DATE(`date`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date`)';
		$this->iaDb->bind($stmt, array('status' => self::PASSED));
		$rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date`)), SUM(`total`)', $stmt);
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

		$total = $this->iaDb->one_bind('ROUND(SUM(`total`)) `total`', '`status` = :status', array('status' => self::PASSED));
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
}