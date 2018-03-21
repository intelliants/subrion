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

$iaItem = $iaCore->factory('item');
$iaUsers = $iaCore->factory('users');

// get all available items list
$itemsList = $iaItem->getModuleItems();

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_GET['action'])) {
    $output = ['error' => true, 'message' => iaLanguage::get('invalid_parameters')];

    if (isset($_GET['item']) && $_GET['item_id']) {
        $itemName = isset($itemsList[$_GET['item']]) ? $_GET['item'] : $iaUsers->getItemName();
        $itemId = (int)$_GET['item_id'];

        switch ($_GET['action']) {
            case iaCore::ACTION_ADD:

                if ($iaUsers->hasIdentity()) {
                    $iaDb->query(iaDb::printf("INSERT IGNORE `:prefix:table` (`id`, `member_id`, `item`) VALUES (:id, :user, ':item')",
                        ['prefix' => $iaDb->prefix,
                            'table' => $iaItem->getFavoritesTable(),
                            'id' => $itemId,
                            'user' => iaUsers::getIdentity()->id,
                            'item' => $itemName]
                    ));

                    // $output['error'] = !(bool)$iaDb->getAffected();
                } else {
                    // initialize necessary class
                    $itemInstance = (iaCore::CORE != $itemsList[$itemName])
                            ? $iaCore->factoryItem($itemName)
                            : $iaCore->factory($iaUsers->getItemName() == $itemName ? 'users' : $itemName);

                    // get listing information
                    $array = (array)$_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName];
                    if ($listing = $itemInstance->getById($itemId)) {
                        if (!array_key_exists($listing['id'], $array['items'])) {
                            $listing['favorite'] = 1;
                            $array['items'][$listing['id']] = $listing;
                        }
                    }
                    $_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName] = $array;
                }

                $output['error'] = false;
                $output['message'] = iaLanguage::get('favorites_action_added');

                break;

            case iaCore::ACTION_DELETE:

                if ($iaUsers->hasIdentity()) {
                    $iaDb->delete('`id` = :item_id AND `member_id` = :user AND `item` = :item',
                            $iaItem->getFavoritesTable(),
                            ['item_id' => $itemId, 'user' => iaUsers::getIdentity()->id, 'item' => $itemName]
                    );
                } else {
                    unset($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items'][$itemId]);
                }

                $output['error'] = false;
                $output['message'] = iaLanguage::get('favorites_action_deleted');
        }
    }

    $iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $iaField = $iaCore->factory('field');

    $favorites = [];
    $fields = [];

    if ($iaUsers->hasIdentity()) {
        $favorites = $iaItem->getFavoritesByMemberId(iaUsers::getIdentity()->id);

        foreach ($favorites as $itemName => $ids) {
            $fields = ['id'];

            $itemInstance = (iaCore::CORE != $itemsList[$itemName])
                ? $iaCore->factoryItem($itemName)
                : $iaCore->factory(iaUsers::getItemName() == $itemName ? 'users' : $itemName);

            if ($itemInstance && method_exists($itemInstance, iaUsers::METHOD_NAME_GET_FAVORITES)) {
                $favorites[$itemName]['items'] = $itemInstance->{iaUsers::METHOD_NAME_GET_FAVORITES}($ids);
            } else {
                $fields[] = 'member_id';

                $stmt = iaDb::printf("`id` IN (:ids) && `status` = ':status'", ['ids' => implode(',', $ids), 'status' => iaCore::STATUS_ACTIVE]);
                $favorites[$itemName]['items'] = $iaDb->all('*, 1 `favorite`', $stmt, null, null, $iaItem->getItemTable($itemName));
            }
        }
    } elseif (isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY])) {
        $favorites = (array)$_SESSION[iaUsers::SESSION_FAVORITES_KEY];
    }

    foreach ($favorites as $itemName => &$data) {
        if (!empty($data['items'])) {
            $module = iaCore::CORE == $itemsList[$itemName] ? '' : $itemsList[$itemName];

            $favorites[$itemName]['fields'] = $iaField->filter($itemName, $data['items']);
            $favorites[$itemName]['tpl'] = iaCore::CORE == $itemsList[$itemName]
                ? 'search.' . iaItem::toPlural($itemName) . '.tpl'
                : sprintf('module:%s/search.%s.tpl', $module, iaItem::toPlural($itemName));

            $iaCore->startHook('phpFavoritesAfterGetExtraItems', ['favorites' => &$data, 'item' => $itemName]);
        } else {
            unset($favorites[$itemName]);
        }
    }

    $iaView->assign('fields', $fields);
    $iaView->assign('favorites', $favorites);
}
