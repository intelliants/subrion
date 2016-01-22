<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2016 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!iaUsers::hasIdentity())
	{
		return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
	}

	$iaField = $iaCore->factory('field');
	$iaUsers = $iaCore->factory('users');

	$itemName = $tableName = iaUsers::getTable();
	$messages = array();

	$assignableGroups = $iaDb->keyvalue(array('id', 'name'), '`assignable` = 1', iaUsers::getUsergroupsTable());

	$iaPlan = $iaCore->factory('plan');
	$plans = $iaPlan->getPlans($iaUsers->getItemName());

	$iaDb->setTable($tableName);

	if (isset($_POST['change_pass']))
	{
		$error = false;
		$newPassword = empty($_POST['new']) ? false : $_POST['new'];
		// checks for current password
		if (iaUsers::getIdentity()->password != $iaUsers->encodePassword($_POST['current']))
		{
			$error = true;
			$messages[] = iaLanguage::get('password_incorrect');
		}

		if (!$newPassword)
		{
			$error = true;
			$messages[] = iaLanguage::get('password_empty');
		}

		if ($newPassword != $_POST['confirm'])
		{
			$error = true;
			$messages[] = iaLanguage::get('error_password_match');
		}

		if (!$error)
		{
			$iaUsers->changePassword(iaUsers::getIdentity(true), $newPassword, false);

			$error = false;
			$messages[] = iaLanguage::get('password_changed');
		}

		$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
	}
	elseif ($_POST && (isset($_POST['change_info']) || isset($_POST['plan_id'])))
	{
		if (isset($_POST['change_info']))
		{
			$item = array();
			$fields = iaField::getAcoFieldsList(null, $itemName, null, true, iaUsers::getIdentity(true));

			list($item, $error, $messages, $error_fields) = $iaField->parsePost($fields, iaUsers::getIdentity(true));

			if (!$error)
			{
				if (isset($_POST['usergroup_id']) && $assignableGroups && in_array((int)$_POST['usergroup_id'], array_keys($assignableGroups)))
				{
					$item['usergroup_id'] = $_POST['usergroup_id'];
				}

				$iaDb->update($item, iaDb::convertIds(iaUsers::getIdentity()->id));

				if (0 == $iaDb->getErrorNumber())
				{
					$iaCore->startHook('phpUserProfileUpdate', array('userInfo' => iaUsers::getIdentity(true), 'data' => $item));
					iaUsers::reloadIdentity();

					$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
				}
				else
				{
					$iaView->setMessages(iaLanguage::get('db_error'));
				}
			}
			else
			{
				$iaView->setMessages($messages);
			}
		}

		if (isset($_POST['plan_id']) && $_POST['plan_id'] != iaUsers::getIdentity()->sponsored_plan_id)
		{
			if ($plan = $iaPlan->getById((int)$_POST['plan_id']))
			{
				$url = $iaPlan->prePayment($itemName, iaUsers::getIdentity(true), $plan['id'], IA_SELF);
				iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('plan_added'), $url);
			}
			else
			{
				$iaPlan->setUnpaid(iaUsers::getItemName(), iaUsers::getIdentity()->id);
			}
		}
	}

	$iaCore->startHook('phpFrontAfterProfileProcessData');

	$item = iaUsers::getIdentity(true);

	// get fieldgroups
	list($tabs, $fieldgroups) = $iaField->generateTabs($iaField->filterByGroup($item, $itemName));

	// compose tabs
	$sections = array_merge(array('common' => $fieldgroups), $tabs);

	$extraTabs = array();
	$iaCore->startHook('phpFrontEditProfileExtraTabs', array('tabs' => &$extraTabs, 'item' => &$item));
	$sections = array_merge($sections, $extraTabs);

	if (iaUsers::MEMBERSHIP_ADMINISTRATOR != iaUsers::getIdentity()->usergroup_id)
	{
		$iaView->assign('assignableGroups', $assignableGroups);
	}

	$iaView->assign('sections', $sections);
	$iaView->assign('plans_count', (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, null, iaPlan::getTable()));
	$iaView->assign('item', $item);
	$iaView->assign('plans', $plans);

	$iaDb->resetTable();
}