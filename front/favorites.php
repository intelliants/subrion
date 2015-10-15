<?php
//##copyright##

$iaItem = $iaCore->factory('item');
$iaUsers = $iaCore->factory('users');

// get all available items list
$itemsList = $iaItem->getPackageItems();

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_GET['action']))
{
	$output = array('error' => true, 'message' => iaLanguage::get('invalid_parameters'));

	if (isset($_GET['item']) && $_GET['item_id'])
	{
		$itemName = in_array($_GET['item'], array_keys($itemsList)) ? $_GET['item'] : $iaUsers->getItemName();
		$itemId = (int)$_GET['item_id'];

		switch ($_GET['action'])
		{
			case iaCore::ACTION_ADD:

				if ($iaUsers->hasIdentity())
				{
					$iaDb->query(iaDb::printf("INSERT IGNORE `:prefix:table` (`id`, `member_id`, `item`) VALUES (:id, :user, ':item')",
						array('prefix' => $iaDb->prefix,
							'table' => $iaItem->getFavoritesTable(),
							'id' => $itemId,
							'user' => iaUsers::getIdentity()->id,
							'item' => $itemName)
					));

					// $output['error'] = !(bool)$iaDb->getAffected();
				}
				else
				{
					// initialize necessary class
					$class = (iaCore::CORE != $itemsList[$itemName])
							? $iaCore->factoryPackage('item', $itemsList[$itemName], iaCore::FRONT, $itemName)
							: $iaCore->factory($iaUsers->getItemName() == $itemName ? 'users' : $itemName);

					// get listing information
					$array = (array)$_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName];
					if ($listing = $class->getById($itemId))
					{
						if (!array_key_exists($listing['id'], $array['items']))
						{
							$listing['favorite'] = 1;
							$array['items'][$listing['id']] = $listing;
						}
					}
					$_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName] = $array;
				}

				$output['error'] = false;
				$output['message'] = iaLanguage::get('favorites_action_added');

				break;

			case iaCore::ACTION_DELETE:

				if ($iaUsers->hasIdentity())
				{
					$iaDb->delete('`id` = :item_id AND `member_id` = :user AND `item` = :item',
							$iaItem->getFavoritesTable(),
							array('item_id' => $itemId, 'user' => iaUsers::getIdentity()->id, 'item' => $itemName)
					);
				}
				else
				{
					unset($_SESSION[iaUsers::SESSION_FAVORITES_KEY][$itemName]['items'][$itemId]);
				}

				$output['error'] = false;
				$output['message'] = iaLanguage::get('favorites_action_deleted');
		}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$itemInfo = $fields = array();
	$iaField = $iaCore->factory('field');

	if ($iaUsers->hasIdentity())
	{
		if ($favorites = $iaItem->getFavoritesByMemberId(iaUsers::getIdentity()->id))
		{
			foreach ($favorites as $itemName => $ids)
			{
				$fields = array('id');

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
						$fields[] = 'fullname';
						$fields[] = 'avatar';
						$fields[] = 'id` `member_id';
					}
					else
					{
						$fields[] = 'member_id';
					}

					$stmt = iaDb::printf("`id` IN (:ids) && `status` = ':status'", array('ids' => implode(',', $ids), 'status' => iaCore::STATUS_ACTIVE));
					$favorites[$itemName]['items'] = $iaDb->all('*, 1 `favorite`', $stmt, null, null, $iaItem->getItemTable($itemName));
				}

				// we need this to generate correct template filename
				$favorites[$itemName]['package'] = (iaCore::CORE == $itemsList[$itemName]) ? '' : $itemsList[$itemName];

				// filter values
				$favorites[$itemName]['fields'] = $iaField->filter($favorites[$itemName]['items'], $itemName);
			}
		}
	}
	else
	{
		$favorites = isset($_SESSION[iaUsers::SESSION_FAVORITES_KEY]) ? (array)$_SESSION[iaUsers::SESSION_FAVORITES_KEY] : array();

		// populate visible fields
		foreach ($favorites as $itemName => &$items)
		{
			if (isset($items['items']) && $items['items'])
			{
				// generate correct fields array
				$favorites[$itemName]['fields'] = $iaField->filter($items['items'], $itemName);

				// generate correct template filename
				$favorites[$itemName]['package'] = iaCore::CORE == $itemsList[$itemName] ? '' : $itemsList[$itemName];

				$iaCore->startHook('phpFavoritesAfterGetExtraItems', array('favorites' => &$items, 'item' => $itemName));
			}
			else
			{
				unset($favorites[$itemName]);
			}
		}
	}

	$iaView->assign('fields', $fields);
	$iaView->assign('favorites', $favorites);
}