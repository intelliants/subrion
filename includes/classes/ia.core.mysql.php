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

class iaDb extends abstractUtil implements iaInterfaceDbAdapter
{
	protected $_link;

	protected $_counter = 0;

	protected $_lastQuery = '';

	protected $_queryList = array();

	protected $_tableList = array();

	protected $_table;

	public $tableOptions = 'ENGINE = MyISAM DEFAULT CHARSET = utf8';
	public $prefix;


	public function init()
	{
		$this->prefix = INTELLI_DBPREFIX;
		$this->_connect();
	}

	/**
	 * Creates connection to database
	 *
	 * @return void
	 */
	protected function _connect()
	{
		$this->_link = mysql_connect(INTELLI_DBHOST . ':' . INTELLI_DBPORT, INTELLI_DBUSER, INTELLI_DBPASS);
		if (!$this->_link)
		{
			$message = !INTELLI_DEBUG ? 'Could not connect.' : 'Could not connect to the database. For more information see error logs.';
			die($message);
		}

		$this->query("SET NAMES 'utf8'");

		if (!mysql_select_db(INTELLI_DBNAME, $this->_link))
		{
			trigger_error('An error occurred while selecting database: ' . mysql_error($this->_link), E_USER_ERROR);
			die();
		}
	}

	/**
	 * Escapes string using valid connection
	 *
	 * @param string $string string to be escaped
	 *
	 * @return string
	 */
	public function sql($string = '')
	{
		return mysql_real_escape_string($string);
	}

	/**
	 * Returns a string that represents various information on a connected database
	 *
	 * @param $type information type
	 *
	 * @return string
	 */
	public function getInfo($type)
	{
		$function = 'mysql_get_' . $type;

		return $function();
	}

	/**
	 * Sets table to work with (in most cases resetTable should be called after calling this method)
	 *
	 * @param string $tableName table name
	 * @param bool $addPrefix true - use prefix for the table name
	 *
	 * @return void
	 */
	public function setTable($tableName, $addPrefix = true)
	{
		$this->_table = ($addPrefix ? $this->prefix : '') . $tableName;
		array_unshift($this->_tableList, $this->_table);
	}

	/**
	 * Resets table name to the previous state
	 *
	 * @return void
	 */
	public function resetTable()
	{
		if (empty($this->_tableList) || 1 == count($this->_tableList))
		{
			$this->_table = '';
			$this->_tableList = array();
		}
		else
		{
			array_shift($this->_tableList);
			$this->_table = $this->_tableList[0];
		}
	}

	/**
	 * Converts short aliases into MySQL query format
	 *
	 * @param string $type selection type
	 * @param string|array $fields fields to be selected
	 * @param string $condition condition to be used for the selection
	 * @param int $start start position
	 * @param int|null $limit number of records to be returned
	 *
	 * @return array|bool
	 */
	protected function _get($type, $fields, $condition = '', $start = 0, $limit = null)
	{
		$stmtFields = $fields;

		if (is_array($fields))
		{
			$stmtFields = '';
			foreach ($fields as $key => $field)
			{
				$stmtFields .= is_int($key)
					? '`' . $field . '`'
					: sprintf('%s `%s`', is_numeric($field) ? $field : '`' . $field . '`', $key);
				$stmtFields .= ', ';
			}
			$stmtFields = substr($stmtFields, 0, -2);
		}

		if ($condition)
		{
			$condition = ' WHERE ' . $condition;
		}
		if ($limit && stripos($condition, 'limit') === false)
		{
			$condition .= ' LIMIT ' . $start . ', ' . $limit;
		}

		$sql = 'SELECT ' . $stmtFields . ' FROM `' . $this->_table . '` ' . $condition;

		switch ($type)
		{
			case 'all':
				return $this->getAll($sql);
			case 'keyval':
				return $this->getKeyValue($sql);
			case 'assoc':
				return $this->getAssoc($sql, true);
			default:
				return $this->getRow($sql);
		}
	}

