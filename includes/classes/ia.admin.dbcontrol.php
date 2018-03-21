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

class iaDbControl extends abstractCore
{


    /**
     * Returns array of tables
     *
     * @return array
     */
    public function getTables()
    {
        $where = '`table_schema` = :schema && `table_name` LIKE :name';
        $this->iaDb->bind($where, ['schema' => INTELLI_DBNAME, 'name' => INTELLI_DBPREFIX . '%']);

        $sql = "SELECT `table_name` FROM `information_schema`.`tables` WHERE " . $where;
        $tables = $this->iaDb->getAssoc($sql);

        return $tables ? array_keys($tables) : [];
    }

    /**
     * Truncates table
     *
     * @param string $table - without prefix
     *
     * @return bool
     */
    public function truncate($table)
    {
        if (empty($table)) {
            return false;
        }

        $sql = sprintf('TRUNCATE TABLE `%s`', $this->iaDb->prefix . $table);

        return $this->iaDb->query($sql);
    }

    /**
     * Splits MySQL dump file into queries and executes each one separately
     *
     * @param string $file filename path
     * @param string $delimiter delimiter for queries
     *
     * @return bool
     */
    public function splitSQL($file, $delimiter = ';')
    {
        $result = false;

        if (is_file($file)) {
            $file = fopen($file, 'r');

            if (is_resource($file)) {
                set_time_limit(0);
                $query = [];

                while (!feof($file)) {
                    $query[] = fgets($file);

                    if (preg_match('#' . preg_quote($delimiter, '~') . '\s*$#iS', end($query)) === 1) {
                        $query = trim(implode('', $query));
                        $query = str_replace(
                            ['{prefix}', '{mysql_version}', '{db_options}', '{lang}'],
                            [$this->iaDb->prefix, $this->iaDb->tableOptions, $this->iaDb->tableOptions, iaLanguage::getMasterLanguage()->iso],
                            $query
                        );

                        $this->iaDb->query($query);
                    }

                    if (is_string($query)) {
                        $query = [];
                    }
                }

                $result = true;
            }
        }

        return $result;
    }

    /**
     * Return structure sql dump
     *
     * @param string $tableName table name
     * @param bool $aDrop if true use DROP TABLE
     * @param bool $prefix if true use prefix
     *
     * @return string
     */
    public function makeStructureBackup($tableName, $aDrop = false, $prefix = true)
    {
        $tableNameReplacement = $prefix ? $tableName : str_replace($this->iaDb->prefix, '{prefix}', $tableName);

        $fields = $this->iaDb->describe($tableName, false);

        $output = '';
        $output .= $aDrop ? "DROP TABLE IF EXISTS `$tableNameReplacement`;" . PHP_EOL : '';
        $output .= "CREATE TABLE `$tableNameReplacement` (" . PHP_EOL;

        // compose table's structure
        foreach ($fields as $value) {
            $output .= "	`{$value['Field']}` {$value['Type']}";
            if ($value['Null'] != 'YES') {
                $output .= ' NOT NULL';
            }
            if ($value['Default']) {
                if (0 === strpos($value['Type'], 'enum')) {
                    $output .= " default '{$value['Default']}'";
                }
                else {
                    $output .= is_numeric($value['Default']) || ('CURRENT_TIMESTAMP' == $value['Default'])
                        ? ' default ' . $value['Default']
                        : " default '{$value['Default']}'";
                }
            }
            if ($value['Extra']) {
                $output .= " {$value['Extra']}";
            }
            $output .= ',' . PHP_EOL;
        }

        // compose table's indices
        if ($indices = $this->iaDb->getAll('SHOW INDEXES FROM ' . $tableName)) {
            $compositeIndices = [];

            // assemble composite indices for further usage
            foreach ($indices as $key => $index) {
                isset($compositeIndices[$index['Key_name']]) || $compositeIndices[$index['Key_name']] = [];
                $compositeIndices[$index['Key_name']][] = $index['Column_name'];
                if (1 < count($compositeIndices[$index['Key_name']])) {
                    unset($indices[$key]);
                }
            }

            // generate the output
            foreach ($indices as $index) {
                $line = "\t";
                $columnList = '(`' . implode('`,`', $compositeIndices[$index['Key_name']]) . '`),';

                if ('PRIMARY' == $index['Key_name']) {
                    $line .= 'PRIMARY KEY ' . $columnList;
                } else {
                    if ('FULLTEXT' == $index['Index_type']) {
                        $line .= 'FULLTEXT ';
                    }
                    if (0 == $index['Non_unique']) {
                        $line .= 'UNIQUE ';
                    }
                    $line .= 'KEY `' . $index['Key_name'] . '` ';
                    $line .= $columnList;
                }

                $output .= $line . PHP_EOL;
            }
        }

        $output = substr($output, 0, -3);
        $output .= PHP_EOL . ')';

        if ($collation = $this->_getTableCollation($tableName)) {
            $output .= ' ENGINE=MyISAM DEFAULT CHARSET = `' . $collation . '`;';
        }

        return stripslashes($output);
    }

