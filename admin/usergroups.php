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

$iaUsers = $iaCore->factory('users');

$iaDb->setTable(iaUsers::getUsergroupsTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			switch ($iaCore->requestPath[0])
			{
				case 'store':
					$output = array('data' => array());
					foreach ($iaUsers->getUsergroups() as $id => $title)
					{
						$output['data'][] = array('value' => $id, 'title' => $title);
					}
					break;
				default:
					$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
					$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 15;
					$order = '';
					$sort = $_GET['sort'];
					$dir = in_array($_GET['dir'], array('ASC', 'DESC')) ? $_GET['dir'] : 'ASC';

					if ($sort && $dir)
					{
						$order = " ORDER BY `{$sort}` {$dir} ";
					}
					$users = 'FROM `' . iaUsers::getTable(true) . '` a WHERE a.`usergroup_id` = u.`id` GROUP BY a.`usergroup_id`';

					$sql = 'SELECT u.*, IF(u.`id` = 1, 0, u.`id`) `permissions`, u.`id` `config`, IF(u.`system` = 1, 0, 1) `delete` '
						. ',if (u.`id`=1,1,p.`access`) as `admin` '
						. ',(SELECT CONCAT(a.`fullname`,\', \') ' . $users . ' LIMIT 10) `members` '
						. ',(SELECT COUNT(a.`id`) ' . $users . ') `count`'
						. 'FROM `' . $iaCore->iaDb->prefix . 'usergroups` u '
						. 'LEFT JOIN `' . $iaCore->iaDb->prefix . 'acl_privileges` p '
						. 'ON (p.`type` = \'group\' '
						. 'AND p.`type_id` = u.`id` '
						. 'AND `object` = \'admin_login\' '
						. 'AND `action` = \'read\' '
						. ')'
						. $order
						. 'LIMIT ' . $start . ', ' . $limit;

					$output = array(
						'data' => $iaDb->getAll($sql),
						'total' => $iaDb->one(iaDb::STMT_COUNT_ROWS)
					);

					foreach ($output['data'] as $key => $row)
					{
						$output['data'][$key]['members'] = trim($row['members'], ',');
						if (empty($row['count']))
						{
							$output['data'][$key]['count'] = 0;
						}
					}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaCore->factory('grid', iaCore::ADMIN)->gridDelete($_POST, 'usergroup_deleted');

			if ($output['result'])
			{
				$iaDb->update(array('usergroup_id' => iaUsers::MEMBERSHIP_REGULAR), iaDb::convertIds((int)$_POST['id'][0], 'usergroup_id'), null, iaUsers::getTable());
			}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$objectId = 0;

	if (iaCore::ACTION_ADD == $pageAction)
	{
		iaBreadcrumb::toEnd(iaLanguage::get('add_usergroup'), IA_SELF);

		$title = false;
		if ($objectId != 0)
		{
			$title = $iaDb->one('title', iaDb::convertIds($objectId));
		}

		if (isset($_POST['action']) && iaCore::ACTION_ADD == $_POST['action'])
		{
			$data = array();
			$data['title'] = isset($_POST['title']) ? iaSanitize::sql($_POST['title']) : '';
			$data['id']	= $iaAcl->obtainFreeId();

			if (empty($data['title']))
			{
				$error = true;
				$iaView->setMessages(iaLanguage::get('error_usergroup_incorrect'));
			}

			if ($iaDb->exists('`title` = :title', $data))
			{
				$error = true;
				$iaView->setMessages(iaLanguage::get('error_usergroup_exists'));
			}

			if (!isset($error))
			{
				$objectId = $data['id'];
				$iaDb->insert($data);

				$copyFrom = isset($_POST['copy_from']) ? (int)$_POST['copy_from'] : 0;
				if ($copyFrom > 1)
				{
					$iaDb->setTable('acl_privileges');
					$rows = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`type_id` = '{$copyFrom}' AND `type` = 'group'");
					foreach ($rows as $index => $row)
					{
						$rows[$index]['type_id'] = $objectId;
						unset($all[$index]['id']);
					}

					$iaDb->insert($rows);
					$iaDb->resetTable();
				}

				if ($objectId)
				{
					$title = $iaDb->one('title', iaDb::convertIds($objectId));
					$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);

					iaCore::util()->go_to(IA_ADMIN_URL . 'usergroups/');
				}
				else
				{
					$iaView->setMessages(iaLanguage::get('not_saved') . $objectId);
				}
			}
		}

		$iaView->assign('groups', $iaDb->keyvalue(array('id', 'title')));

		$iaView->display('usergroups');
	}
	else
	{
		$iaView->grid('admin/usergroups');
	}
}