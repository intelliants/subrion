<?php
/******************************************************************************
 *
 * Subrion - open source content management system
 * Copyright (C) 2014 Intelliants, LLC <http://www.intelliants.com>
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
	iaBreadcrumb::preEnd(iaLanguage::get('payment'), IA_SELF);

	// enable logging for payments
	if (INTELLI_DEBUG)
	{
		// require log class
		require_once IA_INCLUDES . 'utils/KLogger.php';

		$iaLog = new KLogger(IA_TMP, KLogger::INFO);
	}

	$transactionId = isset($iaCore->requestPath[0]) ? iaSanitize::paranoid($iaCore->requestPath[0]) : 0;

	if (empty($transactionId))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND);
	}

	$iaTransaction = $iaCore->factory('transaction');

	$transaction = $iaTransaction->getBy('sec_key', $transactionId);

	if (empty($transaction))
	{
		return iaView::errorPage(iaView::ERROR_NOT_FOUND, iaLanguage::get('no_transaction'));
	}

	$tplFile = 'pay';

	$error = false;
	$messages = array();

	$iaPlan = $iaCore->factory('plan', iaCore::FRONT);
	$iaUsers = $iaCore->factory('users');
	$iaPage = $iaCore->factory('page', iaCore::FRONT);

	// configure return url on payment success
	if (isset($transaction['sec_key']))
	{
		define('IA_RETURN_URL', IA_URL . 'pay/' . $transaction['sec_key'] . '/');
	}

	$action = isset($iaCore->requestPath[1]) ? iaSanitize::sql($iaCore->requestPath[1]) : null;
	$gateways = $iaTransaction->getPaymentGateways();
	$balance = (iaUsers::hasIdentity() && 'balance' == $transaction['item'] && iaUsers::getIdentity()->id == $transaction['item_id']);

	$iaView->assign('balance', $balance);

	// if account has enough funds to pay internally
	$isFundsEnough = (bool)(!$balance && iaUsers::hasIdentity() && iaUsers::getIdentity()->funds >= $transaction['total']);

	// delete transaction
	if (isset($_GET['delete']))
	{
		$iaTransaction->delete($transaction['id']);

		$iaView->setMessages(iaLanguage::get('invoice_deleted'), iaView::SUCCESS);
		iaUtil::go_to($iaPage->getUrlByName('member_balance'));
	}

	// cancel payment
	if (iaTransaction::CANCELED == $action)
	{
		$iaTransaction->update(array('status' => iaTransaction::FAILED), $transaction['id']);

		$iaView->setMessages(iaLanguage::get('payment_canceled'), iaView::SUCCESS);
		iaUtil::go_to($iaPage->getUrlByName('member_balance'));
	}
	else
	{
		$order = array();

		switch ($transaction['status'])
		{
			case iaTransaction::PENDING:
				if (isset($_POST['source']) && 'internal' == $_POST['source'])
				{
					$iaCore->startHook('phpFrontPaymentBeforeBalanceUpdate');

					if ($transaction && isset($_POST['source']) && 'internal' == $_POST['source'])
					{
						$iaPlan->extractFunds($transaction);
						iaUtil::redirect(iaLanguage::get('thanks'), iaLanguage::get('payment_done'), $transaction['return_url']);
					}
				}
				elseif (empty($transaction['gateway_name']) || isset($_GET['repay']))
				{
					if (empty($transaction['plan_id']))
					{
						$plan['title'] = $transaction['operation_name'];
						$plan['cost'] = $transaction['total'];
					}
					else
					{
						$plan = $iaPlan->getPlanById($transaction['plan_id']);
					}

					$plan['title'] = $transaction['item'] . ' - ' . $plan['title'];
					$iaView->assign('plan', $plan);

					foreach ($gateways as $key => $gateway)
					{
						$gateway_form = IA_PLUGINS . $key . IA_DS . 'templates' . IA_DS . 'form.tpl';
						$gateways[$key] = file_exists($gateway_form) ? $gateway_form : false;
					}

					// process payment button click
					if (isset($_POST['payment_type']))
					{
						$gate = iaSanitize::sql($_POST['payment_type']);

						if (isset($gateways[$gate]))
						{
							$affected = $iaDb->update(array('id' => $transaction['id'], 'gateway_name' => $gate), null, array('date' => iaDb::FUNCTION_NOW), iaTransaction::getTable());

							// include pre form send files
							$pre_php_gate = IA_PLUGINS . $gate . IA_DS . 'includes' . IA_DS . 'pre-processing' . iaSystem::EXECUTABLE_FILE_EXT;
							if (file_exists($pre_php_gate))
							{
								include $pre_php_gate;
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
				}
				elseif (!empty($transaction['gateway_name']) && 'completed' == $action)
				{
					$gate = $transaction['gateway_name'];
					if (isset($gateways[$gate]))
					{
						$temp_transaction = $transaction;

						$transaction = array();

						// include post form send files
						$post_php_gate = IA_PLUGINS . $gate . IA_DS . 'includes' . IA_DS . 'post-processing' . iaSystem::EXECUTABLE_FILE_EXT;

						if (file_exists($post_php_gate))
						{
							// set to true if custom transaction handling needed
							$replaceHandler = false;
							$iaCore->startHook('phpPayBeforeIncludePostGate');

							include $post_php_gate;

							$iaCore->startHook('phpPayAfterIncludePostGate');

							// print transaction information
							if (isset($iaLog))
							{
								$iaLog->logInfo('Processed transaction information', $transaction);
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
									$iaPlan->postPayment($transaction);

									// notify admin of a complete payment
									$action = 'admin_payment_notification';
									if ($iaCore->get($action))
									{
										$iaMailer = $iaCore->factory('mailer');

										$iaMailer->load_template($action);
										$iaMailer->AddAddress($iaCore->get('site_email'));
										$iaMailer->replace['{%USERNAME%}'] = iaUsers::getIdentity()->username;
										$iaMailer->replace['{%AMOUNT%}'] = $transaction['total'];
										$iaMailer->replace['{%OPERATION%}'] = $transaction['operation_name'];

										$iaMailer->Send();
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
					iaUtil::go_to($iaPage->getUrlByName('member_balance'));
				}

				break;

			case iaTransaction::REFUNDED:
			case iaTransaction::FAILED:
				$iaView->setMessages($messages);
				iaUtil::go_to($iaPage->getUrlByName('member_balance'));

				break;

			default:
				$error = true;
				$messages[] = 'Unknown status';
		}
	}

	$iaView->setMessages($messages, $error ? iaView::ERROR : iaView::SUCCESS);

	$memberBalance = iaUsers::hasIdentity() ? iaUsers::getIdentity()->funds : 0;
	$phrase = iaLanguage::getf('balance_in_your_account', array('sum' => $memberBalance, 'currency' => $iaCore->get('currency')));
	iaLanguage::set('balance_in_your_account', $phrase);

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

	$iaView->assign('order', $order);
	$iaView->assign('gateways', $gateways);
	$iaView->assign('enough_funds', $isFundsEnough);
	$iaView->assign('transaction', $transaction);

	$iaView->display($tplFile);
}