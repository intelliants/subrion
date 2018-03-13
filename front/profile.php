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

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (!iaUsers::hasIdentity()) {
        return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
    }

    $iaField = $iaCore->factory('field');
    $iaPlan = $iaCore->factory('plan');
    $iaUsers = $iaCore->factory('users');

    $itemName = $iaUsers->getitemName();
    $tableName = iaUsers::getTable();

    $messages = [];

    $assignableGroups = $iaDb->keyvalue(['id', 'name'], '`assignable` = 1', iaUsers::getUsergroupsTable());

    $plans = $iaPlan->getPlans($iaUsers->getItemName());

    $iaDb->setTable($tableName);

    $item = iaUsers::getIdentity(true);

    if (isset($_POST['change_pass'])) {
        $error = false;
        $newPassword = empty($_POST['new']) ? false : $_POST['new'];
        // checks for current password
        if (!password_verify($_POST['current'], iaUsers::getIdentity()->password)) {
            $error = true;
            $messages[] = iaLanguage::get('password_incorrect');
        }

        if (!$newPassword) {
            $error = true;
            $messages[] = iaLanguage::get('password_empty');
        }

        if ($newPassword != $_POST['confirm']) {
            $error = true;
            $messages[] = iaLanguage::get('error_password_match');
        }

        if (!$error) {
            $iaUsers->changePassword(iaUsers::getIdentity(true), $newPassword, false);

            $error = false;
            $messages[] = iaLanguage::get('password_changed');
        }

        $iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
    } elseif ($_POST && (isset($_POST['change_info']) || isset($_POST['plan_id']))) {
        if (isset($_POST['change_info'])) {
            list($item, $error, $messages) = $iaField->parsePost($iaUsers->getItemName(), $item);

            if (!$error) {
                if (isset($_POST['usergroup_id']) && $assignableGroups && in_array((int)$_POST['usergroup_id'], array_keys($assignableGroups))) {
                    $item['usergroup_id'] = $_POST['usergroup_id'];
                }

                $iaDb->update($item, iaDb::convertIds(iaUsers::getIdentity()->id));

                if (0 == $iaDb->getErrorNumber()) {
                    $iaCore->startHook('phpUserProfileUpdate', ['userInfo' => iaUsers::getIdentity(true), 'data' => $item]);
                    iaUsers::reloadIdentity();

                    $iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
                } else {
                    $iaView->setMessages(iaLanguage::get('db_error'));
                }
            } else {
                $iaView->setMessages($messages);
            }
        }

        if (isset($_POST['plan_id']) && $_POST['plan_id'] != iaUsers::getIdentity()->sponsored_plan_id) {
            if ($plan = $iaPlan->getById((int)$_POST['plan_id'])) {
                if ((float)$plan['cost']) {
                    $url = $iaPlan->prePayment($itemName, iaUsers::getIdentity(true), $plan['id'], IA_SELF);
                    iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('plan_added'), $url);
                } else {
                    $iaPlan->setPaid(['item' => $itemName, 'plan_id' => $plan['id'],
                        'item_id' => iaUsers::getIdentity()->id, 'member_id' => iaUsers::getIdentity()->id]);
                }
            } else {
                $iaPlan->setUnpaid(iaUsers::getItemName(), iaUsers::getIdentity()->id);
            }
        }
    }

    $iaCore->startHook('phpFrontAfterProfileProcessData');

    $sections = $iaField->getTabs($itemName, $item);

    foreach ($sections['common'][0]['fields'] as &$field) {
        if ('email_language' == $field['name']) {
            $field['type'] = iaField::RADIO;
            $field['default'] = iaLanguage::getMasterLanguage()->iso;
            $field['values'] = [];
            foreach ($iaCore->languages as $iso => $language) {
                $field['values'][$iso] = $language['title'];
            }
            break;
        }
    }


    $extraTabs = [];
    $iaCore->startHook('phpFrontEditProfileExtraTabs', ['tabs' => &$extraTabs, 'item' => &$item]);
    $sections = array_merge($sections, $extraTabs);

    if (iaUsers::MEMBERSHIP_ADMINISTRATOR != iaUsers::getIdentity()->usergroup_id) {
        $iaView->assign('assignableGroups', $assignableGroups);
    }

    $item['item'] = $itemName;

    $iaView->assign('sections', $sections);
    $iaView->assign('plans_count', (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, null, iaPlan::getTable()));
    $iaView->assign('item', $item);
    $iaView->assign('plans', $plans);

    $iaDb->resetTable();
}
