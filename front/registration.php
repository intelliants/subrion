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

$iaUsers = $iaCore->factory('users');

$iaDb->setTable(iaUsers::getTable());

if (iaView::REQUEST_JSON == $iaView->getRequestType()) {
    if (isset($_GET['email'])) {
        $code = isset($_GET['code']) ? trim($_GET['code']) : false;
        $email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : '');
        $message = null;

        if ($email) {
            if (iaValidate::isEmail($email)) {
                $member = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`email` = :email', ['email' => $email]);

                if (!$member) {
                    $message = iaLanguage::get('error_no_member_email');
                } elseif (false !== $code && $member['sec_key'] != $code) {
                    $message = iaLanguage::get('confirmation_code_incorrect');
                }
            } else {
                $message = iaLanguage::get('error_email_incorrect');
            }

            if (!$message && false === $code) {
                $iaUsers->sendPasswordResetEmail($member);

                $message = iaLanguage::get('restore_pass_confirm');
            } elseif (!$message && $code) {
                $iaUsers->changePassword($member);

                $message = iaLanguage::get('new_password_sent');
            }
        } elseif ($_POST && empty($_POST['email'])) {
            $message = iaLanguage::get('error_email_incorrect');
        }

        $iaView->assign('message', $message);
        $iaView->assign('result', empty($message));
    }
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (!$iaCore->get('members_enabled')) {
        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
    }

    $iaCore->factory('util');

    if (iaUsers::hasIdentity()) {
        iaUtil::go_to(IA_URL . 'profile/');
    }

    $memberId = null;
    $error = false;
    $messages = [];
    $itemData = [];

    if ('member_password_forgot' == $iaView->name()) {
        $code = isset($_GET['code']) ? trim($_GET['code']) : false;
        $email = isset($_POST['email']) ? $_POST['email'] : (isset($_GET['email']) ? $_GET['email'] : '');
        $form = (false === $code) ? 'request' : 'confirm';

        if ($email) {
            if ($form != 'confirm' && !iaValidate::isCaptchaValid()) {
                $error = true;
                $messages[] = iaLanguage::get('confirmation_code_incorrect');
            }

            if (!iaValidate::isEmail($email)) {
                $error = true;
                $messages[] = iaLanguage::get('error_email_incorrect');
            }

            if (!$error) {
                $member = $iaDb->row_bind(iaDb::ALL_COLUMNS_SELECTION, '`email` = :email', ['email' => $email]);

                if (!$member) {
                    $error = true;
                    $messages[] = iaLanguage::get('error_no_member_email');
                } elseif (in_array($member['status'], [iaUsers::STATUS_SUSPENDED, iaUsers::STATUS_UNCONFIRMED])) {
                    $error = true;
                    $messages[] = iaLanguage::get('your_membership_is_inactive');
                }

                if (false !== $code && $member['sec_key'] != $code) {
                    $error = true;
                    $messages[] = iaLanguage::get('confirmation_code_incorrect');
                }

                if (!$error && false === $code) {
                    $token = iaUtil::generateToken();
                    $confirmationUrl = IA_URL . 'forgot/?email=' . $email . '&code=' . $token;

                    $iaMailer = $iaCore->factory('mailer');

                    $iaMailer->loadTemplate('password_restoration');
                    $iaMailer->addAddressByMember($member);
                    $iaMailer->setReplacements([
                        'fullname' => $member['fullname'],
                        'url' => $confirmationUrl,
                        'code' => $token,
                        'email' => $member['email']
                    ]);

                    $iaMailer->send();

                    $messages[] = iaLanguage::get('restore_pass_confirm');
                    $iaDb->update(['id' => $member['id'], 'sec_key' => $token], 0, 0, iaUsers::getTable());
                    $form = 'confirm';
                } elseif (!$error && $code) {
                    $error = false;
                    $messages[] = iaLanguage::get('new_password_sent');

                    $iaUsers->changePassword($member);
                    $form = false;
                }
            }
        } elseif ($_POST && empty($_POST['email'])) {
            $error = true;
            $messages[] = iaLanguage::get('error_email_incorrect');

            if (!iaValidate::isCaptchaValid()) {
                $error = true;
                $messages[] = iaLanguage::get('confirmation_code_incorrect');
            }
        }

        $iaView->assign('email', $email);
        $iaView->assign('form', $form);
    } else {
        $iaField = $iaCore->factory('field');
        $iaPlan = $iaCore->factory('plan');

        $iaView->assign('plans', $iaPlan->getPlans($iaUsers->getItemName()));
        $iaView->assign('sections', $iaField->getGroupsFiltered($iaUsers->getItemName(), $itemData));

        if (isset($_POST['register'])) {
            list($itemData, $error, $messages) = $iaField->parsePost($iaUsers->getItemName(), $itemData);

            if (!iaValidate::isCaptchaValid()) {
                $error = true;
                $messages[] = iaLanguage::get('confirmation_code_incorrect');
            }

            if (isset($_POST['plan_id'])) {
                $itemData[iaPlan::SPONSORED_PLAN_ID] = (int)$_POST['plan_id'];
            }

            if (isset($_POST['username'])) {
                if ($iaDb->exists('`username` = :value', ['value' => $_POST['username']], iaUsers::getTable())) {
                    $error = true;
                    $messages[] = iaLanguage::get('username_already_exists');
                }
            }
            if (isset($_POST['email'])) {
                if ($iaDb->exists('`email` = :value', ['value' => $_POST['email']], iaUsers::getTable())) {
                    $error = true;
                    $messages[] = iaLanguage::get('error_duplicate_email');
                }
            }

            if (!$error) {
                $itemData['password'] = iaUtil::checkPostParam('password');
                $itemData['disable_fields'] = isset($_POST['disable_fields']) ? (int)$_POST['disable_fields'] : 0;

                // check password
                if (!$itemData['disable_fields']) {
                    if (!$itemData['password']) {
                        $error = true;
                        $messages[] = iaLanguage::get('error_password_empty');
                    } else {
                        if ($_POST['password'] != $_POST['password2']) {
                            $error = true;
                            $messages[] = iaLanguage::get('error_password_match');
                        }
                    }
                } else {
                    $itemData['password'] = '';
                }
            } else {
                $iaView->setMessages($messages);
            }

            if (!$error) {
                $memberId = $iaUsers->register($itemData);

                if ($memberId) {
                    $iaCore->factory('log')->write(iaLog::ACTION_CREATE, ['item' => 'member', 'name' => $itemData['fullname'], 'id' => $memberId, 'type' => iaCore::FRONT]);
                }

                // process sponsored plan
                if ($memberId && isset($_POST['plan_id']) && is_numeric($_POST['plan_id'])) {
                    $plan = $iaPlan->getById($_POST['plan_id']);

                    if ($plan['cost'] > 0) {
                        $itemData['id'] = $memberId;
                        $itemData['member_id'] = $memberId;

                        if ($url = $iaPlan->prePayment($iaUsers->getItemName(), $itemData, $plan['id'])) {
                            iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('member_created'), $url);
                        }
                    }
                }
            }
        } elseif ('register_confirm' == $iaView->name()) {
            if (!isset($_GET['email']) || !isset($_GET['key'])) {
                return iaView::accessDenied();
            }

            $error = true;

            if ($iaUsers->confirmation($_GET['email'], $_GET['key'])) {
                $messages[] = $iaCore->get('members_autoapproval') ? iaLanguage::get('reg_confirmed') : iaLanguage::get('reg_confirm_adm_approve');
                $error = false;

                $url = $iaCore->get('members_autoapproval') ? IA_URL . 'login/' : IA_URL;
                iaUtil::redirect(iaLanguage::get('reg_confirmation'), $messages, $url);
            } else {
                $messages[] = iaLanguage::get('confirmation_key_incorrect');
            }

            $iaView->assign('success', !$error);
        }
    }

    switch ($iaView->name()) {
        case 'member_password_forgot':
            $template = 'forgot';

            break;

        case 'register_confirm':
            $template = 'registration-confirmation';

            break;

        default:
            if ($memberId) {
                $error = false;
                $template = 'registration-confirmation';
                $messages[] = iaLanguage::get('member_created');
                $iaView->assign('email', $itemData['email']);
            } else {
                $error = true;
                $template = 'registration';
                $iaView->assign('tmp', $itemData);
            }
    }

    $iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

    $iaView->display($template);
}

$iaDb->resetTable();
