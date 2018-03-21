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

abstract class abstractModuleAdmin extends abstractCore
{
    protected $_activityLog;

    protected $_itemName;

    protected $_moduleUrl = '';

    protected $_moduleName;

    protected $_statuses = [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE];

    public $dashboardStatistics;


    public function init()
    {
        parent::init();

        if ($this->_itemName && !$this->_moduleName) {
            $this->_moduleName = $this->iaCore->factory('item')->getModuleByItem($this->_itemName);
        }

        if (empty($this->_moduleUrl)) {
            $this->_moduleUrl = $this->getModuleName() . IA_URL_DELIMITER . $this->getItemName() . IA_URL_DELIMITER;
        }

        if ($this->_activityLog) {
            is_array($this->_activityLog) || $this->_activityLog = [];

            $this->_activityLog['path'] = trim($this->getModuleUrl(), IA_URL_DELIMITER);

            if (!isset($this->_activityLog['item'])) {
                $this->_activityLog['item'] = $this->getItemName();
            }
        }

        if ($this->dashboardStatistics) {
            is_array($this->dashboardStatistics) || $this->dashboardStatistics = [];

            if (!isset($this->dashboardStatistics['icon'])) {
                $this->dashboardStatistics['icon'] = $this->getItemName();
            }
            if (!isset($this->dashboardStatistics['url'])) {
                $this->dashboardStatistics['url'] = $this->getModuleUrl();
            }
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

    public function getStatuses()
    {
        return $this->_statuses;
    }

    public function getModuleUrl()
    {
        return $this->_moduleUrl;
    }

    public function getInfo($key)
    {
        static $cachedData;

        if (is_null($cachedData)) {
            $cachedData = $this->iaDb->row(['id', 'type', 'title', 'url', 'version'], iaDb::convertIds($this->getModuleName(), 'name'), 'modules');

            $cachedData['url'] = IA_URL . (IA_URL_DELIMITER == $cachedData['url'] ? '' : $cachedData['url']);
        }

        return isset($cachedData[$key]) ? $cachedData[$key] : null;
    }

    public function getById($id, $process = true)
    {
        $row = $this->iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), self::getTable());

        $process && $this->_processValues($row, true);

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

    public function getDashboardStatistics($defaultProcessing = true)
    {
        $statuses = $this->iaDb->keyvalue('`status`, COUNT(*)', '1 = 1 GROUP BY `status`', self::getTable());
        $total = 0;

        foreach ($this->getStatuses() as $status) {
            isset($statuses[$status]) || $statuses[$status] = 0;
            $total += $statuses[$status];
        }

        if ($defaultProcessing) {
            $data = [];
            $max = 0;
            $weekDay = getdate();
            $weekDay = $weekDay['wday'];
            $rows = $this->iaDb->all('DAYOFWEEK(DATE(`date_added`)) `day`, `status`, `date_added`', 'DATE(`date_added`) BETWEEN DATE(DATE_SUB(NOW(), INTERVAL ' . $weekDay . ' DAY)) AND DATE(NOW())', null, null, self::getTable());

            foreach ($this->getStatuses() as $status) {
                $data[$status] = [];
            }
            foreach ($rows as $row) {
                isset($data[$row['status']][$row['day']]) || $data[$row['status']][$row['day']] = 0;
                $data[$row['status']][$row['day']]++;
            }
            foreach ($data as $key => &$days) {
                $i = null;
                for ($i = 1; $i < 8; $i++) {
                    isset($days[$i]) || $days[$i] = 0;
                    $max = max($max, $days[$i]);
                }
                ksort($days, SORT_NUMERIC);
                $days = implode(',', $days);
                $stArray[] = $key;
            }
        }

        return array_merge([
            '_format' => 'package',
            'data' => $defaultProcessing
                ? ['array' => implode('|', $data), 'max' => $max, 'statuses' => implode('|', $stArray)]
                : implode(',', $statuses),
            'rows' => $statuses,
            'item' => $this->getItemName() . 's',
            'total' => number_format($total)
        ], $this->dashboardStatistics);
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

        if ($entryData = $this->getById($itemId)) {
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

    public function getSitemapEntries()
    {
        // should return URLs array to be used in sitemap creation
        return [];
    }

    protected function _removeFromFavorites($itemId)
    {
        $iaItem = $this->iaCore->factory('item');

        $affected = $this->iaDb->delete('`item` = :item AND `id` = :id', $iaItem::getFavoritesTable(),
            ['item' => $this->getItemName(), 'id' => $itemId]);

        return $affected > 0;
    }

    protected function _writeLog($action, array $itemData, $itemId)
    {
        if ($this->_activityLog) {
            $iaLog = $this->iaCore->factory('log');

            $actionsMap = [
                iaCore::ACTION_ADD => iaLog::ACTION_CREATE,
                iaCore::ACTION_EDIT => iaLog::ACTION_UPDATE,
                iaCore::ACTION_DELETE => iaLog::ACTION_DELETE
            ];

            if (empty($itemData['title'])) {
                $multilingualFields = $this->iaCore->factory('field')->getMultilingualFields($this->getItemName());

                $field = in_array('title', $multilingualFields)
                    ? 'title_' . $this->iaView->language
                    : 'title';

                $title = $this->iaDb->one($field, iaDb::convertIds($itemId), self::getTable());
            } else {
                $title = $itemData['title'];
            }

            $params = array_merge($this->_activityLog, ['name' => $title, 'id' => $itemId]);

            $iaLog->write($actionsMap[$action], $params);
        }
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
