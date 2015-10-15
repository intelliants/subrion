<?php
//##copyright##

if (!iaUsers::hasIdentity())
{
	return iaView::errorPage(iaView::ERROR_UNAUTHORIZED);
}

$iaTransaction = $iaCore->factory('transaction');
$profilePageUrl = IA_URL . 'profile/';

if (iaView::REQUEST_JSON == $iaView->getRequestType() && isset($_GET['amount']))
{
	$output = array('error' => false);
	$amount = (float)$_GET['amount'];

	if ($amount > 0)
	{
		if ($amount >= (float)$iaCore->get('funds_min_amount'))
		{
			$transactionId = $iaTransaction->createInvoice(iaLanguage::get('funds'), $amount, iaTransaction::TRANSACTION_MEMBER_BALANCE, iaUsers::getIdentity(true), $profilePageUrl, 0, true);
			$transactionId
				? $output['url'] = IA_URL . 'pay' . IA_URL_DELIMITER . $transactionId . IA_URL_DELIMITER
				: $output['error'] = iaLanguage::get('db_error');
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

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	iaUsers::reloadIdentity();

	if (isset($_POST['amount']))
	{
		$amount = (float)$_POST['amount'];
		if ($amount > 0)
		{
			if ($amount >= (float)$iaCore->get('funds_min_amount'))
			{
				$iaTransaction->createInvoice(iaLanguage::get('funds'), $amount, iaTransaction::TRANSACTION_MEMBER_BALANCE, iaUsers::getIdentity(true), $profilePageUrl);
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
		'template' => $profilePageUrl . 'funds/?page={page}'
	);

	$pagination['page'] = (isset($_GET['page']) && 1 < $_GET['page']) ? (int)$_GET['page'] : $pagination['page'];
	$pagination['page'] = ($pagination['page'] - 1) * $pagination['limit'];

	$transactions = $iaDb->all('SQL_CALC_FOUND_ROWS *', '`member_id` = ' . iaUsers::getIdentity()->id . ' ORDER BY `status`', $pagination['page'], $pagination['limit'], iaTransaction::getTable());
	$pagination['total'] = $iaDb->foundRows();

	$iaView->caption($iaView->title() . ': ' . number_format(iaUsers::getIdentity()->funds, 2, '.', '') . ' ' . $iaCore->get('currency'));

	$iaView->assign('pagination', $pagination);
	$iaView->assign('transactions', $transactions);

	$iaView->display('transactions');
}