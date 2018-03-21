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

interface iaInterfaceDbAdapter
{
    const ALL_COLUMNS_SELECTION = '*';
    const ID_COLUMN_SELECTION = 'id';

    const STMT_COUNT_ROWS = 'COUNT(*)';
    const STMT_CALC_FOUND_ROWS = 'SQL_CALC_FOUND_ROWS';

    const FUNCTION_NOW = 'NOW()';
    const FUNCTION_RAND = 'RAND()';
    const FUNCTION_FOUND_ROWS = 'FOUND_ROWS()';

    const EMPTY_CONDITION = '1 = 1';

    const DATE_FORMAT = 'Y-m-d';
    const TIME_FORMAT = 'H:i:s';
    const DATETIME_FORMAT = 'Y-m-d H:i:s';
    const DATETIME_SHORT_FORMAT = 'Y-m-d H:i';

    const ORDER_ASC = 'ASC';
    const ORDER_DESC = 'DESC';


    public function setTimezoneOffset($offset);

    /**
     * Sets table to work with (in most cases resetTable should be called after calling this method)
     *
     * @param string $tableName table name
     * @param bool $addPrefix true - adds prefix for the table name
     *
     * @return void
     */
    public function setTable($tableName, $addPrefix = true);

    /**
     * Resets table name to the previous state
     *
     * @return void
     */
    public function resetTable();

    /**
     * Executes database query
     *
     * @param string $sql sql query to be executed
     *
     * @return resource
     */
    public function query($sql);

    /**
     * Returns the ID generated in the last query
     *
     * @return int
     */
    public function getInsertId();

    /**
     * Returns a number of affected rows in a previous INSERT operation
     *
     * @return int
     */
    public function getAffected();

    /**
     * Returns a number of rows in a result
     *
     * @param resource $resource query resource
     *
     * @return int
     */
    public function getNumRows($resource);

    /**
     * Returns number of found rows of the previous query with SQL_CALC_FOUND_ROWS
     * Note: this SQL function is MySQL specific
     *
     * @return int
     */
    public function foundRows();

    /**
     * Returns last executed query
     *
     * @return string
     */
    public function getLastQuery();

    /**
     * Returns list of executed queries
     *
     * @return array
     */
    public function getQueriesList();

    /**
     * Returns number of queries
     *
     * @return int
     */
    public function getCount();

    /**
     * Returns table prefix value
     *
     * @return string
     */
    public function getPrefix();

    /**
     * Returns one table row as array
     *
     * @param string $sql sql query
     *
     * @return array|bool
     */
    public function getRow($sql);

    /**
     * Returns table rows as array
     *
     * @param string $sql sql query
     * @param int $start start position
     * @param int $limit number of rows to be returned
     *
     * @return array
     */
    public function getAll($sql, $start = 0, $limit = 0);

    /**
     * Returns associative array of rows, first column of the query is used as a key
     *
     * @param string $sql sql query
     * @param bool $singleRow
     *
     * @return array
     */
    public function getAssoc($sql, $singleRow = false);

    /**
     * Returns key => value pair array of rows
     *
     * @param string $sql sql query
     *
     * @return array
     */
    public function getKeyValue($sql);

    /**
     * Returns field value of a row
     *
     * @param string $sql sql query
     *
     * @return mixed
     */
    public function getOne($sql);

    /**
     * Returns the numerical value of the error message from previous database operation
     *
     * @return int
     */
    public function getErrorNumber();

    /**
     * Returns a formatted string according to replacements
     * Does NOT sanitize any input (!)
     *
     * @param string $pattern replacement pattern
     * @param array $replacements new values
     *
     * @return string
     */
    public static function printf($pattern, array $replacements);

    /**
     * Returns true if at least 1 record exists
     *
     * @param string $where condition
     * @param array $values
     * @param null $tableName table name
     *
     * @return bool
     */
    public function exists($where, $values = [], $tableName = null);

    /**
     * Provides information about the columns in a table
     *
     * @param null $tableName table name
     * @param bool $addPrefix avoid prefix addition if false
     *
     * @return array
     */
    public function describe($tableName = null, $addPrefix = true);

