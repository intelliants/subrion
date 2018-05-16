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

class iaConfig extends abstractCore
{
    const KEY_COLUMN = 'name';

    const TYPE_HIDDEN = 'hidden';

    const TYPE_DIVIDER = 'divider';

    const TYPE_COMBO = 'combo';
    const TYPE_CHECKBOX = 'checkbox';
    const TYPE_COLORPICKER = 'colorpicker';
    const TYPE_IMAGE = 'image';
    const TYPE_ITEMSCHECKBOX = 'itemscheckbox';
    const TYPE_PASSWORD = 'password';
    const TYPE_RADIO = 'radio';
    const TYPE_SELECT = 'select';
    const TYPE_TEXT = 'text';
    const TYPE_TEXTAREA = 'textarea';
    const TYPE_TPL = 'tpl';

    protected static $_table = 'config';
    protected static $_customConfigDbTable = 'config_custom';
    protected static $_configGroupsDbTable = 'config_groups';

    protected $optionsDefaults = [
        'wysiwyg' => false,
        'code_editor' => false,
        'multilingual' => false
    ];


    public static function getCustomConfigTable()
    {
        return self::$_customConfigDbTable;
    }

    public static function getConfigGroupsTable()
    {
        return self::$_configGroupsDbTable;
    }

    public function getGroup($groupName)
    {
        $result = $this->iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', ['name' => $groupName],
            self::getConfigGroupsTable());
        empty($result) || $result['title'] = iaLanguage::get('config_group_' . $result['name']);

