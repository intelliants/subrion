<?php
//##copyright##

$iaPlan = $iaCore->factory('plan', iaCore::ADMIN);

$iaDb->setTable(iaPlan::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$output = $iaGrid->gridRead($_GET,
				array('item', 'cost', 'days', 'order', 'status')
			);

			if ($output['data'])
			{
				foreach ($output['data'] as &$row)
				{
					$row['title'] = iaLanguage::get('plan_title_' . $row['id']);
					$row['description'] = iaLanguage::get('plan_description_' . $row['id']);
				}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaGrid->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaGrid->gridDelete($_POST, 'plan_deleted');
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaCore::ACTION_ADD == $pageAction || iaCore::ACTION_EDIT == $pageAction)
	{
		iaBreadcrumb::add(iaLanguage::get('plans'), IA_ADMIN_URL . $iaPlan->getModuleUrl());

		$iaCore->factory('field');

		$items = $iaDb->onefield('item', '`payable` = 1', null, null, 'items');

		$fields = array();
		$rows = $iaDb->all(array('name', 'item', 'for_plan', 'required'), ' 1=1 ORDER BY `for_plan` DESC', null, null, iaField::getTable());
		foreach ($rows as $row)
		{
			$type = $row['for_plan'];
			if ($row['required'] == 1)
			{
				$type = 2; // required
			}
			if (!isset($fields[$row['item']]))
			{
				$fields[$row['item']] = array(2 => array(), 1 => array(), 0 => array());
			}

			$fields[$row['item']][$type][] = $row['name'];
		}

		if (isset($_POST['save']))
		{
			$error = false;
			$data = array();
			$messages = array();

			if (empty($_POST['item']))
			{
				$error = true;
				$messages[] = iaLanguage::get('incorrect_item');
			}
			else
			{
				$data['item'] = in_array($_POST['item'], $items) ? $_POST['item'] : false;
				if ($data['item'] == iaUsers::getItemName())
				{
					if (isset($_POST['usergroup']))
					{
						$data['usergroup'] = (int)$_POST['usergroup'];
					}
				}

				if (isset($fields[$data['item']]))
				{
					if (isset($_POST['fields']) && $_POST['fields'])
					{
						$f = $fields[$data['item']];
						$array = array();
						foreach ($_POST['fields'] as $field)
						{
							if (in_array($field, $f[0]))
							{
								$data['data']['fields'][] = $field;
								$array[] = $field;
							}
							elseif (in_array($field, $f[1]))
							{
								$data['data']['fields'][] = $field;
							}
						}
						if ($array)
						{
							$iaDb->update(array('for_plan' => 1), "`name` IN ('" . implode("','", $data['data']['fields']) . "')", null, iaField::getTable());
						}
					}
					else
					{
						$data['data']['fields'] = array();
					}
				}
				$iaCore->startHook('phpAdminAddPlanValidation');

				iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

				$lang = array(
					'title' => $_POST['title'],
					'description' => $_POST['description']
				);

				foreach ($iaCore->languages as $plan_language => $plan_language_title)
				{
					if (isset($lang['title'][$plan_language]))
					{
						if (empty($lang['title'][$plan_language]))
						{
							$error = true;
							$messages[] = iaLanguage::getf('error_lang_title', array('lang' => $plan_language_title));
						}
						elseif (!utf8_is_valid($lang['title'][$plan_language]))
						{
							$block['title'][$plan_language] = utf8_bad_replace($lang['title'][$plan_language]);
						}
					}

					if (isset($lang['description'][$plan_language]))
					{
						if (empty($lang['description'][$plan_language]))
						{
							$error = true;
							$messages[] = iaLanguage::getf('error_lang_description', array('lang' => $plan_language_title));
						}

						if (!utf8_is_valid($lang['description'][$plan_language]))
						{
							$lang['description'][$plan_language] = utf8_bad_replace($lang['description'][$plan_language]);
						}
					}
				}

				$data['days'] = isset($_POST['days']) ? $_POST['days'] : 0;
				if (!is_numeric($data['days']))
				{
					$error = true;
					$messages[] = iaLanguage::get('error_plan_number_days');
				}

				$data['cost'] = isset($_POST['cost']) ? (float)$_POST['cost'] : 0;
				if (empty($data['cost']) && $data['cost'] != 0)
				{
					$error = true;
					$messages[] = iaLanguage::get('error_plan_cost');
				}
				$data['status'] = $_POST['status'];
				$data['email_expire'] = isset($_POST['email_expire']) ? $_POST['email_expire'] : '';

				$iaCore->startHook('phpAdminPlanCommonFieldFilled', array('item' => &$data));

				if (!$error)
				{
					if (isset($data['data']) && is_array($data['data']))
					{
						$data['data'] = serialize($data['data']);
					}
					if ($pageAction == iaCore::ACTION_ADD)
					{
						$result = $iaPlan->insert($data);
						$planId = $result;

						if ($result)
						{
							$messages[] = iaLanguage::get('plan_added');
						}
						else
						{
							$error = true;
							$messages[] = $iaPlan->getMessage();
						}
					}
					elseif ($pageAction == iaCore::ACTION_EDIT)
					{
						$planId = (int)$_GET['id'];
						$result = $iaPlan->update($data, $planId);

						if ($result)
						{
							$messages[] = iaLanguage::get('saved');
						}
						else
						{
							$error = true;
							$messages[] = $iaPlan->getMessage();
						}
					}

					$iaPlan->updatePlanLanguage($planId, $lang);

					$iaView->setMessages($messages, iaView::SUCCESS);

					$baseUrl = IA_ADMIN_URL . $iaPlan->getModuleUrl();

					iaUtil::post_goto(array(
						'add' => $baseUrl . 'add/',
						'list' => $baseUrl,
						'stay' => $baseUrl . 'edit/?id=' . $planId,
					));
				}
			}
			$iaView->setMessages($messages, iaView::ERROR);
		}

		if (iaCore::ACTION_EDIT == $pageAction)
		{
			$planId = empty($_GET['id']) ? false : (int)$_GET['id'];
			$plan = $planId ? $iaPlan->getById($planId) : false;
		}
		else
		{
			$plan = array('usergroup' => 0);
		}

		if (isset($plan['data']))
		{
			$plan['data'] = unserialize($plan['data']);
			$plan['data']['fields'] = array_reverse($plan['data']['fields']);
		}
		else
		{
			$plan['data'] = array();
		}

		$usergroups = $iaDb->keyvalue(array(iaDb::ID_COLUMN_SELECTION, 'title'), $iaDb->convertIds(array(iaUsers::MEMBERSHIP_ADMINISTRATOR, iaUsers::MEMBERSHIP_GUEST), 'id', false), iaUsers::getUsergroupsTable());

		$iaView->assign('usergroups', $usergroups);
		$iaView->assign('plan', $plan);
		$iaView->assign('fields', $fields);
		$iaView->assign('items', $items);

		$iaView->display('plans');
	}
	else
	{
		$iaView->grid('admin/plans');
	}
}

$iaDb->resetTable();