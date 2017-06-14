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

$iaBlog = $iaCore->factoryPlugin(IA_CURRENT_MODULE);

$baseUrl = $iaCore->factory('page', iaCore::FRONT)->getUrlByName('blog');

$iaDb->setTable($iaBlog::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    if (isset($iaCore->requestPath[0]) && 'alias' == $iaCore->requestPath[0]) {
        $output['url'] = $baseUrl . $iaDb->getNextId() . '-' . $iaBlog->titleAlias($_POST['title']);

        $iaView->assign($output);
    }
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    switch ($pageAction) {
        case iaCore::ACTION_ADD:
        case iaCore::ACTION_EDIT:
            $iaView->title(iaLanguage::get($pageAction . '_blog_entry'));
            $iaView->display('manage');

            if (!iaUsers::hasIdentity()) {
                return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
            }

            if (iaCore::ACTION_ADD == $pageAction) {
                $entry = [
                    'lang' => $iaView->language,
                    'date_added' => date(iaDb::DATETIME_FORMAT),
                    'status' => iaCore::STATUS_ACTIVE,
                    'member_id' => iaUsers::getIdentity()->id,
                    'title' => '',
                    'body' => '',
                    'image' => ''
                ];
            } else {
                if (1 != count($iaCore->requestPath)) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                $id = (int)$iaCore->requestPath[0];
                $entry = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

                if (!$entry) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                if ($entry['member_id'] != iaUsers::getIdentity()->id) {
                    return iaView::errorPage(iaView::ERROR_FORBIDDEN);
                }
            }

            if (isset($_POST['data-blog-entry'])) {
                $result = false;
                $messages = [];

                iaUtil::loadUTF8Functions('ascii', 'validation', 'bad', 'utf8_to_ascii');

                $entry['title'] = $_POST['title'];
                utf8_is_valid($entry['title']) || $entry['title'] = utf8_bad_replace($entry['title']);

                if (empty($entry['title'])) {
                    $messages[] = iaLanguage::get('title_is_empty');
                }

                $entry['body'] = $_POST['body'];
                utf8_is_valid($entry['body']) || $entry['body'] = utf8_bad_replace($entry['body']);

                if (empty($entry['body'])) {
                    $messages[] = iaLanguage::getf('field_is_empty', ['field' => iaLanguage::get('body')]);
                }

                $entry['alias'] = $iaBlog->titleAlias(empty($_POST['alias']) ? $entry['title'] : $_POST['alias']);

                if (!$messages) {
                    if (isset($_FILES['image']['error']) && !$_FILES['image']['error']) {
                        $iaField = $iaCore->factory('field');

                        try {
                            $imagePath = $iaField->uploadImage($_FILES['image'], $iaCore->get('blog_image_width'),
                                $iaCore->get('blog_image_height'), $iaCore->get('blog_thumb_width'),
                                $iaCore->get('blog_thumb_height'), $iaCore->get('blog_image_resize'));

                            if ($imagePath) {
                                if ($entry['image']) {
                                    list($path, $file) = explode('|', $entry['image']);
                                    $iaField->deleteUploadedFile($path, $file);
                                }

                                $entry['image'] = $imagePath;
                            }
                        } catch (Exception $e) {
                            $messages[] = $e->getMessage();
                        }
                    }

                    $result = (iaCore::ACTION_ADD == $pageAction)
                        ? $iaBlog->insert($entry)
                        : $iaBlog->update($entry, $id);

                    if ($result) {
                        $id = (iaCore::ACTION_ADD == $pageAction) ? $result : $id;

                        $iaBlog->saveTags($id, $_POST['tags']);

                        $iaView->setMessages(iaLanguage::get('saved'), iaView::SUCCESS);
                        iaUtil::go_to($baseUrl . sprintf('%d-%s', $id, $entry['alias']));
                    } else {
                        $messages[] = iaLanguage::get('db_error');
                    }
                }

                $iaView->setMessages($messages);
            }

            $tags = (iaCore::ACTION_ADD == $pageAction) ? '' : $iaBlog->getTagsString($id);

            $iaView->assign('item', $entry);
            $iaView->assign('blog_entry_tags', $tags);

            break;

        case iaCore::ACTION_DELETE:
            if (1 != count($iaCore->requestPath)) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $id = (int)$iaCore->requestPath[0];
            $entry = $iaDb->row(iaDb::ALL_COLUMNS_SELECTION, iaDb::convertIds($id));

            if (!$entry) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $result = $iaBlog->delete($id);

            $iaView->setMessages(iaLanguage::get($result ? 'deleted' : 'db_error'), $result ? iaView::SUCCESS : iaView::ERROR);

            iaUtil::go_to($baseUrl);

            break;

        default:
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
                $iaView->title(htmlentities($entry['title']));

                // add open graph data
                $openGraph = [
                    'title' => $entry['title'],
                    'url' => IA_SELF,
                    'description' => $entry['body']
                ];
                empty($entry['image']) || $openGraph['image'] = IA_CLEAR_URL . 'uploads/' . $entry['image'];

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
        $blogbody = '';
        if ($entry['image']!='') {
            //Let's add the blog image as well, if used
            $blogbody.= '<p><img src="' . IA_CLEAR_URL . 'uploads/' . $entry["image"] . '"/></p>';
        }
        $blogbody.= iaSanitize::tags($entry['body']);

        $output['item'][] = [
            'title' => $entry['title'],
            'guid' => $baseUrl . $entry['id'] . '-' . $entry['alias'],
            'pubDate' => date('D, d M Y H:i:s O', strtotime($entry['date_added'])),
            'description' => $blogbody
        ];
    }

    $iaView->assign('channel', $output);
}

$iaDb->resetTable();
