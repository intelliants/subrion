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

class iaPlan extends abstractCore
{
	const SPONSORED = 'sponsored';
	const SPONSORED_DATE_START = 'sponsored_start';
	const SPONSORED_DATE_END = 'sponsored_end';
	const SPONSORED_PLAN_ID = 'sponsored_plan_id';

	protected static $_table = 'plans';

	protected $_item;
	protected $_plans = array();


	/**
	 * Payment pre-processing actions
	 *
	 * @param $itemName item name
	 * @param $itemData current item data, id field is mandatory
	 * @param $planId plan id to be paid for
	 * @param null $title transaction title
	 * @param int $amount amount to be paid
	 * @param string $returnUrl post payment return url
	 *
	 * @return bool|string
	 */
	public function prePayment($itemName, $itemData, $planId, $title = null, $amount = 0, $returnUrl = IA_URL)
	{
		if ($planId == 0 || !isset($this->_plans[$planId]))
		{
			return $returnUrl;
		}

		if (empty($itemData))
		{
			return false;
		}

		$cost = $amount ? $amount : $this->_plans[$planId]['cost'];

		if ('members' != $itemName && !empty($itemData[self::SPONSORED]))
		{
			$rdbmsDate = $this->iaDb->one('CURDATE()');
			$daysLeft = strtotime($itemData[self::SPONSORED_DATE_END]) - strtotime($rdbmsDate);
			$daysLeft = $daysLeft > 0 ? $daysLeft / 86400 : 0;
			$cost -= round($daysLeft * ($itemData['cost'] / $itemData['days']), 2);
		}

		$iaTransaction = $this->iaCore->factory('transaction');
		$paymentId = $iaTransaction->createInvoice($title, $cost, $itemName, $itemData, $returnUrl, $planId, true);

		return IA_URL . 'pay' . IA_URL_DELIMITER . $paymentId . IA_URL_DELIMITER;
	}

	/**
	 * Payment post-processing actions
	 *
	 * @param $transaction transaction information
	 *
	 * @return bool
	 */
	public function postPayment($transaction)
	{
		if ($transaction['status'] == 'passed')
		{
			$planId = intval($transaction['plan_id']);
			$itemId = intval($transaction['item_id']);
			$item = $transaction['item'];

			if ($item == 'balance' && $planId == 0)
			{
				$this->iaDb->update(null, iaDb::convertIds($transaction['member_id']), array('funds' => "`funds` + {$transaction['total']}"), iaUsers::getTable());

				if ($transaction['member_id'] == iaUsers::getIdentity()->id)
				{
					$this->iaCore->factory('users')->getAuth($transaction['member_id']);
				}

				return true;
			}
			elseif ($planId != 0 && $itemId != 0)
			{
				return $this->payItem($transaction);
			}
		}

		return false;
	}

