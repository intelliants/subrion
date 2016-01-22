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
	// display 404 if members are disabled
	if (!$iaCore->get('members_enabled'))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
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
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaCore->factory('util');
	$iaPage = $iaCore->factory('page', iaCore::FRONT);

	$member['item'] = $iaUsers->getItemName();

	$iaCore->startHook('phpViewListingBeforeStart', array(
		'listing' => $member['id'],
		'item' => $member['item'],
		'title' => $member['fullname'],
		'url' => $iaView->iaSmarty->ia_url(array(
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

	if (iaUsers::hasIdentity() && iaUsers::getIdentity()->id == $member['id'])
	{
		$iaItem->setItemTools(array(
			'id' => 'action-edit',
			'title' => iaLanguage::get('edit'),
			'attributes' => array(
				'href' => $iaPage->getUrlByName('profile'),
			)
		));
	}

	$member = array_shift($iaItem->updateItemsFavorites(array($member), $member['item']));
	$member['items'] = array();

	// get fieldgroups
	$iaField = $iaCore->factory('field');
	list($tabs, $fieldgroups) = $iaField->generateTabs($iaField->filterByGroup($member, $iaUsers->getItemName()));

	// compose tabs
	$sections = array_merge(array('common' => $fieldgroups), $tabs);

	// get all items added by this account
	$itemsList = $iaItem->getPackageItems();
	$itemsFlat = array();

	if ($array = $iaItem->getItemsInfo(true))
	{
		foreach ($array as $itemData)
		{
			if ($itemData['item'] != $member['item'] && ($iaItem->isExtrasExist($itemsList[$itemData['item']])))
			{
				$itemsFlat[] = $itemData['item'];
			}
		}
	}

	if (count($itemsFlat) > 0)
	{
		$limit = $iaCore->get('num_items_perpage');
		foreach ($itemsFlat as $itemName)
		{
			if ($class = $iaCore->factoryPackage('item', $itemsList[$itemName], iaCore::FRONT, $itemName))
			{
				$result = method_exists($class, iaUsers::METHOD_NAME_GET_LISTINGS)
					? $class->{iaUsers::METHOD_NAME_GET_LISTINGS}($member['id'], $start, $limit)
					: null;

				if (!is_null($result))
				{
					if ($result['items'])
					{
						$result['items'] = $iaItem->updateItemsFavorites($result['items'], $itemName);
					}

					$member['items'][$itemName] = $result;
					$member['items'][$itemName]['package'] = isset($itemsList[$itemName]) ? $itemsList[$itemName] : '';
					$member['items'][$itemName]['fields'] = $iaField->filter($member['items'][$itemName]['items'], $itemName);
				}
			}
		}
	}

	$iaUsers->incrementViewsCounter($member['id']);

	$alpha = substr($member[$filterBy], 0, 1);
	$alpha || $alpha = substr($member['username'], 0, 1);
	$alpha = strtoupper($alpha);

	$iaView->set('subpage', $alpha);

	iaBreadcrumb::preEnd($alpha, $iaPage->getUrlByName('members') . $alpha . IA_URL_DELIMITER);

	// TODO: custom http validation
	if (isset($member['website']) && $member['website'] && preg_match('#^http#i', $member['website']) !== 1)
	{
		$member['website'] = 'http://' . $member['website'];
	}

	$iaView->assign('item', $member);
	$iaView->assign('sections', $sections);

	$title = empty($member['fullname']) ? $member['username'] : $member['fullname'];
	$iaView->title($title);

	// add open graph data
	$avatar = $member['avatar'] ? unserialize($member['avatar']) : '';
	$openGraph = array(
		'title' => $title,
		'url' => IA_SELF,
		'image' => $avatar ? IA_CLEAR_URL . 'uploads/' . $avatar['path'] : ''
	);
	$iaView->set('og', $openGraph);

	$iaView->display('view-member');
}