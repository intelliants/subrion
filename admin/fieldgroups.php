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

$iaField = $iaCore->factory('field');

$iaDb->setTable(iaField::getTableGroups());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	$iaGrid = $iaCore->factory('grid', iaCore::ADMIN);

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:

			if (isset($_GET['action']) && 'gettabs' == $_GET['action'])
			{
				$output = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`item` = '{$_GET['item']}' AND `name` != '{$_GET['name']}' AND `tabview` = 1");
			}
			else
			{
				$output = $iaGrid->gridRead($_GET,
					array('name', 'extras', 'item', 'collapsible', 'order', 'tabview'),
					array('id' => 'equal', 'item' => 'equal')
				);

				if ($output['data'])
				{
					foreach ($output['data'] as &$row)
					{
						$row['title'] = iaLanguage::get('fieldgroup_' . $row['name'], $row['name']);
					}
				}
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaGrid->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaGrid->gridDelete($_POST, 'fieldgroup_deleted');
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_ADD:
		case iaCore::ACTION_EDIT:

			$title = iaLanguage::get('field_group_' . $pageAction);
			iaBreadcrumb::toEnd($title, IA_SELF);
			$iaView->title($title);

			if (iaCore::ACTION_ADD == $pageAction)
			{
				$group = array('tabview' => false, 'collapsed' => false, 'tabcontainer' => '');
			}
			else
			{
				if (!isset($_GET['id']))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$group = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($_GET['id']));

				// generate title & description for all available languages
				$iaDb->setTable(iaLanguage::getTable());
				$desc = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'fieldgroup_description_{$group['item']}_{$group['name']}'");
				$titles = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`key` = 'fieldgroup_{$group['name']}'");
				$iaDb->resetTable();

				foreach ($desc as $key => $val)
				{
					$group['description'][$val['code']] = $val['value'];
				}

				foreach ($titles as $key => $val)
				{
					$group['titles'][$val['code']] = $val['value'];
				}
			}

			// get list of all available items
			$items = $iaCore->factory('item')->getItems();
			$iaView->assign('items', $items);

			if ($_POST)
			{
				$group['collapsible'] = iaUtil::checkPostParam('collapsible');
				$group['collapsed'] = iaUtil::checkPostParam('collapsed');
				$group['tabview'] = iaUtil::checkPostParam('tabview');
				$group['tabcontainer'] = iaUtil::checkPostParam('tabcontainer');

				$error = false;
				$messages = array();

				$languages = $iaCore->languages;

				iaUtil::loadUTF8Functions('ascii', 'bad', 'validation');

				if (iaCore::ACTION_ADD == $pageAction)
				{
					$group['name'] = iaUtil::checkPostParam('name');
					$group['item'] = iaUtil::checkPostParam('item');

					if (!utf8_is_ascii($group['name']))
					{
						$error = true;
						$messages[] = iaLanguage::get('ascii_required');
					}
					else
					{
						$group['name'] = strtolower($group['name']);
					}

					if (!$error && !preg_match('/^[a-z0-9\-_]{2,50}$/', $group['name']))
					{
						$error = true;
						$messages[] = iaLanguage::get('name_is_incorrect');
					}

					if (!isset($_POST['item']) || empty($_POST['item']))
					{
						$error = true;
						$messages[] = iaLanguage::get('at_least_one_item_should_be_checked');
					}
				}

				foreach ($languages as $code => $l)
				{
					if ($_POST['titles'][$code])
					{
						if (!utf8_is_valid($_POST['titles'][$code]))
						{
							$_POST['titles'][$code] = utf8_bad_replace($_POST['titles'][$code]);
						}
					}
					else
					{
						$error = true;
						$messages[] = $l . ' ' . iaLanguage::get('title_incorrect');
					}
					if ($_POST['description'][$code])
					{
						if (!utf8_is_valid($_POST['description'][$code]))
						{
							$_POST['description'][$code] = utf8_bad_replace($_POST['description'][$code]);
						}
					}
				}

				if (!$error)
				{
					$name = $group['name'];

					if (iaCore::ACTION_EDIT == $pageAction)
					{
						// update multilingual values
						$iaDb->setTable(iaLanguage::getTable());
						foreach ($languages as $code => $l)
						{
							if ($iaDb->exists("`key` = 'fieldgroup_{$name}' AND `code` = '{$code}'"))
							{
								$iaDb->update(array('value' => iaSanitize::html($_POST['titles'][$code])), "`key` = 'fieldgroup_{$name}' AND `code` = '{$code}'");
							}
							else
							{
								iaLanguage::addPhrase(
									'fieldgroup_' . $name,
									iaSanitize::html($_POST['titles'][$code]),
									$code,
									false,
									iaLanguage::CATEGORY_COMMON
								);
							}

							if ($_POST['description'][$code])
							{
								$lang_key = "fieldgroup_description_{$group['item']}_{$name}";

								if ($iaDb->exists("`key` = '{$lang_key}' AND `code` = '{$code}'"))
								{
									$iaDb->update(array("value" => iaSanitize::html($_POST['description'][$code])),
										"`key` = '{$lang_key}' AND `code`='{$code}'");
								}
								else
								{
									iaLanguage::addPhrase(
										$lang_key,
										iaSanitize::html($_POST['description'][$code]),
										$code,
										false,
										iaLanguage::CATEGORY_COMMON
									);
								}
							}
						}
						$iaDb->resetTable();

						// update group values
						unset($group['titles']);
						unset($group['description']);

						$iaDb->update($group);

						$messages = iaLanguage::get('saved');
					}
					else
					{
						foreach ($languages as $code => $l)
						{
							iaLanguage::addPhrase(
								'fieldgroup_' . $group['name'],
								iaSanitize::html($_POST['titles'][$code]),
								$code
							);

							if ($_POST['description'][$code])
							{
								$lang_key = "fieldgroup_description_{$group['item']}_{$name}";
								iaLanguage::addPhrase(
									$lang_key,
									iaSanitize::html($_POST['description'][$code]),
									$code
								);
							}
						}

						if (!$iaDb->exists("`name` = '{$group['name']}' AND `item` = '{$group['item']}'") && in_array($group['item'], $items))
						{
							$group['order'] = $iaDb->getMaxOrder(iaField::getTableGroups()) + 1;
							$iaDb->insert($group);
						}

						$messages = iaLanguage::get('fieldgroup_added');

						$iaCore->factory('cache')->clearAll();
					}

					$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

					if (isset($_POST['goto']))
					{
						switch($_POST['goto'])
						{
							case 'add':
								iaUtil::go_to(IA_ADMIN_URL . 'fields/group/add/');
								break;
							case 'list':
								iaUtil::go_to(IA_ADMIN_URL . 'fields/group/');
						}
					}
					else
					{
						iaUtil::go_to(IA_ADMIN_URL . 'fields/group/');
					}
				}

				$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
			}

			$iaView->assign('group', $group);

			$iaView->display('fieldgroups');

			break;

		default:
			$iaView->grid('admin/fieldgroups');
	}
}

$iaDb->resetTable();