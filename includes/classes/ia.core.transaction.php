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
        if (is_null($this->_gateways)) {
            $stmt = "`name` IN ('" . implode("','", $this->iaCore->get('module')) . "')";
            $this->_gateways = $this->iaDb->keyvalue(['name', 'title'], $stmt, $this->getTableGateways());
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
        return $this->iaDb->exists('`name` = :name', ['name' => $gatewayName], $this->getTableGateways());
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

        if ($transaction = $this->getById($id)) {
            $transactionData['date_updated'] = date(iaDb::DATETIME_FORMAT);
            !(self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status'])
                    || $transactionData['date_paid'] = date(iaDb::DATETIME_FORMAT);
            $result = (bool)$this->iaDb->update($transactionData, iaDb::convertIds($id), null, self::getTable());

            if ($result && isset($transactionData['status'])) {
                $operation = empty($transactionData['item']) ? $transaction['item'] : $transactionData['item'];

                if (self::TRANSACTION_MEMBER_BALANCE == $operation) {
                    $itemId = empty($transactionData['item_id']) ? $transaction['item_id'] : $transactionData['item_id'];
                    $amount = empty($transactionData['amount']) ? $transaction['amount'] : $transactionData['amount'];

                    if (self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status']) {
                        $result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), ['funds' => '`funds` + ' . $amount], iaUsers::getTable());
                    } elseif (self::PASSED != $transactionData['status'] && self::PASSED == $transaction['status']) {
                        $result = (bool)$this->iaDb->update(null, iaDb::convertIds($itemId), ['funds' => '`funds` - ' . $amount], iaUsers::getTable());
                    }
                }

                if (self::PASSED == $transactionData['status'] && self::PASSED != $transaction['status']) {
                    $transaction = $this->getById($id);

                    $this->_sendEmailNotification($transaction);

                    $this->iaCore->factory('plan')->setPaid($transaction);
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
        if ($transactionId) {
            $result = (bool)$this->iaDb->delete('`id` = :id AND `status` != :status', self::getTable(), ['id' => (int)$transactionId, 'status' => self::PASSED]);
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

    public function getBy($key, $id, $countRows = false)
    {
        return $this->iaDb->row(($countRows ? iaDb::STMT_CALC_FOUND_ROWS . ' ' : '') . iaDb::ALL_COLUMNS_SELECTION,
            iaDb::convertIds($id, $key), self::getTable());
    }

    public function getList()
    {
        $rows = $this->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' ' . iaDb::ALL_COLUMNS_SELECTION,
            iaDb::convertIds(iaUsers::getIdentity()->id, 'member_id'), null, null, self::getTable());
        $count = $this->iaDb->foundRows();

        if ($rows) {
            foreach ($rows as &$row) {
                $row['gateway_title'] = iaLanguage::get($row['gateway'], $row['gateway']);
                $row['gateway_icon'] = null;

                $path = IA_HOME . 'modules/' . $row['gateway'] . '/templates/front/img/';
                if (is_file($path . 'button.png')) {
                    $row['gateway_icon'] = 'button.png';
                } elseif (is_file($path . $row['gateway'] . '.png')) {
                    $row['gateway_icon'] = $row['gateway'] . '.png';
                }

                if ($row['gateway_icon']) {
                    $row['gateway_icon'] = IA_CLEAR_URL . 'modules/' . $row['gateway'] . '/templates/front/img/' . $row['gateway_icon'];
                }
            }
        }

        return [$rows, $count];
    }

    public function createIpn($transaction)
    {
        empty($transaction['status']) || $status = $transaction['status'];
        unset($transaction['id'], $transaction['date_created'], $transaction['status']);

        $id = $this->iaDb->insert($transaction, ['date_created' => iaDb::FUNCTION_NOW], self::getTable());

        if ($id && isset($status)) {
            $this->update(['status' => $status], $id);
        }

        return $id;
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
    public function create($title, $cost, $itemName = 'members', $itemData = [], $returnUrl = '', $planId = 0, $return = false)
    {
        if (!isset($itemData['id'])) {
            $itemData['id'] = 0;
        }

        $title = empty($title) ? iaLanguage::get('plan_title_' . $planId) : $title;
        $title .= ($itemData['id']) ? ' - #' . $itemData['id'] : '';

        $transactionId = uniqid('t');
        $transaction = [
            'member_id' => (int)(empty($itemData['member_id']) ? iaUsers::getIdentity()->id : $itemData['member_id']),
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
        ];

        $result = $this->iaDb->insert($transaction, null, $this->getTable());

        if ($result) {
            if ($transaction['member_id']) { // create corresponding invoice
                $this->iaCore->factory('invoice')->create($transaction, $result);
            }

            $this->iaCore->startHook('phpTransactionCreated', ['id' => $result, 'transaction' => $transaction]);
        }

        $return || iaUtil::go_to(IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER);

        return $result ? $transactionId : false;
    }

    public function getLatestTransactions($limit = 10)
    {
        $sql = <<<SQL
SELECT t.`id`, t.`item`, t.`item_id`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, 
	t.`date_created`, t.`status`, t.`currency`, t.`operation`, t.`plan_id`, t.`reference_id`, 
	t.`gateway`, IF(t.`fullname` = '', m.`fullname`, t.`fullname`) `user`, IF(t.`status` != 'passed', 1, 0) `delete`
FROM `:prefix:table_transactions` t 
LEFT JOIN `:prefix:table_members` m ON (m.`id` = t.`member_id`)
ORDER BY t.`date_created`
LIMIT 0, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_members' => iaUsers::getTable(),
            'table_transactions' => $this->getTable(),
            'limit' => $limit
        ]);

        return $this->iaDb->getAll($sql);
    }

    /**
     * Filling admin dashboard statistics with the financial information
     *
     * @return array
     */
    public function getDashboardStatistics()
    {
        $this->iaDb->setTable(self::getTable());

        $currenciesToSymbolMap = ['USD' => '$', 'EUR' => '€', 'RMB' => '¥', 'CNY' => '¥'];

        $currency = strtoupper($this->iaCore->get('currency'));
        $currency = isset($currenciesToSymbolMap[$currency]) ? $currenciesToSymbolMap[$currency] : '';


        $data = [];
        $weekDay = getdate();
        $weekDay = $weekDay['wday'];
        $stmt = '`status` = :status AND DATE(`date_paid`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW()) GROUP BY DATE(`date_paid`)';
        $this->iaDb->bind($stmt, ['status' => self::PASSED]);
        $rows = $this->iaDb->keyvalue('DAYOFWEEK(DATE(`date_paid`)), SUM(`amount`)', $stmt);
        for ($i = 0; $i < 7; $i++) {
            $data[$i] = isset($rows[$i]) ? $rows[$i] : 0;
        }

        $statuses = [self::PASSED, self::PENDING, self::REFUNDED, self::FAILED];
        $rows = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 GROUP BY `status`');

        foreach ($statuses as $status) {
            isset($rows[$status]) || $rows[$status] = 0;
        }

        $total = (int)$this->iaDb->one_bind('ROUND(SUM(`amount`)) `total`',
            "`status` = :status && (`item` != :funds || (`item` = :funds && `gateway` != ''))",
            ['status' => self::PASSED, 'funds' => 'funds']);

        $this->iaDb->resetTable();

        return [
            '_format' => 'medium',
            'data' => ['array' => implode(',', $data)],
            'icon' => 'banknote',
            'item' => iaLanguage::get('total_income'),
            'rows' => $rows,
            'total' => $currency . $total,
            'url' => 'transactions/'
        ];
    }

    public function addIpnLogEntry($gateway, $data, $result)
    {
        $entry = [
            'gateway' => $gateway,
            'data' => var_export($data, true),
            'result' => $result
        ];

        return (bool)$this->iaDb->insert($entry, ['date' => iaDb::FUNCTION_NOW], $this->getTableIpnLog());
    }

    protected function _sendEmailNotification(array $transaction)
    {
        // first, do check if gateway has its own email submission
        if ($result = $this->_doGatewayCallback($transaction['gateway'], $transaction)) {
            return $result;
        }

        $result1 = true;

        $notification = 'transaction_paid';
        if ($this->iaCore->get($notification)) {
            $iaUsers = $this->iaCore->factory('users');

            $member = $iaUsers->getById($transaction['member_id']);

            if (!$member) {
                return false;
            }

            $iaMailer = $this->iaCore->factory('mailer');

            $iaMailer->loadTemplate('transaction_paid');
            $iaMailer->addAddress($member['email']);

            $iaMailer->setReplacements($transaction);
            $iaMailer->setReplacements([
                'email' => $member['username'],
                'username' => $member['username'],
                'fullname' => $member['fullname']
            ]);

            $result1 = $iaMailer->send();
        }

        // notify admin
        $result2 = true;

        $notification.= '_admin';
        if ($this->iaCore->get($notification)) {
            $iaMailer->loadTemplate($notification);
            $iaMailer->addAddress($this->iaCore->get('site_email'));
            $iaMailer->setReplacements([
                'username' => iaUsers::getIdentity()->username,
                'amount' => $transaction['amount'],
                'operation' => $transaction['operation']
            ]);

            $result2 = $iaMailer->send();
        }

        return $result1 && $result2;
    }

    protected function _doGatewayCallback($gatewayName, array $transaction)
    {
        if (!$gatewayName) {
            return false;
        }

        $gatewayInstance = $this->iaCore->factoryModule($gatewayName, $gatewayName, 'common');

        if ($gatewayInstance && method_exists($gatewayInstance, self::GATEWAY_CALLBACK_NAME)) {
            return call_user_func([$gatewayInstance, self::GATEWAY_CALLBACK_NAME], $transaction);
        }

        return false;
    }

    public function refund($transactionId)
    {
        $transaction = $this->getById($transactionId);

        if (!$transaction) {
            return false;
        }

        $gatewayName = $transaction['gateway'];

        $gatewayInstance = $this->iaCore->factoryModule($gatewayName, $gatewayName, 'common');

        if ($gatewayInstance && method_exists($gatewayInstance, 'refund')) {
            try {
                return call_user_func([$gatewayInstance, 'refund'], $transaction);
            } catch (Exception $e) {
            }
        }

        return false;
    }
}
