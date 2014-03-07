<?php
//##copyright##

$iaBlock = $iaCore->factory('block', iaCore::ADMIN);

$iaDb->setTable(iaBlock::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$output = $iaGrid->gridRead($_GET,
				array('title', 'name', 'status', 'order', 'position', 'delete' => 'removable'),
				array(),
				array("`type` = 'menu'")
			);

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaGrid->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaGrid->gridDelete($_POST, 'menu_removed');
	}

	if (isset($_GET['action']))
	{
		$output = array();

		switch($_GET['action'])
		{
			case 'pages':
				$pageGroups = $iaCore->factory('page', iaCore::ADMIN)->getGroups();

				foreach ($pageGroups as $groupId => $group)
				{
					$children = array();
					foreach ($group['children'] as $pageId => $pageTitle)
					{
						$children[] = array('text' => $pageTitle, 'leaf' => true, 'id' => $pageId);
					}

					$output[] = array(
						'text' => $group['title'],
						'id' => 'group_' . $groupId,
						'cls' => 'folder',
						'draggable' => false,
						'children' => $children
					);
				}

				$output[0]['expanded'] = true;

				break;

			case 'menus':
				function recursiveRead($list, $pid = 0)
				{
					$result = array();

					if (isset($list[$pid]))
					{
						foreach ($list[$pid] as $child)
						{
							$title = iaLanguage::get('page_title_' . $child['el_id'], 'none');
							if ($title == 'none')
							{
								$title = iaLanguage::get('page_title_' . $child['page_name'], 'none');
								if ($title == 'none' || $child['page_name'] == 'node')
								{
									$title = iaLanguage::get('_page_removed_');
								}
							}
							else
							{
								$title .= ((int)$child['el_id'] > 0)
									? ' (custom)'
									: ' (no link)';
							}

							$item = array(
								'text' => $title,
								'id' => $child['el_id'],
								'expanded' => true,
								'children' => recursiveRead($list, $child['el_id'])
							);

							$result[] = $item;
						}
					}

					return $result;
				}

				$list = array();

				if ($name = (int)$_GET['id'])
				{
					$rows = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, '`menu_id` = ' . $name . ' ORDER BY `id`', null, null, 'menus');
					foreach ($rows as $row)
					{
						$list[$row['parent_id']][] = $row;
					}
				}

				$output = recursiveRead($list);

				break;

			case 'titles':

				$output['languages'] = array();

				$languagesList = $iaCore->languages;
				$node = isset($_GET['id']) ? iaSanitize::sql($_GET['id']) : false;
				$entry = isset($_GET['menu']) ? iaSanitize::sql($_GET['menu']) : false;

				if (isset($_GET['new']) && $_GET['new'])
				{
					ksort($languagesList);
					foreach ($languagesList as $code => $lang)
					{
						$output['languages'][] = array('fieldLabel' => $lang, 'name' => $code, 'value' => '');
					}
				}
				elseif ($node && $entry)
				{
					$key = false;
					$title = iaLanguage::get('page_title_' . $node, 'none');
					if ($title != 'none')
					{
						$key = 'page_title_' . $node;
					}
					else
					{
						if ($pageId = (int)$node)
						{
							$page = $iaDb->one('`name`', iaDb::convertIds($pageId), 'pages');
							$key = 'page_title_' . $page;
						}
						else
						{
							$current = isset($_GET['current']) ? $_GET['current'] : '';
							ksort($languagesList);
							foreach ($languagesList as $code => $lang)
							{
								$output['languages'][] = array('fieldLabel' => $lang, 'name' => $code, 'value' => $current);
							}
						}
					}

					if ($key)
					{
						$titles = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = '$key' ORDER BY `code`", null, null, iaLanguage::getTable());
						foreach ($titles as $row)
						{
							if (isset($languagesList[$row['code']]))
							{
								$output['languages'][] = array(
									'fieldLabel' => $languagesList[$row['code']],
									'name' => $row['code'],
									'value' => $row['value']
								);
							}
						}
					}

					$output['key'] = $key;
				}

				break;

			case 'save':
				$output['message'] = iaLanguage::get('invalid_parameters');

				$entry = isset($_GET['menu']) ? iaSanitize::sql($_GET['menu']) : null;
				$node = isset($_GET['node']) ? iaSanitize::sql($_GET['node']) : null;

				if ($entry && $node)
				{
					$rows = array();
					foreach ($_POST as $code => $value)
					{
						$rows[] = array(
							'code' => $code,
							'value' => iaSanitize::sql($value),
							'extras' => $entry,
							'key' => 'page_title_' . $node,
							'category' => iaLanguage::CATEGORY_COMMON
						);
					}

					$iaDb->setTable(iaLanguage::getTable());
					$iaDb->delete('`key` = :key', null, array('key' => 'page_title_' . $node));
					$iaDb->insert($rows);
					$iaDb->resetTable();

					$output['message'] = iaLanguage::get('saved');
					$output['success'] = true;

					$iaCore->factory('cache')->remove('menu_' . $entry . '.inc');
				}
		}
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaCore::ACTION_READ == $pageAction)
	{
		$iaView->grid('admin/menus');
	}
	else
	{
		$pageTitle = iaLanguage::get($pageAction . '_menu');
		iaBreadcrumb::toEnd($pageTitle);
		$iaView->title($pageTitle);

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			if (isset($_GET['id']) && is_numeric($_GET['id']) && $_GET['id'])
			{
				$entry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`id` = :id', array('id' => $_GET['id']));
				if (empty($entry))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}
			}
			else
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}
		}
		else
		{
			$entry = array(
				'name' => '',
				'position' => '',
				'status' => iaCore::STATUS_ACTIVE,
				'sticky' => false,
				'title' => '',
				'tpl' => iaBlock::DEFAULT_MENU_TEMPLATE,
				'type' => iaBlock::TYPE_MENU
			);
		}

		if (isset($_POST['name']))
		{
			$error = false;
			$messages = array();

			$entry['name'] = isset($_POST['name']) ? iaSanitize::sql(iaSanitize::paranoid(strip_tags($_POST['name']))) : 'menu_' . mt_rand(1000, 9999);
			$entry['title'] = isset($_POST['title']) ? iaSanitize::html($_POST['title']) : 'Menu "' . $entry['name'] . '"';
			$entry['position'] = isset($_POST['position']) ? iaSanitize::sql($_POST['position']) : 'left';
			$entry['sticky'] = (int)$_POST['sticky'];

			if (empty($entry['title']))
			{
				$entry['title'] = iaLanguage::get('without_title');
			}

			$shownPagesList = isset($_POST['pages']) ? $_POST['pages'] : '';

			if (trim($entry['name']))
			{
				$iaDb->setTable('blocks_pages');
				$menuExists = $iaDb->exists('`name` = :name', $entry, iaBlock::getTable());

				if (iaCore::ACTION_EDIT == $pageAction)
				{
					if ($menuExists)
					{
						$iaDb->update($entry, "`name` = '" . $entry['name'] . "'", null, iaBlock::getTable());
						$iaDb->delete('`block_id` = :id', null, $entry);

						$iaCore->factory('cache')->remove('menu_' . $entry['id'] . '.inc');
					}
					else
					{
						$messages = iaLanguage::get('menu_doesnot_exists');
						$error = true;
					}
				}
				else
				{
					if ($menuExists)
					{
						$messages = iaLanguage::get('menu_exists');
						$error = true;
						$entry = null;
					}
					else
					{
						$entry['id'] = $iaDb->insert($entry, null, iaBlock::getTable());
					}
				}

				if ($entry['id'])
				{
					function recursive_read_menu($menus, $pages, &$list, $menuId)
					{
						foreach ($menus as $menu)
						{
							$pageId = reset(explode('_', $menu['id']));
							$list[] = array(
								'parent_id' => ('root' == $menu['parentId']) ? 0 : $menu['parentId'],
								'menu_id' => $menuId,
								'el_id' => $menu['id'],
								'level' => $menu['depth'] - 1,
								'page_name' => ($pageId > 0 && isset($pages[$pageId])) ? $pages[$pageId] : 'node',
							);
						}
					}

					$menus = isset($_POST['menus']) && $_POST['menus'] ? $_POST['menus'] : '';
					$menus = $iaCore->util()->jsonDecode($menus);
					array_shift($menus);

					$rows = array();
					$pages = $iaDb->keyvalue(array('id', 'name'), null, 'pages');
					recursive_read_menu($menus, $pages, $rows, $entry['id']);

					$iaDb->setTable(iaBlock::getMenusTable());
					$iaDb->delete('`menu_id` = :id', null, $entry);
					if ($rows)
					{
						$iaDb->insert($rows);
					}
					$iaDb->resetTable();
				}

				// FIXME: functionality below is already exist in iaBlock::insert. Class method should be used instead
				if (isset($shownPagesList) && is_array($shownPagesList) && $shownPagesList)
				{
					$data = array();
					foreach ($shownPagesList as $key => $page)
					{
						$data[] = array('block_id' => $entry['id'], 'page_name' => $page);
					}

					$iaDb->insert($data);
				}
				//

				$messages = iaLanguage::get('saved');
				$error = false;

				$iaDb->resetTable();
			}
			else
			{
				$messages = iaLanguage::get('error_save');
				$error = true;
			}

			$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

			if (!$error)
			{
				$url = IA_ADMIN_URL . 'menus/';
				$goto = array(
					'add' => $url . 'add/',
					'list' => $url,
					'stay' => $url . 'edit/?id=' . $entry['id'],
				);
				iaUtil::post_goto($goto);
			}
			else
			{
				$iaView->assign('tree_data', $_POST['menus']);
			}
		}

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			if (!isset($_GET['id']))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}
			$menuId = (int)$_GET['id'];
		}

		$pageGroups = array();
		$extras = array();
		$visibleOn = array();

		if (empty($entry['name']))
		{
			$entry['name'] = 'menu_' . iaUtil::generateToken(5);
		}

		// get pages
		//$pages = $iaDb->all('*', "`status`='active' AND `service`=0 ORDER BY `name`", 0,  0, 'pages');
		$sql = "SELECT DISTINCTROW p.*, if (t.`value` is null, p.`name`, t.`value`) as `title`
			FROM `{$iaCore->iaDb->prefix}pages` as p
				LEFT JOIN `{$iaCore->iaDb->prefix}language` as t
					ON `key` = CONCAT('page_title_', p.`name`) AND t.`code` = '" . IA_LANGUAGE . "'
			WHERE p.`status` = 'active'
				AND p.`service` = 0
			ORDER BY t.`value`";
		$pages = $iaDb->getAll($sql);

		// get groups
		$groups = $iaDb->onefield('`group`', '1=1 GROUP BY `group`', null, null, 'pages');
		$rows = $iaDb->all(array('id', 'name', 'title'), null, null, null, 'admin_pages_groups');
		foreach ($rows as $row)
		{
			if (in_array($row['id'], $groups))
			{
				$pageGroups[$row['id']] = $row;
				$extras[$row['id']] = $row['title'];
			}
		}

		if ($pageAction == iaCore::ACTION_EDIT)
		{
			if ($array = $iaDb->onefield('page_name', "`block_id` = " . (int)$_GET['id'], null, null, 'blocks_pages'))
			{
				$visibleOn = $array;
			}
		}
		elseif (isset($_POST['visible_on_pages']) && $_POST['visible_on_pages'])
		{
			$visibleOn = $_POST['visible_on_pages'];
		}

		$iaView->assign('visibleOn', $visibleOn);
		$iaView->assign('form', $entry);
		$iaView->assign('pages', $pages);
		$iaView->assign('pages_group', $pageGroups);
		$iaView->assign('positions', explode(',', $iaCore->get('block_positions', '')));

		$iaView->display('menus');
	}
}

$iaDb->resetTable();