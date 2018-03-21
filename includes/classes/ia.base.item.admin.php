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

class itemModelAdmin extends abstractCore
{
    protected $_itemName;

    protected $_moduleUrl = '';

    protected $_moduleName;


    public function setParams(array $params)
    {
        $this->_itemName = $params['item'];
        $this->_moduleName = $params['module'];

        self::$_table = $params['table_name'];
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }

    public function getItemName()
    {
        return $this->_itemName;
    }

    public function getById($id, $process = true)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

        $process && $this->_processValues($row, true);

        return $row;
    }

    public function insert(array $itemData)
    {
        $itemId = $this->iaDb->insert($itemData, null, self::getTable());

        if ($itemId) {
            $this->_writeLog(iaCore::ACTION_ADD, $itemData, $itemId);

            $this->updateCounters($itemId, $itemData, iaCore::ACTION_ADD);

            $this->iaCore->startHook('phpListingAdded', [
                'itemId' => $itemId,
                'itemName' => $this->getItemName(),
                'itemData' => $itemData
            ]);
        }

        return $itemId;
    }

    public function update(array $itemData, $id)
    {
        if (empty($id)) {
            return false;
        }

        $currentData = $this->getById($id);

        if (empty($currentData)) {
            return false;
        }

        $this->iaDb->update($itemData, iaDb::convertIds($id), null, self::getTable());

        $result = (0 === $this->iaDb->getErrorNumber());

        if ($result) {
            $this->_writeLog(iaCore::ACTION_EDIT, $itemData, $id);

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
                $this->_writeLog(iaCore::ACTION_DELETE, $entryData, $itemId);

                $this->iaCore->startHook('phpListingRemoved', [
                    'itemId' => $itemId,
                    'itemName' => $this->getItemName(),
                    'itemData' => $entryData
                ]);
            }
        }

        return $result;
    }

    public function gridUpdate($params)
    {
        $result = [
            'result' => false,
            'message' => iaLanguage::get('invalid_parameters')
        ];

        $params || $params = [];

        if (isset($params['id']) && is_array($params['id']) && count($params) > 1) {
            $ids = $params['id'];
            unset($params['id']);

            $total = count($ids);
            $affected = 0;

            foreach ($ids as $id) {
                if ($this->update($params, $id)) {
                    $affected++;
                }
            }

            if ($affected) {
                $result['result'] = true;
                $result['message'] = ($affected == $total)
                    ? iaLanguage::get('saved')
                    : iaLanguage::getf('items_updated_of', ['num' => $affected, 'total' => $total]);
            } else {
                $result['message'] = iaLanguage::get('db_error');
            }
        }

        return $result;
    }

    public function gridDelete($params, $languagePhraseKey = 'deleted')
    {
        $result = [
            'result' => false,
            'message' => iaLanguage::get('invalid_parameters')
        ];

        if (isset($params['id']) && is_array($params['id']) && $params['id']) {
            $total = count($params['id']);
            $affected = 0;

            foreach ($params['id'] as $id) {
                if ($this->delete($id)) {
                    $affected++;
                }
            }

            if ($affected) {
                $result['result'] = true;
                if (1 == $total) {
                    $result['message'] = iaLanguage::get($languagePhraseKey);
                } else {
                    $result['message'] = ($affected == $total)
                        ? iaLanguage::getf('items_deleted', ['num' => $affected])
                        : iaLanguage::getf('items_deleted_of', ['num' => $affected, 'total' => $total]);
                }
            } else {
                $result['message'] = iaLanguage::get('db_error');
            }
        }

        return $result;
    }

    public function updateCounters($itemId, array $itemData, $action, $previousData = null)
    {
        // within final class, the counters update routines should be placed here
    }

    public function getSitemapEntries()
    {
        // should return URLs array to be used in sitemap creation
        return [];
    }

    /**
     * Used to unwrap fields
     *
     * @param array $rows items array
     * @param boolean $singleRow true when item is passed as one row
     * @param array $fieldNames list of custom serialized fields
     */
    protected function _processValues(&$rows, $singleRow = false)
    {
        if (!$rows) {
            return;
        }

        $singleRow && $rows = [$rows];

        $iaField = $this->iaCore->factory('field');

        if ($multilingualFields = $iaField->getMultilingualFields($this->getItemName())) {
            foreach ($rows as &$row) {
                if (!is_array($row)) {
                    break;
                }

                $currentLangCode = $this->iaCore->language['iso'];
                foreach ($multilingualFields as $fieldName) {
                    if (isset($row[$fieldName . '_' . $currentLangCode]) && !isset($row[$fieldName])) {
                        $row[$fieldName] = $row[$fieldName . '_' . $currentLangCode];
                    }
                }
            }
        }

        $singleRow && $rows = array_shift($rows);
    }
}
