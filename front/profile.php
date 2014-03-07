<?php
//##copyright##

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

	$iaPlan = $iaCore->factory('plan', iaCore::FRONT);
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
			$iaUsers->changePassword(iaUsers::getIdentity()->id, $newPassword);
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
			if ($fields = iaField::getAcoFieldsList(false, $itemName, null, true, iaUsers::getIdentity(true)))
			{
				list($item, $error, $messages, $error_fields) = $iaField->parsePost($fields, iaUsers::getIdentity(true));

				if (!$error)
				{
					$item['id'] = iaUsers::getIdentity()->id;

					$iaDb->update($item);
					$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);

					// update current profile data
					if ($item['id'] == iaUsers::getIdentity()->id)
					{
						$iaUsers->getAuth($item['id']);
					}
				}
				else
				{
					$iaView->setMessages($messages);
				}
			}
		}

		if (!empty($_POST['plan_id']) && $_POST['plan_id'] != iaUsers::getIdentity()->sponsored_plan_id)
		{
			$plan = $iaPlan->getPlanById((int)$_POST['plan_id']);
			if ($plan && $plan['cost'] > 0)
			{
				$url = $iaPlan->prePayment($itemName, iaUsers::getIdentity(true), $plan['id'], false, 0, IA_SELF);
				$iaCore->util()->redirect(iaLanguage::get('thanks'), iaLanguage::get('plan_added'), $url);
			}
		}
	}

	$iaCore->startHook('editProfileProcessData');

	$item = iaUsers::getIdentity(true);

	// get fieldgroups
	list($tabs, $fieldgroups) = $iaField->generateTabs($iaField->filterByGroup($item, $itemName));

	// compose tabs
	$sections = array_merge(array('common' => $fieldgroups), $tabs, array('password' => null, 'member_balance' => null, 'plans' => null));

	$extraTabs = array();
	$iaCore->startHook('editProfileExtraTabs', array('tabs' => &$extraTabs, 'item' => &$item));
	$sections = array_merge($sections, $extraTabs);

	$iaView->assign('sections', $sections);
	$iaView->assign('plans_count', (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, null, iaPlan::getTable()));
	$iaView->assign('item', $item);
	$iaView->assign('plans', $plans);

	$iaDb->resetTable();
}