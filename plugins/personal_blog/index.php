<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2015 Intelliants, LLC <http://www.intelliants.com>
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

$iaBlog = $iaCore->factoryPlugin(IA_CURRENT_PLUGIN, iaCore::FRONT, 'blog');

$baseUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog');

$iaDb->setTable($iaBlog::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($iaCore->requestPath[0]) && 'alias' == $iaCore->requestPath[0])
	{
		$url = $baseUrl . $iaDb->getNextId() . '-' . $iaBlog->titleAlias($_POST['title']);

		$iaView->output(array('url' => $url));
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	switch ($pageAction)
	{
		case iaCore::ACTION_ADD:
		case iaCore::ACTION_EDIT:
			$iaView->title(iaLanguage::get($pageAction . '_blog_entry'));
			$iaView->display('manage');

			if (!iaUsers::hasIdentity())
			{
				return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
			}

			if (iaCore::ACTION_ADD == $pageAction)
			{
				$entry = array(
					'lang' => $iaView->language,
					'date_added' => date(iaDb::DATETIME_FORMAT),
					'status' => iaCore::STATUS_ACTIVE,
					'member_id' => iaUsers::getIdentity()->id,
					'title' => '',
					'body' => ''
				);
			}
			else
			{
				if (1 != count($iaCore->requestPath))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$id = (int)$iaCore->requestPath[0];
				$entry = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

				if (!$entry)
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				if ($entry['member_id'] != iaUsers::getIdentity()->id)
				{
					return iaView::errorPage(iaView::ERROR_FORBIDDEN);
				}
			}

			if (isset($_POST['data-blog-entry']))
			{
				$result = false;
				$messages = array();

				iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

				$entry['title'] = $_POST['title'];
				utf8_is_valid($entry['title']) || $entry['title'] = utf8_bad_replace($entry['title']);

				if (empty($entry['title']))
				{
					$messages[] = iaLanguage::get('title_is_empty');
				}

				$entry['body'] = $_POST['body'];
				utf8_is_valid($entry['body']) || $entry['body'] = utf8_bad_replace($entry['body']);

				if (empty($entry['body']))
				{
					$messages[] = iaLanguage::getf('field_is_empty', array('field' => iaLanguage::get('body')));
				}

				$entry['alias'] = $iaBlog->titleAlias(empty($_POST['alias']) ? $entry['title'] : $_POST['alias']);

				if (!$messages)
				{
					if (isset($_FILES['image']['tmp_name']) && $_FILES['image']['tmp_name'])
					{
						$iaPicture = $iaCore->factory('picture');

						$info = array(
							'image_width' => 1000,
							'image_height' => 750,
							'thumb_width' => 250,
							'thumb_height' => 250,
							'resize_mode' => iaPicture::CROP
						);

						if ($image = $iaPicture->processImage($_FILES['image'], iaUtil::getAccountDir(), iaUtil::generateToken(), $info))
						{
							if ($entry['image']) // it has an already assigned image
							{
								$iaPicture = $iaCore->factory('picture');
								$iaPicture->delete($entry['image']);
							}

							$entry['image'] = $image;
						}
					}

					$result = (iaCore::ACTION_ADD == $pageAction)
						? $iaBlog->insert($entry)
						: $iaBlog->update($entry, $id);

					if ($result)
					{
						$id = (iaCore::ACTION_ADD == $pageAction) ? $result : $id;

						$iaBlog->saveTags($id, $_POST['tags']);

						$iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
						iaUtil::go_to($baseUrl . sprintf('%d-%s', $id, $entry['alias']));
					}
					else
					{
						$messages[] = iaLanguage::get('db_error');
					}
				}

				$iaView->setMessages($messages);
			}

			$tags = (iaCore::ACTION_ADD == $pageAction) ? '' : $iaBlog->getTagsString($id);

			$iaView->assign('item', $entry);
			$iaView->assign('tags', $tags);

			break;

		case iaCore::ACTION_DELETE:
			if (1 != count($iaCore->requestPath))
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}

			$id = (int)$iaCore->requestPath[0];
			$entry = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

			if (!$entry)
			{
				return iaView::errorPage(iaView::ERROR_NOT_FOUND);
			}

			$result = $iaBlog->delete($id);

			$iaView->setMessages(iaLanguage::get($result ? 'deleted' : 'db_error'), $result ? iaView::SUCCESS : iaView::ERROR);

			iaUtil::go_to($baseUrl);

			break;

		default:
			$iaView->display('index');

			$pageActions = array();

			if (isset($iaCore->requestPath[0]))
			{
				$id = (int)$iaCore->requestPath[0];

				if (!$id)
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}
				
				$entry = $iaBlog->getById($id);

				if (empty($entry))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$title = iaSanitize::tags($entry['title']);
				iaBreadcrumb::toEnd($title);
				$iaView->title($title);

				// add open graph data
				$openGraph = array(
					'title' => $title,
					'url' => IA_SELF,
					'description' => $entry['body']
				);
				empty($entry['image']) || $openGraph['image'] = IA_CLEAR_URL . 'uploads/' . $entry['image'];

				$iaView->set('og', $openGraph);

				$iaView->assign('tags', $iaBlog->getTags($id));
				$iaView->assign('blog_entry', $entry);

				if ($iaAcl->isAccessible(iaBlog::PAGE_NAME, iaCore::ACTION_EDIT) && iaUsers::hasIdentity()
					&& iaUsers::getIdentity()->id == $entry['member_id'])
				{
					$pageActions[] = array(
						'icon' => 'pencil',
						'title' => iaLanguage::get('edit_blog_entry'),
						'url' => $baseUrl . 'edit/' . $id . '/',
						'classes' => 'btn-info'
					);
					$pageActions[] = array(
						'icon' => 'remove',
						'title' => iaLanguage::get('delete'),
						'url' => $baseUrl . 'delete/' . $id . '/',
						'classes' => 'btn-danger'
					);
				}
			}
			else
			{
				$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
				$page = ($page < 1) ? 1 : $page;

				$pagination = array(
					'start' => ($page - 1) * $iaCore->get('blog_number'),
					'limit' => (int)$iaCore->get('blog_number'),
					'template' => $baseUrl . '?page={page}'
				);

				$entries = $iaBlog->get($pagination['start'], $pagination['limit']);
				$pagination['total'] = $iaDb->foundRows();

				$iaView->assign('tags', $iaBlog->getAllTags());
				$iaView->assign('blog_entries', $entries);
				$iaView->assign('pagination', $pagination);
			}

			if ($iaAcl->isAccessible('blog', iaCore::ACTION_ADD))
			{
				$pageActions[] = array(
					'icon' => 'plus',
					'title' => iaLanguage::get('add_blog_entry'),
					'url' => $baseUrl . 'add/',
					'classes' => 'btn-success'
				);
			}

			$pageActions[] = array(
				'icon' => 'rss',
				'title' => null,
				'url' => IA_URL . 'blog.xml',
				'classes' => 'btn-warning'
			);

			$iaView->set('actions', $pageActions);
	}
}

if (iaView::REQUEST_XML == $iaView->getRequestType())
{
	$output = array(
		'title' => $iaCore->get('site') . ' :: ' . $iaView->title(),
		'description' => '',
		'url' => IA_URL . 'blog',
		'item' => array()
	);

	$entries = $iaBlog->get(0, 20);

	foreach ($entries as $entry)
	{
		$output['item'][] = array(
			'title' => $entry['title'],
			'link' => $baseUrl . $entry['id'] . '-' . $entry['alias'],
			'pubDate' => date('D, d M Y H:i:s T', strtotime($entry['date_modified'])),
			'description' => iaSanitize::tags($entry['body'])
		);
	}

	$iaView->assign('channel', $output);
}

$iaDb->resetTable();