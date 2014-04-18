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

$iaDb->setTable('hooks');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($_POST['action']))
	{
		switch ($_POST['action'])
		{
			case 'get':
				$iaView->assign('code', $iaDb->one_bind('`code`', '`id` = :id', array('id' => (int)$_POST['hook'])));
				break;

			case 'set':
				$iaDb->update(array('code' => $_POST['code']), iaDb::convertIds($_POST['hook']));
		}

		return;
	}

	$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$conditions = array();
			if (isset($_GET['item']) && $_GET['item'])
			{
				$value = ('core' == strtolower($_GET['item']) ? '' : iaSanitize::sql($_GET['item']));

				$stmt = '`extras` = :extras';
				$iaDb->bind($stmt, array('extras' => $value));

				$conditions[] = $stmt;
			}

			$output = $iaGrid->gridRead($_GET,
				"`id`, `name`, `extras`, `order`, `type`, `status`, `filename`, 1 `delete`, IF(`filename` = '', 1, 0) `open`",
				array('name' => 'like', 'type' => 'equal'),
				$conditions
			);

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaGrid->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaGrid->gridDelete($_POST);
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaView->grid('admin/hooks');
	$iaView->display('hooks');
}

$iaDb->resetTable();