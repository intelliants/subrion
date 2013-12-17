<?php
//##copyright##

$iaPage = $iaCore->factory('page', iaCore::ADMIN);
$iaUtil = iaCore::util();

$iaDb->setTable(iaPage::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			switch ($_GET['get'])
			{
				case 'url':
					iaUtf8::loadUTF8Core();
					iaUtf8::loadUTF8Util('ascii', 'bad', 'utf8_to_ascii');

					$name = $_GET['name'];
					$name = !utf8_is_ascii($name) ? utf8_to_ascii($name) : $name;
					$name = preg_replace('#[^a-z0-9-_]#iu', '', $name);

					$url = $_GET['url'];
					$url = !utf8_is_ascii($url) ? utf8_to_ascii($url) : $url;
					$url = preg_replace('#[^a-z0-9-_]#iu', '', $url);

					$url = $url ? $url : $name;

					if (is_numeric($_GET['parent']) && $_GET['parent'])
					{
						$parentPage = $iaPage->getById($_GET['parent']);
						$url = (empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias']) . $url;
					}

					$url = IA_URL . $url . IA_URL_DELIMITER;

					$output = array('url' => $url);

					break;

				case 'plugins':
					$sql =
						'SELECT ' .
							"IF(p.`extras` = '', 'core', p.`extras`) `value`, " .
							"IF(p.`extras` = '', 'Core', g.`title`) `title` " .
						'FROM `:prefix:pages` p ' .
						'LEFT JOIN `:prefixextras` g ON g.`name` = p.`extras` ' .
						'GROUP BY p.`extras`';
					$sql = iaDb::printf($sql, array(
						'prefix' => $iaCore->iaDb->prefix,
						'pages' => iaPage::getTable()
					));

					$output = array('data' => $iaDb->getAll($sql));

					break;

				default:
					$output = $iaPage->gridRead($_GET,
						"`id`, `name`, `status`, `last_updated`, IF(`custom_url` != '', `custom_url`, IF(`alias` != '', `alias`, CONCAT(`name`, '/'))) `url`, `id` `update`, IF(`readonly` = 0, 1, 0) `delete`",
						array('name' => 'like', 'extras' => 'equal'),
						array('`service` = 0')
					);
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaPage->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaPage->gridDelete($_POST);
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaUsers = $iaCore->factory('users');
	$userGroups = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, null, null, iaUsers::getUsergroupsTable());

	if (isset($_POST['preview']))
	{
		if ($pageAction == iaCore::ACTION_ADD)
		{
			$_POST['save'] = true;
		}
		else
		{
			if (!defined('IA_NOUTF'))
			{
				iaUTF8::loadUTF8Core();
				iaUTF8::loadUTF8Util('ascii', 'validation', 'bad', 'utf8_to_ascii');
			}
			$newPage = array();
			$name = strtolower($_POST['name'] = !utf8_is_ascii($_POST['name']) ? utf8_to_ascii($_POST['name']) : $_POST['name']);
			if (isset($_POST['contents']) && is_array($_POST['contents']))
			{
				function utf8_validation(&$item)
				{
					$item = !utf8_is_valid($item) ? utf8_bad_replace($item) : $item;
				}

				foreach ($_POST['contents'] as $key => $content)
				{
					utf8_validation($_POST['contents'][$key]);
				}
				$newPage['contents'] = $_POST['contents'];
			}

			$newPage['titles'] = $_POST['titles'];
			$newPage['passw'] = iaSanitize::sql($_POST['passw']);

			if (!isset($_SESSION['preview_pages']))
			{
				$_SESSION['preview_pages'] = array();
			}
			$_SESSION['preview_pages'][$name] = $newPage;

			$languagesEnabled = $iaCore->get('language_switch', false) && count($iaCore->languages);
			$redirectUrl = IA_CLEAR_URL . ($languagesEnabled ? $_POST['language'] . IA_URL_DELIMITER : '') . 'page' . IA_URL_DELIMITER . $name . IA_URL_DELIMITER . '?preview';

			iaUtil::go_to($redirectUrl);
		}
	}

	if (isset($_POST['save']))
	{
		$iaCore->startHook('phpAdminAddPageValidation');

		if (!defined('IA_NOUTF'))
		{
			iaUTF8::loadUTF8Core();
			iaUTF8::loadUTF8Util('ascii', 'validation', 'bad', 'utf8_to_ascii');
		}

		$error = false;
		$messages = array();

		$newPage = array(
			'name' => iaSanitize::sql(strtolower($_POST['name'] = !utf8_is_ascii($_POST['name']) ? utf8_to_ascii($_POST['name']) : $_POST['name'])),
			'alias' => empty($_POST['alias']) ? $_POST['name'] : $_POST['alias']
		);

		if (iaCore::ACTION_ADD == $pageAction)
		{
			$newPage['group'] = 2;
			$newPage['filename'] = 'page';
		}

		foreach ($_POST['titles'] as $key => $title)
		{
			if (empty($title))
			{
				$error = true;
				$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('title') . ' (' . $key . ')'));

				break;
			}
		}

		if (isset($_POST['preview']))
		{
			$newPage['status'] = iaCore::STATUS_DRAFT;
		}
		else
		{
			$newPage['status'] = in_array($_POST['status'], array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)) ? $_POST['status'] : iaCore::STATUS_DRAFT;
		}

		$newPage['passw'] = empty($_POST['passw']) ? '' : iaSanitize::sql($_POST['passw']);
		$newPage['name'] = preg_replace('#[^a-z0-9-_]#iu', '', $newPage['name']);
		$newPage['custom_url'] = empty($_POST['custom_url']) ? '' : $_POST['custom_url'];

		$newPage['alias'] = !utf8_is_ascii($newPage['alias']) ? utf8_to_ascii($newPage['alias']) : $newPage['alias'];
		$newPage['alias'] = empty($newPage['alias']) ? '' : iaSanitize::alias($newPage['alias']);
		$newPage['alias'].= IA_URL_DELIMITER;

		if (is_numeric($_POST['parent_id']) && $_POST['parent_id'] > 0)
		{
			$parentPage = $iaPage->getById($_POST['parent_id']);

			$newPage['parent'] = $parentPage['name'];
			$newPage['alias'] = (empty($parentPage['alias']) ? $parentPage['name'] . IA_URL_DELIMITER : $parentPage['alias']) . $newPage['alias'];
		}

		if ($iaDb->exists('`id` != :id AND `alias` = :alias', array('id' => isset($_GET['id']) ? $_GET['id'] : 0, 'alias' => $newPage['alias'])))
		{
			$error = true;
			$messages[] = iaLanguage::get('custom_url_exist');
		}

		if (isset($_POST['home_page']) && $iaAcl->checkAccess($permission . 'home'))
		{
			if ((int)$_POST['home_page'])
			{
				$iaCore->set('home_page', $newPage['name'], true);
			}
		}

		if (isset($_POST['meta_description']) && $_POST['meta_description'])
		{
			if (!utf8_is_valid($_POST['meta_description']))
			{
				$_POST['meta_description'] = utf8_bad_replace($_POST['meta_description']);
			}
			$newPage['meta_description'] = $_POST['meta_description'];
		}

		if (isset($_POST['meta_keywords']) && $_POST['meta_keywords'])
		{
			if (!utf8_is_valid($_POST['meta_keywords']))
			{
				$_POST['meta_keywords'] = utf8_bad_replace($_POST['meta_keywords']);
			}
			$newPage['meta_keywords'] = $_POST['meta_keywords'];
		}

		if (isset($_POST['nofollow']))
		{
			$newPage['nofollow'] = (int)$_POST['nofollow'];
		}

		$newPage['extras'] = isset($_POST['extras']) ? iaSanitize::sql($_POST['extras']) : '';

		if (isset($_POST['contents']) && is_array($_POST['contents']))
		{
			foreach ($_POST['contents'] as $key => $content)
			{
				utf8_is_valid($_POST['contents'][$key], $key);
			}
			$newPage['contents'] = $_POST['contents'];
		}

		if (empty($newPage['name']))
		{
			$error = true;
			$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('name')));
		}
		elseif (iaCore::ACTION_ADD == $pageAction && $iaDb->exists('`status` != :status AND `name` = :name', array('status' => iaCore::STATUS_DRAFT, 'name' => $newPage['name'])))
		{
			$error = true;
			$messages[] = iaLanguage::get('page_name_exists');
		}

		$newPage['titles'] = $_POST['titles'];

		// delete custom url
		if (isset($_POST['unique']) && 0 == $_POST['unique'])
		{
			$newPage['custom_url'] = '';
		}

		if (!$error)
		{
			// TODO: refactor the permissions management
/*			if (isset($_POST['usergroups']))
			{
				$iaDb->setTable('acl_privileges');
				$iaDb->delete("`object` = 'pages' AND `type` = 'group' AND `object_id` = '{$newPage['name']}'");
				foreach ($userGroups as $userGroup)
				{
					if (!in_array($ugroup['id'], $_POST['usergroups']))
					{
						$iaDb->insert(array(
							'type' => 'group',
							'type_id' => $userGroup['id'],
							'action' => iaCore::ACTION_READ,
							'access' => 0,
							'object' => 'pages',
							'object_id' => $newPage['name']
						));
					}
				}
				$iaDb->resetTable();
			}
*/
			if (iaCore::ACTION_EDIT == $pageAction)
			{
				$id = (int)$_POST['id'];
				if (isset($_POST['service']) && 1 == $_POST['service'])
				{
					$update = $newPage;
					$newPage = array(
						'name' => $update['name'],
						'titles' => $update['titles'],
						'meta_keywords' => isset($update['meta_keywords']) ? $update['meta_keywords'] : '',
						'meta_description' => isset($update['meta_description']) ? $update['meta_description'] : '',
						'status' => $update['status'],
						'extras' => $update['extras']
					);
				}

				$result = $iaPage->update($newPage, $id);

				if ($result)
				{
					$messages[] = iaLanguage::get('saved');
				}
				else
				{
					$error = true;
					$messages[] = $iaPage->getMessage();
				}
			}
			else
			{
				$id = $iaPage->insert($newPage);
				if ($id)
				{
					$messages[] = iaLanguage::get('page_added');
				}
				else
				{
					$error = true;
					$messages[] = $iaPage->getMessage();
				}
			}

			if (!$error)
			{
				if ($iaAcl->checkAccess('admin_pages:add', 0, 0, 'menus'))
				{
					$menus = (isset($_POST['menus']) && is_array($_POST['menus'])) ? $_POST['menus'] : array();
					$iaDb->setTable('menus');

					$menusList = $iaDb->all(array('id', 'name', 'title', 'removable'), "`type` = 'menu'", null, null, 'blocks');
					foreach ($menusList as $item)
					{
						$items = array();
						$add = false;
						if (in_array($item['name'], $menus))
						{
							if (!$iaDb->exists('`menu_id` = :menu AND `page_name` = :page', array('menu' => $item['id'], 'page' => $newPage['name'])))
							{
								$items[] = array(
									'parent_id' => 0,
									'menu_id' => $item['id'],
									'el_id' => $id . '_' . iaUtil::generateToken(5),
									'level' => 0,
									'page_name' => $newPage['name']
								);
								$add = true;

								$menus[] = $item['name'];
							}
						}
						else
						{
							$iaDb->delete('`menu_id` = :menu AND `page_name` = :page', null, array('menu' => $item['id'], 'page' => $newPage['name']));
						}

						if ($add)
						{
							$iaDb->insert($items);
						}

						$iaCore->factory('cache')->remove('menu_' . $item['id'] . '.inc');
					}
					$iaDb->resetTable();
				}

				if (!isset($_POST['preview']))
				{
					$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
					$url = IA_ADMIN_URL . 'pages/';
					iaUtil::post_goto(array(
						'add' => $url . 'add/',
						'list' => $url,
						'stay' => $url . 'edit/?id=' . $id,
					));
				}
				else
				{
					iaUtil::go_to(IA_URL . 'page/' . $newPage['name'] . '/?page_preview=true');
				}
			}
		}

		$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
	}

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$iaView->grid('admin/pages');

			break;

		case iaCore::ACTION_ADD:
		case iaCore::ACTION_EDIT:
			iaBreadcrumb::add(iaLanguage::get('pages'), IA_ADMIN_URL . 'pages/');

			$pageId = (isset($_GET['id']) && is_numeric($_GET['id'])) ? (int)$_GET['id'] : null;
			$page = null;
			$menus = array();
			$displayableInMenus = array();

			if (iaCore::ACTION_EDIT == $pageAction)
			{
				if (!$pageId)
				{
					iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$pageId = (int)$_GET['id'];
				$page = $iaPage->getById($pageId);

				$iaDb->setTable(iaLanguage::getTable());
				$page['titles'] = $iaDb->keyvalue(array('code', 'value'), "`key` = 'page_title_{$page['name']}' AND `category` = 'page'");
				$page['contents'] = $iaDb->keyvalue(array('code', 'value'), "`key` = 'page_content_{$page['name']}' AND `category` = 'page'");
				$iaDb->resetTable();

				$parentAlias = $iaPage->getByName($page['parent'], false);
				$parentAlias = empty($parentAlias['alias']) ? $parentAlias['name'] . IA_URL_DELIMITER : $parentAlias['alias'];

				$page['alias'] = str_replace($parentAlias, '', $page['alias']);

				$iaView->assign('home_page', ($iaCore->get('home_page', iaView::DEFAULT_ACTION) == $page['name'] ? 1 : 0));
				$iaView->assign('entry', $page);
			}
			else
			{
				$iaView->assign('home_page', 0);
			}

			if ($iaAcl->checkAccess('admin_pages:add', 0, 0, 'menus'))
			{
				$displayableInMenus[0] = array('title' => iaLanguage::get('core_menus', 'Core menus'), 'list' => array());
				$displayableInMenus[1] = array('title' => iaLanguage::get('custom_menus', 'Custom menus'), 'list' => array());

				$menusList = $iaDb->all(array('id', 'name', 'title', 'removable'), "`type` = 'menu'", null, null, 'blocks');

				foreach ($menusList as $item)
				{
					$item['title'] = iaLanguage::get($item['title'], $item['title']);
					if ($pageId)
					{
						// TODO: refactor (remove the SQL query)
						if ($iaDb->exists('`menu_id` = :menu AND `page_name` = :page', array('menu' => (int)$item['id'], 'page' => $page['name']), 'menus'))
						{
							$menus[] = $item['name'];
						}
					}
					$displayableInMenus[$item['removable']]['list'][$item['name']] = $item;
				}

				ksort($displayableInMenus[0]['list']);
				ksort($displayableInMenus[1]['list']);

				$iaView->assign('menus', $menus);

				if (iaCore::ACTION_ADD == $pageAction)
				{
					$menus = (isset($_POST['menus']) && $_POST['menus']) ? $_POST['menus'] : array();
					$iaView->assign('menus', $menus);
				}
			}
/*
			if ($pageAction == iaCore::ACTION_EDIT)
			{
				$perms = array();
				if ($array = $iaDb->all(array('type_id', 'access'), "`type` = 'group' AND `action` = 'read' AND `object` = 'pages' AND `object_id` = '{$page['name']}'", null, null, 'acl_privileges'))
				{
					foreach ($array as $row)
					{
						if (!$row['access'])
						{
							$perms[] = $row['type_id'];
						}
					}
				}
			}
			else
			{
				$perms = isset($_POST['usergroups']) ? $_POST['usergroups'] : array();
			}
*/
			$parentPage = $iaPage->getByName($page['parent'], false);

			$iaView->assign('pages', $iaPage->getNonServicePages(array('index')));
			$iaView->assign('pages_group', $iaPage->getGroups());
			$iaView->assign('parent_page', $parentPage['id']);

			$iaView->assign('usergroups', $userGroups);
//			$iaView->assign('perms', $perms);
			$iaView->assign('show_in_menus', $displayableInMenus);
			$iaView->add_js('ckeditor/ckeditor, admin/pages');

			$iaView->display('pages');
	}
}

$iaDb->resetTable();