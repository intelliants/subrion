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

$iaBlog = $iaCore->factoryModule('blog', IA_CURRENT_MODULE);

$baseUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog');


if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    if (isset($iaCore->requestPath[0]) && 'slug' == $iaCore->requestPath[0]) {
        $output['url'] = $baseUrl . $iaDb->getNextId() . '-' . $iaBlog->titleAlias($_POST['title']);

        $iaView->assign($output);
    }
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {

            $iaView->display('index');

            $pageActions = [];

            if (isset($iaCore->requestPath[0])) {
                $id = (int)$iaCore->requestPath[0];

                if (!$id) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                $entry = $iaBlog->getById($id);
                if (empty($entry)) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                iaBreadcrumb::toEnd($entry['title']);
                $iaView->title($entry['title']);

                // add open graph data
                $openGraph = [
                    'title' => $entry['title'],
                    'url' => IA_SELF,
                    'description' => $entry['body']
                ];

                empty($entry['image']) || $openGraph['image'] = IA_CLEAR_URL . 'uploads/' . $entry['image']['path'] . 'thumbnail/' .$entry['image']['file'];

                $iaView->set('og', $openGraph);

                $iaView->assign('blog_tags', $iaBlog->getTags($id));
                $iaView->assign('blog_entry', $entry);

                if ($iaAcl->isAccessible(iaBlog::PAGE_NAME, iaCore::ACTION_EDIT) && iaUsers::hasIdentity()
                    && iaUsers::getIdentity()->id == $entry['member_id']) {
                    $pageActions[] = [
                        'icon' => 'pencil',
                        'title' => iaLanguage::get('edit_blog_entry'),
                        'url' => $baseUrl . 'edit/' . $id . '/',
                        'classes' => 'btn-info'
                    ];
                    $pageActions[] = [
                        'icon' => 'remove',
                        'title' => iaLanguage::get('delete'),
                        'url' => $baseUrl . 'delete/' . $id . '/',
                        'classes' => 'btn-danger'
                    ];
                }
            } else {
                $page = empty($_GET['page']) ? 0 : (int)$_GET['page'];
                $page = ($page < 1) ? 1 : $page;

                $pagination = [
                    'start' => ($page - 1) * $iaCore->get('blog_number'),
                    'limit' => (int)$iaCore->get('blog_number'),
                    'template' => $baseUrl . '?page={page}'
                ];

                $entries = $iaBlog->get($pagination['start'], $pagination['limit']);
                $pagination['total'] = $iaDb->foundRows();

                $iaView->assign('blog_tags', $iaBlog->getAllTags());
                $iaView->assign('blog_entries', $entries);
                $iaView->assign('pagination', $pagination);
            }

            if ($iaAcl->isAccessible('blog', iaCore::ACTION_ADD)) {
                $pageActions[] = [
                    'icon' => 'plus',
                    'title' => iaLanguage::get('add_blog_entry'),
                    'url' => $baseUrl . 'add/',
                    'classes' => 'btn-success'
                ];
            }

            $pageActions[] = [
                'icon' => 'rss',
                'title' => iaLanguage::get('rss'),
                'url' => IA_URL . 'blog.xml',
                'classes' => 'btn-warning'
            ];

            $iaView->set('actions', $pageActions);
}

if (iaView::REQUEST_XML == $iaView->getRequestType()) {
    $output = [
        'title' => $iaCore->get('site') . ' :: ' . $iaView->title(),
        'description' => '',
        'link' => IA_URL . 'blog',
        'item' => []
    ];

    //Add default Feed Image displayed in RSS Readers
    //You can add your own by replacing rss.png in "/modules/blog/templates/front/img" folder
    $output['image'][] = [
        'title' => $iaCore->get('site') . ' :: ' . $iaView->title(),
        'url' => IA_CLEAR_URL . 'modules/blog/templates/front/img/rss.png',
        'link' => $baseUrl
    ];

    $entries = $iaBlog->get(0, 20);

    foreach ($entries as $entry) {
        $blogbody.= iaSanitize::tags($entry['body']);

        $itemValues = [
            'title' => $entry['title'],
            'guid' => $baseUrl . $entry['id'] . '-' . $entry['alias'],
            'pubDate' => date('D, d M Y H:i:s O', strtotime($entry['date_added'])),
            'description' => $blogbody
        ];

        if ($entry['image']) {
            $itemValues['enclosure'] = ['@attr' => [
                'url' => IA_CLEAR_URL . 'uploads/'. $entry['image']['path'] . 'thumbnail/' . $entry['image']['file'],
                'type' => 'image/jpg',
                'length' => $entry['image']['size']
            ]];
        }

        $output['item'][] = $itemValues;
    }

    $iaView->assign('channel', $output);
}

$iaDb->resetTable();
