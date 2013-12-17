<?php
//##copyright##

if (!iaUsers::hasIdentity())
{
	iaView::accessDenied();
}

$iaTransaction = $iaCore->factory('transaction');

if (iaView::REQUEST_JSON == $iaView->getRequestType())
{
	if (isset($_GET['amount']))
	{
		$output = array('error' => false);
		$amount = (float)$_GET['amount'];

		if ($amount > 0)
		{
			if ($amount >= (float)$iaCore->get('balance_min_amount'))
			{
				$transactionId = $iaTransaction->createInvoice(iaLanguage::get('member_balance'), $amount, 'balance', iaUsers::getIdentity(true), $profilePageUrl, 0, true);

				$output['url'] = IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER;
			}
			else
			{
				$output['error'] = iaLanguage::get('amount_less_min');
			}
		}
		else
		{
			$output['error'] = iaLanguage::get('amount_incorrect');
		}

		$iaView->assign($output);
	}
}

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$profilePageUrl = IA_URL . 'profile/';

	if (isset($_POST['amount']))
	{
		$amount = (float)$_POST['amount'];
		if ($amount > 0)
		{
			if ($amount >= (float)$iaCore->get('balance_min_amount'))
			{
				$iaTransaction->createInvoice(iaLanguage::get('member_balance'), $amount, 'balance', iaUsers::getIdentity(true), $profilePageUrl);
			}
			else
			{
				$iaView->setMessages(iaLanguage::get('amount_less_min'));
			}
		}
		else
		{
			$iaView->setMessages(iaLanguage::get('amount_incorrect'));
		}
	}

	$pagination = array(
		'page' => 1,
		'limit' => 10,
		'total' => 0,
		'template' => $profilePageUrl . 'balance/?page={page}'
	);

	$pagination['page'] = (isset($_GET['page']) && 1 < $_GET['page']) ? (int)$_GET['page'] : $pagination['page'];
	$pagination['page'] = ($pagination['page'] - 1) * $pagination['limit'];

	$transactions = $iaDb->all('SQL_CALC_FOUND_ROWS *', '`member_id` = ' . iaUsers::getIdentity()->id . ' ORDER BY `status`', $pagination['page'], $pagination['limit'], iaTransaction::getTable());
	$pagination['total'] = $iaDb->foundRows();

	$iaView->caption(iaLanguage::get('member_balance'));
	$iaView->title(iaLanguage::get('member_balance') . ': ' . number_format(iaUsers::getIdentity()->funds, 2, '.', '') . ' ' . $iaCore->get('currency'));

	$iaView->assign('pagination', $pagination);
	$iaView->assign('transactions', $transactions);

	$iaView->display('transactions');
}