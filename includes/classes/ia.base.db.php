<?php
//##copyright##

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


	public function setTable($tableName, $addPrefix = true);
	public function resetTable();

	public function query($sql);

	public function getLastQuery();
	public function getQueriesList();
	public function getCount();

	public function getPrefix();

	public function getRow($sql);
	public function getAll($sql, $start = 0, $limit = 0);
	public function getAssoc($sql, $singleRow = false);
	public function getKeyValue($sql);
	public function getOne($sql);

	public function getErrorNumber();

	public static function printf($pattern, array $replacements);

	public function exists($where, $values = array(), $tableName = null);

	public function getInsertId();
	public function getAffected();

	public function getNumRows($resource);
	public function foundRows();

	public function describe($table = null);

	public function truncate($table = null);

	public function one($field, $condition = '', $tableName = null, $start = 0);
	public function onefield($field = self::ID_COLUMN_SELECTION, $condition = null, $start = 0, $limit = null, $tableName = null);
	public function row($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0);
	public function all($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $start = 0, $limit = null, $tableName = null);
	public function assoc($fields = self::ALL_COLUMNS_SELECTION, $condition = '', $tableName = null, $start = 0, $limit = null);
	public function keyvalue($fields = self::ALL_COLUMNS_SELECTION, $condition = null, $tableName = null, $start = 0, $limit = null);

	public function insert(array $values, $rawValues = null, $tableName = null);
	public function update($values, $condition = null, $rawValues = null, $tableName = null);
	public function delete($condition, $tableName = null, $values = array());

	public function bind(&$sql, $values);

	public function row_bind($fields, $condition, array $values, $tableName = null, $start = 0);
	public function one_bind($field, $condition, array $values, $tableName = null, $start = 0);

	public static function convertIds($ids, $columnName = 'id', $equal = true);

	public function cascadeDelete($tbl, $where = '');

	public function getEnumValues($table, $field);

	public function getMaxOrder($table = null);

	public function orderByRand($max, $id_name = '`id`', $pieces = 12, $delimiter = 100);
}