<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (isset($iaCore->requestPath[0]))
	{
		$tag = $iaCore->requestPath[0];

		$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
		$page = ($page < 1) ? 1 : $page;

		$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('tag');

		$pagination = array(
			'start' => ($page - 1) * $iaCore->get('blog_number'),
			'limit' => (int)$iaCore->get('blog_number'),
			'template' => $pageUrl . '?page={page}'
		);

		$sql =
			'SELECT SQL_CALC_FOUND_ROWS ' .
			'b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname`, bt.`title` `tag_title`' .
			'FROM `:prefix:table_blog_entries` b ' .
			'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
			'LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (b.`id` = bet.`blog_id`) ' .
			'LEFT JOIN `:prefix:table_blog_tags` bt ON (bt.`id` = bet.`tag_id`) ' .
			'WHERE bt.`alias` = \':tag\' AND bet.`tag_id` = bt.`id` ' .
			'AND b.`status` = \':status\' LIMIT :start, :limit';

		$sql = iaDb::printf($sql, array(
			'prefix' => $iaDb->prefix,
			'table_blog_entries' => 'blog_entries',
			'table_blog_entries_tags' => 'blog_entries_tags',
			'table_blog_tags' => 'blog_tags',
			'table_members' => 'members',
			'tag' => iaSanitize::sql($tag),
			'status' => iaCore::STATUS_ACTIVE,
			'start' => $pagination['start'],
			'limit' => $pagination['limit']
		));

		$blogEntries = $iaDb->getAll($sql);

		$pagination['total'] = $iaDb->foundRows();

		if (empty($blogEntries)) {
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}
		$title = '#' . $blogEntries[0]['tag_title'];
		iaBreadcrumb::toEnd($title);

		$iaView->title($title);

		$iaView->display('tag');

		$iaView->assign('pagination', $pagination);
		$iaView->assign('blog_entries', $blogEntries);
	}

	else {
		$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
		$page = ($page < 1) ? 1 : $page;

		$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('tag');

		$pagination = array(
			'start' => ($page - 1) * $iaCore->get('tag_number'),
			'limit' => (int)$iaCore->get('tag_number'),
			'template' => $pageUrl . '?page={page}'
		);

		$prefix = $iaDb->prefix;
		$sql =
			'SELECT DISTINCT SQL_CALC_FOUND_ROWS bt.`id`, bt.`title`, bt.`alias` ' .
			'FROM `:prefix:table_blog_tags` bt ' .
			'LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) ' .
			'LEFT JOIN `:prefix:table_blog_entries` b ON (b.`id` = bet.`blog_id`) ' .
			'WHERE b.`status` = \':status\' ' .
			'GROUP BY bt.`id` ' .
			'ORDER BY bt.`title` ' .
			'LIMIT :start, :limit';

		$sql = iaDb::printf($sql, array(
			'prefix' => $iaDb->prefix,
			'table_blog_entries' => 'blog_entries',
			'table_blog_entries_tags' => 'blog_entries_tags',
			'table_blog_tags' => 'blog_tags',
			'status' => iaCore::STATUS_ACTIVE,
			'start' => $pagination['start'],
			'limit' => $pagination['limit']
		));

		$tags = $iaDb->getAll($sql);
		$pagination['total'] = $iaDb->foundRows();

		$iaView->assign('tags', $tags);
		$iaView->assign('pagination', $pagination);
		$iaView->display('tag');
	}
}