	/**
	 * Executes MySQL query
	 *
	 * @param string $sql sql query to be executed
	 *
	 * @return resource
	 */
	public function query($sql)
	{
		if (!$this->_link)
		{
			$this->_connect();
		}

		$timeStart = explode(' ', microtime());
		$rs = mysql_query($sql, $this->_link);
		$timeEnd = explode(' ', microtime());

		$start = $timeStart[1] + $timeStart[0];
		$end = $timeEnd[1] + $timeEnd[0];
		$times = number_format($end - $start, 5, '.', '');

		$this->_counter++;
		$this->_lastQuery = $sql;
		if (INTELLI_DEBUG)
		{
			$this->_queryList[] = array($sql, $times);
		}

		// 2013 - lost connection during the execution
		if (!$rs && 2013 != mysql_errno())
		{
			$error = mysql_error();
			$error .= PHP_EOL . $sql;

			trigger_error($error, E_USER_WARNING);
		}

		return $rs;
	}

	/**
	 * Returns last executed query
	 *
	 * @return string
	 */
	public function getLastQuery()
	{
		return $this->_lastQuery;
	}

	/**
	 * Returns list of executed queries
	 *
	 * @return array
	 */
	public function getQueriesList()
	{
		return $this->_queryList;
	}

	/**
	 * Returns number of queries
	 *
	 * @return int
	 */
	public function getCount()
	{
		return $this->_counter;
	}

	/**
	 * Returns table prefix
	 *
	 * @return string
	 */
	public function getPrefix()
	{
		return $this->prefix;
	}

	/**
	 * Returns one table row as array
	 *
	 * @param string $sql sql query
	 *
	 * @return array|bool
	 */
	public function getRow($sql)
	{
		$result = false;

		$query = $this->query($sql);
		if ($this->getNumRows($query) > 0)
		{
			$result = mysql_fetch_assoc($query);
		}

		return $result;
	}

	/**
	 * Returns table rows as array
	 *
	 * @param string $sql sql query
	 * @param int $start start position
	 * @param int $limit number of rows to be returned
	 *
	 * @return array
	 */
	public function getAll($sql, $start = 0, $limit = 0)
	{
		if ($limit != 0)
		{
			$sql .= sprintf(' LIMIT %d, %d', $start, $limit);
		}

		$result = array();

		$query = $this->query($sql);
		if ($this->getNumRows($query) > 0)
		{
			while ($row = mysql_fetch_assoc($query))
			{
				$result[] = $row;
			}
		}

		return $result;
	}

	/**
	 * Returns associative array of rows, first column of the query is used as a key
	 *
	 * @param string $sql sql query
	 * @param bool $singleRow
	 *
	 * @return array
	 */
	public function getAssoc($sql, $singleRow = false)
	{
		$result = array();

		$query = $this->query($sql);
		if ($this->getNumRows($query))
		{
			while ($row = mysql_fetch_assoc($query))
			{
				$key = array_shift($row);
				if ($singleRow)
				{
					$result[$key] = $row;
				}
				else
				{
					$result[$key][] = $row;
				}
			}
		}

		return $result;
	}

	/**
	 * Returns key => value pair array of rows
	 *
	 * @param string $sql sql query
	 *
	 * @return array
	 */
	public function getKeyValue($sql)
	{
		$result = array();

		$query = $this->query($sql);
		if ($this->getNumRows($query) > 0)
		{
			$array = mysql_fetch_row($query);
			$asArray = false;
			if (count($array) > 2)
			{
				$result[$array[0]] = $array;
				$asArray = true;
			}
			else
			{
				$result[$array[0]] = $array[1];
			}

			while ($array = mysql_fetch_row($query))
			{
				$result[$array[0]] = $asArray ? $array : $array[1];
			}
		}

		return $result;
	}

	/**
	 * Returns field value of a row
	 *
	 * @param string $sql sql query
	 *
	 * @return mixed
	 */
	public function getOne($sql)
	{
		$query = $this->query($sql);

		return ($this->getNumRows($query) > 0) ? mysql_result($query, 0, 0) : false;
	}

