<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	// display 404 if members are disabled
	if (!$iaCore->get('members_enabled'))
	{
		iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaUsers = $iaCore->factory('users');

	if (isset($_GET['account_by']))
	{
		$_SESSION['account_by'] = $_GET['account_by'];
	}
	if (!isset($_SESSION['account_by']))
	{
		$_SESSION['account_by'] = 'username';
	}

	$filterBy = ($_SESSION['account_by'] == 'fullname') ? 'fullname' : 'username';
	$member = $iaUsers->getInfo($iaCore->requestPath[0], 'username');
	if (empty($member))
	{
		$member = $iaUsers->getInfo((int)$iaCore->requestPath[0]);
	}
	if (empty($member))
	{
		iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	iaCore::util();

	$member['item'] = $iaUsers->getItemName();

	$iaCore->startHook('phpViewListingBeforeStart', array(
		'listing' => $member['id'],
		'item' => $member['item'],
		'title' => $member['fullname'],
		'url' => $iaCore->iaSmarty->ia_url(array(
			'data' => $member,
			'item' => $member['item'],
			'type' => 'url'
		)),
		'desc' => $member['fullname']
	));

	$iaItem = $iaCore->factory('item');
	$iaCore->set('num_items_perpage', 20);

	$page = !empty($_GET['page']) ? (int)$_GET['page'] : 1;
	$page = ($page < 1) ? 1 : $page;
	$start = ($page - 1) * $iaCore->get('num_items_perpage');

	// get all items added by this account
	$itemsList = $iaItem->getPackageItems();
	$array = $iaItem->getItemsInfo(true);
	$itemsFlat = array();

	if ($array)
	{
		foreach ($array as $itemData)
		{
			if ($itemData['item'] != $member['item'] && ($iaItem->isExtrasExist($itemsList[$itemData['item']])))
			{
				$itemsFlat[] = $itemData['item'];
			}
		}
	}

	if (iaUsers::hasIdentity() && iaUsers::getIdentity()->id == $member['id'])
	{
		$iaItem->setItemTools(array(
			'title' => iaLanguage::get('edit'),
			'url' => IA_URL . 'profile/'
		));
	}

	$member = array_shift($iaItem->updateItemsFavorites(array($member), $member['item']));
	$member['items'] = array();

	// get fieldgroups
	$iaField = $iaCore->factory('field');
	list($tabs, $fieldgroups) = $iaField->generateTabs($iaField->filterByGroup($member, $member['item']));

	// compose tabs
	$sections = array_merge(array('common' => $fieldgroups), $tabs);

	if (count($itemsFlat) > 0)
	{
		$limit = $iaCore->get('num_items_perpage');
		foreach ($itemsFlat as $item)
		{
			$class = $iaCore->factoryPackage('item', $itemsList[$item], iaCore::FRONT, $item);

			if ($class && method_exists($class, iaUsers::TAB_FILLER_METHOD))
			{
				$return = $class->{iaUsers::TAB_FILLER_METHOD}(null, $start, $limit, $member['id']);

				if ($return['items'])
				{
					// add tab in case items exist
					$sections[$item] = array();

					$return['items'] = $iaItem->updateItemsFavorites($return['items'], $item);
				}

				$member['items'][$item] = $return;
				$member['items'][$item]['fields'] = $iaField->filter($member['items'][$item]['items'], $item);
			}
		}
	}

	$iaView->assign('sections', $sections);
	$iaView->assign('item', $member);

	$alpha = substr($member[$filterBy], 0, 1);
	if (empty($alpha) || $alpha === false)
	{
		$alpha = substr($member['username'], 0, 1);
	}
	$alpha = strtoupper($alpha);
	$iaView->set('subpage', $alpha);

	$iaView->assign('url', IA_URL . 'members/' . iaSanitize::alias($member['username']) . '.html');

	iaBreadcrumb::preEnd($alpha, IA_URL . 'members' . IA_URL_DELIMITER . $alpha . IA_URL_DELIMITER);

	$iaView->title($iaView->title() . ' - ' . (empty($member['fullname']) ? $member['username'] : $member['fullname']));

	$iaView->set('subpage', array_search($alpha, iaUtil::getLetters()));

	$iaView->display('view-member');
}