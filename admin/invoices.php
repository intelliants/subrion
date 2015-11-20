<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2015 Intelliants, LLC <http://www.intelliants.com>
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
	protected $_name = 'invoices';

	protected $_gridFilters = array('fullname' => self::LIKE, 'status' => self::EQUAL);
	protected $_gridQueryMainTableAlias = 't';


	public function __construct()
	{
		parent::__construct();

		$this->setHelper($this->_iaCore->factory('invoice'));
		$this->setTable(iaInvoice::getTable());
	}

	protected function _indexPage(&$iaView)
	{
		if (2 == count($this->_iaCore->requestPath) && 'printable' == $this->_iaCore->requestPath[0])
		{
			$this->_printableVersion($iaView, $this->_iaCore->requestPath[1]);
		}
		else
		{
			$iaView->grid('admin/' . $this->getName());
		}
	}

	protected function _gridQuery($columns, $where, $order, $start, $limit)
	{
		$sql =
			'SELECT SQL_CALC_FOUND_ROWS '
				. 'i.`id`, i.`date_created`, i.`fullname` `user`, '
				. 't.`plan_id`, t.`operation`, '
				. 't.`status`, CONCAT(t.`amount`, " ", t.`currency`) `amount`, t.`currency`, t.`gateway`, '
				. "1 `pdf`, 1 `update`, IF(t.`status` != 'passed', 1, 0) `delete` " .
			'FROM `:prefix:table_invoices` i ' .
			'LEFT JOIN `:prefix:table_transactions` t ON (t.`id` = i.`transaction_id`) ' .
			'LEFT JOIN `:prefix:table_members` m ON (m.`id` = t.`member_id`) ' .
			($where ? 'WHERE ' . $where . ' ' : '') . $order . ' ' .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->_iaDb->prefix,
			'table_invoices' => self::getTable(),
			'table_members' => iaUsers::getTable(),
			'table_transactions' => 'payment_transactions',
			'start' => $start,
			'limit' => $limit
		));

		return $this->_iaDb->getAll($sql);
	}

	protected function _modifyGridResult(array &$entries)
	{
		foreach ($entries as &$entry)
		{
			$entry['plan'] = $entry['plan_id'] ? iaLanguage::get('plan_title_' . $entry['plan_id']) : $entry['operation'];
			//$entry['gateway'] && ($entry['gateway'] = iaLanguage::get($entry['gateway']));

			unset($entry['operation'], $entry['plan_id']);
		}
	}

	protected function _printableVersion(&$iaView, $invoiceId)
	{
		$invoice = $this->getHelper()->getById($invoiceId);

		if (!$invoice)
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		$iaView->assign('invoice', $invoice);
		$iaView->assign('items', $this->getHelper()->getItemsByInvoiceId($invoiceId));

		$iaView->display('printable.invoice');
		$iaView->disableLayout();

		return;
	}

	protected function _setDefaultValues(array &$entry)
	{
		$entry['id'] = $this->getHelper()->generateId();
		$entry['date_due'] = '';
		$entry['fullname'] = '';

		$entry['address1'] = $entry['address2'] = $entry['zip'] = $entry['country'] = '';
	}

	protected function _assignValues(&$iaView, array &$entryData)
	{
		$iaView->set('toolbarActionsReplacements', array('id' => $this->getEntryId()));

		$items = isset($_POST['items']) ? $this->_normalizeItems($_POST['items']) : array();
		$items || $items = $this->getHelper()->getItemsByInvoiceId($this->getEntryId());

		$iaView->assign('items', $items);
	}

	protected function _preSaveEntry(array &$entry, array $data, $action)
	{
		$entry['date_due'] = $data['date_due'];
		$entry['fullname'] = $data['fullname'];
		$entry['address1'] = $data['address1'];
		$entry['address2'] = $data['address2'];
		$entry['zip'] = $data['zip'];
		$entry['country'] = $data['country'];

		if (iaCore::ACTION_ADD == $action)
		{
			$entry['id'] = $data['id'];
			$entry['date_created'] = date(iaDb::DATETIME_FORMAT);

			if (empty($entry['id']))
			{
				$this->addMessage(iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('invoice_id'))), false);
			}
		}

		return !$this->getMessages();
	}

	protected function _entryAdd(array $entryData)
	{
		$this->_iaDb->insert($entryData);

		return (0 == $this->_iaDb->getErrorNumber) ? $entryData['id'] : false;
	}

	protected function _postSaveEntry(array &$entry, array $data, $action)
	{
		$this->getHelper()->saveItems($this->getEntryId(), $data['items']);
	}

	private function _normalizeItems(array $items)
	{
		$result = array();

		foreach ($items['title'] as $i => $title)
			$result[] = array('title' => $title, 'price' => $items['price'][$i],
				'quantity' => $items['quantity'][$i], 'tax' => $items['tax'][$i]);

		return $result;
	}
}