	/**
	 * Returns the error text from the last MySQL function
	 *
	 * @return string
	 */
	public function getError()
	{
		return mysql_error();
	}

	/**
	 * Returns the numerical value of the error message from previous MySQL operation
	 *
	 * @return int
	 */
	public function getErrorNumber()
	{
		return mysql_errno($this->_link);
	}

	/**
	 * Returns a formatted string according to replacements
	 * Does NOT sanitize any input (!)
	 *
	 * @param string $pattern replacement pattern
	 * @param array $replacements new values
	 *
	 * @return string
	 */
	public static function printf($pattern, array $replacements)
	{
		if ($replacements)
		{
			$keys = array();
			$values = array();
			foreach ($replacements as $key => $value)
			{
				$keys[] = ':' . $key;
				$values[] = is_scalar($value) ? $value : '';
			}
			$pattern = str_replace($keys, $values, $pattern);
		}

		return $pattern;
	}

	/**
	 * Returns true if at least 1 record exists
	 *
	 * @param string $where condition
	 * @param array $values
	 * @param null $tableName table name
	 *
	 * @return bool
	 */
	public function exists($where, $values = array(), $tableName = null)
	{
		$this->bind($where, $values);
		if ($tableName)
		{
			$this->setTable($tableName);
			$result = $this->query("SELECT 1 FROM `" . $this->_table . "` WHERE " . $where);
			$this->resetTable();
		}
		else
		{
			$result = $this->query("SELECT 1 FROM `" . $this->_table . "` WHERE " . $where);
		}

		return ($this->getNumRows($result) > 0);
	}

	/**
	 * Returns the ID generated in the last query
	 *
	 * @return int
	 */
	public function getInsertId()
	{
		return mysql_insert_id($this->_link);
	}

	/**
	 * Returns the ID using auto increment value for a table
	 */
	public function getNextId($table = null)
	{
		$table = empty($table) ? $this->_table : $table;

		$result = $this->query("SHOW TABLE STATUS LIKE '{$table}'");
		$row = mysql_fetch_array($result);

		return $row['Auto_increment'];
	}

	/**
	 * Returns a number of affected rows in previous MySQL operation
	 *
	 * @return int
	 */
	public function getAffected()
	{
		return mysql_affected_rows($this->_link);
	}

	/**
	 * Returns number of found rows of the previous query with SQL_CALC_FOUND_ROWS
	 * Note: this SQL function is MySQL specific
	 *
	 * @return int
	 */
	public function foundRows()
	{
		return (int)$this->getOne('SELECT ' . self::FUNCTION_FOUND_ROWS);
	}

	/**
	 * Returns a number of rows in result
	 *
	 * @param resource $resource query resource
	 *
	 * @return int
	 */
	public function getNumRows($resource)
	{
		if (is_resource($resource))
		{
			return mysql_num_rows($resource);
		}

		return 0;
	}

	/**
	 * Retrieves the number of fields from a query
	 *
	 * @param $result query result
	 *
	 * @return int
	 */
	public function getNumFields($result)
	{
		return mysql_num_fields($result);
	}

	/**
	 * Returns an array of objects which contains field definition information or FALSE if no field information is available
	 *
	 * @param $result query result
	 *
	 * @return array|bool
	 */
	public function getFieldNames($result)
	{
		$fieldsNumber = $this->getNumFields($result);

		$output = array();
		for ($i = 0; $i < $fieldsNumber; $i++)
		{
			$output[$i]->name = mysql_field_name($result, $i);
		}

		return $output;
	}

	/**
	 * Fetches one row of data from the result set and returns it as an enumerated array
	 *
	 * @param $result query result
	 *
	 * @return array|null
	 */
	public function fetchRow($result)
	{
		return mysql_fetch_row($result);
	}

