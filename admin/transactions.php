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

class iaBackendController extends iaAbstractControllerBackend
{
	protected $_name = 'transactions';

	protected $_gridFilters = array('email' => self::EQUAL, 'reference_id' => self::LIKE, 'status' => self::EQUAL, 'gateway' => self::EQUAL);
	protected $_gridQueryMainTableAlias = 't';

	protected $_processAdd = false;
	protected $_processEdit = false;

	protected $_phraseGridEntryDeleted = 'transaction_deleted';


	public function __construct()
	{
		parent::__construct();

		$iaTransaction = $this->_iaCore->factory('transaction');
		$this->setHelper($iaTransaction);

		$this->setTable(iaTransaction::getTable());
	}

	protected function _gridRead($params)
	{
		$action = (1 == count($this->_iaCore->requestPath)) ? $this->_iaCore->requestPath[0] : null;

		switch ($action)
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
				if (!isset($params['itemname']) || (isset($params['itemname']) && iaUsers::getItemName() == $params['itemname']))
				{
					$stmt = iaDb::convertIds(iaUsers::getItemName(), 'item');

					$output['data'][] = array('title' => iaLanguage::get('funds'), 'value' => 0);
				}
				elseif (!empty($params['itemname']))
				{
					$stmt = iaDb::convertIds($params['itemname'], 'item');
				}

				$this->_iaCore->factory('plan');

				if ($planIds = $this->_iaDb->onefield(iaDb::ID_COLUMN_SELECTION, $stmt, null, null, iaPlan::getTable()))
				{
					foreach ($planIds as $planId)
					{
						$output['data'][] = array('title' => iaLanguage::get('plan_title_' . $planId), 'value' => $planId);
					}
				}

				break;

			case 'gateways':
				$output = array('data' => null);

				if ($items = $this->getHelper()->getPaymentGateways())
				{
					foreach ($items as $name => $title)
					{
						$output['data'][] = array('value' => $name, 'title' => $title);
					}
				}

				break;

			case 'members':
				$output = array('data' => null);

				if (!empty($params['query']))
				{
					$where[] = 'CONCAT(`username`, `fullname`) LIKE :username';
					$values['username'] = '%' . iaSanitize::sql($params['query']) . '%';
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
				. 't.`date_created`, t.`status`, t.`currency`, t.`operation`, t.`plan_id`, t.`reference_id`, '
				. "t.`gateway`, IF(t.`fullname` = '', m.`username`, t.`fullname`) `user`, IF(t.`status` != 'passed', 1, 0) `delete` " .
			'FROM `:prefix:table_transactions` t ' .
			'LEFT JOIN `:prefix:table_members` m ON (m.`id` = t.`member_id`) ' .
			($where ? 'WHERE ' . $where . ' ' : '') . str_replace('t.`user`', '`user`', $order) . ' ' .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_members' => iaUsers::getTable(),
			'table_transactions' => $this->getTable(),
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _modifyGridParams(&$conditions, &$values, array $params)
	{
		if (!empty($params['item']))
		{
			$conditions[] = ('members' == $params['item']) ? "(t.`item` = :item OR t.`item` = 'funds') " : 't.`item` = :item';
			$values['item'] = $params['item'];
		}
		if (!empty($params['username']))
		{
			$conditions[] = 'm.`username` LIKE :username';
			$values['username'] = '%' . $params['username'] . '%';
		}
	}

	protected function _jsonAction() // ADD action is handled here
	{
		$output = array('error' => false, 'message' => array());

		$transaction = array(
			'member_id' => (int)$_POST['member'],
			'plan_id' => (int)$_POST['plan'],
			'email' => $_POST['email'],
			'item_id' => (int)$_POST['itemid'],
			'gateway' => (string)$_POST['gateway'],
			'sec_key' => uniqid('t'),
			'reference_id' => empty($_POST['reference_id']) ? date('mdyHis') : iaSanitize::htmlInjectionFilter($_POST['reference_id']),
			'amount' => (float)$_POST['amount'],
			'currency' => $this->_iaCore->get('currency'),
			'date_created' => $_POST['date'] . ' ' . $_POST['time']
		);

		if ($transaction['plan_id'])
		{
			$this->_iaCore->factory('plan');

			if ($plan = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($transaction['plan_id']), iaPlan::getTable()))
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
			$transaction['item'] = iaTransaction::TRANSACTION_MEMBER_BALANCE;
			$transaction['operation'] = iaLanguage::get('funds');
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

		if ($transaction['email'] && !iaValidate::isEmail($transaction['email']))
		{
			$output['error'] = true;
			$output['message'][] = iaLanguage::get('error_email_incorrect');
		}

		if (isset($transaction['item']) && in_array($transaction['item'], array(iaTransaction::TRANSACTION_MEMBER_BALANCE, 'members')))
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

		if (isset($output['success']) && $output['success'])
		{
			$this->_iaCore->startHook('phpTransactionCreated', array('id' => $output['success'], 'transaction' => $transaction));
			$output['success'] = (bool)$output['success'];
		}

		return $output;
	}
}