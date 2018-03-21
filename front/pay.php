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

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    if (!isset($iaCore->requestPath[0])) {
        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
    }

    $iaTransaction = $iaCore->factory('transaction');

    $transaction = $iaTransaction->getBy('sec_key', $iaCore->requestPath[0]);

    if (empty($transaction)) {
        return iaView::errorPage(iaView::ERROR_NOT_FOUND, iaLanguage::get('no_transaction'));
    }

    if (iaUsers::hasIdentity() && $transaction['member_id'] != iaUsers::getIdentity()->id) {
        return iaView::errorPage(iaView::ERROR_FORBIDDEN);
    }

    $iaPage = $iaCore->factory('page', iaCore::FRONT);
    $action = isset($iaCore->requestPath[1]) ? iaSanitize::sql($iaCore->requestPath[1]) : null;

    // delete transaction
    if (isset($_GET['delete'])) {
        $iaTransaction->delete($transaction['id']);

        $iaView->setMessages(iaLanguage::get('invoice_deleted'), iaView::SUCCESS);
        iaUtil::go_to($iaPage->getUrlByName('member_funds'));
    }

    // cancel payment
    if ('canceled' == $action) {
        if (iaTransaction::FAILED != $transaction['status']) {
            $iaTransaction->update(['status' => iaTransaction::FAILED], $transaction['id']);
        }

        $iaView->setMessages(iaLanguage::get('payment_process_canceled'), iaView::ALERT);

        if (iaUsers::hasIdentity()) {
            iaUtil::go_to($iaPage->getUrlByName('member_funds'));
        } else {
            $iaView->title(iaLanguage::get('payment_cancellation'));
            $iaView->display(iaView::NONE);
            return;
        }
    }

    // configure return url on payment success
    if (isset($transaction['sec_key'])) {
        define('IA_RETURN_URL', IA_URL . 'pay/' . $transaction['sec_key'] . IA_URL_DELIMITER);
    }

    $gateways = $iaTransaction->getPaymentGateways();
    $order = [];
    $tplFile = 'pay';

    $error = false;
    $messages = [];

    $iaPlan = $iaCore->factory('plan');
    $iaUsers = $iaCore->factory('users');

    $iaCore->startHook('phpFrontBeforePaymentProcessing', ['transaction' => $transaction]);

    switch ($transaction['status']) {
        case iaTransaction::PENDING:
            if (isset($_POST['source']) && 'internal' == $_POST['source']) {
                $iaCore->startHook('phpFrontPaymentBeforeBalanceUpdate');

                if ($transaction && isset($_POST['source']) && 'internal' == $_POST['source']) {
                    if ($iaPlan->extractFunds($transaction)) {
                        empty($_POST['invaddr']) || $iaCore->factory('invoice')->updateAddress($transaction['id'], $_POST['invaddr']);
                        iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('payment_done'), $transaction['return_url']);
                    }
                }
            } elseif (empty($transaction['gateway']) || isset($_GET['repay'])) {
                if (empty($transaction['plan_id'])) {
                    $plan['title'] = $transaction['operation'];
                    $plan['cost'] = $transaction['amount'];
                } else {
                    $plan = $iaPlan->getById($transaction['plan_id']);
                }

                $plan['title'] = $transaction['item'] . ' - ' . $plan['title'];

                $iaView->assign('plan', $plan);
                $iaView->assign('address', iaUsers::hasIdentity() ? $iaCore->factory('invoice')->getAddress($transaction['id']) : null);

                // process payment button click
                if (isset($_POST['payment_type']) && isset($gateways[$_POST['payment_type']])) {
                    $gate = $_POST['payment_type'];

                    $iaDb->update(['gateway' => $gate, 'date_updated' => (new \DateTime())->format(iaDb::DATETIME_FORMAT)], iaDb::convertIds($transaction['id']), null, iaTransaction::getTable());

                    empty($_POST['invaddr']) || $iaCore->factory('invoice')->updateAddress($transaction['id'], $_POST['invaddr']);

                    // include pre form send files
                    $paymentGatewayHandler = IA_MODULES . $gate . '/includes/pre-processing' . iaSystem::EXECUTABLE_FILE_EXT;
                    if (is_file($paymentGatewayHandler)) {
                        include $paymentGatewayHandler;
                    }

                    $form = IA_MODULES . $gate . '/templates/front/form.tpl';

                    if (is_file($form)) {
                        $data = [
                            'caption' => 'Redirect to ' . $gate . '',
                            'msg' => 'You will be redirected to ' . $gate . '',
                            'form' => $form
                        ];

                        $iaView->assign('redir', $data);

                        $tplFile = 'redirect-gateway';
                        $iaView->disableLayout();
                    }
                } elseif (isset($_POST['source']) && 'external' == $_POST['source']) {
                    $iaView->setMessages(iaLanguage::get('payment_gateway_not_chosen'), iaView::ERROR);
                }

                iaBreadcrumb::add(iaLanguage::get('page_title_member_funds'),
                    $iaCore->factory('page', iaCore::FRONT)->getUrlByName('member_funds'));
            } elseif (!empty($transaction['gateway']) && 'completed' == $action) {
                $gate = $transaction['gateway'];
                if (isset($gateways[$gate])) {
                    $temp_transaction = $transaction;

                    $transaction = [];

                    // include post form send files
                    $paymentGatewayHandler = IA_MODULES . $gate . '/includes/post-processing' . iaSystem::EXECUTABLE_FILE_EXT;

                    if (file_exists($paymentGatewayHandler)) {
                        // set to true if custom transaction handling needed
                        $replaceHandler = false;
                        $iaCore->startHook('phpPayBeforeIncludePostGate', ['gateway' => $gate]);

                        include $paymentGatewayHandler;

                        $iaCore->startHook('phpPayAfterIncludePostGate', ['gateway' => $gate]);

                        // print transaction information
                        if (INTELLI_DEBUG) {
                            iaDebug::log('Processed transaction information', $transaction);
                        }

                        // use default processing handler
                        if (false === $replaceHandler) {
                            if (!$transaction) {
                                return iaView::errorPage(iaView::ERROR_FORBIDDEN, $messages);
                            }

                            if (in_array($transaction['status'], [iaTransaction::PASSED, iaTransaction::PENDING])) {
                                // update transaction record
                                $iaTransaction->update($transaction, $transaction['id']);

                                // disable debug display
                                $iaView->set('nodebug', true);

                                $iaView->assign('gateway', $transaction['gateway']);

                                $tplFile = 'purchase-post';
                            } else {
                                return iaView::errorPage(iaView::ERROR_NOT_FOUND, $messages);
                            }
                        }
                    } else {
                        return iaView::errorPage(iaView::ERROR_NOT_FOUND);
                    }
                }
            } else {
                $iaView->assign('pay_message', iaLanguage::getf('wait_for_gateway_answer', ['url' => IA_SELF . '?repay']));
            }

            break;

        case iaTransaction::PASSED:
            if ('ipn' == $action) {
                $iaTransaction->createIpn($transaction);
            } else {
                $iaView->setMessages(iaLanguage::get('this_transaction_already_passed'), iaView::ALERT);
                iaUtil::go_to($iaPage->getUrlByName('member_funds'));
            }

            break;

        case iaTransaction::REFUNDED:
        case iaTransaction::FAILED:
            $iaView->setMessages($messages);
            iaUtil::go_to($iaPage->getUrlByName('member_funds'));

            break;

        default:
            $error = true;
            $messages[] = 'Unknown status';
    }

    $iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

    $memberBalance = iaUsers::hasIdentity() ? iaUsers::getIdentity()->funds : 0;
    iaLanguage::set('funds_in_your_account', iaLanguage::getf('funds_in_your_account', ['sum' => $memberBalance, 'currency' => $iaCore->get('currency')]));

    $isBalancePayment = (iaUsers::hasIdentity() && iaTransaction::TRANSACTION_MEMBER_BALANCE == $transaction['item'] && iaUsers::getIdentity()->id == $transaction['item_id']);
    $isFundsEnough = (bool)(!$isBalancePayment && iaUsers::hasIdentity() && iaUsers::getIdentity()->funds >= $transaction['amount']);

    // FIXME: solution to prevent csrf catching.
    // Should be replaced once it is possible to disable csrf checking for a single page.
    if (isset($_POST)) {
        $paymentPost = $_POST;

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            $wwwChunk = 'www.';

            $referrerDomain = explode(IA_URL_DELIMITER, $_SERVER['HTTP_ORIGIN']);
            $referrerDomain = strtolower($referrerDomain[2]);
            $referrerDomain = str_replace($wwwChunk, '', $referrerDomain);
            $domain = explode(IA_URL_DELIMITER, $iaCore->get('baseurl'));
            $domain = strtolower($domain[2]);
            $domain = str_replace($wwwChunk, '', $domain);

            if ($referrerDomain !== $domain) {
                $_POST = [];
            }
        }
    }
    //

    $iaView->assign('isBalancePayment', $isBalancePayment);
    $iaView->assign('isFundsEnough', $isFundsEnough);
    $iaView->assign('order', $order);
    $iaView->assign('gateways', $gateways);
    $iaView->assign('transaction', $transaction);

    $iaView->display($tplFile);
}