	/**
	 * Provides information about the columns in a table
	 *
	 * @param null $tableName table name
	 * @param bool $addPrefix avoid prefix addition if false
	 * @return array
	 */
	public function describe($tableName = null, $addPrefix = true)
	{
		if (empty($tableName))
		{
			$tableName = $this->_table;
		}
		else
		{
			$tableName = ($addPrefix ? $this->prefix : '') . $tableName;
		}

		$sql = sprintf('DESCRIBE `%s`', $tableName);

		return $this->getAll($sql);
	}

	/**
	 * Truncates table
	 *
	 * @param null|string $table table name
	 *
	 * @return bool
	 */
	public function truncate($table = null)
	{
		if (is_null($table))
		{
			$table = $this->_table;
		}
		$sql = 'TRUNCATE TABLE `' . $table . '`';

		return $this->query($sql);
	}

	/**
	 * Returns field value of a row
	 *
	 * @param string $field field to be selected
	 * @param string $condition condition for the selection
	 * @param string|null $tableName table name to select records from, null uses current set table
	 * @param int $start starting position
	 *
	 * @return bool|mixed
	 */
	public function one($field, $condition = '', $tableName = null, $start = 0)
	{
		$result = $this->row($field, $condition, $tableName, $start);

		return is_bool($result) ? $result : array_shift($result);
	}

	/**
	 * Returns table column values as an array
	 *
	 * @param string $field field name column to be selected
	 * @param string $condition condition for the selection
	 * @param int $start start position
	 * @param int|null $limit number of records to be returned
	 * @param string|null $tableName table name to select records from, null uses current set table
	 *
	 * @return array
	 */
	public function onefield($field = self::ID_COLUMN_SELECTION, $condition = null, $start = 0, $limit = null, $tableName = null)
	{
		if (false !== strpos($field, ','))
		{
			return false;
		}

		if ($tableName)
		{
			$this->setTable($tableName);
			$rows = $this->_get('all', $field, $condition, $start, $limit);
			$this->resetTable();
		}
		else
		{
			$rows = $this->_get('all', $field, $condition, $start, $limit);
		}

		$result = array();

		if (empty($rows))
		{
			return $result;
		}

		$field	= str_replace('`', '', $field);
		foreach ($rows as $row)
		{
			$result[] = $row[$field];
		}

		return $result;
	}

	/**
	 * Returns one table row as array
	 *
	 * @param string $fields fields to be selected
	 * @param string $condition condition for the selection
	 * @param string|null $tableName table name to select records from, null uses current set table
	 * @param int $start start position
	 *
	 * @return array
	 */
	public function row($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0)
	{
		if (is_null($tableName))
		{
			$result = $this->_get('row', $fields, $condition, $start, 1);
		}
		else
		{
			$this->setTable($tableName);
			$result = $this->_get('row', $fields, $condition, $start, 1);
			$this->resetTable();
		}

		return $result;
	}

	/**
	 * Returns table rows as array
	 *
	 * @param string $fields fields to be selected
	 * @param string $condition condition for the selection
	 * @param int $start start position
	 * @param int|null $limit number of records to be returned
	 * @param string|null $tableName table name to select records from, null uses current set table
	 *
	 * @return array
	 */
	public function all($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $start = 0, $limit = null, $tableName = null)
	{
		if (is_null($tableName))
		{
			$result = $this->_get('all', $fields, $condition, $start, $limit);
		}
		else
		{
			$this->setTable($tableName);
			$result = $this->_get('all', $fields, $condition, $start, $limit);
			$this->resetTable();
		}

		return $result;
	}

	/**
	 * Returns associative array of rows, first column of the query is used as a key
	 *
	 * @param string $fields fields to be selected
	 * @param string $condition condition for the selection
	 * @param string|null $tableName table name to select records from, null uses current set table
	 * @param int $start start position
	 * @param int|null $limit number of records to be returned
	 *
	 * @return array
	 */
	public function assoc($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0, $limit = null)
	{
		if (is_null($tableName))
		{
			$result = $this->_get('assoc', $fields, $condition, $start, $limit);
		}
		else
		{
			$this->setTable($tableName);
			$result = $this->_get('assoc', $fields, $condition, $start, $limit);
			$this->resetTable();
		}

		return $result;
	}

