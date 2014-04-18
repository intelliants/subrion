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

$iaBlog = $iaCore->factoryPlugin('personal_blog', iaCore::ADMIN, 'blog');

$iaDb->setTable(iaBlog::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_READ:

			switch ($_GET['get'])
			{
				case 'alias':
					// url part is hardcoded since the main page is marked as non-editable,
					// so the alias couldn't be changed by user/admin
					$output['url'] = IA_URL . 'blog' . IA_URL_DELIMITER . $iaDb->getNextId() . '-' . $iaBlog->titleAlias($_GET['title']);

					break;

				default:
					$params = array();
					if (isset($_GET['text']) && $_GET['text'])
					{
						$stmt = '(`title` LIKE :text OR `body` LIKE :text)';
						$iaDb->bind($stmt, array('text' => '%' . $_GET['text'] . '%'));

						$params[] = $stmt;
					}

					$output = $iaBlog->gridRead($_GET,
						array('title', 'alias', 'date', 'status'),
						array('status' => 'equal'),
						$params
					);
			}

			break;

		case iaCore::ACTION_EDIT:
			$output = $iaBlog->gridUpdate($_POST);

			break;

		case iaCore::ACTION_DELETE:
			$output = $iaBlog->gridDelete($_POST, 'blog_entry_deleted');
	}

	$iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (iaCore::ACTION_ADD == $pageAction || iaCore::ACTION_EDIT == $pageAction)
	{
		$blogEntry = array(
			'lang' => $iaView->language,
			'status' => iaCore::STATUS_ACTIVE
		);

		if (iaCore::ACTION_EDIT == $pageAction)
		{
			if (!isset($_GET['id']))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}

			$id = (int)$_GET['id'];
			$blogEntry = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));
			if (empty($blogEntry))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}
		}

		$iaCore->factory('util');

		$blogEntry = array(
			'id' => isset($id) ? $id : 0,
			'lang' => iaUtil::checkPostParam('lang', $blogEntry),
			'title' => iaUtil::checkPostParam('title', $blogEntry),
			'status' => iaUtil::checkPostParam('status', $blogEntry),
			'alias' => iaUtil::checkPostParam('alias', $blogEntry),
			'body' => iaUtil::checkPostParam('body', $blogEntry),
			'image' => iaUtil::checkPostParam('image', $blogEntry),
			'date' => iaUtil::checkPostParam('date', $blogEntry)
		);

		if (empty($blogEntry['date']))
		{
			$blogEntry['date'] = date(iaDb::DATETIME_FORMAT);
		}

		if (isset($_POST['save']))
		{
			iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

			$error = false;
			$messages = array();

			$blogEntry['status'] = in_array($blogEntry['status'], array(iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE)) ? $blogEntry['status'] : iaCore::STATUS_INACTIVE;

			if (!array_key_exists($blogEntry['lang'], $iaCore->languages))
			{
				$blogEntry['lang'] = $iaView->language;
			}

			if (!utf8_is_valid($blogEntry['title']))
			{
				$blogEntry['title'] = utf8_bad_replace($blogEntry['title']);
			}

			if (!utf8_is_valid($blogEntry['body']))
			{
				$blogEntry['body'] = utf8_bad_replace($blogEntry['body']);
			}

			if (empty($blogEntry['title']))
			{
				$error = true;
				$messages[] = iaLanguage::get('title_is_empty');
			}

			if (empty($blogEntry['body']))
			{
				$error = true;
				$messages[] = iaLanguage::get('body_is_empty');
			}

			$blogEntry['alias'] = $iaBlog->titleAlias(empty($blogEntry['alias']) ? $blogEntry['title'] : $blogEntry['alias']);

			if (!$error)
			{
				if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'])
				{
					$iaPicture = $iaCore->factory('picture');

					$path = iaUtil::getAccountDir();
					$file = $_FILES['image'];
					$token = iaUtil::generateToken();
					$info = array(
						'image_width' => 1000,
						'image_height' => 750,
						'thumb_width' => 250,
						'thumb_height' => 250,
						'resize_mode' => iaPicture::CROP
					);

					$blogEntry['image'] = $iaPicture->processImage($file, $path, $token, $info);
				}

				if (iaCore::ACTION_EDIT == $pageAction)
				{
					$blogEntry['id'] = (int)$_GET['id'];
					$error = !$iaDb->update($blogEntry);

					if (!$error)
					{
						$messages[] = iaLanguage::get('saved');
						$iaCore->factory('log')->write(iaLog::ACTION_UPDATE, array('module' => 'blog', 'item' => 'blog', 'name' => $blogEntry['title'], 'id' => $blogEntry['id']));
					}
				}
				else
				{
					$blogEntry['id'] = $iaDb->insert($blogEntry);
					$error = empty($blogEntry['id']);

					if (!$error)
					{
						$messages[] = iaLanguage::get('blog_entry_added');
						$iaCore->factory('log')->write(iaLog::ACTION_CREATE, array('module' => 'blog', 'item' => 'blog', 'name' => $blogEntry['title'], 'id' => $blogEntry['id']));
					}
				}

				if ($error)
				{
					$messages[] = iaLanguage::get('db_error');
				}

				$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));

				if (isset($_POST['goto']))
				{
					$url = IA_ADMIN_URL . 'blog/';
					iaUtil::post_goto(array(
						'add' => $url . 'add/',
						'list' => $url,
						'stay' => $url . 'edit/?id=' . $blogEntry['id'],
					));
				}
				else
				{
					iaUtil::go_to(IA_ADMIN_URL . 'blog/edit/?id=' . $blogEntry['id']);
				}
			}
			else
			{
				$iaView->setMessages($messages, ($error ? iaView::ERROR : iaView::SUCCESS));
			}
		}

		$iaView->assign('entry', $blogEntry);

		$iaView->display('manage');
	}
	else
	{
		$iaView->grid('_IA_URL_plugins/personal_blog/js/admin/index');
	}
}

$iaDb->resetTable();