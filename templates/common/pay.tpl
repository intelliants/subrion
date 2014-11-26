{if isset($pay_message) && $pay_message}
	<div class="alert alert-warning">{$pay_message}</div>
{elseif isset($transaction) && $transaction && is_array($transaction)}
	<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>{lang key='pay_reason'}</th>
			<th>{lang key='item'}</th>
			<th>ID</th>
			<th>{lang key='total'}</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>{$transaction.operation}</td>
			<td>{lang key=$transaction.item}</td>
			<td>{$transaction.item_id}</td>
			<td>{$transaction.amount}&nbsp;{$config.currency}</td>
		</tr>
	</tbody>
	</table>

	<form method="post" id="payment_form" class="form-horizontal">
		{preventCsrf}
		{if $member && !$isBalancePayment}
			<label class="radio">
				<input type="radio" name="source" value="internal"{if $isFundsEnough} checked{else} disabled{/if}>
				<strong>{lang key='pay_using_account_funds'}</strong>
			</label>
			<div class="plan_description">{lang key='balance_in_your_account'}</div>
		{/if}

		<div class="plan">
			<label class="radio">
				<input type="radio" name="source" value="external"{if !$isFundsEnough && $gateways} checked{elseif !$gateways} disabled{/if}>
				<strong>{lang key='pay_external'}</strong>
			</label>
			<div class="plan_description">{lang key='pay_via_payment_gateways'}</div>
		</div>

		{if $gateways}
			<hr />
			<div style="display: none;" id="gw_wrap" class="clearfix">
				{ia_hooker name='paymentButtons'}
			</div>
			<hr />
		{elseif $member && iaUsers::MEMBERSHIP_ADMINISTRATOR == $member.usergroup_id}
			<div class="alert alert-warning">{lang key='no_gateway'}</div>
		{/if}
		<button type="submit" class="btn btn-primary"{if !$gateways && !$isFundsEnough} disabled="disabled"{/if}>{lang key='proceed_pay'}</button>
	</form>

	{ia_print_js files='frontend/pay'}
{/if}