	/**
	 * Returns key => value pair array of rows
	 *
	 * @param string $fields fields to be selected
	 * @param string $condition condition for the selection
	 * @param string|null $tableName table name to select records from, null uses current set table
	 * @param int $start start position
	 * @param int|null $limit number of records to be returned
	 *
	 * @return array
	 */
	public function keyvalue($fields = self::ALL_COLUMNS_SELECTION, $condition = null, $tableName = null, $start = 0, $limit = null)
	{
		if (is_null($tableName))
		{
			$result = $this->_get('keyval', $fields, $condition, $start, $limit);
		}
		else
		{
			$this->setTable($tableName);
			$result = $this->_get('keyval', $fields, $condition, $start, $limit);
			$this->resetTable();
		}

		return $result;
	}

	/**
	 * Inserts a new record in a table and returns id of the inserted row
	 *
	 * Ex:  the code below inserts a new record in 'table_name'
	 * 		$iaCore->iaDb->insert(array('title' => 'My Row Title', 'text' => 'My Row Text'), array('date' => 'NOW()'), 'table_name');
	 *
	 * 		generated sql code looks like:
	 * 		INSERT INTO `prefix_table_name` SET `title` = 'My Row Title', `text` = 'My Row Text', `date` = NOW();
	 *
	 * @param array $values key=>value array for the record
	 * @param array|null $rawValues key=>value array for the record without sanitizing, commonly used for date insert
	 * @param string|null $tableName table name to perform insertion, null uses current set table
	 *
	 * @return int
	 */
	public function insert(array $values, $rawValues = null, $tableName = null)
	{
		$table = $tableName ? $this->prefix . $tableName : $this->_table;
		$queue = is_array(current($values)) ? $values : array($values);

		foreach ($queue as $entryValues)
		{
			if ($stmtSet = $this->_wrapValues($entryValues, $rawValues))
			{
				$sql = sprintf('INSERT INTO `%s` SET %s', $table, $stmtSet);
				$this->query($sql);
			}
		}

		return $this->getInsertId();
	}

	/**
	 * Updates a record in a table and returns a number of affected rows
	 *
	 * Ex:  the code below updates a record in 'table_name'
	 * 		$iaCore->iaDb->update(array('id' => '50', 'title' => 'My Row Title', 'text' => 'My Row Text'), null, array('date' => 'NOW()'), 'table_name');
	 *
	 * 		generated sql code looks like:
	 * 		UPDATE `prefix_table_name` SET `title` = 'My Row Title', `text` = 'My Row Text', `date` = NOW() WHERE `id` = '50';
	 *
	 * @param array $fields fields key=>value array to be updated
	 * @param string $condition condition used for the update query, if empty tries to update using id field from $fields array
	 * @param array|null $rawValues key=>value array for the record without sanitizing, commonly used for date insert
	 * @param string|null $tableName table name to perform update, null uses current set table
	 *
	 * @return bool|int
	 */
	public function update($values, $condition = null, $rawValues = null, $tableName = null)
	{
		if (empty($values) && empty($rawValues))
		{
			return false;
		}

		if (empty($this->_table) && empty($tableName))
		{
			return false;
		}

		$stmtWhere = '';
		if ($condition)
		{
			$stmtWhere = 'WHERE ' . $condition;
		}
		elseif (isset($values['id']))
		{
			$stmtWhere = 'WHERE `' . self::ID_COLUMN_SELECTION . "` = '" . $values['id'] . "'";
			unset($values['id']);
		}

		$stmtSet = $this->_wrapValues($values, $rawValues);
		if (empty($stmtSet))
		{
			return false;
		}

		$table = $tableName ? $this->prefix . $tableName : $this->_table;

		$sql = sprintf('UPDATE `%s` SET %s %s', $table, $stmtSet, $stmtWhere);
		$this->query($sql);

		return $this->getAffected();
	}