	public function getPlanById($planId)
	{
		$plan = null;

		if (isset($planId) && !is_array($planId))
		{
			$stmt = iaDb::printf("`status` = ':status' AND `id` = :id", array(
				'status' => iaCore::STATUS_ACTIVE,
				'id' => (int)$planId
			));
			$plan = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, $stmt, self::getTable());
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
	public function getPlans($itemName = null)
	{
		if (is_null($itemName))
		{
			return isset($this->_item) ? $this->_item : array();
		}

		if (!isset($this->_item) || ($this->_item != $itemName))
		{
			if ($plans = $this->iaDb->all(array('id', 'days', 'cost', 'data'), "`item` = '{$itemName}' AND `status` = 'active' ORDER BY `order` ASC ", null, null, self::getTable()))
			{
				foreach ($plans as $plan)
				{
					$plan['data'] = unserialize($plan['data']);
					$plan['fields'] = isset($plan['data']['fields']) ? implode(',', $plan['data']['fields']) : '';

					$this->_plans[$plan['id']] = $plan;
				}
			}

			$this->_item = $itemName;
		}

		return $this->_plans;
	}

	/**
	 * write funds off from account balance.
	 *
	 * @param array $aTransaction data about transaction
	 *
	 * @return bool true on success
	 */
	public function extractFunds($aTransaction)
	{
		$iaDb = &$this->iaDb;

		$ret = false;
		$funds = iaUsers::hasIdentity() ? $iaDb->one_bind('funds', '`id` = :user', array('user' => iaUsers::getIdentity()->id), iaUsers::getTable()) : 0;
		$balance = $funds - $aTransaction['total'];
		if ($balance >= 0)
		{
			$iaDb->update(array('funds' => $balance), '`id` = ' . iaUsers::getIdentity()->id, null, iaUsers::getTable());
			$_SESSION['user']['funds'] = $balance;

			$ret = true;

			// close transaction
			$trans = array('status' => 'passed', 'gateway_name' => 'balance', 'order_number' => date("YmdHis"));

			// change member_id if different account makes the payment
			if (iaUsers::hasIdentity() && iaUsers::getIdentity()->id != $aTransaction['member_id'])
			{
				$trans['member_id'] = iaUsers::getIdentity()->id;
			}
			$iaDb->update($trans, "`id` = '{$aTransaction['id']}'", array('date' => iaDb::FUNCTION_NOW), 'transactions');

			$this->payItem($aTransaction);
			//$this->enableItem($aTransaction);
		}

		return $ret;
	}

	public function payItem($aTransaction)
	{
		$item = $aTransaction['item'];
		if (!empty($item) && $item != 'balance')
		{
			// update item sponsored record
			if (empty($this->_plans))
			{
				$this->getPlans($item);
			}
			$this->iaDb->setTable(self::getTable());
			$days = (int)$this->iaDb->one('days', "`id` = {$aTransaction['plan_id']}");
			$this->iaDb->resetTable();

			// TODO: use enableItem method
			// if plan remains the same and not expired yet, use remained days too
			if ($this->_plans[$aTransaction['plan_id']]['days'] > 0)
			{
				$upd = sprintf("if (`sponsored` AND `sponsored_plan_id`=%d AND `sponsored_end`>NOW(), DATE_ADD(`sponsored_end`, INTERVAL %d DAY), DATE_ADD(NOW(), INTERVAL %d DAY))", $aTransaction['plan_id'], $days, $days);
			}
			else
			{
				$upd = '0000-00-00';
			}

			$this->iaDb->update(array(self::SPONSORED => 1, self::SPONSORED_PLAN_ID => $aTransaction['plan_id'], 'status' => iaCore::STATUS_ACTIVE), "`id` = '{$aTransaction['item_id']}'", array(self::SPONSORED_DATE_END => $upd), $item);

			// initiating class to perform item specific actions when plan is assigned
			$class_name = ucfirst(substr($item, 0, -1));
			$iaItems = $this->iaCore->factory('item');
			$iaItem = ($item == 'members')
				? $this->iaCore->factory('users')
				: $this->iaCore->factoryPackage($class_name, $iaItems->getPackageByItem($item));

			if ($iaItem && method_exists($iaItem, 'postPayment'))
			{
				$aPlan = $this->getPlanById($aTransaction['plan_id']);

				return $iaItem->postPayment($aPlan, $aTransaction);
			}
		}

		return false;
	}

	/**
	 * Mark item as sponsored and set sponsored expire date
	 */
	public function enableItem($aTransaction)
	{
		if ('balance' == $aTransaction['item'])
		{
			$this->iaDb->update(array(), "`id`='{$aTransaction['item_id']}'", array('funds' => "`funds`+{$aTransaction['total']}"), $aTransaction['item']);
		}
		else
		{
			if (empty($this->_plans))
			{
				$this->getPlans($aTransaction['item']);
			}
			$days = (int)$this->iaDb->one('days', "`id`={$aTransaction['plan_id']}");
			// if plan remains the same and not expired yet, use remained days too
			if ($this->_plans[$aTransaction['plan_id']]['days'])
			{
				$upd = sprintf("IF(`sponsored` AND `sponsored_plan_id` = %d AND `sponsored_end` > NOW(), DATE_ADD(`sponsored_end`, INTERVAL %d DAY), DATE_ADD(NOW(), INTERVAL %d DAY))", $aTransaction['plan_id'], $days, $days);
			}
			else
			{
				$upd = '0000-00-00';
			}
			$this->iaDb->update(array(self::SPONSORED => 1), "`id`='{$aTransaction['item_id']}'", array(self::SPONSORED_DATE_END => $upd),	$aTransaction['item']);
		}
	}
}