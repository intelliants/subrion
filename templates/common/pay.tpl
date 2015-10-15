{if isset($pay_message) && $pay_message}
	<div class="alert alert-warning">{$pay_message}</div>
{elseif isset($transaction) && $transaction && is_array($transaction)}
	<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>ID</th>
			<th>{lang key='pay_reason'}</th>
			<th>{lang key='item'}</th>
			<th>{lang key='total'}</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>{$transaction.item_id}</td>
			<td>{$transaction.operation}</td>
			<td>{lang key=$transaction.item}</td>
			<td>{$transaction.amount}&nbsp;{$core.config.currency}</td>
		</tr>
	</tbody>
	</table>

	<form method="post" id="payment_form" class="form-horizontal">
		{preventCsrf}
		<div class="plans">
			{if $member && !$isBalancePayment}
				<div class="plans__item">
					<label class="plans__item__header radio">
						<input type="radio" name="source" value="internal"{if $isFundsEnough} checked{else} disabled{/if}>
						<strong>{lang key='pay_using_account_funds'}</strong>
					</label>
					<div class="plans__item__body">{lang key='funds_in_your_account'}</div>
				</div>
			{/if}

			<div class="plans__item">
				<label class="plans__item__header radio">
					<input type="radio" name="source" value="external"{if !$isFundsEnough && $gateways} checked{elseif !$gateways} disabled{/if}>
					<strong>{lang key='pay_external'}</strong>
				</label>
				<div class="plans__item__body">{lang key='pay_via_payment_gateways'}</div>

				{if $gateways}
					<div id="gw_wrap" class="gw-list">
						{ia_hooker name='paymentButtons'}
					</div>
				{elseif $member && iaUsers::MEMBERSHIP_ADMINISTRATOR == $member.usergroup_id}
					<div class="alert alert-warning">{lang key='no_gateway'}</div>
				{/if}
			</div>
		</div>

		<button type="submit" class="btn btn-primary m-t"{if !$gateways && !$isFundsEnough} disabled="disabled"{/if}>{lang key='proceed_pay'}</button>
	</form>

	{ia_print_js files='frontend/pay'}
{/if}