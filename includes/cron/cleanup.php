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

$iaDb->delete('`time` < (UNIX_TIMESTAMP() - 86400)', 'search'); // 24 hours
$iaDb->delete('`date` < (UNIX_TIMESTAMP() - 172800)', 'views_log'); // 48 hours

$iaCore->factory('log')->cleanup();

// check sponsored items for expiration
$iaPlan = $iaCore->factory('plan');
$iaItem = $iaCore->factory('item');

$itemNames = $iaDb->onefield('item', "`status` = 'active' AND `duration` > 0 GROUP BY `item`", null, null, iaPlan::getTable());

foreach ($itemNames as $itemName)
{
	$tableName = $iaItem->getItemTable($itemName);
	$itemIds = $iaDb->onefield(iaDb::ID_COLUMN_SELECTION, '`sponsored` = 1 AND `sponsored_end` < NOW()', null, null, $tableName);

	if ($itemIds)
	{
		foreach ($itemIds as $itemId)
		{
			$iaPlan->setUnpaid($itemName, $itemId);
		}
	}
}
//