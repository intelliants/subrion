<?php
//##copyright##

$iaUsers = $iaCore->factory('users');
$iaUtil = iaCore::util();

$iaDb->setTable(iaUsers::getTable());

$userGroups = $iaUsers->getUsergroups();

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			if ('usergroup' == $_GET['sort'])
			{
				$_GET['sort'] = 'usergroup_id';
			}

			$conditions = array();
			if (isset($_GET['name']) && $_GET['name'])
			{
				$conditions[] = "CONCAT(`username`, `fullname`, `email`) LIKE '%" . iaSanitize::sql($_GET['name']) . "%'";
			}

			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridRead($_GET,
				array('username', 'fullname', 'usergroup_id', 'email', 'status', 'date_reg'),
				array('status' => 'equal', 'usergroup_id' => 'equal', 'id' => 'equal'),
				$conditions
			);

			if ($output['data'])
			{
				$userId = iaUsers::getIdentity()->id;
				foreach ($output['data'] as $key => &$entry)
				{
					$entry['usergroup'] = isset($userGroups[$entry['usergroup_id']]) ? $userGroups[$entry['usergroup_id']] : '';
					$entry['permissions'] = $entry['config'] = $entry['update'] = true;
					$entry['delete'] = ($entry['id'] != $userId);
				}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = array(
				'result' => false,
				'message' => iaLanguage::get('invalid_parameters')
			);

			if (isset($_POST['id']) && is_array($_POST['id']) && count($_POST) > 1)
			{
				$error = false;
				$values = $_POST;

				$ids = $values['id'];
				unset($values['id']);

				$total = count($ids);
				$currentUserId = iaUsers::getIdentity()->id;
				$totalAdminsCount = (int)$iaDb->one_bind(iaDb::STMT_COUNT_ROWS, '`usergroup_id` = :group AND `status` = :status AND `id` != :id', array('group' => iaUsers::MEMBERSHIP_ADMINISTRATOR, 'status' => iaCore::STATUS_ACTIVE, 'id' => $currentUserId));

				if (1 == $total && in_array($currentUserId, $ids))
				{
					if (0 == $totalAdminsCount && isset($values['status']) && $values['status'] != iaCore::STATUS_ACTIVE)
					{
						$error = true;
						$output['message'] = iaLanguage::get('action_not_allowed_since_you_only_admin');
					}
				}

				if (!$error)
				{
					$affected = 0;

					foreach ($ids as $userId)
					{
						if ($userId == $currentUserId)
						{
							if (0 == $totalAdminsCount && isset($values['status']) && $values['status'] != iaCore::STATUS_ACTIVE)
							{
								continue;
							}
						}

						$success = $iaUsers->update($values, '`id` = ' . $userId);
						empty($success) || $affected++;

						if ($userId == $currentUserId && $success)
						{
							$iaUsers->getAuth($userId);
						}
					}

					if (1 == $total)
					{
						$output['result'] = ($affected == $total);
						$output['message'] = iaLanguage::get($output['result'] ? 'saved' : 'db_error');
					}
					else
					{
						$output['result'] = true;
						$output['message'] = ($affected == $total)
							? iaLanguage::get('saved')
							: iaLanguage::getf('items_updated_of', array('num' => $affected, 'total' => $total));
					}
				}
			}

			break;

		case iaCore::ACTION_DELETE:
			$output = array(
				'result' => false,
				'message' => iaLanguage::get('invalid_parameters')
			);

			if (isset($_POST['id']) && is_array($_POST['id']) && $_POST['id'])
			{
				$affected = 0;
				$currentUserId = (int)iaUsers::getIdentity()->id;

				foreach ($_POST['id'] as $id)
				{
					$stmt = '`id` = :id AND `id` != :admin';
					$iaDb->bind($stmt, array('id' => (int)$id, 'admin' => $currentUserId));

					if ($iaUsers->delete($stmt))
					{
						$affected++;
					}
				}

				$total = count($_POST['id']);
				if (1 == $total)
				{
					$output['result'] = ($affected == $total);
					$output['message'] = iaLanguage::get($output['result'] ? 'member_deleted' : 'db_error');
				}
				else
				{
					$output['result'] = (bool)$affected;
					if ($output['result'])
					{
						$output['message'] = ($affected == $total)
							? iaLanguage::get('items_deleted')
							: iaLanguage::getf('items_deleted_of', array('num' => $affected, 'total' => $total));
					}
					else
					{
						$output['message'] = iaLanguage::get('db_error');
					}
				}
			}
	}

	$iaView->assign($output);

