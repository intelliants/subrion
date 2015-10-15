<?php
//##copyright##

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	if ($iaView->blockExists('blogroll') || $iaView->blockExists('new_blog_posts'))
	{
		$stmt = 'b.`status` = :status AND `lang` = :language ORDER BY b.`date_added` DESC';
		$iaDb->bind($stmt, array('status' => iaCore::STATUS_ACTIVE, 'language' => $iaView->language));

		$sql =
			'SELECT b.`id`, b.`title`, b.`date_added`, b.`alias`, b.`body`, b.`image`, m.`fullname` ' .
			'FROM `:prefix:table_blog_entries` b ' .
			'LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) ' .
			'WHERE :condition ' .
			'LIMIT :start, :limit';
		$sql = iaDb::printf($sql, array(
			'prefix' => $iaDb->prefix,
			'table_blog_entries' => 'blog_entries',
			'table_members' => 'members',
			'condition' => $stmt,
			'start' => 0,
			'limit' => $iaCore->get('blog_number_block')
		));
		$array = $iaDb->getAll($sql);

		$iaView->assign('block_blog_entries', $array);
	}

	if ($iaView->blockExists('blogs_archive'))
	{
		$data = array();
		if ($array = $iaDb->all('DISTINCT(MONTH(`date_added`)) `month`, YEAR(`date_added`) `year`', "`status` = 'active' GROUP BY `date_added` ORDER BY `date_added` DESC", 0, 6, 'blog_entries'))
		{

			foreach ($array as $date)
			{
				$data[] = array(
					'url' => IA_URL . 'blog/date/' .  $date['year'] . IA_URL_DELIMITER . $date['month'] . IA_URL_DELIMITER,
					'month' => $date['month'],
					'year' => $date['year']
				);
			}
		}

		$iaView->assign('blogs_archive', $data);
	}
}