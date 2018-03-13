<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
 *
 ******************************************************************************/

class iaBackendController extends iaAbstractControllerBackend
{
    protected $_name = 'transactions';

    protected $_gridFilters = [
        'email' => self::EQUAL,
        'reference_id' => self::LIKE,
        'status' => self::EQUAL,
        'gateway' => self::EQUAL
    ];
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

        switch ($action) {
            case 'items':
                $output = ['data' => null];

                if ($items = $this->_iaCore->factory('item')->getItems(true)) {
                    foreach ($items as $key => $item) {
                        $output['data'][] = ['title' => iaLanguage::get($item), 'value' => $item];
                    }
                }

                break;

            case 'plans':
                $output = ['data' => null];

                $stmt = '';
                if (!isset($params['itemname']) || (isset($params['itemname']) && iaUsers::getItemName() == $params['itemname'])) {
                    $stmt = iaDb::convertIds(iaUsers::getItemName(), 'item');

                    $output['data'][] = ['title' => iaLanguage::get('funds'), 'value' => 0];
                } elseif (!empty($params['itemname'])) {
                    $stmt = iaDb::convertIds($params['itemname'], 'item');
                }

                $this->_iaCore->factory('plan');

                if ($planIds = $this->_iaDb->onefield(iaDb::ID_COLUMN_SELECTION, $stmt, null, null,
                    iaPlan::getTable())
                ) {
                    foreach ($planIds as $planId) {
                        $output['data'][] = ['title' => iaLanguage::get('plan_title_' . $planId), 'value' => $planId];
                    }
                }

                break;

            case 'gateways':
                $output = ['data' => null];

                if ($items = $this->getHelper()->getPaymentGateways()) {
                    foreach ($items as $name => $title) {
                        $output['data'][] = ['value' => $name, 'title' => $title];
                    }
                }

                break;

            case 'members':
                $output = ['data' => null];

                if (!empty($params['query'])) {
                    $where[] = 'CONCAT_WS(`username`, `fullname`) LIKE :username';
                    $values['username'] = '%' . iaSanitize::sql($params['query']) . '%';
                }

                $where || $where[] = iaDb::EMPTY_CONDITION;
                $where = implode(' AND ', $where);
                $this->_iaDb->bind($where, $values);

                if ($members = $this->_iaDb->all(['id', 'username', 'fullname'], $where, null, null,
                    iaUsers::getTable())
                ) {
                    foreach ($members as $member) {
                        $output['data'][] = ['value' => $member['id'], 'title' => $member['username']];
                    }
                }

                break;

            default:
                if (isset($params['export_excel']) && $params['export_excel']) {
                    $order = $this->_gridGetSorting($params);

                    $conditions = $values = [];
                    foreach ($this->_gridFilters as $name => $type) {
                        if (isset($params[$name]) && $params[$name]) {
                            $value = $params[$name];

                            switch ($type) {
                                case self::EQUAL:
                                    $conditions[] = sprintf('%s`%s` = :%s', $this->_gridQueryMainTableAlias, $name,
                                        $name);
                                    $values[$name] = $value;
                                    break;
                                case self::LIKE:
                                    $conditions[] = sprintf('%s`%s` LIKE :%s', $this->_gridQueryMainTableAlias, $name,
                                        $name);
                                    $values[$name] = '%' . $value . '%';
                            }
                        }
                    }

                    $this->_gridModifyParams($conditions, $values, $params);

                    $conditions || $conditions[] = iaDb::EMPTY_CONDITION;
                    $conditions = implode(' AND ', $conditions);
                    $this->_iaDb->bind($conditions, $values);

                    $columns = $this->_gridUnpackColumnsArray();

                    if ($data = $this->_gridQuery($columns, $conditions, $order, 0, 2147483640, true)) {
                        $this->_gridModifyOutput($data);

                        require_once IA_INCLUDES . 'utils/php-export-data.class.php';
                        $exportExcel = new ExportDataExcel('file', IA_TMP . 'transactions.xls');

                        $exportExcel->initialize();

                        $titles = [
                            'username',
                            'plan',
                            'item',
                            'item_id',
                            'reference_id',
                            'total',
                            'gateway',
                            'status',
                            'date'
                        ];
                        $exportExcel->addRow(array_map(function ($key) {
                            return iaLanguage::get($key);
                        }, $titles));
                        foreach ($data as $row) {
                            $exportExcel->addRow($row);
                        }
                        $exportExcel->finalize();

                        $result['result'] = true;
                        $result['redirect_url'] = IA_CLEAR_URL . 'tmp/transactions.xls';
                    }
                }

                $output = parent::_gridRead($params);
        }

