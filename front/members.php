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
	$cause = $letters['active'] ? ('0-9' == $letters['active'] ? "(`$filterBy` REGEXP '^[0-9]') AND " : "(`$filterBy` LIKE '{$letters['active']}%') AND ") : '';
	if ($letters['active'])
	{
		$iaView->set('subpage', array_search($letters['active'], $letters) + 1);
	}

	$iaDb->setTable(iaUsers::getTable());

	/* gets current page and defines start position */
	$pagination = array(
		'limit' => 20,
		'total' => (int)$iaDb->one(iaDb::STMT_COUNT_ROWS, $cause . "`status` = 'active' "),
		'url' => IA_URL . 'members/' . ($letters['active'] ? $letters['active'] . '/' : '') . '?page={page}'
	);
	$page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
	$start = (max($page, 1) - 1) * $pagination['limit'];

	$membersList = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, $cause . "`status` = 'active' ORDER BY `date_reg`", $start, $pagination['limit']);
	$fields = $iaCore->factory('field')->filter($membersList, iaUsers::getTable());

	$letters['existing'] = array();
	$array = $iaDb->all('DISTINCT UPPER(SUBSTR(`' . $filterBy . '`, 1, 1)) `letter`', "`status` = 'active' GROUP BY `username`");
	$iaDb->resetTable();
	if ($array)
	{
		foreach ($array as $item)
		{
			$letters['existing'][] = $item['letter'];
		}
	}
	// breadcrumb formation
	if ($letters['active'])
	{
		iaBreadcrumb::toEnd($letters['active'], IA_SELF);
	}

	$iaUsers = $iaCore->factory('users');

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