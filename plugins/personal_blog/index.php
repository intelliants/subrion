<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$iaDb->setTable('blog_entries');

	if (isset($iaCore->requestPath[0]))
	{
		$id = (int)$iaCore->requestPath[0];

		if (!$id)
		{
			iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		$blogEntry = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, 'id = :id AND `status` = :status', array('id' => $id, 'status' => iaCore::STATUS_ACTIVE));

		if (empty($blogEntry))
		{
			iaView::errorPage(iaView::ERROR_NOT_FOUND);
		}

		iaBreadcrumb::toEnd($blogEntry['title'], IA_SELF);

		$iaView->assign('blog_entry', $blogEntry);

		$iaView->title(iaSanitize::tags($blogEntry['title']));
	}
	else
	{
		$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
		$page = ($page < 1) ? 1 : $page;

		$pagination = array(
			'start' => ($page - 1) * $iaCore->get('blog_number'),
			'limit' => (int)$iaCore->get('blog_number'),
			'template' => $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog') . '?page={page}'
		);

		$order = ('date' == $iaCore->get('blog_order')) ? 'ORDER BY `date` DESC' : 'ORDER BY `title` ASC';

		$stmt = '`status` = :status AND `lang` = :language';
		$iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'language' => $iaView->language));
		$rows = $iaDb->all('SQL_CALC_FOUND_ROWS `id`, `title`, `date`, `body`, `alias`, `image`', $stmt . ' ' . $order, $pagination['start'], $pagination['limit']);
		$pagination['total'] = $iaDb->foundRows();

		$iaView->assign('blog_entries', $rows);
		$iaView->assign('pagination', $pagination);
	}

	$iaView->display('index');

	$iaDb->resetTable();
}