        return isset($result) ? array_merge($output, $result) : $output;
    }

    protected function _entryUpdate(array $values, $entryId)
    {
        return $this->getHelper()->update($values, $entryId);
    }

    protected function _entryDelete($entryId)
    {
        return $this->getHelper()->delete($entryId);
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit, $isExport = false)
    {
        $fields = $isExport
            ? 'SELECT IF(t.`fullname` = \'\', m.`username`, t.`fullname`) `user`, ' .
            't.`operation`, t.`item`, t.`item_id`, t.`reference_id`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, ' .
            't.`gateway`, t.`status`, t.`date_created`'
            : 'SELECT SQL_CALC_FOUND_ROWS ' .
            't.`id`, t.`item`, t.`item_id`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, ' .
            't.`date_created`, t.`status`, t.`currency`, t.`operation`, t.`plan_id`, t.`reference_id`, ' .
            "t.`gateway`, IF(t.`fullname` = '', m.`username`, t.`fullname`) `user`, IF(t.`status` != 'passed', 1, 0) `delete` ";

        $sql = $fields . 'FROM `:prefix:table_transactions` t ' .
            'LEFT JOIN `:prefix:table_members` m ON (m.`id` = t.`member_id`) ' .
            ($where ? 'WHERE ' . $where . ' ' : '') . str_replace('t.`user`', '`user`', $order) . ' ' .
            'LIMIT :start, :limit';
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_members' => iaUsers::getTable(),
            'table_transactions' => $this->getTable(),
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['item'])) {
            $conditions[] = ('members' == $params['item']) ? "(t.`item` = :item OR t.`item` = 'funds') " : 't.`item` = :item';
            $values['item'] = $params['item'];
        }
        if (!empty($params['username'])) {
            $conditions[] = 'm.`username` LIKE :username';
            $values['username'] = '%' . $params['username'] . '%';
        }
    }

    protected function _jsonAction(&$iaView) // ADD action is handled here
    {
        $output = ['error' => false, 'message' => []];

        $transaction = [
            'member_id' => (int)$_POST['member'],
            'plan_id' => (int)$_POST['plan'],
            'email' => $_POST['email'],
            'item_id' => (int)$_POST['itemid'],
            'gateway' => (string)$_POST['gateway'],
            'sec_key' => uniqid('t'),
            'reference_id' => empty($_POST['reference_id']) ? (new \DateTime())->format('mdyHis') : iaSanitize::htmlInjectionFilter($_POST['reference_id']),
            'amount' => (float)$_POST['amount'],
            'currency' => $this->_iaCore->get('currency'),
            'date_created' => $_POST['date'] . ' ' . $_POST['time']
        ];

        if ($transaction['plan_id']) {
            $this->_iaCore->factory('plan');

            if ($plan = $this->_iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($transaction['plan_id']),
                iaPlan::getTable())
            ) {
                $transaction['item'] = $plan['item'];
                $transaction['operation'] = iaLanguage::get('plan_title_' . $plan['id']);
            } else {
                $output['error'] = true;
                $output['message'][] = iaLanguage::get('error_plan_not_exists');
            }
        } else {
            $transaction['item'] = iaTransaction::TRANSACTION_MEMBER_BALANCE;
            $transaction['operation'] = iaLanguage::get('funds');
        }

        if (isset($_POST['username']) && $_POST['username']) {
            if ($memberId = $this->_iaDb->one_bind(iaDb::ID_COLUMN_SELECTION, '`username` = :user',
                ['user' => $_POST['username']], iaUsers::getTable())
            ) {
                $transaction['member_id'] = $memberId;
            } else {
                $output['error'] = true;
                $output['message'][] = iaLanguage::get('incorrect_username');
            }
        }

        if ($transaction['email'] && !iaValidate::isEmail($transaction['email'])) {
            $output['error'] = true;
            $output['message'][] = iaLanguage::get('error_email_incorrect');
        }

        if (isset($transaction['item']) && in_array($transaction['item'],
                [iaTransaction::TRANSACTION_MEMBER_BALANCE, 'members'])
        ) {
            $transaction['item_id'] = $transaction['member_id'];
        }

        if (!$output['error']) {
            $output['success'] = (bool)$this->_iaDb->insert($transaction);
            $output['message'] = $output['success']
                ? iaLanguage::get('transaction_added')
                : iaLanguage::get('invalid_parameters');
        }

        if (isset($output['success']) && $output['success']) {
            $this->_iaCore->startHook('phpTransactionCreated',
                ['id' => $output['success'], 'transaction' => $transaction]);
            $output['success'] = (bool)$output['success'];
        }

        return $output;
    }
}
