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
    if (isset($iaCore->requestPath[0])) {
        $tag = $iaCore->requestPath[0];

        $page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
        $page = ($page < 1) ? 1 : $page;

        $pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('tag');

        $pagination = [
            'start' => ($page - 1) * $iaCore->get('blog_number'),
            'limit' => (int)$iaCore->get('blog_number'),
            'template' => $pageUrl . $tag . '?page={page}'
        ];

        $sql = <<<SQL
SELECT SQL_CALC_FOUND_ROWS b.`id`, b.`title`, b.`date_added`, b.`body`, b.`alias`, b.`image`, m.`fullname`, bt.`title` `tag_title`
  FROM `:prefix:table_blog_entries` b 
LEFT JOIN `:prefix:table_members` m ON (b.`member_id` = m.`id`) 
LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (b.`id` = bet.`blog_id`) 
LEFT JOIN `:prefix:table_blog_tags` bt ON (bt.`id` = bet.`tag_id`) 
WHERE bt.`alias` = ':tag' AND bet.`tag_id` = bt.`id` 
AND b.`status` = ':status' LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $iaDb->prefix,
            'table_blog_entries' => 'blog_entries',
            'table_blog_entries_tags' => 'blog_entries_tags',
            'table_blog_tags' => 'blog_tags',
            'table_members' => 'members',
            'tag' => iaSanitize::sql($tag),
            'status' => iaCore::STATUS_ACTIVE,
            'start' => $pagination['start'],
            'limit' => $pagination['limit']
        ]);

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
    } else {
        $page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
        $page = ($page < 1) ? 1 : $page;

        $pageUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('tag');

        $pagination = [
            'start' => ($page - 1) * $iaCore->get('blog_tag_number'),
            'limit' => (int)$iaCore->get('blog_tag_number'),
            'template' => $pageUrl . '?page={page}'
        ];

        $prefix = $iaDb->prefix;

        $sql = <<<SQL
SELECT DISTINCT SQL_CALC_FOUND_ROWS bt.`id`, bt.`title`, bt.`alias` 
  FROM `:prefix:table_blog_tags` bt 
LEFT JOIN `:prefix:table_blog_entries_tags` bet ON (bt.`id` = bet.`tag_id`) 
LEFT JOIN `:prefix:table_blog_entries` b ON (b.`id` = bet.`blog_id`) 
WHERE b.`status` = ':status' 
GROUP BY bt.`id` 
ORDER BY bt.`title` 
LIMIT :start, :limit
SQL;
        $sql = iaDb::printf($sql, [
            'prefix' => $iaDb->prefix,
            'table_blog_entries' => 'blog_entries',
            'table_blog_entries_tags' => 'blog_entries_tags',
            'table_blog_tags' => 'blog_tags',
            'status' => iaCore::STATUS_ACTIVE,
            'start' => $pagination['start'],
            'limit' => $pagination['limit']
        ]);

        $tags = $iaDb->getAll($sql);
        $pagination['total'] = $iaDb->foundRows();

        $iaView->assign('blog_tags', $tags);
        $iaView->assign('pagination', $pagination);
        $iaView->display('tag');
    }
}
