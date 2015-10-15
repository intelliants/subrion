<?php
//##copyright##

if (!$iaCore->get('members_enabled'))
{
	return iaView::errorPage(iaView::ERROR_NOT_FOUND);
}

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($_GET['q']) && $_GET['q'])
	{
		$stmt = '(`username` LIKE :name OR `fullname` LIKE :name) AND `status` = :status ORDER BY `username` ASC';
		$iaDb->bind($stmt, array('name' => $_GET['q'] . '%', 'status' => iaCore::STATUS_ACTIVE));

		$output = $iaDb->onefield('fullname', $stmt, 0, 20, iaUsers::getTable());

		$iaView->assign($output);
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaUsers = $iaCore->factory('users');

	// filter by usergroups
	$usergroups = $iaUsers->getUsergroups(true);
	$iaView->assign('usergroups', $usergroups);

	$activeGroup = '';
	if (isset($_GET['group']))
	{
		$_SESSION['group'] = (int)$_GET['group'];

		if ('all' == $_GET['group'])
		{
			unset($_SESSION['group']);
		}
	}

	if (isset($_SESSION['group']) && in_array($_SESSION['group'], array_keys($usergroups)))
	{
		$activeGroup = $_SESSION['group'];

		$cause = '`usergroup_id` = ' . $activeGroup . ' AND ';
	}
	else
	{
		$cause = '`usergroup_id` IN (' . implode(',', array_keys($usergroups)) . ') AND ';
	}
	$iaView->assign('activeGroup', $activeGroup);


	$filterBy = 'username';

	/* check values */
	if (isset($_GET['account_by']))
	{
		$_SESSION['account_by'] = $_GET['account_by'];
	}
	if (!isset($_SESSION['account_by']))
	{
		$_SESSION['account_by'] = 'username';
	}
	$filterBy = ($_SESSION['account_by'] == 'fullname') ? 'fullname' : 'username';

	$letters['all'] = iaUtil::getLetters();
	$letters['active'] = (isset($iaCore->requestPath[0]) && in_array($iaCore->requestPath[0], $letters['all'])) ? $iaCore->requestPath[0] : false;
	$letters['existing'] = array();

	$iaDb->setTable(iaUsers::getTable());
	if ($array = $iaDb->all('DISTINCT UPPER(SUBSTR(`' . $filterBy . '`, 1, 1)) `letter`', $cause . "`status` = 'active' GROUP BY `username`"))
	{
		foreach ($array as $item)
		{
			$letters['existing'][] = $item['letter'];
		}
	}

	$cause .= $letters['active'] ? ('0-9' == $letters['active'] ? "(`$filterBy` REGEXP '^[0-9]') AND " : "(`$filterBy` LIKE '{$letters['active']}%') AND ") : '';
	if ($letters['active'])
	{
		$iaView->set('subpage', array_search($letters['active'], $letters) + 1);
	}

	// gets current page and defines start position
	$pagination = array(
		'limit' => 20,
		'total' => (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, $cause . "`status` = 'active' "),
		'url' => IA_URL . 'members/' . ($letters['active'] ? $letters['active'] . '/' : '') . '?page={page}'
	);
	$page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
	$start = (max($page, 1) - 1) * $pagination['limit'];

	$membersList = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $cause . "`status` = 'active' ORDER BY `date_reg`", $start, $pagination['limit']);
	$fields = $iaCore->factory('field')->filter($membersList, iaUsers::getTable());

	$iaDb->resetTable();

	// breadcrumb formation
	if ($activeGroup)
	{
		iaBreadcrumb::toEnd(iaLanguage::get('usergroup_' . $usergroups[$activeGroup]), IA_URL . 'members/?group=' . $activeGroup);
	}
	if ($letters['active'])
	{
		iaBreadcrumb::toEnd($letters['active'], IA_SELF);
	}

	if ($membersList)
	{
		$membersList = $iaCore->factory('item')->updateItemsFavorites($membersList, $iaUsers->getItemName());
	}

	$iaView->assign('title', iaLanguage::get('members') . ($letters['active'] ? " [ {$letters['active']} ] " : ''));
	$iaView->assign('filter', $filterBy);
	$iaView->assign('letters', $letters);
	$iaView->assign('members', $membersList);
	$iaView->assign('pagination', $pagination);
	$iaView->assign('fields', $fields);

	$iaView->title(iaLanguage::get('members') . ($letters['active'] ? " [{$letters['active']}] " : ''));
}