        return $result;
    }

    public function fetchGroups($where = null)
    {
        if (is_null($where)) {
            $where = iaDb::EMPTY_CONDITION;
        }
        $where.= ' ORDER BY `order`';

        $rows = $this->iaDb->all(['name', 'module'], $where, null, null, self::getConfigGroupsTable());

        foreach ($rows as &$row) {
            $row['title'] = iaLanguage::get('config_group_' . $row['name']);
        }

        return $rows;
    }

    public function update(array $data, $key)
    {
        $this->validateEntry($data);

        return $this->iaDb->update($data, iaDb::convertIds($key, self::KEY_COLUMN), null, self::getTable());
    }

    public function insert(array $data)
    {
        $this->validateEntry($data);

        return $this->iaDb->insert($data, null, self::getTable());
    }

    public function insertGroup(array $data)
    {
        $maxOrder = $this->iaDb->getMaxOrder(self::getConfigGroupsTable());

        $this->iaDb->insert($data, ['order' => ++$maxOrder], self::getConfigGroupsTable());

        return 0 === $this->iaDb->getErrorNumber();
    }

    public function deleteGroup($key, $value)
    {
        return $this->iaDb->delete(iaDb::convertIds($value, $key), self::getConfigGroupsTable());
    }

    public function fetch($where)
    {
        $where.= ' ORDER BY `order`';

        $rows = $this->iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $where, null, null, self::getTable());

        foreach ($rows as &$row) {
            $row['options'] = $this->unpackOptions($row['options']);
            if ($row['options']['multilingual']) {
                $row['value'] = $this->unpack($row['value']);
            }
        }

        return $rows;
    }

    public function fetchKeyValue($where = null)
    {
        $result = [];

        is_null($where) && $where = iaDb::EMPTY_CONDITION;
        $where.= " AND `type` != '" . self::TYPE_DIVIDER . "' ";
        $where.= 'ORDER BY `order`';

        $rows = $this->iaDb->all(['key' => self::KEY_COLUMN, 'value', 'type', 'options'], $where, null, null, self::getTable());

        if ($rows) {
            $langCode = $this->iaCore->language['iso'];

            foreach ($rows as $row) {
                $value = $row['value'];

                if ((self::TYPE_TEXT == $row['type'] || self::TYPE_TEXTAREA == $row['type'])
                    && $this->unpackOptions($row['options'])['multilingual']) {
                    $value = $this->unpack($value);
                    $value = isset($value[$langCode]) ? $value[$langCode] : '';
                }

                $result[$row['key']] = $value;
            }
        }

        return $result;
    }

    public function fetchCustom($user, $group = null)
    {
        $result = [];

        $stmt = [];
        $user && $stmt[] = "(cc.`type` = 'user' AND cc.`type_id` = $user) ";
        $group && $stmt[] = "(cc.`type` = 'group' AND cc.`type_id` = $group) ";

        $sql = <<<SQL
SELECT 
  cc.name `key`, cc.value, cc.type,
  c.type config_type, c.options config_options
FROM `:table_config` c
LEFT JOIN `:table_custom_config` cc ON (c.name = cc.name)
WHERE :where
GROUP BY cc.name;
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $this->iaDb->prefix,
            'table_config' => self::getTable(true),
            'table_custom_config' => $this->iaDb->prefix . self::$_customConfigDbTable,
            'where' => implode(' OR ', $stmt)
        ]);

        $rows = $this->iaDb->getAll($sql);

        if (!$rows) {
            return $result;
        }

        $result = ['group' => [], 'user' => [], 'plan' => []];

        foreach ($rows as $row) {
            $value = $row['value'];

            if (self::TYPE_TEXT == $row['config_type'] || self::TYPE_TEXTAREA == $row['config_type']) {
                if ($this->unpackOptions($row['config_options'])['multilingual']) {
                    $value = $this->unpack($value);
                }
            }

            $result[$row['type']][$row['key']] = $value;
        }

        return array_merge($result['group'], $result['user'], $result['plan']);
    }

    public function get($key)
    {
        return ($rows = $this->fetch(iaDb::convertIds($key, self::KEY_COLUMN)))
            ? $rows[0]['value']
            : false;
    }

    public function set($key, $value)
    {
        if (is_array($value)) {
            $row = $this->getByKey($key);

            $value = $this->unpackOptions($row['options'])['multilingual']
                ? $this->pack($value)
                : implode(',', $value);
        }

        return (bool)$this->iaDb->update(['value' => $value], iaDb::convertIds($key, self::KEY_COLUMN), null, self::getTable());
    }

    public function saveCustom($data, $key, $value, $type, $typeId)
    {
        $where = sprintf("name = '%s' && type = '%s' && type_id = %d", $key, $type, $typeId);

        $this->iaDb->setTable(self::getCustomConfigTable());

        if ($data[$key]) {
            $config = $this->getByKey($key);
            if ($config && $this->unpackOptions($config['options'])['multilingual']) {
                $value = $this->pack($value);
            }

            $entry = ['name' => $key, 'value' => $value, 'type' => $type, 'type_id' => $typeId];

            if ($this->iaDb->exists($where)) {
                unset($entry['value']);
                $this->iaDb->bind($where, $entry);
                $this->iaDb->update(['value' => $value], $where);
            } else {
                $this->iaDb->insert($entry);
            }
        } else {
            $this->iaDb->delete($where);
        }

        $this->iaDb->resetTable();
    }

    public function getBy($key, $value)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($value, $key), self::getTable());

        if ($row) {
            $row['key'] = $row[self::KEY_COLUMN];
        }

        return $row;
    }

    public function getByKey($key)
    {
        return $this->getBy(self::KEY_COLUMN, $key);
    }

    protected function pack($valueToBePacked)
    {
        return json_encode($valueToBePacked, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    protected function unpack($packedValue)
    {
        return json_decode($packedValue, true);
    }

    protected function unpackOptions($packedOptions)
    {
        $result = $packedOptions
            ? json_decode($packedOptions, true)
            : [];

        foreach ($this->optionsDefaults as $optionName => $optionValue) {
            if (!isset($result[$optionName])) {
                $result[$optionName] = $optionValue;
            }
        }

        return $result;
    }

    public function copyMultilingualKeys($langIsoCode)
    {
        $this->iaDb->setTable(self::getTable());

        $rows = $this->iaDb->all(['key' => self::KEY_COLUMN, 'value', 'options'], "`type` IN ('text', 'textarea')");

        foreach ($rows as $row) {
            if (!$this->unpackOptions($row['options'])['multilingual']) {
                continue;
            }

            $value = $this->unpack($row['value']);

            if (!isset($value[$langIsoCode])) {
                $value[$langIsoCode] = $value[iaLanguage::getMasterLanguage()->iso];

                $this->set($row['key'], $value);
            }
        }

        $this->iaDb->resetTable();
    }

    protected function validateEntry(array &$data)
    {
        if (isset($data['options'])) {
            if (is_array($data['options'])) {
                $data['options'] = json_encode($data['options'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }
    }
}
