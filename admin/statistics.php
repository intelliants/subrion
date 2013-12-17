<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$packageName = explode('_stats', $iaView->name());
	$packageName = array_shift($packageName);

	$iaCore->startHook('phpAdminPackageStatistics', array('package' => $packageName));

	$statistics = array();

	$iaItem = $iaCore->factory('item');
	if ($packageItems = $iaItem->getItemsByPackage($packageName))
	{
		foreach ($packageItems as $itemName)
		{
			$itemName = substr($itemName, 0, -1);
			$itemClass = $iaCore->factoryPackage($itemName, $packageName, iaCore::ADMIN);
			if (method_exists($itemClass, 'getDashboardStatistics'))
			{
				if ($itemClass->dashboardStatistics)
				{
					if ($data = $itemClass->getDashboardStatistics(false))
					{
						$statistics[$itemName] = $data;
					}
				}
			}
		}
	}

	$timeline = $iaCore->factory('log')->get($packageName);

	$iaView->assign('package', $packageName);
	$iaView->assign('statistics', $statistics);
	$iaView->assign('timeline', $timeline);

	$iaView->display('statistics');
}