<?php
//##copyright##

$iaDb->delete('`time` < (UNIX_TIMESTAMP() - 86400)', 'search'); // 24 hours
$iaDb->delete('`date` < (UNIX_TIMESTAMP() - 172800)', 'views_log'); // 48 hours

$iaCore->factory('log')->cleanup();

// check sponsored items for expiration
$iaItem = $iaCore->factory('item');
$items = $iaDb->onefield('item', "`status` = 'active' AND `days` > 0 GROUP BY `item`", null, null, 'plans');

foreach ($items as $i)
{
	$values = array(
		'sponsored' => 0,
		'sponsored_end' => null,
		'sponsored_plan_id' => 0
	);

	if ($i == 'members')
	{
		$values['status'] = 'suspended';
	}

	$iaDb->update($values, '`sponsored` != 0 AND `sponsored_end` < CURDATE()', null, $iaItem->getItemTable($i));
}