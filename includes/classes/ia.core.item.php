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

class iaItem extends abstractCore
{
    const TYPE_PACKAGE = 'package';
    const TYPE_PLUGIN = 'plugin';

    protected static $_table = 'items';
    protected static $_favoritesTable = 'favorites';
    protected static $_modulesTable = 'modules';

    private $_itemTools;

    protected $items;


    public function init()
    {
        parent::init();

        $this->items = $this->iaDb->assoc(['item', 'id', 'module', 'instantiable', 'payable', 'searchable', 'table_name'],
            null, self::getTable());
    }

    public static function getFavoritesTable()
    {
        return self::$_favoritesTable;
    }

    public static function getModulesTable()
    {
        return self::$_modulesTable;
    }

    // compatibility layer helpers
    public static function toPlural($input)
    {
        switch (true) {
            case 'y' == substr($input, -1):
                return substr($input, 0, -1) . 'ies';
            case 's' != substr($input, -1):
                return $input . 's';
            default:
                return $input;
        }
    }

    public static function toSingular($input)
    {
        $ex = ['news'];

        if ('s' == substr($input, -1)
            && !in_array($input, $ex)) {
            $input = substr($input, 0, -1);
            if ('ie' == substr($input, -2)) {
                $input = substr($input, 0, -2) . 'y';
            }
        }

        return $input;
    }
    //

    public function factory($itemName, $type)
    {
        try {
            if (!$itemName) {
                throw new Exception('No item name provided');
            }

            $itemName = self::toSingular($itemName);

            if (!isset($this->items[$itemName])) {
                throw new Exception(sprintf('Item not found (%s)', $itemName));
            }

            $item = $this->items[$itemName];

            if ($item['instantiable']) {
                $result = $this->iaCore->factoryModule($itemName, $item['module'], $type);

                if (!$result) {
                    throw new Exception(sprintf('Unable to instantiate item class (%s)', $itemName));
                }
            } else {
                $result = $this->_instantiateItemModel($item, $type);
            }

            return $result;
        } catch (Exception $e) {
            iaDebug::debug($e->getMessage());

            return false;
        }
    }

    protected function _instantiateItemModel(array $item, $type)
    {
        $itemModel = (iaCore::FRONT == $type)
            ? new itemModelFront()
            : new itemModelAdmin();

        $itemModel->init();
        $itemModel->setParams($item);

        return $itemModel;
    }

    public function getFavoritesByMemberId($memberId)
    {
        $stmt = "`item` IN (':items') && `member_id` = :user";
        $stmt = iaDb::printf($stmt, ['items' => implode("','", $this->getItems()), 'user' => (int)$memberId]);

        $result = [];

        if ($rows = $this->iaDb->all(['item', 'id'], $stmt, null, null, self::getFavoritesTable())) {
            foreach ($rows as $row) {
                $key = $row['item'];
                isset($result[$key]) || $result[$key] = [];

                $result[$key][] = $row['id'];
            }
        }

        return $result;
    }

    /**
    * Returns array with keys of available items and values - module titles
    *
    * @param bool $payableOnly - flag to return items, that can be paid
    *
    * @return array
    */
    public function getModuleItems($payableOnly = false)
    {
        $result = [];

        $itemsInfo = $this->getItemsInfo($payableOnly);
        foreach ($itemsInfo as $itemInfo) {
            $result[$itemInfo['item']] = $itemInfo['module'];
        }

        return $result;
    }

    /**
     * Returns items list
     *
     * @param bool $payableOnly - flag to return items, that can be paid
     *
     * @return array
     */
    public function getItemsInfo($payableOnly = false)
    {
        static $itemsInfo;

        if (!isset($itemsInfo[(int)$payableOnly])) {
            $items = $this->iaDb->all('`item`, `module`, IF(`table_name` != \'\', `table_name`, `item`) `table_name`',
                $payableOnly ? '`payable` = 1' : '', null, null, self::getTable());
            $itemsInfo[(int)$payableOnly] = is_array($items) ? $items : [];
        }

        return $itemsInfo[(int)$payableOnly];
    }

