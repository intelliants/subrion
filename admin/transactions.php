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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'transactions';

	protected $_processAdd = false;
	protected $_processEdit = false;

	protected $_phraseGridEntryDeleted = 'transaction_deleted';


	public function __construct()
	{
		parent::__construct();

		$iaTransaction = $this->_iaCore->factory('transaction');
		$this->setHelper($iaTransaction);
	}

	protected function _gridRead($params)
	{
		switch ($_GET['get'])
		{
			case 'items':
				$output = array('data' => null);

				if ($items = $this->_iaCore->factory('item')->getItems(true))
				{
					foreach ($items as $key => $item)
					{
						$output['data'][] = array('title' => iaLanguage::get($item), 'value' => $item);
					}
				}

				break;

			case 'plans':
				$output = array('data' => null);

				$stmt = '';
/*				if (isset($_POST['itemname']) && $_POST['itemname'])
				{
					$stmt = "`item` = '{$_POST['itemname']}' ";
				}
				if (!empty($_POST['itemname']) && 'accounts' == $_POST['itemname'])
				{
					$output['data'][] = array('title' => iaLanguage::get('member_balance'), 'value' => 0);
				}
				elseif (!isset($_POST['itemname']))
				{*/
				$output['data'][] = array('title' => iaLanguage::get('member_balance'), 'value' => 0);
//				}

				if ($planIds = $this->_iaDb->onefield(iaDb::ID_COLUMN_SELECTION, $stmt, null, null, 'plans'))
				{
					foreach ($planIds as $planId)
					{
						$output['data'][] = array('title' => iaLanguage::get('plan_title_' . $planId), 'value' => $planId);
					}
				}

				break;

			case 'gateways':
				$output = array('data' => null);

				if ($items = $this->_iaDb->keyvalue(array('name', 'title'), null, $this->getHelper()->getTableGateways()))
				{
					foreach ($items as $name => $title)
					{
						$output['data'][] = array('value' => $name, 'title' => $title);
					}
				}

				break;

			case 'members':
				$output = array('data' => null);

				if (isset($_GET['query']) && $_GET['query'])
				{
					$where[] = 'CONCAT(`username`, `fullname`) LIKE :username';
					$values['username'] = '%' . iaSanitize::sql($_GET['query']) . '%';
				}

				$where || $where[] = iaDb::EMPTY_CONDITION;
				$where = implode(' AND ', $where);
				$this->_iaDb->bind($where, $values);

				if ($members = $this->_iaDb->all(array('id', 'username', 'fullname'), $where, null, null, iaUsers::getTable()))
				{
					foreach ($members as $member)
					{
						$output['data'][] = array('title' => $member['username'], 'value' => $member['id']);
					}
				}

				break;

			default:
				$output = parent::_gridRead($params);
		}

		return $output;
	}

	protected function _entryUpdate(array $values, $entryId)
	{
		return $this->getHelper()->update($values, $entryId);
	}

	protected function _entryDelete($entryId)
	{
		return $this->getHelper()->delete($entryId);
	}

	protected function _gridQuery($columns, $where, $order, $start, $limit)
	{
		$sql =
			'SELECT SQL_CALC_FOUND_ROWS '
				. 't.`id`, t.`item`, t.`item_id`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, '
				. 't.`date`, t.`status`, t.`currency`, t.`operation`, t.`plan_id`, t.`reference_id`, '
				. "m.`username`, IF(t.`status` != 'passed', 1, 0) `delete` " .
			'FROM `:prefix:transactions` t ' .
			'LEFT JOIN `:prefix:plans` p ON (p.`id` = t.`plan_id`) ' .
			'LEFT JOIN `:prefix:members` m ON (m.`id` = t.`member_id`) ' .
			($where ? 'WHERE ' . $where . ' ' : '') . $order .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'plans' => $this->getTable(),
			'members' => iaUsers::getTable(),
			'transactions' => iaTransaction::getTable(),
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _modifyGridParams(&$conditions, &$values)
	{
		if (isset($_GET['email']) && $_GET['email'])
		{
			$conditions[] = 't.`email` = :email';
			$values['email'] = $_GET['email'];
		}
		if (isset($_GET['reference_id']) && $_GET['reference_id'])
		{
			$conditions[] = 't.`reference_id` LIKE :reference';
			$values['reference'] = '%' . $_GET['reference_id'] . '%';
		}
		if (isset($_GET['item']) && $_GET['item'])
		{
			$conditions[] = ('members' == $_GET['item']) ? "(t.`item` = :item OR t.`item` = 'balance') " : 't.`item` = :item';
			$values['item'] = $_GET['item'];
		}
		if (isset($_GET['username']) && $_GET['username'])
		{
			$conditions[] = 'a.`username` LIKE :username';
			$values['username'] = '%' . $_GET['username'] . '%';
		}
		if (isset($_GET['status']) && $_GET['status'] && in_array($_GET['status'], array('pending', 'passed', 'failed', 'refunded')))
		{
			$conditions[] = 't.`status` = :status';
			$values['status'] = $_GET['status'];
		}
	}

	protected function _jsonAction() // ADD action is handled here
	{
		$output = array('error' => false, 'message' => array());

		$transaction = array(
			'member_id' => (int)$_POST['member'],
			'plan_id' => (int)$_POST['plan'],
			//'email' => $_POST['email'],
			'item_id' => (int)$_POST['itemid'],
			'gateway' => (string)$_POST['payment'],
			'sec_key' => uniqid('t'),
			'reference_id' => empty($_POST['order']) ? date('mdyHis') : $_POST['order'],
			'amount' => (float)$_POST['amount'],
			'currency' => $this->_iaCore->get('currency'),
			'date' => $_POST['date'] . ' ' . $_POST['time']
		);

		if ($transaction['plan_id'])
		{
			if ($plan = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($transaction['plan_id']), 'plans'))
			{
				$transaction['item'] = $plan['item'];
				$transaction['operation'] = iaLanguage::get('plan_title_' . $plan['id']);
			}
			else
			{
				$output['error'] = true;
				$output['message'][] = iaLanguage::get('error_plan_not_exists');
			}
		}
		else
		{
			$transaction['item'] = 'balance';
			$transaction['operation'] = iaLanguage::get('member_balance');
		}

		if (isset($_POST['username']) && $_POST['username'])
		{
			if ($memberId = $this->_iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`username` = :user', array('user' => $_POST['username']), iaUsers::getTable()))
			{
				$transaction['member_id'] = $memberId;
			}
			else
			{
				$output['error'] = true;
				$output['message'][] = iaLanguage::get('incorrect_username');
			}
		}

/*		if (!iaValidate::isEmail($transaction['email']))
		{
			$output['error'] = true;
			$output['message'][] = iaLanguage::get('error_email_incorrect');
		}*/

		if (isset($transaction['item']) && in_array($transaction['item'], array('balance', 'members')))
		{
			$transaction['item_id'] = $transaction['member_id'];
		}

		if (!$output['error'])
		{
			$output['success'] = (bool)$this->_iaDb->insert($transaction);
			$output['message'] = $output['success']
				? iaLanguage::get('transaction_added')
				: iaLanguage::get('invalid_parameters');
		}

		return $output;
	}
}