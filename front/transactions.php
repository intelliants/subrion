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

if (!iaUsers::hasIdentity()) {
    return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
}

$iaTransaction = $iaCore->factory('transaction');
$profilePageUrl = IA_URL . 'profile/';

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_GET['amount'])) {
    $output = ['error' => false];
    $amount = (float)$_GET['amount'];

    if ($amount >= (float)$iaCore->get('funds_min_deposit')
        && $amount <= (float)$iaCore->get('funds_max_deposit')) {
        $transactionId = $iaTransaction->create(iaLanguage::get('funds'), $amount, iaTransaction::TRANSACTION_MEMBER_BALANCE, iaUsers::getIdentity(true), $profilePageUrl, 0, true);
        $transactionId
            ? $output['url'] = IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER
            : $output['error'] = iaLanguage::get('db_error');
    } else {
        $output['error'] = iaLanguage::getf('amount_incorrect', [
            'min' => $iaCore->get('funds_min_deposit'),
            'max' => $iaCore->get('funds_max_deposit'),
            'currency' => $iaCore->get('currency')]
        );
    }

    $iaView->assign($output);
}

if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    $iaInvoice = $iaCore->factory('invoice');
    $iaTransaction = $iaCore->factory('transaction');

    if (isset($iaCore->requestPath[0]) && 'invoice' == $iaCore->requestPath[0]) {
        if (isset($iaCore->requestPath[1])) {
            $transaction = $iaTransaction->getBy('sec_key', $iaCore->requestPath[1]);

            if (!$transaction) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $invoice = $iaInvoice->getBy('transaction_id', $transaction['id']);

            if (!$invoice) {
                return iaView::errorPage(iaView::ERROR_NOT_FOUND);
            }

            $iaView->assign('invoice', $invoice);
            $iaView->assign('items', $iaInvoice->getItemsByInvoiceId($invoice['id']));

            $iaView->disableLayout();
            echo $iaView->display('invoice');

            return;
        } else {
            return iaView::errorPage(iaView::ERROR_NOT_FOUND);
        }
    }

    iaUsers::reloadIdentity();

    if (isset($_POST['amount'])) {
        $amount = (float)$_POST['amount'];

        if ($amount >= (float)$iaCore->get('funds_min_deposit')
            && $amount <= (float)$iaCore->get('funds_max_deposit')) {
            $iaTransaction->create(iaLanguage::get('funds'), $amount, iaTransaction::TRANSACTION_MEMBER_BALANCE, iaUsers::getIdentity(true), $profilePageUrl);
        } else {
            $iaView->setMessages(iaLanguage::getf('amount_incorrect', [
                'min' => $iaCore->get('funds_min_deposit'),
                'max' => $iaCore->get('funds_max_deposit'),
                'currency' => $iaCore->get('currency')]
            ));
        }
    }

    $pagination = [
        'page' => 1,
        'limit' => 10,
        'total' => 0,
        'template' => $profilePageUrl . 'funds/?page={page}'
    ];

    $pagination['page'] = (isset($_GET['page']) && 1 < $_GET['page']) ? (int)$_GET['page'] : $pagination['page'];
    $pagination['page'] = ($pagination['page'] - 1) * $pagination['limit'];

    list($transactions, $pagination['total']) = $iaTransaction->getList();

    $iaView->caption($iaView->title() . ': ' . number_format(iaUsers::getIdentity()->funds, 2, '.', '') . ' ' . $iaCore->get('currency'));

    $iaView->assign('pagination', $pagination);
    $iaView->assign('transactions', $transactions);

    $iaView->display('transactions');
}
