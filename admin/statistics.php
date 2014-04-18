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