    /**
     * Truncates table
     *
     * @param null|string $table table name
     *
     * @return bool
     */
    public function truncate($table = null);

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
    public function one($field, $condition = '', $tableName = null, $start = 0);

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
    public function onefield($field = self::ID_COLUMN_SELECTION, $condition = null, $start = 0, $limit = null, $tableName = null);

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
    public function row($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0);

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
    public function all($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $start = 0, $limit = null, $tableName = null);

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
    public function assoc($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0, $limit = null);

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
    public function keyvalue($fields = self::ALL_COLUMNS_SELECTION, $condition = null, $tableName = null, $start = 0, $limit = null);

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
    public function insert(array $values, $rawValues = null, $tableName = null);

    /**
     * Updates a record in a table and returns a number of affected rows
     *
     * Ex:  the code below updates a record in 'table_name'
     * 		$iaCore->iaDb->update(array('id' => '50', 'title' => 'My Row Title', 'text' => 'My Row Text'), null, array('date' => 'NOW()'), 'table_name');
     *
     * 		generated sql code looks like:
     * 		UPDATE `prefix_table_name` SET `title` = 'My Row Title', `text` = 'My Row Text', `date` = NOW() WHERE `id` = '50';
     *
     * @param array $values fields key=>value array to be updated
     * @param string $condition condition used for the update query, if empty tries to update using id field from $fields array
     * @param array|null $rawValues key=>value array for the record without sanitizing, commonly used for date insert
     * @param string|null $tableName table name to perform update, null uses current set table
     *
     * @return bool|int
     */
    public function update($values, $condition = null, $rawValues = null, $tableName = null);

    /**
     * Deletes records in a table and returns number of affected rows by the query
     *
     * @param string $condition condition to perform deletion
     * @param string|null $tableName table name where to perform deletion, null - deletes currently set table
     * @param array $values real values key=>value array to be replaced in condition
     *
     * @return int
     */
    public function delete($condition, $tableName = null, $values = []);

    /**
     * Replaces a record in a table
     *
     * Ex:  the code below inserts a new record in 'table_name'
     * 		$iaCore->iaDb->insert(array('title' => 'My Row Title', 'text' => 'My Row Text'), array('date' => 'NOW()'), 'table_name');
     *
     * 		generated sql code looks like:
     * 		REPLACE INTO `prefix_table_name` SET `title` = 'My Row Title', `text` = 'My Row Text', `date` = NOW();
     *
     * @param array $values key=>value array for the record
     * @param array|null $rawValues key=>value array for the record without sanitizing, commonly used for date insert
     * @param string|null $tableName table name to perform insertion, null uses current set table
     *
     * @return int
     */
    public function replace(array $values, $rawValues = null, $tableName = null);

    /**
     * Binds queries to make SQL more readable
     *
     * Ex: 	$sql = "SELECT * FROM users WHERE user = :user AND password = :password";
     * 		mysqli_bind($sql, array('user' => $user, 'password' => $password));
     * 		mysqli_query($sql);
     *
     * @param string $sql sql query to be processed
     * @param array $values real values key=>value array to be replaced in sql query
     *
     * @return void
     */
    public function bind(&$sql, $values);

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
    public function row_bind($fields, $condition, array $values, $tableName = null, $start = 0);

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
    public function one_bind($field, $condition, array $values, $tableName = null, $start = 0);

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
    public static function convertIds($ids, $columnName = 'id', $equal = true);

    /**
     * Accepts array of table names and deletes records in them
     *
     * @param string|array $tbl table name(s)
     * @param string $where sql condition to perform deletion
     *
     * @return bool|int
     */
    public function cascadeDelete($tbl, $where = '');

    /**
     * Returns all ENUM and SET values for selected table and field
     *
     * @param string $table table name
     * @param string $field field name
     *
     * @return array|bool
     */
    public function getEnumValues($table, $field);

    /**
     * Returns max value for `order` column
     *
     * @param string $table table name
     * @param array $condition column name, value
     *
     * @return bool|mixed
     */
    public function getMaxOrder($table = null, $condition = null);

    /**
     * Generates a faster way to select random records
     *
     * @param int|string $max max order or table name
     * @param string $id_name id column
     * @param int $pieces
     * @param int $delimiter
     *
     * @return mixed
     */
    public function orderByRand($max, $id_name = '`id`', $pieces = 12, $delimiter = 100);
}
