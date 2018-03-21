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

abstract class iaAbstractControllerModuleBackend extends iaAbstractControllerBackend
{
    /**
     * @var smarty/javascript controller files
     */
    protected $_moduleName;

    /**
     * @var php class helper
     */
    protected $_helperName;

    /**
     * @var set this value to process $_POST through fields parse
     */
    protected $_itemName;

    /**
     * @var array activity log specific settings
     */
    protected $_activityLog;

    protected $_iaField;


    public function __construct()
    {
        parent::__construct();

        $this->_iaField = $this->_iaCore->factory('field');

        $this->_moduleName = IA_CURRENT_MODULE;

        if ($this->_helperName) {
            $helperClass = $this->_iaCore->factoryModule($this->_helperName, $this->getModuleName());
            $this->setHelper($helperClass);

            $this->_itemName || $this->_setItemName($helperClass->getItemName());
            $this->setTable($helperClass::getTable());
        }

        if ($this->_itemName) {
            $this->_path = IA_ADMIN_URL . $this->getModuleName() . IA_URL_DELIMITER . $this->getName() . IA_URL_DELIMITER;
            $this->_template = 'form-' . $this->getName();
        }

        if ($this->_activityLog) {
            is_array($this->_activityLog) || $this->_activityLog = [];

            $this->_activityLog['path'] = $this->getModuleName() . IA_URL_DELIMITER . $this->getName();
            if (!isset($this->_activityLog['item'])) {
                $this->_activityLog['item'] = $this->getItemName();
            }
        }

        $this->init();
    }

    public function init()
    {
    }

    public function getModuleName()
    {
        return $this->_moduleName;
    }

    public function getItemName()
    {
        return $this->_itemName;
    }

    protected function _setItemName($itemName)
    {
        $this->_itemName = $itemName;
    }

    protected function _gridRead($params)
    {
        $action = empty($this->_iaCore->requestPath[0]) ? null : $this->_iaCore->requestPath[0];

        if (!is_null($action)) {
            $methodName = '_getJson' . ucfirst($action);
            if (is_callable([$this, $methodName])) {
                return call_user_func([$this, $methodName], $params);
            }
        }

        return parent::_gridRead($params);
    }

    // multilingual fields support for package items
    protected function _gridApplyFilters(&$conditions, &$values, array $params)
    {
        if (!is_array($this->_gridFilters) || !$this->_gridFilters) {
            return;
        }

        $multilingualFields = $this->_iaField->getMultilingualFields($this->getItemName());

        foreach ($this->_gridFilters as $name => $type) {
            if (!empty($params[$name])) {
                $column = $name;
                $value = $params[$name];

                in_array($name, $multilingualFields) && $column.= '_' . $this->_iaCore->language['iso'];

                switch ($type) {
                    case self::EQUAL:
                        $conditions[] = sprintf('%s`%s` = :%s', $this->_gridQueryMainTableAlias, $column, $name);
                        $values[$name] = $value;
                        break;
                    case self::LIKE:
                        $conditions[] = sprintf('%s`%s` LIKE :%s', $this->_gridQueryMainTableAlias, $column, $name);
                        $values[$name] = '%' . $value . '%';
                }
            }
        }
    }

    protected function _gridUnpackColumnsArray()
    {
        if (is_array($this->_gridColumns)
            && ($multilingualFields = $this->_iaField->getMultilingualFields($this->getItemName()))) {
            foreach ($this->_gridColumns as $key => &$field) {
                if (in_array($field, $multilingualFields)) {
                    unset($this->_gridColumns[$key]);
                    $this->_gridColumns[$field] = $field . '_' . $this->_iaCore->language['iso'];
                }
            }
        }

        return parent::_gridUnpackColumnsArray();
    }

    protected function _gridGetSorting(array $params)
    {
        if (!empty($params['sort']) && is_string($params['sort'])) {
            $sorting = $params['sort'];

            if (in_array($sorting, $this->_iaField->getMultilingualFields($this->getItemName()))) {
                $params['sort'] .= '_' . $this->_iaCore->language['iso'];
            } elseif (isset($this->_gridSorting[$sorting])
                && is_array($this->_gridSorting[$sorting])
                && 3 == count($this->_gridSorting[$sorting])) {
                $joinFieldName = $this->_gridSorting[$sorting][0];
                $joinedItemName = $this->_gridSorting[$sorting][2];

                $multilingualFields = $this->_iaField->getMultilingualFields($joinedItemName);

                if (in_array($joinFieldName, $multilingualFields)) {
                    $this->_gridSorting[$sorting][0] .= '_' . $this->_iaCore->language['iso'];
                }
            }
        }

        return parent::_gridGetSorting($params);
    }
    //

    protected function _indexPage(&$iaView)
    {
        $iaView->grid('_IA_URL_modules/' . $this->getModuleName() . '/js/admin/' . $this->getName());
    }

    protected function _assignValues(&$iaView, array &$entryData)
    {
        $this->_setSystemDefaults($entryData);

        $sections = $this->_iaField->getGroups($this->getItemName());
        $plans = $this->_getPlans();

        $iaView->assign('item_sections', $sections);
        $iaView->assign('plans', $plans);
    }

    protected function _unwrapValues(array &$entryData)
    {
        $this->_iaField->unwrapItemValues($this->getItemName(), $entryData);
    }

    protected function _getPlans()
    {
        $iaPlan = $this->_iaCore->factory('plan');

        if ($plans = $iaPlan->getPlans($this->getItemName())) {
            foreach ($plans as &$plan) {
                list(, $plan['defaultEndDate']) = $iaPlan->calculateDates($plan['duration'], $plan['unit']);
            }
        }

        return $plans;
    }

