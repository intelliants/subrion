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
                    //'lang' => $iaView->language,
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
                $entry = $iaBlog->getById($id);

                if (!$entry) {
                    return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                }

                if ($entry['member_id'] != iaUsers::getIdentity()->id) {
                    return iaView::errorPage(iaView::ERROR_FORBIDDEN);
                }
            }


        $iaField = $iaCore->factory('field');

            if (isset($_POST['data-blog-entry'])) {

                $result = false;
                $messages = [];

                list($entry, $error, $messages) = $iaField->parsePost($iaBlog->getItemName(), $entry);

                $entry['alias'] = $iaBlog->titleAlias(empty($_POST['alias']) ? $entry['title_'.$iaView->language] : $_POST['alias']);
                $entry['member_id'] = iaUsers::getIdentity()->id;
                $entry['date_added'] = date(iaDb::DATETIME_FORMAT);

                if (!$messages) {

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

            $sections = $iaField->getTabs($iaBlog->getItemName(), $entry);

            $iaView->assign('item', $entry);
            $iaView->assign('blog_entry_tags', $tags);
            $iaView->assign('sections', $sections);

            break;

        case iaCore::ACTION_DELETE:

            if (1 != count($iaCore->requestPath)) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            if (!iaUsers::hasIdentity()) {
                return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
            }

            $id = (int)$iaCore->requestPath[0];
            $entry = $iaBlog->getById($id);


            if (!$entry) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }


            if ($entry['member_id'] != iaUsers::getIdentity()->id) {
                return iaView::errorPage(iaView::ERROR_FORBIDDEN);
            }

            $result = $iaBlog->delete($id);

            $iaView->setMessages(iaLanguage::get($result ? 'deleted' : 'db_error'), $result ? iaView::SUCCESS : iaView::ERROR);

            iaUtil::go_to($baseUrl);

            break;


    }
}