	/**
	 * Deletes records in a table and returns number of affected rows by the query
	 *
	 * @param string $condition condition to perform deletion
	 * @param string|null $tableName table name where to perform deletion, null - deletes currently set table
	 * @param array $values real values key=>value array to be replaced in condition
	 *
	 * @return int
	 */
	public function delete($condition, $tableName = null, $values = array())
	{
		if (empty($condition))
		{
			trigger_error(__CLASS__ . '::' . __METHOD__ . ' Parameters required "where clause"). All rows deletion is restricted.', E_USER_ERROR);
		}

		if ($values)
		{
			$this->bind($condition, $values);
		}

		$table = is_null($tableName) ? $this->_table : $this->prefix . $tableName;
		$this->query(sprintf('DELETE FROM `%s` WHERE %s', $table, $condition));

		return $this->getAffected();
	}

	/**
	 * Binds queries to make SQL more readable
	 *
	 * Ex: 	$sql = "SELECT * FROM users WHERE user = :user AND password = :password";
	 * 		mysql_bind($sql, array('user' => $user, 'password' => $password));
	 * 		mysql_query($sql);
	 *
	 * @param string $sql sql query to be processed
	 * @param array $values real values key=>value array to be replaced in sql query
	 *
	 * @return void
	 */
	public function bind(&$sql, $values)
	{
		if (is_array($values) && $values)
		{
			foreach ($values as $key => $value)
			{
				$sql = str_replace(':' . $key, "'" . iaSanitize::sql($value) . "'", $sql);
			}
		}
	}

	/**
	 * Returns single row array on binding completion
	 *
	 * @param string $fields fields to be returned
	 * @param string $condition condition to be used for the selection
	 * @param array $values values key=>value array to be replaced in sql query
	 * @param null $tableName table name to select a record from, null uses current set table
	 * @param int $start start position
	 *
	 * @return array
	 */
	public function row_bind($fields, $condition, array $values, $tableName = null, $start = 0)
	{
		$this->bind($condition, $values);

		return $this->row($fields, $condition, $tableName, $start);
	}

	/**
	 * Returns one field value on binding completion
	 *
	 * @param string $field field name value to be returned
	 * @param string $condition condition to be used for the selection
	 * @param array $values values key=>value array to be replaced in sql query
	 * @param null $tableName table name to select a record from, null uses current set table
	 * @param int $start start position
	 *
	 * @return array
	 */
	public function one_bind($field, $condition, array $values, $tableName = null, $start = 0)
	{
		$this->bind($condition, $values);

		return $this->one($field, $condition, $tableName, $start);
	}

	/**
	 * Converts various condition params to a mysql formatted WHERE case string
	 *
	 * Ex: 	$where = $iaCore->iaDb->convertIds('id', array('1', '3', '5', '6'));
	 * 		echo $where; // `id` = IN('1', '3', '5', '6')
	 *
	 * @param array|string|int $ids
	 * @param string $columnName field name
	 * @param boolean $equal operand type
	 *
	 * @return bool|string
	 */
	public static function convertIds($ids, $columnName = 'id', $equal = true)
	{
		if (empty($columnName))
		{
			return false;
		}

		switch (true)
		{
			case is_numeric($ids):
				return sprintf('`%s` ' . ($equal ? '=' : '!=') . ' %d', $columnName, $ids);

			case is_array($ids):
				$array = array();
				foreach ($ids as $id)
				{
					$array[] = (int)$id;
				}

				return "`{$columnName}` " . ($equal ? '' : 'NOT ') . "IN (" . implode(',', $array) . ')';

			case is_string($ids):
				return sprintf("`%s` " . ($equal ? '=' : '!=') . " '%s'", $columnName, iaSanitize::sql($ids));

			default:
				return false;
		}
	}