    protected function _insert(array $entryData)
    {
        return parent::_entryAdd($entryData);
    }

    protected function _update(array $entryData, $entryId)
    {
        return parent::_entryUpdate($entryData, $entryId);
    }

    protected function _delete($entryId)
    {
        return parent::_entryDelete($entryId);
    }

    protected function _entryAdd(array $entryData)
    {
        $entryId = $this->_insert($entryData);

        if ($entryId) {
            $this->_writeLog(iaCore::ACTION_ADD, $entryData, $entryId);
            $this->updateCounters($entryId, $entryData, iaCore::ACTION_ADD);

            $this->_iaCore->startHook('phpListingAdded', [
                'itemId' => $entryId,
                'itemName' => $this->getItemName(),
                'itemData' => $entryData
            ]);
        }

        return $entryId;
    }

    protected function _entryUpdate(array $entryData, $entryId)
    {
        if (empty($entryId)) {
            return false;
        }

        $currentData = $this->getById($entryId);

        if (empty($currentData)) {
            return false;
        }

        $result = $this->_update($this->_validateMultilingualFieldsKeys($entryData), $entryId);

        if ($result) {
            $this->_writeLog(iaCore::ACTION_EDIT, $entryData, $entryId);
            $this->updateCounters($entryId, $entryData, iaCore::ACTION_EDIT, $currentData);

            $this->_iaCore->startHook('phpListingUpdated', [
                'itemId' => $entryId,
                'itemName' => $this->getItemName(),
                'itemData' => $entryData,
                'previousData' => $currentData
            ]);
        }

        return $result;
    }

    protected function _entryDelete($entryId)
    {
        $result = false;

        if ($entryData = $this->getById($entryId)) {
            if ($result = $this->_delete($entryId)) {
                $this->_writeLog(iaCore::ACTION_DELETE, $entryData, $entryId);
                $this->updateCounters($entryId, $entryData, iaCore::ACTION_DELETE);

                $this->_iaField->cleanUpItemFiles($this->getItemName(), $entryData);

                $this->_iaCore->startHook('phpListingRemoved', [
                    'itemId' => $entryId,
                    'itemName' => $this->getItemName(),
                    'itemData' => $entryData
                ]);
            }
        }

        return $result;
    }

    protected function _preSaveEntry(array &$entry, array $data, $action)
    {
        if ($this->_itemName) {
            list($entry, , $this->_messages) = $this->_iaField->parsePost($this->getItemName(), $entry);
        } else {
            $entry = array_merge($entry, $data);
        }

        return empty($this->_messages);
    }

    protected function _postSaveEntry(array &$entry, array $data, $action)
    {
        if ($this->getItemName()) {
            $this->_iaCore->startHook('phpItemSaved', [
                'action' => $action,
                'itemId' => $this->getEntryId(),
                'itemData' => $entry,
                'itemName' => $this->getItemName()
            ]);
        }
    }

    protected function _writeLog($action, array $entryData, $entryId)
    {
        if ($this->_activityLog) {
            $iaLog = $this->_iaCore->factory('log');

            $actionsMap = [
                iaCore::ACTION_ADD => iaLog::ACTION_CREATE,
                iaCore::ACTION_EDIT => iaLog::ACTION_UPDATE,
                iaCore::ACTION_DELETE => iaLog::ACTION_DELETE
            ];

            $titleKey = empty($this->_activityLog['title_field']) ? 'title' : $this->_activityLog['title_field'];
            in_array($titleKey, $this->_iaField->getMultilingualFields($this->getItemName()))
                && $titleKey.= '_' . $this->_iaCore->language['iso'];

            $title = empty($entryData[$titleKey])
                ? $this->_iaDb->one($titleKey, iaDb::convertIds($entryId), self::getTable())
                : $entryData[$titleKey];

            $params = array_merge($this->_activityLog, ['name' => $title, 'id' => $entryId]);

            $iaLog->write($actionsMap[$action], $params);
        }
    }

    public function updateCounters($entryId, array $entryData, $action, $previousData = null)
    {
        // within final class, the counters update routines should be placed here
    }

    protected function _setSystemDefaults(&$entryData)
    {
        if (isset($entryData['featured']) && $entryData['featured']) {
            $entryData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($entryData['featured_end']));
        } else {
            $date = getdate();
            $date = mktime($date['hours'], $date['minutes'] + 1, 0, $date['mon'] + 1, $date['mday'], $date['year']);
            $entryData['featured_end'] = date(iaDb::DATETIME_SHORT_FORMAT, $date);
        }

        if (isset($entryData['sponsored']) && $entryData['sponsored']) {
            $entryData['sponsored_end'] = date(iaDb::DATETIME_SHORT_FORMAT, strtotime($entryData['sponsored_end']));
        }

        if (isset($entryData['member_id'])) {
            $entryData['owner'] = '';
            if ($entryData['member_id'] > 0) {
                $iaUsers = $this->_iaCore->factory('users');
                if ($ownerInfo = $iaUsers->getInfo((int)$entryData['member_id'])) {
                    $entryData['owner'] = $ownerInfo['fullname'] . ' (' . $ownerInfo['email'] . ')';
                }
            }
        }
    }


    protected function _getJsonTree(array $data)
    {
        return $this->getHelper()->getJsonTree($data);
    }

    protected function _validateMultilingualFieldsKeys(array $data)
    {
        if ($multilingualFields = $this->_iaField->getMultilingualFields($this->getItemName())) {
            foreach ($data as $key => $value) {
                if (in_array($key, $multilingualFields)) {
                    $data[$key . '_' . $this->_iaCore->language['iso']] = $value;
                    unset($data[$key]);
                }
            }
        }

        return $data;
    }
}
