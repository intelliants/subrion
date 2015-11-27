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

class iaInvoice extends abstractCore
{
	protected static $_table = 'invoices';
	protected static $_tableItems = 'invoices_items';


	public function create($id, array $transaction)
	{
		$invoice = array(
			'id' => $this->generateId(),
			'transaction_id' => $id,
			'date_created' => date(iaDb::DATETIME_FORMAT),
			'date_due' => null,
			'fullname' => $transaction['fullname'] ? $transaction['fullname'] : iaUsers::getIdentity()->fullname
		);

		return $this->iaDb->insert($invoice, null, self::getTable());
	}

	public function getById($id)
	{
		return $this->getBy(iaDb::ID_COLUMN_SELECTION, $id);
	}

	public function getBy($key, $value)
	{
		return $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($value, $key), self::getTable());
	}

	public function getItemsByInvoiceId($id)
	{
		return $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id, 'invoice_id'), null, null, self::$_tableItems);
	}

	public function saveItems($invoiceId, array $data)
	{
		$this->iaDb->setTable(self::$_tableItems);

		$this->iaDb->delete(iaDb::convertIds($invoiceId, 'invoice_id'));

		foreach ($data['title'] as $i => $title)
		{
			if ($title)
			{
				$entry = array(
					'invoice_id' => $invoiceId,
					'title' => $title,
					'price' => (float)$data['price'][$i],
					'quantity' => (int)$data['quantity'][$i],
					'tax' => (int)$data['tax'][$i]
				);

				$this->iaDb->insert($entry);
			}
		}

		$this->iaDb->resetTable();
	}

	public function getAddress($transactionId)
	{
		$invoice = $this->getBy('transaction_id', $transactionId);

		if ($invoice && $invoice['address1']) // if address fields of this entry have already been populated, then return them
		{
			return $invoice;
		}

		// else return an address of the latest populated transaction

		$iaTransaction = $this->iaCore->factory('transaction');

		$sql = 'SELECT SQL_CALC_FOUND_ROWS i.`address1`, i.`address2`, i.`zip`, i.`country` '
			. 'FROM `:prefix:table_transactions` t '
			. 'LEFT JOIN `:prefix:table_invoices` i ON (i.`transaction_id` = t.`id`) '
			. 'WHERE t.`member_id` = :member AND i.`address1` != "" '
			. 'ORDER BY t.`date` DESC '
			. 'LIMIT 1';
		$sql = iaDb::printf($sql, array(
			'prefix' => $this->iaDb->prefix,
			'table_transactions' => $iaTransaction::getTable(),
			'table_invoices' => self::getTable(),
			'member' => iaUsers::getIdentity()->id
		));

		$row = $this->iaDb->getRow($sql);

		return $row ? $row : array('address1' => '', 'address2' => '', 'zip' => '', 'country' => '');
	}

	public function updateAddress($transactionId, $address)
	{
		// in order to not rewrite the sensitive data (transaction id, id) it's better to set values this way
		// since it's the data comes directly through $_POST

		$values = array(
			'address1' => $address['address1'],
			'address2' => $address['address2'],
			'zip' => $address['zip'],
			'country' => $address['country']
		);

		return (bool)$this->iaDb->update($values, iaDb::convertIds($transactionId, 'transaction_id'), null, self::getTable());
	}

	public function deleteCorrespondingInvoice($transactionId)
	{
		if ($invoice = $this->getBy('transaction_id', $transactionId))
		{
			$result1 = (bool)$this->iaDb->delete(iaDb::convertIds($invoice['id']), self::getTable());
			$result2 = (bool)$this->iaDb->delete(iaDb::convertIds($invoice['id'], 'invoice_id'), self::$_tableItems);

			return $result1 && $result2;
		}

		return true;
	}

	public function generateId()
	{
		$stmt = 'DATE(`date_created`) = DATE(NOW())';
		$count = $this->iaDb->one(iaDb::STMT_COUNT_ROWS, $stmt, self::getTable());

		$result = date('ymd') . str_pad($count + 1, 5, '0', STR_PAD_LEFT);

		return $result;
	}
}