	/**
	 * Accepts array of table names and deletes records in them
	 *
	 * @param string|array $tbl table name(s)
	 * @param string $where sql condition to perform deletion
	 *
	 * @return bool|int
	 */
	public function cascadeDelete($tbl, $where = '')
	{
		if (empty($tbl) || empty($where))
		{
			return false;
		}

		if (!is_array($tbl))
		{
			$tbl = (array)$tbl;
		}

		// we don't use setTable because this is multiple changing
		$old = $this->_table;
		$totalDeleted = 0;
		foreach ($tbl as $table)
		{
			$this->_table = $this->prefix . $table;
			$totalDeleted += $this->delete($where);
		}
		$this->_table = $old;

		return $totalDeleted;
	}

	/**
	 * Returns all ENUM and SET values for selected table and field
	 *
	 * @param string $table table name
	 * @param string $field field name
	 *
	 * @return array|bool
	 */
	public function getEnumValues($table, $field)
	{
		$result = $this->getRow('SHOW COLUMNS FROM `' . $this->prefix . $table . '` LIKE "' . $field . '"');

		if ($result['Type'])
		{
			if (preg_match('#^(set|enum)\((.*?)\)$#i', $result['Type'], $enumArray))
			{
				$values = explode(',', $enumArray[2]);
				$enumFields = array();
				if ($values)
				{
					foreach ($values as $val)
					{
						$enumFields[] = trim($val, "'");
					}
				}

				return array(
					'values' => $enumFields,
					'type' => $enumArray[1],
					'default' => $result['Default']
				);
			}
		}

		return false;
	}

	/**
	 * Returns max value for `order` column
	 *
	 * @param string $table table name
	 *
	 * @return bool|mixed
	 */
	public function getMaxOrder($table = null)
	{
		return (int)$this->one('MAX(`order`)', null, $table);
	}

	/*
	 * Optimizes random records selection
	 *
	 * @param int|string $max max order or table name
	 * @param string $id_name id column
	 * @param int $pieces
	 * @param int $delimiter
	 *
	 * @return string
	 */
	public function orderByRand($max, $id_name = '`id`', $pieces = 12, $delimiter = 100)
	{
		// we get max order value if $max is a table name
		if (!is_numeric($max))
		{
			$max = $this->getMaxOrder($max);
		}

		$where = '';
		$pieces = max($pieces, 6);
		$delimiter = max($delimiter, 10);
		if ($pieces * $delimiter > 5000)
		{
			$pieces = 12;
			$delimiter = 100;
		}

		if ($max > 2000)
		{
			$piece_first = ceil($max / $pieces);
			$piece_second = ceil($piece_first / $delimiter);
			$where = array();
			for($i = 0; $i < $pieces; $i++)
			{
				$start = mt_rand(0, $piece_second) * $delimiter + $piece_first * $i;
				$end = $start + $delimiter;
				$where[] = '(' . $id_name . ' >= ' . $start . ' AND ' . $id_name . ' <= ' . $end . ')';
			}
			$where = 'AND (' . implode(' OR ', $where) . ')';
		}

		return $where;
	}

	/*
	 * Internal utility function used to generate SET stmt
	 *
	 * @param array $values values to be set checking by type
	 * @param array $rawValues values to be set without processing
	 *
	 * @return string
	 */
	protected function _wrapValues($values, $rawValues)
	{
		$result = '';

		// no need for further processing
		if (empty($values) && empty($rawValues))
		{
			return $result;
		}

		$array = array();
		if (is_array($values))
		{
			foreach ($values as $columnName => $value)
			{
				$pattern = "`%s` = '%s'";

				switch (true) // an order of statements is important!
				{
					case is_numeric($value):
//						$pattern = '`%s` = %s';
						break;
					case is_bool($value):
						$value = $value ? 1 : 0;
						break;
					case is_scalar($value):
						$value = iaSanitize::sql($value);
						break;
					case is_null($value):
						$value = 'NULL';
						break;
					default: // arrays, objects & resources are now actually ignored
						continue;
				}

				$array[] = sprintf($pattern, $columnName, $value);
			}
		}
		if (is_array($rawValues) && $rawValues)
		{
			foreach ($rawValues as $field => $value)
			{
				$array[] = "`$field` = $value";
			}
		}
		$result = implode(', ', $array);

		return $result;
	}
}