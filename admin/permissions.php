<?php
//##copyright##

$target = 'all';
$user = 0;
$id = 0;
$group = 0;
$actionType = 2;

$objects = $iaAcl->getObjects();

$iaDb->setTable('acl_privileges');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($_POST['action']) || isset($_GET['action']))
	{
		$_action = isset($_POST['action']) ? $_POST['action'] : $_GET['action'];
		switch ($_action)
		{
			case 'save':
				$actionsList = $_POST['acts'];
				$objectName = $_POST['obj'];
				$objectId = 0;
				$type = in_array($_POST['type'], array('admin_pages', 'pages', 'all')) ? $_POST['type'] : 'all';
				// clear permissions
				if ($type != 'all')
				{
					$objectId = $objectName;
					$objectName = $type;
					$iaDb->delete("`object` = '$objectName-$objectId' AND `object_id` = '0' AND `type` = '$target' AND `type_id` = '$id'");
				}
				$iaDb->delete("`object` = '$objectName' AND `object_id` = '$objectId' AND `type` = '$target' AND `type_id` = '$id'");
				if (($_POST['modified'] == 'true' || $_POST['modified'] === true)
					&& is_array($actionsList))
				{
					foreach ($actionsList as $action => $access)
					{
						$entry = array(
							'object' => $objectName,
							'object_id' => 0,
							'type' => $target,
							'type_id' => $id,
							'action' => $action,
							'access' => $access
						);

						if (isset($objects[$objectName . iaAcl::DELIMITER . $action])
							&& isset($objects[$objectName . '-' . $objectId . iaAcl::DELIMITER . $action]))
						{
							$entry['object_id'] = $objectId;
						}
						else
						{
							$entry['object'] .= '-' . $objectId;
						}

						$iaDb->insert($entry);
					}
				}
				$iaView->assign('msg', iaLanguage::get('_saved') . '. Time: ' . date('H:i:s'));
				$iaView->assign('type', iaView::SUCCESS);
				break;

			case 'search':
				$search = isset($_GET['text']) ? $_GET['text'] : '';
				$type = isset($_GET['type']) && in_array($_GET['type'], array(iaAcl::USER, iaAcl::GROUP, iaAcl::PLAN)) ? $_GET['type'] : iaAcl::USER;
				$all = array();
				switch($type)
				{
					case 'user':
						$all = $iaDb->all('SQL_CALC_FOUND_ROWS `id`, `username` `title`', "(`username` LIKE '%{$search}%' OR `fullname` LIKE '%{$search}%' OR `id` LIKE '%{$search}%')", 0, 10, iaUsers::getTable());
						break;
					case 'group':
						$all = $iaDb->all('SQL_CALC_FOUND_ROWS `id`, `title`', "(`title` LIKE '%{$search}%' OR `id` LIKE '%{$search}%')", 0, 10, 'usergroups');
						break;
					case 'plan':
						$rows = $iaDb->all('SQL_CALC_FOUND_ROWS `key`, `value`', "(`value` LIKE '%{$search}%' AND `key` LIKE 'plan_title_%' OR `key` LIKE 'plan_title_{$search}%')", 0, 10, iaLanguage::getTable());
						foreach ($rows as $row)
						{
							$all[] = array(
								'title' => $row['value'],
								'id' => str_replace('plan_title_', '', $row['key'])
							);
						}
				}
				$iaView->assign('count', $iaDb->foundRows);
				$iaView->assign('list', $all);
		}
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (isset($_GET['user']))
	{
		$target = 'user';
		$actionType = 0;
		$user = $id = isset($_GET[$target]) ? (int)$_GET[$target] : 0;
		$item = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`id` = :id', array('id' => $id), iaUsers::getTable());
		if ($item)
		{
			$group = $item['usergroup_id'];
		}
		iaBreadcrumb::add(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
		$iaView->title(iaLanguage::getf('permissions_members', array('member' => '"' . $item['username'] . '"')));
	}
	elseif (isset($_GET['group']))
	{
		$target = 'group';
		$actionType = 1;
		$group = $id = isset($_GET[$target]) ? (int)$_GET[$target] : 0;
		$item = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id), iaUsers::getUsergroupsTable());

		iaBreadcrumb::add(iaLanguage::get('usergroups'), IA_ADMIN_URL . 'usergroups/');

		$iaView->title(iaLanguage::getf('permissions_usergroups', array('usergroup' => '"' . $item['title'] . '"')));
	}
	if (!isset($item) || empty($item))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$userPermissions = $iaAcl->getPermissions($id, $group);
	$actions = $iaAcl->getActions();
	$groups = array(
		'all' => array(),
		'pages' => array(),
		'admin_pages' => array()
	);
	$custom = array(
		'user' => $user,
		'group' => $group,
		'perms' => $userPermissions
	);

	$pageTypes = array('admin_pages', 'pages');

	foreach ($pageTypes as $i => $pageType)
	{
		$fieldsList = array('name', 'action', 'group', 'parent');
		if (0 == $i) $fieldsList[] = 'title';
		$pages = $iaDb->all($fieldsList, '`' . (0 == $i ? 'readonly' : 'service') . '` = 0 ORDER BY `parent` DESC, `id`', null, null, $pageType);

		foreach ($pages as $page)
		{
			if ($page['parent'])
			{
				$key = $pageType . '-' . $page['parent'];
				isset($actions[$key]) || $actions[$key] = array();
				in_array($page['action'], $actions[$key]) || $actions[$key][] = $page['action'];
			}
			else
			{
				$list = array();
				$info = array();
				$modified = false;
				$key = $pageType . '-' . $page['name'];

				if ($page['group'] == 0)
				{
					$page['group'] = 1;
				}

				if (!isset($groups[$pageType][$page['group']]))
				{
					$groups[$pageType][$page['group']] = array();
				}
				if (!isset($page['title']))
				{
					$page['title'] = iaLanguage::get('page_title_' . $page['name'], ucfirst($page['name']));
				}

				foreach (array(iaCore::ACTION_READ, iaCore::ACTION_ADD, iaCore::ACTION_EDIT, iaCore::ACTION_DELETE) as $action)
				{
					$actionCode = $key . iaAcl::DELIMITER . $action;
					if (isset($objects[$actionCode]) || $action == iaCore::ACTION_READ)
					{
						isset($actions[$key]) || $actions[$key] = array();
						in_array($action, $actions[$key]) || array_unshift($actions[$key], $action);
					}
				}

				foreach ($actions[$key] as $action)
				{
					$objectId = null;
					$actionCode = $key . iaAcl::DELIMITER . $action;
					$title = iaLanguage::get($actionCode, iaLanguage::getf('action-' . $action, array('page' => $page['title'])));
					$param = $key . iaAcl::SEPARATOR . $action;

					if (isset($objects[$pageType . '-' . $page['name'] . iaAcl::DELIMITER . $action])
						&& in_array($action, array(iaCore::ACTION_READ, iaCore::ACTION_ADD, iaCore::ACTION_EDIT, iaCore::ACTION_DELETE)))
					{
						$objectId = $page['name'];
						$param = $pageType . iaAcl::SEPARATOR . $action;
					}

					$list[$action] = array(
						'title' => $title,
						'default' => (int)$iaAcl->checkAccess($param, 0, 0, $objectId, true),
						'access' => (int)$iaAcl->checkAccess($param, 0, 0, $objectId, $custom),
						'custom' => isset($userPermissions[$iaAcl->encodeAction($pageType, $action, $page['name'])][$actionType]) // check user privileges
					);
					$info[$action] = array(
						'title' => $title,
						'classname' => $list[$action]['access'] ? 'act-true' : 'act-false'
					);

					if ($list[$action]['custom'])
					{
						$modified = true;
					}
					if (isset($objects[$actionCode]))
					{
						unset($objects[$actionCode]);
					}
				}

				$groups[$pageType][$page['group']][$page['name']] = array(
					'title' => $page['title'],
					'modified' => $modified,
					'list' => $list,
					'info' => $info
				);
			}
		}
	}

	foreach ($objects as $actionCode => $access)
	{
		list($object, ) = explode('--', $actionCode);
		$list = array();
		$info = array();
		$modified = false;

		foreach (array(iaCore::ACTION_READ, iaCore::ACTION_ADD, iaCore::ACTION_EDIT, iaCore::ACTION_DELETE) as $action)
		{
			$actionCode = $object . iaAcl::DELIMITER . $action;

			if (isset($objects[$actionCode]) || $action == iaCore::ACTION_READ)
			{
				isset($actions[$object]) || $actions[$object] = array();
				in_array($action, $actions[$object]) || array_unshift($actions[$object], $action);
			}
		}

		foreach ($actions[$object] as $action)
		{
			$actionCode = $object . iaAcl::DELIMITER . $action;
			$defaultAccess = in_array($action, array(iaCore::ACTION_READ, iaCore::ACTION_ADD, iaCore::ACTION_EDIT, iaCore::ACTION_DELETE))
				? $access
				: (int)$iaAcl->checkAccess($object . ':' . $action, 0, 0, null, true);

			$title = iaLanguage::get($actionCode, $actionCode);
			$list[$action] = array(
				'title' => $title,
				'default' => $defaultAccess,
				'access' => (int)$iaAcl->checkAccess($object . ':' . $action, 0, 0, null, $custom),
				'custom' => isset($userPermissions[$iaAcl->encodeAction($object, $action)][$actionType]) // check user privileges
			);
			$info[$action] = array(
				'title' => $title,
				'classname' => $list[$action]['access'] ? 'act-true' : 'act-false'
			);
			if ($list[$action]['custom'])
			{
				$modified = true;
			}
			if (isset($objects[$actionCode]))
			{
				unset($objects[$actionCode]);
			}
		}
		$groups['all'][0][$object] = array(
			'title' => iaLanguage::get($object, $object),
			'modified' => $modified,
			'list' => $list,
			'info' => $info
		);
	}
	ksort($groups['admin_pages']);
	ksort($groups['pages']);

	$iaView->assign('admin_login', $groups['all'][0]['admin_login']['list']['read']);

	unset($groups['all']); // FIXME:

	$iaView->assign('titles', $iaDb->keyvalue(array('id', 'title'), null, 'admin_pages_groups'));
	$iaView->assign('groups', $groups);
	$iaView->assign('page_types', $pageTypes);

	$iaView->display('permissions');
}

$iaDb->resetTable();