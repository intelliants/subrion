<?php
//##copyright##

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