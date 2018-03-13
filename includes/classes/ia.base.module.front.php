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

abstract class abstractModuleFront extends abstractCore
{
    protected $_itemName;

    protected $_moduleName;

    protected $_statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE];

    public $coreSearchEnabled = false;
    public $coreSearchOptions = [];

    public $guestAccessCookieName = '__ga';

    private $_foundRows;


    public function init()
    {
        parent::init();

        if ($this->_itemName && !$this->_moduleName) {
            $this->_moduleName = $this->iaCore->factory('item')->getModuleByItem($this->_itemName);
        }
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }

    public function getItemName()
    {
        return $this->_itemName;
    }

    public function getFoundRows()
    {
        return $this->_foundRows;
    }

    public function isSearchable()
    {
        return $this->coreSearchEnabled;
    }

    public function getStatuses()
    {
        return $this->_statuses;
    }

    public function url($action, array $data)
    {
        return $this->getUrl($data);
    }

    public function getActionUrl($action, array $data)
    {
        return $this->getInfo('url') . $action . '/' . $data['id'] . '/';
    }

    public function getUrl(array $data)
    {
        return '#';
    }

    public function getInfo($key)
    {
        $values = &$this->iaCore->modulesData[$this->getModuleName()];

        return isset($values[$key]) ? $values[$key] : null;
    }

    public function accountActions($params)
    {
        return ['', ''];
    }

    public function getById($id, $decorate = true)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

        $decorate && $this->_processValues($row, true);

        return $row;
    }

    public function getOne($where, $fields = '*')
    {
        $row = $this->iaDb->row($fields, $where, self::getTable());

        $this->_processValues($row, true);

        return $row;
    }

    public function getAll($where, $fields = null, $start = null, $limit = null)
    {
        is_null($fields) && $fields = iaDb::ALL_COLUMNS_SELECTION;

        $rows = $this->iaDb->all($fields, $where, $start, $limit, self::getTable());

        $this->_processValues($rows);

        return $rows;
    }

    public function insert(array $itemData)
    {
        $itemId = $this->iaDb->insert($itemData, null, self::getTable());

        if ($itemId) {
            $this->updateCounters($itemId, $itemData, iaCore::ACTION_ADD);

            // finally, notify plugins
            $this->iaCore->startHook('phpListingAdded', [
                'itemId' => $itemId,
                'itemName' => $this->getItemName(),
                'itemData' => $itemData
            ]);

            if (!iaUsers::hasIdentity()) {
                $this->rememberGuestListing(array_merge($itemData, ['id' => $itemId]));
            }
        }

        return $itemId;
    }

    public function update(array $itemData, $id)
    {
        if (empty($id)) {
            return false;
        }

        $currentData = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());
        $result = (bool)$this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

        if ($result) {
            $this->updateCounters($id, $itemData, iaCore::ACTION_EDIT, $currentData);

            $this->iaCore->startHook('phpListingUpdated', [
                'itemId' => $id,
                'itemName' => $this->getItemName(),
                'itemData' => $itemData,
                'previousData' => $currentData
            ]);
        }

        return $result;
    }

    public function delete($itemId)
    {
        $result = false;

        if ($entryData = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($itemId), self::getTable())) {
            $result = (bool)$this->iaDb->delete(iaDb::convertIds($itemId), self::getTable());

            if ($result) {
                $this->iaCore->factory('field')->cleanUpItemFiles($this->getItemName(), $entryData);

                $this->updateCounters($itemId, $entryData, iaCore::ACTION_DELETE);

                $this->_removeFromFavorites($itemId);

                $this->iaCore->startHook('phpListingRemoved', [
                    'itemId' => $itemId,
                    'itemName' => $this->getItemName(),
                    'itemData' => $entryData
                ]);
            }
        }

        return $result;
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        // within final class, the counters update routines should be placed here
    }

    /**
     * Increments the number of views for a specified item
     *
     * Application should ensure if an item is in active status
     * and provide appropriate DB column name if differs from "views_num"
     */
    public function incrementViewsCounter($itemId, $columnName = 'views_num')
    {
        $viewsTable = 'views_log';

        $itemName = $this->getItemName();
        $ipAddress = $this->iaCore->util()->getIp();
        $date = date(iaDb::DATE_FORMAT);

        if ($this->iaDb->exists('`item` = :item AND `item_id` = :id AND `ip` = :ip AND `date` = :date', ['item' => $itemName, 'id' => $itemId, 'ip' => $ipAddress, 'date' => $date], $viewsTable)) {
            return false;
        }

        $this->iaDb->insert(['item' => $itemName, 'item_id' => $itemId, 'ip' => $ipAddress, 'date' => $date], null, $viewsTable);
        $result = $this->iaDb->update(null, iaDb::convertIds($itemId), [$columnName => '`' . $columnName . '` + 1'], self::getTable());

        return (bool)$result;
    }

    public function coreSearch($stmt, $start, $limit, $order)
    {
        $order = empty($order) ? '' : ' ORDER BY ' . $order;

        $rows = $this->iaDb->all(iaDb::STMT_CALC_FOUND_ROWS . ' ' . iaDb::ALL_COLUMNS_SELECTION, $stmt . $order, $start, $limit, self::getTable());
        $count = $this->iaDb->foundRows();

        $this->_processValues($rows);

        return [$count, $rows];
    }

    public function coreSearchTranslateColumn($column, $value)
    {
        return null;
    }

    /**
     * Used to unserialize fields
     *
     * @param array $rows items array
     * @param boolean $singleRow true when item is passed as one row
     * @param array $fieldNames list of custom serialized fields
     */
    protected function _processValues(&$rows, $singleRow = false, $fieldNames = [])
    {
        if (!$rows) {
            return;
        }

        $singleRow && $rows = [$rows];

        // process favorites
        $rows = $this->iaCore->factory('item')->updateItemsFavorites($rows, $this->getItemName());

        $iaField = $this->iaCore->factory('field');
        $iaCurrency = $this->iaCore->factory('currency');

        // fields require processing
        $serializedFields = array_merge($fieldNames, $iaField->getSerializedFields($this->getItemName()));
        $multilingualFields = $iaField->getMultilingualFields($this->getItemName());
        $currencyFields = $iaField->getFieldsByType($this->getItemName(), iaField::CURRENCY);

        foreach ($rows as &$row) {
            if (!is_array($row)) {
                break;
            }

            // filter fields
            $iaField->filter($this->getItemName(), $row);

            foreach ($serializedFields as $fieldName) {
                if (isset($row[$fieldName])) {
                    $row[$fieldName] = $row[$fieldName] ? unserialize($row[$fieldName]) : [];
                }
            }

            $currentLangCode = $this->iaCore->language['iso'];
            foreach ($multilingualFields as $fieldName) {
                if (isset($row[$fieldName . '_' . $currentLangCode]) && !isset($row[$fieldName])) {
                    $row[$fieldName] = $row[$fieldName . '_' . $currentLangCode];
                }
            }

            foreach ($currencyFields as $fieldName) {
                if (isset($row[$fieldName])) {
                    $row[$fieldName . '_formatted'] = $iaCurrency->format($row[$fieldName]);
                }
            }

            // mandatory keys
            $row['item'] = $this->getItemName();
            $row['link'] = $this->getUrl($row);
        }

        $singleRow && $rows = array_shift($rows);
    }

    protected function _removeFromFavorites($itemId)
    {
        $iaItem = $this->iaCore->factory('item');

        $affected = $this->iaDb->delete('`item` = :item AND `id` = :id', $iaItem::getFavoritesTable(),
            ['item' => $this->getItemName(), 'id' => $itemId]);

        return $affected > 0;
    }

    protected function rememberGuestListing(array $itemData)
    {
        $ids = isset($_COOKIE[$this->guestAccessCookieName])
            ? explode(',', $_COOKIE[$this->guestAccessCookieName])
            : [];
        $ids[] = $this->generateGuestAccessId($itemData);
        $ids = implode(',', $ids);

        $lifetime = 60 * 60 * 24 * 365 * 2; // 2 years

        setcookie($this->guestAccessCookieName, $ids, time() + $lifetime, '/');
    }

    // this method to be overloaded
    // each item class should use own token generation logic because of specific set of fields
    public function generateGuestAccessId(array $itemData)
    {
        throw new Exception('No guest access key generator method');
    }

    protected function _checkIfCountersNeedUpdate($action, array $itemData, $previousData, $categoryClassInstance)
    {
        switch ($action) {
            case iaCore::ACTION_EDIT:
                if (!isset($itemData['category_id'])) {
                    if (iaCore::STATUS_ACTIVE == $previousData['status'] && iaCore::STATUS_ACTIVE != $itemData['status']) {
                        $categoryClassInstance->recountById($previousData['category_id'], -1);
                    } elseif (iaCore::STATUS_ACTIVE != $previousData['status'] && iaCore::STATUS_ACTIVE == $itemData['status']) {
                        $categoryClassInstance->recountById($previousData['category_id']);
                    }
                } else {
                    if ($itemData['category_id'] == $previousData['category_id']) {
                        if (iaCore::STATUS_ACTIVE == $previousData['status'] && iaCore::STATUS_ACTIVE != $itemData['status']) {
                            $categoryClassInstance->recountById($itemData['category_id'], -1);
                        } elseif (iaCore::STATUS_ACTIVE != $previousData['status'] && iaCore::STATUS_ACTIVE == $itemData['status']) {
                            $categoryClassInstance->recountById($itemData['category_id']);
                        }
                    } else { // category changed
                        iaCore::STATUS_ACTIVE == $itemData['status']
                            && $categoryClassInstance->recountById($itemData['category_id']);
                        iaCore::STATUS_ACTIVE == $previousData['status']
                            && $categoryClassInstance->recountById($previousData['category_id'], -1);
                    }
                }
                break;
            case iaCore::ACTION_ADD:
                iaCore::STATUS_ACTIVE == $itemData['status']
                    && $categoryClassInstance->recountById($itemData['category_id']);
                break;
            case iaCore::ACTION_DELETE:
                iaCore::STATUS_ACTIVE == $itemData['status']
                    && $categoryClassInstance->recountById($itemData['category_id'], -1);
        }
    }
}
