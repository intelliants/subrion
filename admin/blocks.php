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

$iaBlock = $iaCore->factory('block', iaCore::ADMIN);

$iaDb->setTable(iaBlock::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$output = $iaBlock->gridRead($_GET,
				array('title', 'contents', 'position', 'extras', 'type', 'status', 'order', 'multi_language', 'delete' => 'removable'),
				array('status' => 'equal', 'title' => 'like', 'type' => 'equal', 'position' => 'equal'),
				array("`type` != 'menu'")
			);

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaBlock->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaBlock->gridDelete($_POST);
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$blockData = array();

	if (isset($_POST['data-block']))
	{
		$iaCore->startHook('adminAddBlockValidation');

		iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

		$error = false;

		// validate block name
		if (!empty($_POST['name']) && iaCore::ACTION_ADD == $_POST['do'])
		{
			$blockData['name'] = strtolower(iaSanitize::paranoid($_POST['name']));
			if (!iaValidate::isAlphaNumericValid($blockData['name']))
			{
				$error = true;
				$messages[] = iaLanguage::get('error_block_name');
			}
			elseif ($iaBlock->iaDb->exists('`name` = :name', array('name' => $blockData['name'])))
			{
				$error = true;
				$messages[] = iaLanguage::get('error_block_name_duplicate');
			}
		}
		else
		{
			$blockData['name'] = 'block_' . mt_rand(1000, 9999);
		}

		$blockData['classname'] = $_POST['classname'];
		$blockData['position'] = $_POST['position'];
		$blockData['type'] = $_POST['type'];
		$blockData['status'] = isset($_POST['status']) ? (in_array($_POST['status'], array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)) ? $_POST['status'] : iaCore::STATUS_ACTIVE) : iaCore::STATUS_ACTIVE;

		if (isset($_POST['header']))
		{
			$blockData['header'] = (int)$_POST['header'];
		}
		if (isset($_POST['collapsible']))
		{
			$blockData['collapsible'] = (int)$_POST['collapsible'];
		}
		if (isset($_POST['multi_language']))
		{
			$blockData['multi_language'] = (int)$_POST['multi_language'];
		}
		if (isset($_POST['sticky']))
		{
			$blockData['sticky'] = (int)$_POST['sticky'];
			if (!$blockData['sticky'])
			{
				$blockData['visible_on_pages'] = isset($_POST['visible_on_pages']) ? $_POST['visible_on_pages'] : '';
			}
		}
		if (isset($_POST['external']))
		{
			$blockData['external'] = (int)$_POST['external'];
		}
		if (isset($_POST['filename']))
		{
			$blockData['filename'] = $_POST['filename'];
		}

		$blockData['subpages'] = isset($_POST['subpages']) ? serialize($_POST['subpages']) : '';

		if ($blockData['multi_language'])
		{
			$blockData['title'] = $_POST['multi_title'];

			if (empty($blockData['title']))
			{
				$error = true;
				$messages[] = iaLanguage::get('title_is_empty');
			}
			elseif (!utf8_is_valid($blockData['title']))
			{
				$blockData['title'] = utf8_bad_replace($blockData['title']);
			}

			$blockData['contents'] = $_POST['multi_contents'];

			if (iaBlock::TYPE_MENU != $blockData['type'])
			{
				if(empty($blockData['contents']) && 0 == $blockData['external'])
				{
					$error = true;
					$messages[] = iaLanguage::get('error_contents');
				}
				elseif (empty($blockData['filename']) && 1 == $blockData['external'])
				{
					$error = true;
					$messages[] = iaLanguage::get('error_filename');
				}
			}

			if (iaBlock::TYPE_HTML != $blockData['type'])
			{
				if (!utf8_is_valid($blockData['contents']))
				{
					$blockData['contents'] = utf8_bad_replace($blockData['contents']);
				}
			}
		}
		else
		{
			if (isset($_POST['block_languages']) && $_POST['block_languages'])
			{
				$blockData['block_languages'] = $_POST['block_languages'];
				$blockData['title'] = $_POST['title'];
				$blockData['contents'] = $_POST['contents'];

				foreach ($blockData['block_languages'] as $block_language)
				{
					if (isset($blockData['title'][$block_language]))
					{
						if (empty($blockData['title'][$block_language]))
						{
							$error = true;
							$messages[] = iaLanguage::getf('error_lang_title', array('lang' => $iaCore->languages[$block_language]));
						}
						elseif (!utf8_is_valid($blockData['title'][$block_language]))
						{
							$blockData['title'][$block_language] = utf8_bad_replace($blockData['title'][$block_language]);
						}
					}

					if (isset($blockData['contents'][$block_language]))
					{
						if (empty($blockData['contents'][$block_language]))
						{
							$error = true;
							$messages[] = iaLanguage::getf('error_lang_contents', array('lang' => $iaCore->languages[$block_language]));
						}

						if (iaBlock::TYPE_HTML != $blockData['type'])
						{
							if (!utf8_is_valid($blockData['contents'][$block_language]))
							{
								$blockData['contents'][$block_language] = utf8_bad_replace($blockData['contents'][$block_language]);
							}
						}
					}
				}
			}
			else
			{
				$error = true;
				$messages[] = iaLanguage::get('block_languages_empty');
			}
		}

		$iaCore->startHook('phpAdminBlocksEdit', array('block' => &$blockData));

		if (!$error)
		{
			if (iaCore::ACTION_EDIT == $_POST['do'])
			{
				unset($blockData['name']);
				$id = (int)$_POST['id'];
				$result = $iaBlock->update($blockData, $id);
				if ($result)
				{
					$messages[] = iaLanguage::get('saved');
					$result = $_POST['id'];
				}
				else
				{
					$error = true;
					$messages[] = $iaBlock->getMessage();
				}
			}
			else
			{
				$id = $iaBlock->insert($blockData);

				if ($id)
				{
					$messages[] = iaLanguage::get('block_created');
					$iaCore->factory('log')->write(iaLog::ACTION_CREATE, array('item' => 'block', 'name' => $blockData['title'], 'id' => $id));
				}
				else
				{
					$error = true;
					$messages[] = $iaBlock->getMessage();
				}
			}
			$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);
			if (isset($_POST['goto']))
			{
				$url = IA_ADMIN_URL . 'blocks/';
				$goto = array(
					'add'	=> $url . 'add/',
					'list'	=> $url,
					'stay'	=> $url . 'edit/?id=' . $id,
				);
				iaUtil::post_goto($goto);
			}
			else
			{
				iaUtil::go_to(IA_ADMIN_URL . 'blocks/edit/?id=' . $id);
			}
		}
		else
		{
			$iaView->setMessages($messages, iaView::ERROR);
		}
	}

	switch ($pageAction)
	{
		case iaCore::ACTION_READ:
			$iaView->grid('admin/blocks');
			break;

		case iaCore::ACTION_ADD:
		case iaCore::ACTION_EDIT:
			$visibleOn = array();
			$menuPages = array();

			if (iaCore::ACTION_EDIT == $pageAction)
			{
				$title = iaLanguage::get('edit_block');
				$blockData = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($_GET['id']));
				if (empty($blockData))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				if (0 == $blockData['multi_language'])
				{
					$iaDb->setTable(iaLanguage::getTable());

					$blockData['block_languages'] = $iaDb->onefield('code', "`key` = 'block_content_blc{$blockData['id']}'");

					$blockData['title'] = $iaDb->keyvalue(array('code', 'value'), "`key` = 'block_title_blc{$blockData['id']}'");
					$blockData['contents'] = $iaDb->keyvalue(array('code', 'value'), "`key` = 'block_content_blc{$blockData['id']}'");

					$iaDb->resetTable();
				}

				$menuPages = $iaDb->onefield('`name`', "FIND_IN_SET('{$blockData['name']}', `menus`)", null, null, 'pages');
				if (!$blockData['sticky'])
				{
					$visibleOn = $iaDb->onefield('page_name', '`block_id` = ' . $blockData['id'], null, null, iaBlock::getPagesTable());
				}
			}
			elseif (iaCore::ACTION_ADD == $pageAction)
			{
				$title = iaLanguage::get('add_block');
				$visibleOn = isset($_POST['visible_on_pages']) ? $_POST['visible_on_pages'] : array();
				$menuPages = array();
			}
			$iaDb->resetTable();

			iaBreadcrumb::preEnd(iaLanguage::get('blocks'), IA_ADMIN_URL . 'blocks/');

			$sql =
				'SELECT DISTINCTROW p.*, IF(t.`value` IS NULL, p.`name`, t.`value`) `title` ' .
				"FROM `{$iaCore->iaDb->prefix}pages` p " .
					"LEFT JOIN `{$iaCore->iaDb->prefix}language` t " .
						"ON `key` = CONCAT('page_title_', p.`name`) AND t.`code` = '" . $iaView->language . "' " .
				"WHERE p.`status` = 'active' AND p.`service` = 0 " .
				"ORDER BY t.`value`";
			$pages = $iaDb->getAll($sql);
			$groupList = $iaDb->onefield('`group`', '1 = 1 GROUP BY `group`', null, null, 'pages');

			$iaDb->setTable('admin_pages_groups');
			$array = $iaDb->all(array('id', 'name', 'title'));
			$pagesGroups = array();
			foreach ($array as $row)
			{
				if (in_array($row['id'], $groupList))
				{
					$pagesGroups[$row['id']] = $row;
				}
			}
			$iaDb->resetTable();

			$iaView->assign('menuPages', $menuPages);
			$iaView->assign('visibleOn', $visibleOn);
			$iaView->assign('types', $iaBlock->getTypes());
			$iaView->assign('positions', $iaBlock->getPositions());
			$iaView->assign('pages_group', $pagesGroups);
			$iaView->assign('pages', $pages);

			isset($blockData['type']) || $blockData['type'] = iaBlock::TYPE_PLAIN;
			isset($blockData['header']) || $blockData['header'] = true;
			isset($blockData['collapsible']) || $blockData['collapsible'] = true;
			isset($blockData['multi_language']) || $blockData['multi_language'] = true;
			isset($blockData['sticky']) || $blockData['sticky'] = true;
			isset($blockData['external']) || $blockData['external'] = false;
			empty($blockData['subpages']) || $blockData['subpages'] = unserialize($blockData['subpages']);

			$iaView->assign('block', $blockData);

			$iaView->display('blocks');
	}
}

$iaDb->resetTable();