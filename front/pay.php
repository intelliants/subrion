<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2015 Intelliants, LLC <http://www.intelliants.com>
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
 * @link http://www.subrion.org/
 *
 ******************************************************************************/

if (iaView::REQUEST_HTML == $iaView->getRequestType())
{
	$transactionId = isset($iaCore->requestPath[0]) ? iaSanitize::paranoid($iaCore->requestPath[0]) : 0;
	$action = isset($iaCore->requestPath[1]) ? iaSanitize::sql($iaCore->requestPath[1]) : null;

	if (empty($transactionId))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaTransaction = $iaCore->factory('transaction');
	$iaPage = $iaCore->factory('page', iaCore::FRONT);

	$transaction = $iaTransaction->getBy('sec_key', $transactionId);

	if (empty($transaction))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND, iaLanguage::get('no_transaction'));
	}

	// delete transaction
	if (isset($_GET['delete']))
	{
		$iaTransaction->delete($transaction['id']);

		$iaView->setMessages(iaLanguage::get('invoice_deleted'), iaView::SUCCESS);
		iaUtil::go_to($iaPage->getUrlByName('member_funds'));
	}

	// cancel payment
	if ('canceled' == $action)
	{
		$iaTransaction->update(array('status' => iaTransaction::FAILED), $transaction['id']);

		$iaView->setMessages(iaLanguage::get('payment_canceled'), iaView::SUCCESS);
		iaUtil::go_to($iaPage->getUrlByName('member_funds'));
	}

	// configure return url on payment success
	if (isset($transaction['sec_key']))
	{
		define('IA_RETURN_URL', IA_URL . 'pay/' . $transaction['sec_key'] . IA_URL_DELIMITER);
	}

	$gateways = $iaTransaction->getPaymentGateways();
	$order = array();
	$tplFile = 'pay';

	$error = false;
	$messages = array();

	$iaPlan = $iaCore->factory('plan');
	$iaUsers = $iaCore->factory('users');

	$iaCore->startHook('phpFrontBeforePaymentProcessing', array('transaction' => $transaction));

	switch ($transaction['status'])
	{
		case iaTransaction::PENDING:
			if (isset($_POST['source']) && 'internal' == $_POST['source'])
			{
				$iaCore->startHook('phpFrontPaymentBeforeBalanceUpdate');

				if ($transaction && isset($_POST['source']) && 'internal' == $_POST['source'])
				{
					if ($iaPlan->extractFunds($transaction))
					{
						empty($_POST['invaddr']) || $iaCore->factory('invoice')->updateAddress($transaction['id'], $_POST['invaddr']); /*-- MOD // jjangaraev --*/
						$iaPlan->setPaid($transaction);
						iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('payment_done'), $transaction['return_url']);
					}
				}
			}
			elseif (empty($transaction['gateway']) || isset($_GET['repay']))
			{
				if (empty($transaction['plan_id']))
				{
					$plan['title'] = $transaction['operation'];
					$plan['cost'] = $transaction['amount'];
				}
				else
				{
					$plan = $iaPlan->getById($transaction['plan_id']);
				}

				$plan['title'] = $transaction['item'] . ' - ' . $plan['title'];

				$iaView->assign('plan', $plan);
				$iaView->assign('address', $iaCore->factory('invoice')->getAddress($transaction['id']));

				foreach ($gateways as $key => $gateway)
				{
					$htmlFormTemplate = IA_PLUGINS . $key . IA_DS . 'templates' . IA_DS . 'form.tpl';
					$gateways[$key] = file_exists($htmlFormTemplate) ? $htmlFormTemplate : false;
				}

				// process payment button click
				if (isset($_POST['payment_type']))
				{
					$gate = iaSanitize::sql($_POST['payment_type']);

					if (isset($gateways[$gate]))
					{
						$affected = $iaDb->update(array('id' => $transaction['id'], 'gateway' => $gate), null, array('date' => iaDb::FUNCTION_NOW), iaTransaction::getTable());
						$iaCore->factory('invoice')->updateAddress($transaction['id'], $_POST['invaddr']);

						// include pre form send files
						$paymentGatewayHandler = IA_PLUGINS . $gate . IA_DS . 'includes' . IA_DS . 'pre-processing' . iaSystem::EXECUTABLE_FILE_EXT;
						if (file_exists($paymentGatewayHandler))
						{
							include $paymentGatewayHandler;
						}

						if (!empty($gateways[$gate]))
						{
							$data = array(
								'caption' => 'Redirect to ' . $gate . '',
								'msg' => 'You will be redirected to ' . $gate . '',
								'form' => $gateways[$gate]
							);

							$iaView->assign('redir', $data);

							$tplFile = 'redirect-gateway';
							$iaView->disableLayout();
						}
					}
				}

				iaBreadcrumb::add(iaLanguage::get('page_title_member_funds'),
					$iaCore->factory('page', iaCore::FRONT)->getUrlByName('member_funds'));
			}
			elseif (!empty($transaction['gateway']) && 'completed' == $action)
			{
				$gate = $transaction['gateway'];
				if (isset($gateways[$gate]))
				{
					$temp_transaction = $transaction;

					$transaction = array();

					// include post form send files
					$paymentGatewayHandler = IA_PLUGINS . $gate . IA_DS . 'includes' . IA_DS . 'post-processing' . iaSystem::EXECUTABLE_FILE_EXT;

					if (file_exists($paymentGatewayHandler))
					{
						// set to true if custom transaction handling needed
						$replaceHandler = false;
						$iaCore->startHook('phpPayBeforeIncludePostGate', array('gateway' => $gate));

						include $paymentGatewayHandler;

						$iaCore->startHook('phpPayAfterIncludePostGate', array('gateway' => $gate));

						// print transaction information
						if (INTELLI_DEBUG)
						{
							iaDebug::log('Processed transaction information', $transaction);
						}

						// use default processing handler
						if (false === $replaceHandler)
						{
							if (!$transaction)
							{
								return iaView::errorPage(iaView::ERROR_FORBIDDEN, $messages);
							}

							if (in_array($transaction['status'], array(iaTransaction::PASSED, iaTransaction::PENDING)))
							{
								// update transaction record
								$iaTransaction->update($transaction, $transaction['id']);

								// process item specific post-processing actions
								if (iaTransaction::PASSED == $transaction['status'])
								{
									$iaPlan->setPaid($transaction);
								}

								// notify admin of a completed payment
								$action = 'payment_completion_admin';
								if ($iaCore->get($action))
								{
									$iaMailer = $iaCore->factory('mailer');

									$iaMailer->loadTemplate($action);
									$iaMailer->addAddress($iaCore->get('site_email'));
									$iaMailer->setReplacements(array(
										'username' => iaUsers::getIdentity()->username,
										'amount' => $transaction['amount'],
										'operation' => $transaction['operation']
									));

									$iaMailer->send();
								}

								// disable debug display
								$iaView->set('nodebug', true);

								$tplFile = 'purchase-post';
							}
							else
							{
								return iaView::errorPage(iaView::ERROR_NOT_FOUND, $messages);
							}
						}
					}
					else
					{
						return iaView::errorPage(iaView::ERROR_NOT_FOUND);
					}
				}
			}
			else
			{
				$iaView->assign('pay_message', iaLanguage::getf('wait_for_gateway_answer', array('url' => IA_SELF . '?repay')));
			}

			break;

		case iaTransaction::PASSED:
			if ('ipn' == $action)
			{
				$iaTransaction->createIpn($transaction);
			}
			else
			{
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
	iaLanguage::set('funds_in_your_account', iaLanguage::getf('funds_in_your_account', array('sum' => $memberBalance, 'currency' => $iaCore->get('currency'))));

	$isBalancePayment = (iaUsers::hasIdentity() && iaTransaction::TRANSACTION_MEMBER_BALANCE == $transaction['item'] && iaUsers::getIdentity()->id == $transaction['item_id']);
	$isFundsEnough = (bool)(!$isBalancePayment && iaUsers::hasIdentity() && iaUsers::getIdentity()->funds >= $transaction['amount']);

	// FIXME: solution to prevent csrf catching.
	// Should be replaced once it is possible to disable csrf checking for a single page.
	if (isset($_POST))
	{
		$paymentPost = $_POST;

		if (isset($_SERVER['HTTP_ORIGIN']))
		{
			$wwwChunk = 'www.';

			$referrerDomain = explode(IA_URL_DELIMITER, $_SERVER['HTTP_ORIGIN']);
			$referrerDomain = strtolower($referrerDomain[2]);
			$referrerDomain = str_replace($wwwChunk, '', $referrerDomain);
			$domain = explode(IA_URL_DELIMITER, $iaCore->get('baseurl'));
			$domain = strtolower($domain[2]);
			$domain = str_replace($wwwChunk, '', $domain);

			if ($referrerDomain !== $domain)
			{
				$_POST = array();
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