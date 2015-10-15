<?php
//##copyright##

$iaDb->setTable('blog_entries');

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if (!isset($iaCore->requestPath[1]))
	{
		iaBreadcrumb::preEnd('Blog', 'blog');

		if ($dates = $iaDb->all(iaDb::ALL_COLUMNS_SELECTION, "`status` = 'active' ORDER BY `date_added`", 0, null))
		{
			$years = array();
			$months = array();

			$months['01']['name'] = 'month1';
			$months['02']['name'] = 'month2';
			$months['03']['name'] = 'month3';
			$months['04']['name'] = 'month4';
			$months['05']['name'] = 'month5';
			$months['06']['name'] = 'month6';
			$months['07']['name'] = 'month7';
			$months['08']['name'] = 'month8';
			$months['09']['name'] = 'month9';
			$months['10']['name'] = 'month10';
			$months['11']['name'] = 'month11';
			$months['12']['name'] = 'month12';

			foreach ($dates as $key => $date)
			{
				$fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
				$fullDate = explode('-', $fullDate);
				$years[$fullDate[0]] = array();
			}

			foreach ($years as $y => $year)
			{
				$years[$y]['months'] = $months;

				foreach ($months as $j => $t)
				{
					foreach ($dates as $key => $date)
					{
						$fullDate = substr($date['date_added'], 0, strpos($date['date_added'], ' '));
						$fullDate = explode('-', $fullDate);

						if ($fullDate[1] == $j && $fullDate[0] == $y)
						{
							if (isset($iaCore->requestPath[0]) && $iaCore->requestPath[0] == $y)
							{
								$months[$j]['blogs'] = true;
								$show['months'] = true;
							}
							elseif (!isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1]))
							{
								$years[$y]['months'][$j]['blogs'] = true;
								$show['years'] = true;
							}
						}
					}
				}
			}

			if (isset($iaCore->requestPath[0]) && !isset($iaCore->requestPath[1]))
			{
				iaBreadcrumb::preEnd('Blog Archive', 'blog/date');
				iaBreadcrumb::replaceEnd($iaCore->requestPath[0], IA_SELF);

				$iaView->title($iaCore->requestPath[0]);
			}

			$iaView->assign('show', $show);
			$iaView->assign('years', $years);
			$iaView->assign('months', $months);
		}
		else
		{
			$iaView->setMessages(iaLanguage::get('no_blog_entries'), iaView::ALERT);
		}
	}
	elseif (isset($iaCore->requestPath[0]) && isset($iaCore->requestPath[1]))
	{
		$page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
		$page = ($page < 1) ? 1 : $page;

		$pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog_date');

		$pagination = array(
			'start' => ($page - 1) * $iaCore->get('blog_number'),
			'limit' => (int)$iaCore->get('blog_number'),
			'template' => $pageUrl . '?page={page}'
		);

		$stmt = "`status` = 'active' AND MONTH(b.`date_added`) = '" . $iaCore->requestPath[1] . "' AND YEAR(b.`date_added`) = '" . $iaCore->requestPath[0] . "' ";
		$order = ('date' == $iaCore->get('blog_order')) ? 'ORDER BY b.`date_added` DESC' : 'ORDER BY b.`title` ASC';

		$sql =
			'SELECT SQL_CALC_FOUND_ROWS ' .
			'b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname` ' .
			'FROM `:prefix:table_blog_entries` b ' .
			'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
			'WHERE b.' . $stmt . $order . ' LIMIT :start, :limit';

		$sql = iaDb::printf($sql, array(
			'prefix' => $iaDb->prefix,
			'table_blog_entries' => 'blog_entries',
			'table_members' => 'members',
			'start' => $pagination['start'],
			'limit' => $pagination['limit']
		));

		$blogs  = $iaDb->getAll($sql);

		iaBreadcrumb::toEnd(date("F", mktime(0, 0, 0, $iaCore->requestPath[1], 10)));
		$pagination['total'] = $iaDb->foundRows();

		$iaView->assign('blogs', $blogs);
		$iaView->assign('pagination', $pagination);

		$iaView->title(date("F", mktime(0, 0, 0, $iaCore->requestPath[1], 10)));
	}

	$iaView->display('date');
}
