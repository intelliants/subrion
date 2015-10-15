<?php
//##copyright##

$iaPlan = $iaCore->factory('plan');

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