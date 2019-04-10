<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2019 Intelliants, LLC <https://intelliants.com>
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

    $blocksData = [];

    $iaBlog = $iaCore->factoryModule('blog', 'blog');

    if ($iaView->blockExists('blogroll') || $iaView->blockExists('new_blog_posts')) {

        $array = $iaBlog->get(0, $iaCore->get('blog_number_block'));

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

    if ($iaView->blockExists('blog_featured')) {
        if ($blogs = $iaBlog->get(0, $iaCore->get('blog_num_block_featured'), 'b.`featured` = 1 ', iaDb::FUNCTION_RAND)) {
            $blocksData['featured'] = $blogs;
        }
    }

    $iaView->assign('blog_blocks_data', $blocksData);
}


