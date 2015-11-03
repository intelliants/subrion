<?php
//##copyright##

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

			$tags = (iaCore::ACTION_ADD == $pageAction) ? '' : $iaBlog->getTags($id);

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

			if (isset($iaCore->requestPath[0]))
			{
				$id = (int)$iaCore->requestPath[0];

				if (!$id)
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$sql =
					'SELECT b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname`, b.`member_id` ' .
					'FROM `:prefix:table_blog_entries` b ' .
					'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
					'WHERE b.`id` = :id AND b.`status` = \':status\' ';

				$sql = iaDb::printf($sql, array(
					'prefix' => $iaDb->prefix,
					'table_blog_entries' => $iaBlog::getTable(),
					'table_members' => iaUsers::getTable(),
					'id' => iaSanitize::sql($id),
					'status' => iaCore::STATUS_ACTIVE
				));

				$blogEntry = $iaDb->getRow($sql);


				$sql =
					'SELECT DISTINCT bt.`title`, bt.`alias` ' .
					'FROM `:prefix:table_blog_tags` bt ' .
					'LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) ' .
					'WHERE bet.`blog_id` = :id';

				$sql = iaDb::printf($sql, array(
					'prefix' => $iaDb->prefix,
					'table_blog_entries_tags' => 'blog_entries_tags',
					'table_blog_tags' => 'blog_tags',
					'id' => iaSanitize::sql($id)
				));
				$blogTags = $iaDb->getAll($sql);

				if (empty($blogEntry))
				{
					return iaView::errorPage(iaView::ERROR_NOT_FOUND);
				}

				$title = iaSanitize::tags($blogEntry['title']);
				iaBreadcrumb::toEnd($title);

				$iaView->title($title);

				// add open graph data
				$openGraph = array(
					'title' => $title,
					'url' => IA_SELF,
					'description' => $blogEntry['body']
				);
				empty($blogEntry['image']) || $openGraph['image'] = IA_CLEAR_URL . 'uploads/' . $blogEntry['image'];

				$iaView->set('og', $openGraph);

				$iaView->assign('tags', $blogTags);
				$iaView->assign('blog_entry', $blogEntry);

				if ($iaAcl->isAccessible(iaBlog::PAGE_NAME, iaCore::ACTION_EDIT) && iaUsers::hasIdentity()
					&& iaUsers::getIdentity()->id == $blogEntry['member_id'])
				{
					$pageActions = array(
						array(
							'icon' => 'icon-pencil',
							'title' => iaLanguage::get('edit_blog_entry'),
							'url' => $baseUrl . 'edit/' . $id . '/',
							'classes' => 'btn-info'
						),
						array(
							'icon' => 'icon-remove',
							'title' => iaLanguage::get('delete'),
							'url' => $baseUrl . 'delete/' . $id . '/',
							'classes' => 'btn-danger'
						)
					);

					$iaView->set('actions', $pageActions);
				}
			}
			else
			{
				$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
				$page = ($page < 1) ? 1 : $page;

				$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog');

				$pagination = array(
					'start' => ($page - 1) * $iaCore->get('blog_number'),
					'limit' => (int)$iaCore->get('blog_number'),
					'template' => $pageUrl . '?page={page}'
				);

				$order = ('date' == $iaCore->get('blog_order')) ? 'ORDER BY `date_added` DESC' : 'ORDER BY `title` ASC';

				$stmt = '`status` = :status AND `lang` = :language';
				$iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'language' => $iaView->language));

				$sql =
					'SELECT SQL_CALC_FOUND_ROWS ' .
					'b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname` ' .
					'FROM `:prefix:table_blog_entries` b ' .
					'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
					'WHERE b.' . $stmt . $order . ' LIMIT :start, :limit';

				$sql = iaDb::printf($sql, array(
					'prefix' => $iaDb->prefix,
					'table_blog_entries' => $iaBlog::getTable(),
					'table_members' => iaUsers::getTable(),
					'start' => $pagination['start'],
					'limit' => $pagination['limit']
				));
				$rows = $iaDb->getAll($sql);

				$pagination['total'] = $iaDb->foundRows();

				$sql =
					'SELECT bt.`title`, bt.`alias`, bet.`blog_id` ' .
					'FROM `:prefix:table_blog_tags` bt ' .
					'LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) ' .
					'ORDER BY bt.`title`';

				$sql = iaDb::printf($sql, array(
					'prefix' => $iaDb->prefix,
					'table_blog_entries_tags' => 'blog_entries_tags',
					'table_blog_tags' => 'blog_tags'
				));
				$blogTags = $iaDb->getAll($sql);

				$iaView->assign('tags', $blogTags);
				$iaView->assign('blog_entries', $rows);
				$iaView->assign('pagination', $pagination);
			}

			$pageActions[] = array(
				'icon' => 'rss',
				'title' => '',
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

	$listings = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`lang`= '" . $iaView->language . "'", 0, 20);
	$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog');

	foreach ($listings as $entry)
	{
		$output['item'][] = array(
			'title' => $entry['title'],
			'link' => $pageUrl . $entry['id'] . '-' . $entry['alias'],
			'pubDate' => date('D, d M Y H:i:s T', strtotime($entry['date_modified'])),
			'description' => iaSanitize::tags($entry['body'])
		);
	}

	$iaView->assign('channel', $output);
}

$iaDb->resetTable();