/*
	if (isset($_GET['a']))
	{
		$data = array();
		$a = $_GET['a'];
		$ids = isset($_GET['ids']) ? explode('-', $_GET['ids']) : array();
		$letters = iaUtil::getLetters();

		foreach ($letters as $key => $c)
		{
			if ($a == 'subpages')
			{
				$item = array(
					'id' => $key + 1,
					'text' => '&nbsp;' . $c,
					'cls' => 'folder',
					'leaf' => true,
					'checked' => (in_array($key + 1, $ids) ? true : false),
				);
				$data[] = $item;
			}
		}
		$iaView->assign($data);
	}
*/
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaField = $iaCore->factory('field');

	if ((!isset($_GET['id']) || (int)$_GET['id'] == 0) && iaCore::ACTION_EDIT == $pageAction)
	{
		iaUtil::go_to(IA_ADMIN_URL . 'members/add/');
	}

	$error = false;
	$messages = array();

	if (isset($_POST['save']))
	{
		$iaCore->startHook('adminAddMemberValidation');

		if (!defined('IA_NOUTF'))
		{
			iaUTF8::loadUTF8Core();
			iaUTF8::loadUTF8Util('ascii', 'validation', 'bad', 'utf8_to_ascii');
		}

		if (iaCore::ACTION_EDIT == $pageAction)
		{
			$member = $iaUsers->getInfo((int)$_GET['id']);
		}
		else
		{
			$member = array('sponsored' => false, 'featured' => false);
		}

		$fields = iaField::getAcoFieldsList(iaCore::ADMIN, $iaUsers->getItemName());

		// this is a hack to force the script to upload files to the appropriate folder
		// FIXME
		$activeUser = iaUsers::getIdentity(true);
		$_SESSION[iaUsers::SESSION_KEY] = array(
			'id' => isset($_GET['id']) ? (int)$_GET['id'] : 0,
			'username' => $_POST['username']
		);
		list($member, $error, $messages, ) = $iaField->parsePost($fields, $member, true);
		$_SESSION[iaUsers::SESSION_KEY] = $activeUser;
		//

		if (isset($_POST['status']))
		{
			$member['status'] = $_POST['status'];
		}

		$stmt = '`email` = :email';

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			if (isset($member['status']))
			{
				if ($iaDb->one_bind('status', '`id` = :id', array('id' => (int)$_GET['id'])) == $member['status'])
				{
					unset($member['status']);
				}
			}

			$stmt .= ' AND `id` != ' . (int)$_GET['id'];
		}

		if ($iaDb->exists($stmt, array('email' => $member['email'])))
		{
			$error = true;
			$messages[] = iaLanguage::get('error_duplicate_email');
		}

		if ($iaAcl->checkAccess($permission . 'password') || iaCore::ACTION_ADD == $pageAction)
		{
			$_POST['_password'] = trim($_POST['_password']);
			if ($_POST['_password'] || !empty($_POST['_password2']))
			{
				$member['password'] = $iaUsers->encodePassword($_POST['_password']);

				if (empty($member['password']))
				{
					$error = true;
					$messages[] = iaLanguage::get('error_password_empty');
				}
				elseif (!utf8_is_ascii($member['password']))
				{
					$error = true;
					$messages[] = 'Password: ' . iaLanguage::get('ascii_required');
				}
				elseif ($member['password'] != $iaUsers->encodePassword($_POST['_password2']))
				{
					$error = true;
					$messages[] = iaLanguage::get('error_password_match');
				}
			}
		}

		if (empty($_POST['_password']) && $pageAction == iaCore::ACTION_ADD)
		{
			$error = true;
			$messages[] = iaLanguage::get('error_password_empty');
		}

		if ($iaAcl->checkAccess($permission . 'usergroup'))
		{
			if (isset($_POST['usergroup_id']))
			{
				$member['usergroup_id'] = isset($userGroups[$_POST['usergroup_id']]) ? $_POST['usergroup_id'] : iaUsers::MEMBERSHIP_REGULAR;
			}
		}
		elseif ($pageAction == iaCore::ACTION_ADD)
		{
			$member['usergroup_id'] = iaUsers::MEMBERSHIP_REGULAR;
		}

		if (!$error)
		{
			if (iaCore::ACTION_EDIT == $pageAction)
			{
				$id = (int)$_POST['id'];
				$error = !$iaUsers->update($member, iaDb::convertIds($id), array('date_update' => iaDb::FUNCTION_NOW));

				$messages[] = iaLanguage::get($error ? 'db_error' : 'saved');

				if ($id == iaUsers::getIdentity()->id && !$error) // update current profile data
				{
					$iaUsers->getAuth($id);
				}

				$error || $iaCore->factory('log')->write(iaLog::ACTION_UPDATE, array('item' => 'member', 'name' => $member['fullname'], 'id' => $id));
			}
			else
			{
				if ($id = $iaUsers->insert($member))
				{
					$messages[] = (iaUsers::MEMBERSHIP_ADMINISTRATOR == $member['usergroup_id'])
						? iaLanguage::get('administrator_added')
						: iaLanguage::get('member_added');

					$action = 'member_registration';
					if ($iaCore->get($action) && $member['email'])
					{
						$iaMailer = $iaCore->factory('mailer');

						$iaMailer->load_template($action . '_admin');

						$iaMailer->ClearAddresses();
						$iaMailer->AddAddress($member['email']);
						$iaMailer->Body = str_replace('{%FULLNAME%}', $member['fullname'], $iaMailer->Body);
						$iaMailer->replace['{%USERNAME%}'] = $member['username'];
						$iaMailer->replace['{%PASSWORD%}'] = $_POST['_password'];
						$iaMailer->replace['{%EMAIL%}'] = $member['email'];

						$iaMailer->Send();
					}

					$iaCore->factory('log')->write(iaLog::ACTION_CREATE, array('item' => 'member', 'name' => $member['fullname'], 'id' => $id));
				}
				else
				{
					$error = true;
					$messages[] = iaLanguage::get('member_already_exist');
				}
			}

			$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

			if (!$error)
			{
				$url = IA_ADMIN_URL . 'members/';
				iaUtil::post_goto(array(
					'add' => $url . 'add/',
					'list' => $url,
					'stay' => $url . 'edit/?id=' . $id,
				));
			}
		}
		else
		{
			$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
		}
	}

	if (iaCore::ACTION_ADD == $pageAction || iaCore::ACTION_EDIT == $pageAction)
	{
		iaBreadcrumb::preEnd(iaLanguage::get('members'), 'members/');

		unset($userGroups[iaUsers::MEMBERSHIP_GUEST]);
		$iaView->assign('usergroups', $userGroups);
	}

	switch ($pageAction)
	{
		case iaCore::ACTION_ADD:
		case iaCore::ACTION_EDIT:
			if (iaCore::ACTION_EDIT == $pageAction)
			{
				$adminsCount = $iaDb->one_bind(iaDb::STMT_COUNT_ROWS, '`usergroup_id` = :group AND `status` = :status', array('group' => iaUsers::MEMBERSHIP_ADMINISTRATOR, 'status' => iaCore::STATUS_ACTIVE));
				$iaView->assign('admin_count', $adminsCount);

				$member = $error
					? $_POST
					: $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($_GET['id']));

				if (1 == $adminsCount)
				{
					unset($member['status']);
				}

				$iaView->assign('id', (int)$_GET['id']);
			}
			elseif (iaCore::ACTION_ADD == $pageAction)
			{
				$member = $_POST;

				$member['usergroup_id'] = isset($_POST['usergroup_id']) ? (int)$_POST['usergroup_id'] : iaUsers::MEMBERSHIP_REGULAR;
				$member['sponsored'] = false;
				$member['featured'] = false;
				$member['status'] = iaCore::STATUS_ACTIVE;
			}

			$iaCore->startHook('editItemSetSystemDefaults', array('item' => &$member));

			$sections = $iaField->filterByGroup($member, $iaUsers->getItemName(), array('page' => iaCore::ADMIN, 'selection' => "f.*, IF(f.`name` = 'avatar', 4, `order`) `order`", 'order' => '`order`'));

			$iaPlans = $iaCore->factory('plan', iaCore::FRONT);
			$plans = $iaPlans->getPlans($iaUsers->getItemName());

			$iaView->assign('item', $member);
			$iaView->assign('sections', $sections);
			$iaView->assign('statuses', array(iaCore::STATUS_ACTIVE, iaCore::STATUS_APPROVAL, iaUsers::STATUS_SUSPENDED, iaUsers::STATUS_UNCONFIRMED));
			$iaView->assign('plans', $plans);

			$iaView->display('members');

			break;

		case iaCore::ACTION_READ:
			$iaView->grid('admin/members');
	}
}

$iaDb->resetTable();