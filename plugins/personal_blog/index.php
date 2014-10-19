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

$iaDb->setTable('blog_entries');

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (isset($iaCore->requestPath[0]))
	{
		$id = (int)$iaCore->requestPath[0];

		if (!$id)
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		$blogEntry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, 'id = :id AND `status` = :status', array('id' => $id, 'status' => iaCore::STATUS_ACTIVE));

		if (empty($blogEntry))
		{
			return iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		iaBreadcrumb::toEnd($blogEntry['title'], IA_SELF);

		$iaView->assign('blog_entry', $blogEntry);

		$iaView->title(iaSanitize::tags($blogEntry['title']));
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
		$rows = $iaDb->all('SQL_CALC_FOUND_ROWS `id`, `title`, `date_added`, `body`, `alias`, `image`', $stmt . ' ' . $order, $pagination['start'], $pagination['limit']);
		$pagination['total'] = $iaDb->foundRows();

		$iaView->assign('blog_entries', $rows);
		$iaView->assign('pagination', $pagination);
	}

	$pageActions[] = array(
		'icon' => 'icon-rss',
		'title' => '',
		'url' => IA_URL . 'blog.xml',
		'classes' => 'btn-warning'
	);

	$iaView->assign('page_actions', $pageActions);

	$iaView->display('index');
}

if (iaView::REQUEST_XML == $iaView->getRequestType())
{
	$output = array(
		'title' => $iaCore->get('site') . ' :: ' . $iaView->title(),
		'description' => '',
		'url' => IA_URL . 'blog',
		'item' => array()
	);

	$listings = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, null, 0, 20);
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