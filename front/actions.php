<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2018 Intelliants, LLC <https://intelliants.com>
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

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_POST['action'])) {
    $output = ['error' => true, 'message' => iaLanguage::get('invalid_parameters')];

    switch ($_POST['action']) {
        case 'edit-picture-title':
            $title = empty($_POST['value']) ? '' : $_POST['value'];
            $item = isset($_POST['item']) ? $_POST['item'] : null;
            $field = isset($_POST['field']) ? iaSanitize::sql($_POST['field']) : null;
            $path = isset($_POST['path']) ? $_POST['path'] : null;
            $itemId = isset($_POST['itemid']) ? (int)$_POST['itemid'] : false;

            if ($itemId && $item && $field && $path) {
                $tableName = $iaCore->factory('item')->getItemTable($item);

                if (iaUsers::getItemName() == $item) {
                    $itemValue = $iaDb->one($field, iaDb::convertIds($itemId), $tableName);
                    $memberId = $itemId;
                } else {
                    $row = $iaDb->row($field . ', `member_id` `id`', iaDb::convertIds($itemId), $tableName);
                    $itemValue = $row[$field];
                    $memberId = $row['id'];
                }

                if (iaUsers::hasIdentity() && $memberId == iaUsers::getIdentity()->id && $itemValue) {
                    $pictures = null;
                    if ($itemValue[1] == ':') {
                        $array = unserialize($itemValue);
                        if (is_array($array) && $array) {
                            $pictures = $array;
                        }
                    } else {
                        if ($array = explode(',', $itemValue)) {
                            $pictures = $array;
                        }
                    }

                    if (is_array($pictures)) {
                        foreach ($pictures as $i => $value) {
                            if (is_array($value)) {
                                if ($path == $value['path']) {
                                    $pictures[$i]['title'] = $title;
                                }
                            } else {
                                if ($path == $value) {
                                    $key = $i;
                                }
                            }
                        }

                        $newValue = is_array($value) ? serialize($pictures) : implode(',', $pictures);
                        $iaDb->update([$field => $newValue], iaDb::convertIds($itemId), null, $tableName);

                        if (0 == $iaDb->getErrorNumber()) {
                            $output['error'] = false;
                            unset($output['message']);
                        } else {
                            $output['message'] = iaLanguage::get('db_error');
                        }

                        if (iaUsers::getItemName() == $item) {
                            // update current profile data
                            if ($itemId == iaUsers::getIdentity()->id) {
                                iaUsers::reloadIdentity();
                            }
                        }
                    }
                }
            }

            break;

        case 'delete-file':
            if (!empty($_POST['item']) && !empty($_POST['itemid']) && !empty($_POST['field']) && !empty($_POST['file'])) {
                $output = $iaCore->factory('field')->deleteUploadedFile($_POST['field'], $_POST['item'], $_POST['itemid'], $_POST['file'], true)
                    ? ['error' => false, 'message' => iaLanguage::get('deleted')]
                    : ['error' => true, 'message' => iaLanguage::get('error')];
            }

            break;

        case 'send_email':
            $output['message'] = [];
            $memberInfo = $iaCore->factory('users')->getInfo((int)$_POST['author_id']);

            if (empty($memberInfo) || $memberInfo['status'] != iaCore::STATUS_ACTIVE) {
                $output['message'][] = iaLanguage::get('member_doesnt_exist');
            }

            if (empty($_POST['from_name'])) {
                $output['message'][] = iaLanguage::get('incorrect_fullname');
            }

            if (empty($_POST['from_email']) || !iaValidate::isEmail($_POST['from_email'])) {
                $output['message'][] = iaLanguage::get('error_email_incorrect');
            }

            if (empty($_POST['email_body'])) {
                $output['message'][] = iaLanguage::get('err_message');
            }

            if ($captchaName = $iaCore->get('captcha_name')) {
                $iaCaptcha = $iaCore->factoryModule('captcha', $captchaName);
                if (!$iaCaptcha->validate()) {
                    $output['message'][] = iaLanguage::get('confirmation_code_incorrect');
                }
            }

            if (empty($output['message'])) {
                $iaMailer = $iaCore->factory('mailer');

                $subject = iaLanguage::getf('author_contact_request', ['title' => $_POST['regarding']]);

                // for better delivery we cannot send from customers email, so we add it here
                $body = '<br>' . iaSanitize::tags($_POST['from_name']) . '<br>' . iaSanitize::tags($_POST['from_email'])
                    . '<br>' . nl2br(iaSanitize::tags($_POST['email_body']));

                $iaMailer->addAddress($memberInfo['email'], $memberInfo['fullname']);
                $iaMailer->setSubject($subject);
                $iaMailer->setBody($body);

                $output['error'] = !$iaMailer->send();
                $output['message'][] = iaLanguage::get($output['error'] ? 'unable_to_send_email' : 'mail_sent');
            }

            break;

        case 'set-currency':
            $iaCore->factoryModule('currency', IA_CURRENT_MODULE)->set($_POST['code']);

            $output['error'] = false;
            unset($output['message']);

            break;

        default:
            $output = [];
            $iaCore->startHook('phpActionsJsonHandle', ['action' => $_POST['action'], 'output' => &$output]);
    }

    $iaView->assign($output);
}

if (isset($_GET) && isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'ckeditor_upload':
            $iaView->disableLayout();
            $iaView->display(iaView::NONE);
            $iaView->set('nodebug', 1);

            if (isset($_GET['Type']) && 'Image' == $_GET['Type']
                && isset($_FILES['upload']['error']) && !$_FILES['upload']['error']) {
                $file = $_FILES['upload'];
                $folder = 'uploads/' . iaUtil::getAccountDir();
                $imgTypes = $iaCore->factory('picture')->getSupportedImageTypes();

                if (!isset($imgTypes[$file['type']])) {
                    return '202 error';
                }

                $fileName = iaUtil::generateToken() . '.' . $imgTypes[$file['type']];
                $filePath = IA_HOME . $folder . $fileName;

                if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                    return iaLanguage::get('error');
                }

                chmod($filePath, 0777);

                $fileUrl = IA_CLEAR_URL . $folder . $fileName;
                $callback = (int)$_GET['CKEditorFuncNum'];

                $output = '<html><body><script type="text/javascript">';
                $output.= "window.parent.CKEDITOR.tools.callFunction('{$callback}', '{$fileUrl}', '');";
                $output.= '</script></body></html>';

                die($output);
            }

            break;

        case 'assign-owner':
            if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
                if (isset($_GET['q']) && $_GET['q']) {
                    $stmt = '(`username` LIKE :name OR `email` LIKE :name OR `fullname` LIKE :name) AND `status` = :status ORDER BY `username` ASC';
                    $iaDb->bind($stmt, ['name' => $_GET['q'] . '%', 'status' => iaCore::STATUS_ACTIVE]);

                    $sql = <<<SQL
SELECT `id`, CONCAT(`fullname`, ' (', `email`, ')') `fullname` 
  FROM :table_members 
WHERE :conditions 
LIMIT 0, 20
SQL;
                    $sql = iaDb::printf($sql, [
                        'table_members' => iaUsers::getTable(true),
                        'conditions' => $stmt,
                    ]);

                    $output = $iaDb->getAll($sql);

                    $iaView->assign($output);
                }
            }
    }
}
