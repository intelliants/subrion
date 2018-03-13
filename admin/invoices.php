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
    protected $_name = 'invoices';

    protected $_gridFilters = ['fullname' => self::LIKE, 'status' => self::EQUAL];
    protected $_gridQueryMainTableAlias = 'i';
    protected $_gridSorting = [
        'amount' => ['amount', 't'],
        'gateway' => ['gateway', 't'],
        'status' => ['status', 't']
    ];


    public function __construct()
    {
        parent::__construct();

        $this->setHelper($this->_iaCore->factory('invoice'));
        $this->setTable(iaInvoice::getTable());
    }

    protected function _indexPage(&$iaView)
    {
        if (2 == count($this->_iaCore->requestPath) && 'view' == $this->_iaCore->requestPath[0]) {
            $this->_view($iaView, $this->_iaCore->requestPath[1]);
        } else {
            $iaView->grid('admin/' . $this->getName());
        }
    }

    protected function _gridQuery($columns, $where, $order, $start, $limit)
    {
        $sql =
            'SELECT SQL_CALC_FOUND_ROWS '
            . 'i.`id`, i.`date_created`, i.`fullname`, '
            . 't.`plan_id`, t.`operation`, '
            . 't.`status`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, t.`currency`, t.`gateway`, '
            . "1 `pdf`, 1 `update`, IF(t.`status` != 'passed', 1, 0) `delete` " .
            'FROM `:prefix:table_invoices` i ' .
            'LEFT JOIN `:prefix:table_transactions` t ON (t.`id` = i.`transaction_id`) ' .
            'LEFT JOIN `:prefix:table_members` m ON (m.`id` = t.`member_id`) ' .
            ($where ? 'WHERE ' . $where . ' ' : '') . $order . ' ' .
            'LIMIT :start, :limit';
        $sql = iaDb::printf($sql, [
            'prefix' => $this->_iaDb->prefix,
            'table_invoices' => self::getTable(),
            'table_members' => iaUsers::getTable(),
            'table_transactions' => 'payment_transactions',
            'start' => $start,
            'limit' => $limit
        ]);

        return $this->_iaDb->getAll($sql);
    }

    protected function _gridModifyParams(&$conditions, &$values, array $params)
    {
        if (!empty($params['gateway'])) {
            $conditions[] = 't.`gateway` = :gateway';
            $values['gateway'] = $params['gateway'];
        }
    }

    protected function _gridModifyOutput(array &$entries)
    {
        foreach ($entries as &$entry) {
            $entry['plan'] = $entry['plan_id'] ? iaLanguage::get('plan_title_' . $entry['plan_id']) : $entry['operation'];
            is_null($entry['status']) && $entry['status'] = 'empty';
            //$entry['gateway'] && ($entry['gateway'] = iaLanguage::get($entry['gateway']));

            unset($entry['operation'], $entry['plan_id']);
        }
    }

    protected function _view(&$iaView, $invoiceId)
    {
        iaBreadcrumb::add(iaLanguage::get('view'), IA_SELF);

        $invoice = $this->getHelper()->getById($invoiceId);

        if (!$invoice) {
            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
        }

        $iaView->assign('invoice', $invoice);
        $iaView->assign('items', $this->getHelper()->getItemsByInvoiceId($invoiceId));

        $iaView->display('invoice-view');
    }

    protected function _setDefaultValues(array &$entry)
    {
        $entry['id'] = $this->getHelper()->generateId();
        $entry['date_due'] = '';
        $entry['fullname'] = '';
        $entry['notes'] = '';

        $entry['address1'] = $entry['address2'] = $entry['zip'] = $entry['country'] = '';
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $iaView->set('toolbarActionsReplacements', ['id' => $this->getEntryId()]);

        $items = isset($_POST['items']) ? $this->_normalizeItems($_POST['items']) : [];
        $items || $items = $this->getHelper()->getItemsByInvoiceId($this->getEntryId());

        $iaView->assign('items', $items);
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        $entry['member_id'] = $data['member_id'];
        $entry['fullname'] = $data['fullname'];
        $entry['date_due'] = $data['date_due'];
        $entry['address1'] = $data['address1'];
        $entry['address2'] = $data['address2'];
        $entry['country'] = $data['country'];
        $entry['notes'] = $data['notes'];
        $entry['zip'] = $data['zip'];

        if (iaCore::ACTION_ADD == $action) {
            $entry['id'] = $data['id'];
            $entry['date_created'] = (new \DateTime())->format(iaDb::DATETIME_FORMAT);

            if (empty($entry['id'])) {
                $this->addMessage(iaLanguage::getf('field_is_empty', ['field' => iaLanguage::get('invoice_id')]),
                    false);
            }
        }

        return !$this->getMessages();
    }

    protected function _entryAdd(array $entryData)
    {
        $this->_iaDb->insert($entryData);

        return (0 == $this->_iaDb->getErrorNumber()) ? $entryData['id'] : false;
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        $this->getHelper()->saveItems($this->getEntryId(), $data['items']);
    }

    private function _normalizeItems(array $items)
    {
        $result = [];

        foreach ($items['title'] as $i => $title) {
            $result[] = [
                'title' => $title,
                'price' => $items['price'][$i],
                'quantity' => $items['quantity'][$i],
                'tax' => $items['tax'][$i]
            ];
        }

        return $result;
    }
}
