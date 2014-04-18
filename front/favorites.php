<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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

$iaItem = $iaCore->factory('item');
$iaUsers = $iaCore->factory('users');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($_GET['item']))
	{
		$allItems = array_keys($iaItem->getItems());
		$itemName = in_array($_GET['item'], $allItems) ? $_GET['item'] : $iaUsers->getItemName();
		$itemId = (int)$_GET['item_id'];

		if (iaUsers::getIdentity()->id && $itemId)
		{
			switch ($_GET['action'])
			{
				case iaCore::ACTION_ADD:
					$iaDb->query(iaDb::printf("INSERT IGNORE `:prefix:table` (`id`, `member_id`, `item`) VALUES (:id, :user, ':item')", array(
						'prefix' => $iaDb->prefix,
						'table' => $iaItem->getFavoritesTable(),
						'id' => $itemId,
						'user' => iaUsers::getIdentity()->id,
						'item' => $itemName
					)));

					break;

				case iaCore::ACTION_DELETE:
					$iaDb->delete('`id` = :item_id AND `member_id` = :user AND `item` = :item',
						$iaItem->getFavoritesTable(),
						array(
							'item_id' => $itemId,
							'user' => iaUsers::getIdentity()->id,
							'item' => $itemName
						)
					);
			}

			$result = (bool)$iaDb->getAffected();

			$iaView->assign('error', !$result);
		}
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!iaUsers::hasIdentity())
	{
		return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
	}

	$favorites = $iaItem->getFavoritesByMemberId(iaUsers::getIdentity()->id);

	if ($favorites)
	{
		$itemInfo = array();
		$itemsList = $iaItem->getPackageItems();

		$iaField = $iaCore->factory('field');

		foreach ($favorites as $itemName => $ids)
		{
			$fields = array('id');

			$favorites[$itemName]['fields'] = $iaField->filter($itemInfo, $itemName);

			$class = (iaCore::CORE != $itemsList[$itemName])
				? $iaCore->factoryPackage('item', $itemsList[$itemName], iaCore::FRONT, $itemName)
				: $iaCore->factory('members' == $itemName ? 'users' : $itemName);

			if ($class && method_exists($class, iaUsers::METHOD_NAME_GET_FAVORITES))
			{
				$favorites[$itemName]['items'] = $class->{iaUsers::METHOD_NAME_GET_FAVORITES}($ids, $fields);
			}
			else
			{
				if ($itemName == $iaUsers->getItemName())
				{
					$fields[] = 'username';
					$fields[] = 'id` `member_id';
				}
				else
				{
					$fields[] = 'member_id';
				}

				foreach ($favorites[$itemName]['fields'] as $f)
				{
					$fields[] = $f['name'];
				}

				$stmt = iaDb::printf("`id` IN (:ids) AND `status` = ':status'", array('ids' => implode(',', $ids), 'status' => iaCore::STATUS_ACTIVE));

				$favorites[$itemName]['items'] = $iaDb->all('`' . implode('`,`', $fields) . '`, 1 `favorite`', $stmt, null, null, $iaItem->getItemTable($itemName));
			}

			$iaCore->startHook('phpFavoritesAfterGetExtraItems', array('favorites' => &$favorites, 'item' => $itemName));
		}
	}

	$iaView->assign('favorites', $favorites);
}