    /**
     * Returns list of items
     *
     * @param bool $payableOnly - flag to return items, that can be paid
     *
     * @return array
     */
    public function getItems($payableOnly = false)
    {
        return array_keys($this->getModuleItems($payableOnly));
    }

    protected function _searchItems($search, $type = 'item')
    {
        $items = $this->getModuleItems();
        $result = [];

        foreach ($items as $item => $module) {
            if ($search == $$type) {
                if ('item' == $type) {
                    return $module;
                } else {
                    $result[] = $item;
                }
            }
        }

        return ($type == 'item') ? false : $result;
    }

    /**
     * Returns list of items by module name
     *
     * @alias _searchItems
     * @param string $moduleName
     *
     * @return array
     */
    public function getItemsByModule($moduleName)
    {
        return $this->_searchItems($moduleName, 'module');
    }

    /**
     * Returns package name by item name
     *
     * @alias _searchItems
     * @param $search
     *
     * @return string|bool
     */
    public function getModuleByItem($search)
    {
        return $this->_searchItems($search, 'item');
    }

    /**
     * Returns item table name
     *
     * @param $itemName string item name
     *
     * @return string
     */
    public function getItemTable($itemName)
    {
        $result = $this->iaDb->one_bind('table_name', '`item` = :item', ['item' => $itemName], self::getTable());
        $result || $result = self::toPlural($itemName);

        return $result;
    }

    /**
     * Returns an array of enabled items for specified module
     *
     * @param $module
     *
     * @return array
     */
    public function getEnabledItemsForPlugin($module)
    {
        $result = [];
        if ($module) {
            $items = $this->iaCore->get($module . '_items_enabled');
            if ($items) {
                $result = explode(',', $items);
            }
        }

        return $result;
    }

    /**
     * Set items for specified module
     *
     * @param string $module module name
     * @param array $items items list
     */
    public function setEnabledItemsForPlugin($module, $items)
    {
        if ($module) {
            $this->iaView->set($module . '_items_enabled', implode(',', $items), true);
        }
    }

    /**
     * Return list of items with favorites field
     *
     * @param array $listings listings to be processed
     * @param $itemName item name
     *
     * @return mixed
     */
    public function updateItemsFavorites($listings, $itemName)
    {
        if (empty($itemName)) {
            return $listings;
        }

        if (!iaUsers::hasIdentity()) {
            if (isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items'])) {
                $itemsFavorites = array_keys($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items']);
            }
        } else {
            $itemsList = [];
            foreach ($listings as $entry) {
                if (
                    (iaUsers::getItemName() == $itemName && $entry['id'] != iaUsers::getIdentity()->id) ||
                    (isset($entry['member_id']) && $entry['member_id'] != iaUsers::getIdentity()->id)
                ) {
                    $itemsList[] = $entry['id'];
                }
            }

            if (empty($itemsList)) {
                return $listings;
            }

            // get favorites
            $itemsFavorites = $this->iaDb->onefield('`id`', "`id` IN ('" . implode("','", $itemsList) . "') && `item` = '{$itemName}' && `member_id` = " . iaUsers::getIdentity()->id, 0, null, $this->getFavoritesTable());
        }

        if (empty($itemsFavorites)) {
            return $listings;
        }

        // process listing and set flag is in favorites array
        foreach ($listings as &$listing) {
            $listing['favorite'] = (int)in_array($listing['id'], $itemsFavorites);
        }

        return $listings;
    }

    /**
     * Verifies if a module is installed
     * @param string $moduleName module name
     * @param null $type module type
     *
     * @return bool
     */
    public function isModuleExist($moduleName, $type = null)
    {
        $stmt = iaDb::printf("`name` = ':name' && `status` = ':status'", [
            'name' => $moduleName,
            'status' => iaCore::STATUS_ACTIVE
        ]);

        if ($type) {
            $stmt .= iaDb::printf(" && `type` = ':type'", ['type' => $type]);
        }

        return (bool)$this->iaDb->exists($stmt, null, self::getModulesTable());
    }

    public function setItemTools($params = null)
    {
        if (is_null($params)) {
            return $this->_itemTools;
        }

        if (isset($params['id']) && $params['id']) {
            $this->_itemTools[$params['id']] = $params;
        } else {
            $this->_itemTools[] = $params;
        }
    }
}
