<?php
//##copyright##

$iaTransaction = $iaCore->factory('transaction');

$iaDb->setTable(iaTransaction::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			switch ($_GET['get'])
			{
				case 'items':
					$output = array('data' => null);
					if ($items = $iaDb->onefield('item', '`payable` = 1', null, null, 'items'))
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
/*					if (isset($_POST['itemname']) && $_POST['itemname'])
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
//					}

					if ($plans = $iaDb->all(array('id'), $stmt, null, null, 'plans'))
					{
						foreach ($plans as $key => $plan)
						{
							$output['data'][] = array('title' => iaLanguage::get('plan_title_' . $plan['id']), 'value' => $plan['id']);
						}
					}

					break;

				case 'gateways':
					$output = array('data' => null);

					if ($items = $iaDb->onefield('gateway', null, null, null, $iaTransaction->getTableGateways()))
					{
						foreach ($items as $key => $item)
						{
							$output['data'][] = array('title' => $item, 'value' => $item);
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
					$iaDb->bind($where, $values);

					if ($members = $iaDb->all(array('id', 'username', 'fullname'), $where, null, null, iaUsers::getTable()))
					{
						foreach ($members as $member)
						{
							$output['data'][] = array('title' => $member['username'], 'value' => $member['id']);
						}
					}

					break;

				default:
					$sort = $_GET['sort'];
					$dir = in_array($_GET['dir'], array('ASC', 'DESC')) ? $_GET['dir'] : 'ASC';
					$order = ($sort && $dir) ? " ORDER BY `{$sort}` {$dir} " : '';

					$values = array();
					$conditions = array();

					if (isset($_GET['email']) && $_GET['email'])
					{
						$conditions[] = 't.`email` = :email';
						$values['email'] = $_GET['email'];
					}
					if (isset($_GET['order_number']) && $_GET['order_number'])
					{
						$conditions[] = '`t`.`order_number` LIKE :order';
						$values['order'] = '%' . $_GET['order_number'] . '%';
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

					$condition = isset($_GET['condition']) && in_array($_GET['condition'], array('OR', 'AND')) ? $_GET['condition'] : 'AND';
					$where = '';

					if ($condition && $conditions)
					{
						$where = implode(' ' . $condition . ' ', $conditions);
						$iaDb->bind($where, $values);
					}

					$sql =
						'SELECT SQL_CALC_FOUND_ROWS ' .
						't.*, a.`username`, p.`id` `plan_id`, a.`id` `delete` ' .
						'FROM `:prefix:transactions` t ' .
						'LEFT JOIN `:prefixplans` p ON (p.`id` = t.`plan_id`) ' .
						'LEFT JOIN `:prefix:members` a ON (a.`id` = t.`member_id`) ' .
						($where ? 'WHERE ' . $where : '') . $order .
						'LIMIT :start, :limit';
					$sql = iaDb::printf($sql, array(
						'prefix' => $iaDb->prefix,
						'members' => iaUsers::getTable(),
						'transactions' => iaTransaction::getTable(),
						'start' => isset($_GET['start']) ? (int)$_GET['start'] : 0,
						'limit' => isset($_GET['limit']) ? (int)$_GET['limit'] : 15
					));

					$output = array(
						'data' => $iaDb->getAll($sql),
						'total' => $iaDb->foundRows()
					);

					if ($output['data'])
					{
						foreach ($output['data'] as &$entry)
						{
							$entry['plan_title'] = $entry['operation_name'];
							$entry['total'] .= ' ' . $entry['currency'];
						}
					}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridUpdate($_POST);

			if ($output['result'] && isset($_POST['status']))
			{
				foreach ($_POST['id'] as $transactionId)
				{
					$transaction = $iaTransaction->getById((int)$transactionId);
					if ('balance' == $transaction['item'])
					{
						$iaDb->setTable(iaUsers::getTable());
						if (iaTransaction::PASSED == $_POST['status'])
						{
							$iaDb->update(null, '`id` = ' . $transaction['item_id'], array('funds' => '`funds` + ' . $transaction['total']));
						}
						else
						{
							if (iaTransaction::PASSED == $transaction['status'])
							{
								$iaDb->update(null, '`id` = ' . $transaction['item_id'], array('funds' => '`funds` - ' . $transaction['total']));
							}
						}
						$iaDb->resetTable();
					}
				}
			}

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridDelete($_POST, 'transaction_deleted');

			break;

		case iaCore::ACTION_ADD:
			$output = array('error' => false, 'message' => array());

			$transaction = array(
				'member_id' => (int)$_POST['member'],
				'plan_id' => (int)$_POST['plan'],
				'email' => $_POST['email'],
				'item_id' => (int)$_POST['itemid'],
				'gateway_name' => (string)$_POST['payment'],
				'sec_key' => uniqid('t'),
				'order_number' => $_POST['order'],
				'total' => $_POST['total'],
				'currency' => $iaCore->get('currency'),
				'date' => $_POST['date'] . ' ' . $_POST['time']
			);

			if ($transaction['plan_id'])
			{
				if ($plan = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`id` = :id', array('id' => $transaction['plan_id']), 'plans'))
				{
					$transaction['item'] = $plan['item'];
					$transaction['operation_name'] = iaLanguage::get('plan_title_' . $plan['id']);
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
				$transaction['operation_name'] = iaLanguage::get('member_balance');
			}

			if (isset($_POST['username']) && $_POST['username'])
			{
				if ($memberId = $iaDb->one_bind('`id`', '`username` = :user', array('user' => $_POST['username']), iaUsers::getTable()))
				{
					$transaction['member_id'] = $memberId;
				}
				else
				{
					$output['error'] = true;
					$output['message'][] = iaLanguage::get('incorrect_username');
				}
			}

			if (!iaValidate::isEmail($transaction['email']))
			{
				$output['error'] = true;
				$output['message'][] = iaLanguage::get('error_email_incorrect');
			}

			if (isset($transaction['item']) && in_array($transaction['item'], array('balance', 'members')))
			{
				$transaction['item_id'] = $transaction['member_id'];
			}

			if (!$output['error'])
			{
				$output['success'] = (bool)$iaDb->insert($transaction, null, iaTransaction::getTable());
				$output['message'] = $output['success']
					? iaLanguage::get('transaction_added')
					: iaLanguage::get('invalid_parameters');
			}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->grid('admin/transactions');
}

$iaDb->resetTable();