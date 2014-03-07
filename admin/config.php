<?php
//##copyright##

if (isset($_GET['group']))
{
	$type = 'group';
	$customGroup = (int)$_GET['group'];
	$customUser = false;
	$pageAction = 'custom';

	iaBreadcrumb::preEnd(iaLanguage::get('usergroups'), IA_ADMIN_URL . 'usergroups/');
}
elseif (isset($_GET['user']))
{
	$type = 'user';
	$customUser = (int)$_GET['user'];
	$customGroup = false;
	$pageAction = 'custom';

	iaBreadcrumb::preEnd(iaLanguage::get('members'), IA_ADMIN_URL . 'members/');
}

iaCore::util();

if (isset($_POST['save']) && iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!$iaAcl->checkAccess($pageName . iaAcl::SEPARATOR . iaCore::ACTION_EDIT))
	{
		return iaView::accessDenied();
	}

	iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

	$messages = array();
	$error = false;

	if (is_array($_POST['param']) && $_POST['param'])
	{
		$imageTypes = array(
			'image/gif' => 'gif',
			'image/jpeg' => 'jpg',
			'image/pjpeg' => 'jpg',
			'image/png' => 'png'
		);

		foreach ($_POST['param'] as $key => $value)
		{
			$s = strpos($key, '_items_enabled');
			if ($s !== false)
			{
				$p = $iaCore->get($key, '', (bool)('custom' == $pageAction));
				$array = $p ? explode(',', $p) : array();

				$data = array();

				array_shift($value);
				$diff = array_diff($value, $array);

				if ($diff)
				{
					foreach ($diff as $item)
					{
						array_push($data, array('action' => '+', 'item' => $item));
					}
				}

				$diff = array_diff($array, $value);

				if ($diff)
				{
					foreach ($diff as $item)
					{
						array_push($data, array('action' => '-', 'item' => $item));
					}
				}

				$extra = substr($key, 0, $s);

				$iaCore->startHook('phpPackageItemChangedForPlugin', array('data' => $data), $extra);
			}

			if (is_array($value))
			{
				$value = implode(',', $value);
			}

			if (!utf8_is_valid($value))
			{
				$value = utf8_bad_replace($value);
				trigger_error('Bad UTF-8 detected (replacing with "?") in configuration', E_USER_NOTICE);
			}
			if (('site_logo' == $key || 'site_watermark' == $key))
			{
				if (isset($_POST['delete'][$key]) && $_POST['delete'][$key] == 'on')
				{
					$value = '';
				}
				elseif (!empty($_FILES[$key]['name']))
				{
					if ((bool)$_FILES[$key]['error'])
					{
						$error = true;
						$messages[] = iaLanguage::get('site_logo_image_error');
					}
					else
					{
						if (@is_uploaded_file($_FILES[$key]['tmp_name']))
						{
							$ext = strtolower(utf8_substr($_FILES[$key]['name'], -3));

							// if jpeg
							if ($ext == 'peg')
							{
								$ext = 'jpg';
							}

							if (!array_key_exists(strtolower($_FILES[$key]['type']), $imageTypes) || !in_array($ext, $imageTypes, true) || !getimagesize($_FILES[$key]['tmp_name']))
							{
								$error = true;
								$messages[] = iaLanguage::get('wrong_site_logo_image_type');
							}
							else
							{
								if ('' != $iaCore->get($key))
								{
									if (file_exists(IA_HOME . 'uploads' . IA_DS . $iaCore->get($key)))
									{
										unlink(IA_HOME . 'uploads' . IA_DS . $iaCore->get($key));
									}
								}

								$token = iaUtil::generateToken();
								$fileName = $key . '_' . $token . '.' . $ext;
								$fname = IA_HOME . 'uploads' . IA_DS . $fileName;

								$value = $fileName;

								@move_uploaded_file($_FILES[$key]['tmp_name'], $fname);

								@chmod($fname, 0777);
							}
						}
					}
				}
				else
				{
					$value = $iaCore->get($key, '', ($pageAction == 'custom' ? true : false));
				}
			}

			if ($pageAction == 'custom')
			{
				$type_id = $type == 'user' ? $customUser : $customGroup;
				$where = "`name` = '{$key}' AND `type` = '{$type}' AND `type_id` = '{$type_id}'";

				$iaDb->setTable('config_custom');
				if ($_POST['chck'][$key] == 1)
				{
					$values = array('name' => $key, 'value' => $value, 'type' => $type, 'type_id' => $type_id);

					if ($iaDb->exists($where))
					{
						unset($values['value']);
						$iaDb->bind($where, $values);
						$iaDb->update(array('value' => $value), $where);
					}
					else
					{
						$iaDb->insert($values);
					}
				}
				else
				{
					$iaDb->delete($where);
				}
				$iaDb->resetTable();
			}
			else
			{
				$iaCore->set($key, $value, true);
			}
		}

		$iaCore->factory('cache')->clearAll();
	}

	if (!$error)
	{
		$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
	}
	elseif ($messages)
	{
		$iaView->setMessages($messages, iaView::ERROR);
	}
}

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaView->assign('pageAction', $pageAction);

	switch ($_GET['action'])
	{
		case 'update':
			$output = array(
				'result' => false,
				'message' => iaLanguage::get('invalid_parameters')
			);

			if ($iaCore->set($_POST['name'], $_POST['value'], true))
			{
				$output['result'] = true;
				$output['message'] = iaLanguage::get('saved');
			}

			break;

		case 'remove_site_logo':
			if (file_exists(IA_UPLOADS . $iaCore->get('site_logo')))
			{
				unlink(IA_UPLOADS . $iaCore->get('site_logo'));
			}
			$iaCore->set('site_logo', '', true);

			break;

		case 'get_site_logo':
			echo file_exists(IA_UPLOADS . $iaCore->get('site_logo'))
				? '<img src="' . IA_URL . 'uploads/' . $iaCore->get('site_logo') . '" alt="">'
				: '<div style="padding: 15px; margin: 0; background: #FFE269 none repeat scroll 0 0;">' . iaLanguage::get('logo_image_not_found') . '</div>';

			exit;

		case 'upload':
			if ((bool)$_FILES['site_logo']['error'])
			{
				$iaView->assign('error', true);
				$iaView->assign('msg', iaLanguage::get('site_logo_image_error'));
			}
			else
			{
				if (is_uploaded_file($_FILES['site_logo']['tmp_name']))
				{
					$ext = substr($_FILES['site_logo']['name'], -3);

					// if 'jpeg'
					if ($ext == 'peg')
					{
						$ext = 'jpg';
					}

					if (!array_key_exists($_FILES['site_logo']['type'], $imageTypes) || !in_array($ext, $imageTypes))
					{
						$iaView->assign('error', true);
						$a = implode(', ', array_unique($imageTypes));
						$iaView->assign('msg', iaLanguage::get('wrong_site_logo_image_type'));
					}
					else
					{
						if ($iaCore->get('site_logo'))
						{
							if (file_exists(IA_UPLOADS . $iaCore->get('site_logo')))
							{
								unlink(IA_UPLOADS . $iaCore->get('site_logo'));
							}
						}

						$fileName = 'site_logo_' . iaUtil::generateToken() . '.' . $ext;
						$fname = IA_UPLOADS . $fileName;

						if (@move_uploaded_file($_FILES['site_logo']['tmp_name'], $fname))
						{
							$iaView->assign('error', false);
							$iaView->assign('msg', iaLanguage::getf('image_uploaded', array('name' => $_FILES['site_logo']['name'])));
							$iaView->assign('file_name', $fileName);

							$iaCore->set('site_logo', $fileName, true);

							@chmod($fname, 0777);
						}
					}
				}
			}
	}

	if (isset($output))
	{
		$iaView->assign($output);
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaItem = $iaCore->factory('item');

	$groupName = isset($iaCore->requestPath[0]) ? $iaCore->requestPath[0] : 'general';
	$groupData = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`name` = :name', array('name' => $groupName), iaCore::getConfigGroupsTable());

	if (empty($groupData))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaView->title($groupData['title']);

	if ($groupData['extras']) // special cases
	{
		$iaPage = $iaCore->factory('page', iaCore::ADMIN);

		$activeMenu = $groupData['name'];

		if ($groupData['extras'] == $iaCore->get('tmpl'))
		{
			// template configuration options
			$page = $iaPage->getByName('templates');

			$iaView->set('group', $page['group']);

			iaBreadcrumb::add($page['title'], IA_ADMIN_URL . $page['alias']);
		}
		elseif ($pluginPage = $iaDb->row(array('alias', 'group'), iaDb::printf("`name` = ':name' OR `name` = ':name_stats'", array('name' => $groupData['extras'])), iaPage::getAdminTable()))
		{
			// it is a package
			$iaView->set('group', $pluginPage['group']);

			iaBreadcrumb::add($groupData['title'], IA_ADMIN_URL . $pluginPage['alias']);

		}
		elseif ($iaItem->isExtrasExist($groupData['extras'], iaItem::TYPE_PLUGIN))
		{
			// plugin with no admin pages
			$iaView->set('group', 5);
		}
	}
	else
	{
		$activeMenu = 'configuration_' . $groupName;

		iaBreadcrumb::toEnd($groupData['title'], IA_SELF);
	}
	$iaView->set('active_menu', $activeMenu);

	isset($_GET['show']) || $_GET['show'] = 'plaintext';

	if ($groupName == 'email_templates' && isset($_GET['show']) && in_array($_GET['show'], array('plaintext', 'html'), true))
	{
		$where = ($_GET['show'] == 'plaintext')
			? "`config_group` = '{$groupName}' AND `name` NOT LIKE '%\_body\_html' ORDER BY `order`"
			: "`config_group` = '{$groupName}' AND `name` NOT LIKE '%\_body\_plaintext' ORDER BY `order`";
	}
	else
	{
		$where = "`config_group` = '{$groupName}' AND `type` != 'hidden' " . ($pageAction == 'custom' ? 'AND `custom` = 1' : '') . ' ORDER BY `order`';
	}

	$fields = iaDb::ALL_COLUMNS_SELECTION;
	if ($_GET['show'] == 'plaintext' && 'email_templates' == $groupName)
	{
		$fields .= ', 0 `wysiwyg`';
	}
	$params = $iaDb->all($fields, $where, null, null, iaCore::getConfigTable());
	if ('custom' == $pageAction)
	{
		$custom = $iaCore->getCustomConfig($customUser, $customGroup);
		$custom2 = array();
		if (false === $customGroup)
		{
			$custom2 = $iaDb->getKeyValue("SELECT d.`name`, d.`value`
				FROM `{$iaCore->iaDb->prefix}config_custom` d, `{$iaCore->iaDb->prefix}members` a
				WHERE d.`type` = 'group'
					AND d.`type_id` = a.`usergroup_id`
					AND a.`id` = '{$customUser}'");
		}
	}

	$itemsList = $iaItem->getItems();

	foreach ($params as $index => $item)
	{
		$className = 'default';

		if ('custom' == $pageAction)
		{
			$className = 'custom';

			if ($item['type'] != 'divider')
			{
				if (isset($custom2[$item['name']]))
				{
					$params[$index]['dtype'] = 'usergroup';
					$params[$index]['default'] = $custom2[$item['name']];
					$params[$index]['value'] = $custom2[$item['name']];
				}
				else
				{
					$params[$index]['dtype'] = 'core';
					$params[$index]['default'] = $iaCore->get($item['name']);
				}

				if (isset($custom[$item['name']]))
				{
					$className = 'common';
					$params[$index]['value'] = $custom[$item['name']];
				}
			}
		}

		if ('itemscheckbox' == $item['type'])
		{
			$array = $iaCore->get($item['extras'] . '_items_implemented');
			$array = $array ? explode(',', $array) : array();
			$array = array_values(array_intersect($array, $itemsList));

			if ($array)
			{
				$enabledItems = $iaItem->getEnabledItemsForPlugin($item['extras']);

				for ($i = 0; $i < count($array); $i++)
				{
					$array[$i] = trim($array[$i]);
					$params[$index]['items'][] = array(
						'name' => $array[$i],
						'title' => iaLanguage::get($array[$i]),
						'checked' => (int)in_array($array[$i], $enabledItems)
					);
				}
			}
		}

		if ($item['type'] == 'select')
		{
			switch ($item['name'])
			{
				case 'timezone':
					$params[$index]['values'] = iaUtil::getFormattedTimezones();
					break;
				case 'lang':
					$params[$index]['values'] = $iaCore->languages;
					break;
				default:
					$params[$index]['values'] = explode(',', $item['multiple_values']);
			}
		}

		$params[$index]['classname'] = $className;
	}

	$customUrl = '';
	if ('custom' == $pageAction)
	{
		$customUrl = isset($_GET['user'])
			? '?user=' . $_GET['user']
			: '?group=' . $_GET['group'];
		$customUrl = iaSanitize::html($customUrl);
	}

	$iaView->assign('group', $groupData);
	$iaView->assign('params', $params);
	$iaView->assign('url_custom', $customUrl);
}