<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2017 Intelliants, LLC <https://intelliants.com>
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
 * @link https://subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if ($iaView->blockExists('blogroll') || $iaView->blockExists('new_blog_posts')) {
        $sql = <<<SQL
SELECT b.`id`, b.`title`, b.`date_added`, b.`alias`, b.`body`, b.`image`, m.`fullname` 
	FROM `:prefix:table_blog_entries` b 
LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) 
WHERE b.`status` = ':status' && `lang` = ':language'
ORDER BY b.`date_added` DESC
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $iaDb->prefix,
            'table_blog_entries' => 'blog_entries',
            'table_members' => 'members',
            'status' => iaCore::STATUS_ACTIVE,
            'language' => $iaView->language,
            'start' => 0,
            'limit' => $iaCore->get('blog_number_block')
        ]);
        $array = $iaDb->getAll($sql);

        $iaView->assign('block_blog_entries', $array);
    }

    if ($iaView->blockExists('blogs_archive')) {
        $data = [];
        if ($array = $iaDb->all('DISTINCT(MONTH(`date_added`)) `month`, YEAR(`date_added`) `year`', "`status` = 'active' GROUP BY `date_added` ORDER BY `date_added` DESC", 0, 6, 'blog_entries')) {
            foreach ($array as $date) {
                $data[] = [
                    'url' => IA_URL . 'blog/date/' .  $date['year'] . IA_URL_DELIMITER . $date['month'] . IA_URL_DELIMITER,
                    'month' => $date['month'],
                    'year' => $date['year']
                ];
            }
        }

        $iaView->assign('blogs_archive', $data);
    }
}
