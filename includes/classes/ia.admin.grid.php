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

class iaGrid extends abstractCore
{
	protected $_iaDb;


	public function __construct()
	{
		$this->_iaDb = iaCore::instance()->iaDb;
	}

	public function gridRead($params, $columns, array $filterParams = array(), array $persistentConditions = array())
	{
		$params || $params = array();

		$start = isset($params['start']) ? (int)$params['start'] : 0;
		$limit = isset($params['limit']) ? (int)$params['limit'] : 15;

		$sort = $params['sort'];
		$dir = in_array($params['dir'], array(iaDb::ORDER_ASC, iaDb::ORDER_DESC)) ? $params['dir'] : iaDb::ORDER_ASC;
		$order = ($sort && $dir) ? " ORDER BY `{$sort}` {$dir}" : '';

		$where = $values = array();
		foreach ($filterParams as $name => $type)
		{
			if (isset($params[$name]) && $params[$name])
			{
				$value = iaSanitize::sql($params[$name]);

				switch ($type)
				{
					case 'equal':
						$where[] = sprintf('`%s` = :%s', $name, $name);
						$values[$name] = $value;
						break;
					case 'like':
						$where[] = sprintf('`%s` LIKE :%s', $name, $name);
						$values[$name] = '%' . $value . '%';
				}
			}
		}

		$where = array_merge($where, $persistentConditions);
		$where || $where[] = iaDb::EMPTY_CONDITION;
		$where = implode(' AND ', $where);
		$this->_iaDb->bind($where, $values);

		if (is_array($columns))
		{
			$columns = array_merge(array('id', 'update' => 1, 'delete' => 1), $columns);
		}

		return array(
			'data' => $this->_iaDb->all($columns, $where . $order, $start, $limit),
			'total' => (int)$this->_iaDb->one(iaDb::STMT_COUNT_ROWS, $where)
		);
	}

	public function gridUpdate($params)
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		$params || $params = array();

		if (isset($params['id']) && is_array($params['id']) && count($params) > 1)
		{
			$stmt = '`id` IN (' . implode(',', $params['id']) . ')';
			unset($params['id']);

			$result['result'] = (bool)$this->_iaDb->update($params, $stmt);
			$result['message'] = iaLanguage::get($result['result'] ? 'saved' : 'db_error');
		}

		return $result;
	}

	public function gridDelete($params, $languagePhraseKey = 'deleted')
	{
		$result = array(
			'result' => false,
			'message' => iaLanguage::get('invalid_parameters')
		);

		if (isset($params['id']) && is_array($params['id']) && $params['id'])
		{
			$total = count($params['id']);
			$affected = $this->_iaDb->delete('`id` IN (' . implode(',', $params['id']) . ')');

			if (1 == $total)
			{
				$result['result'] = (1 == $affected);
				$result['message'] = $result['result']
					? iaLanguage::get($languagePhraseKey)
					: iaLanguage::get('db_error');
			}
			else
			{
				$result['result'] = ($affected == $total);
				$result['message'] = $result['result']
					? iaLanguage::getf('items_deleted', array('num' => $affected))
					: iaLanguage::getf('items_deleted_of', array('num' => $affected, 'total' => $total));
			}
		}

		return $result;
	}
}