<?php
//##copyright##

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