    /**
     * makeDataBackup
     *
     * Return data sql dump
     *
     * @param string $tableName $tableName table name
     * @param bool $aComplete if true use complete inserts
     * @param bool $prefix if true use prefix
     * @access public
     *
     * @return string
     */
    public function makeDataBackup($tableName, $aComplete = false, $prefix = true)
    {
        $tableNameReplacement = $prefix ? $tableName : str_replace($this->iaDb->prefix, '{prefix}', $tableName);

        $out = '';
        $complete = '';

        $this->iaDb->setTable($tableName, false);
        if ($aComplete) {
            $fields = $this->iaDb->describe($tableName, false);

            $complete = ' (';

            foreach ($fields as $value) {
                $complete .= "`" . $value['Field'] . "`, ";
            }
            $complete = preg_replace('/(,\n|, )?$/', '', $complete);
            $complete .= ')';
        }

        if ($data = $this->iaDb->all()) {
            foreach ($data as $value) {
                $out .= 'INSERT INTO `' . $tableNameReplacement . '`' . $complete . " VALUES (";
                foreach ($value as $key2 => $value2) {
                    if (!isset($value[$key2])) {
                        $out .= "null, ";
                    } elseif ($value[$key2] != '') {
                        $out .= "'" . iaSanitize::sql($value[$key2]) . "', ";
                    } else {
                        $out .= "'', ";
                    }
                }
                $out = rtrim($out, ', ');
                $out .= ');' . PHP_EOL;
            }
        }

        $this->iaDb->resetTable();

        return $out;
    }

    /**
     * Return data + structure sql dump
     *
     * @param string $tableName table name
     * @param bool $drop if true use DROP TABLE
     * @param bool $complete if true use complete inserts
     * @param bool $prefix if true use prefix
     * @access public
     *
     * @return string
     */
    public function makeFullBackup($tableName, $drop = false, $complete = false, $prefix = true)
    {
        $out = $this->makeStructureBackup($tableName, $drop, $prefix);
        $out .= PHP_EOL;
        $out .= $this->makeDataBackup($tableName, $complete, $prefix);
        $out .= PHP_EOL . PHP_EOL;

        return $out;
    }

    /**
     * Returns structure dump of a database
     *
     * @param bool $drop if true use DROP TABLE
     * @param bool $prefix if true use prefix
     * @access public
     *
     * @return string
     */
    public function makeDbStructureBackup($drop = false, $prefix = true)
    {
        $out = "CREATE DATABASE `" . INTELLI_DBNAME . "`;\n\n";

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $out .= $this->makeStructureBackup($table, $drop, $prefix);
            $out .= "\n\n";
        }

        return $out;
    }

    /**
     * Returns data dump of a database
     *
     * @param bool $complete if true use complete inserts
     * @param bool $prefix if true use prefix
     * @access public
     *
     * @return string
     */
    public function makeDbDataBackup($complete = false, $prefix = true)
    {
        $result = '';
        $tables = $this->getTables();

        foreach ($tables as $table) {
            $result .= $this->makeDataBackup($table, $complete, $prefix);
            $result .= PHP_EOL . PHP_EOL;
        }

        return $result;
    }

    /**
     * Returns whole database dump
     *
     * @param bool $aDrop if true use DROP TABLE
     * @param bool $aComplete if true use complete inserts
     * @param bool $aPrefix if true use prefix
     * @access public
     *
     * @return string
     */
    public function makeDbBackup($aDrop = false, $aComplete = false, $aPrefix = true)
    {
        $out = "CREATE DATABASE `" . INTELLI_DBNAME . "`;\n\n";

        $tables = $this->getTables();

        foreach ($tables as $table) {
            $out .= $this->makeStructureBackup($table, $aDrop, $aPrefix);
            $out .= "\n\n";
            $out .= $this->makeDataBackup($table, $aComplete, $aPrefix);
            $out .= "\n\n";
        }

        return $out;
    }

    protected function _getTableCollation($table)
    {
        $sql = sprintf('SHOW CREATE TABLE `%s`', $table);

        $structure = $this->iaDb->getAll($sql);
        $structure = $structure[0]['Create Table'];

        $result = '';

        $matches = [];
        if (preg_match('/DEFAULT CHARSET=([a-z0-9]+)/i', $structure, $matches)) {
            $result = $matches[1];
        }

        return $result;
    }
}
