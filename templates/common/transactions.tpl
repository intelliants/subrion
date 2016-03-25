{if $transactions}
	<h3>{lang key='current_assets'}</h3>
	<table class="table table-striped table-bordered">
	<thead>
		<tr>
			<th>{lang key='reference_id'}</th>
			<th>{lang key='operation'}</th>
			<th>{lang key='status'}</th>
			<th>{lang key='date'}</th>
			<th>{lang key='gateway'}</th>
			<th>{lang key='total'}</th>
			<th>&nbsp;</th>
		</tr>
	</thead>
	<tbody>
	{foreach $transactions as $transaction}
		<tr>
			<td>{$transaction.reference_id}</td>
			<td>{$transaction.operation}</td>
			<td class="{$transaction.status}">{$transaction.status}</td>
			<td>{$transaction.date_created|date_format:$core.config.date_format}</td>
			<td>{$transaction.gateway}</td>
			<td>{$transaction.amount} {$transaction.currency}</td>
			<td>
				{if iaTransaction::PENDING == $transaction.status}
					{if empty($transaction.gateway)}
						<a href="pay/{$transaction.sec_key}/" class="btn btn-mini btn-primary">{lang key='pay'}</a>
					{else}
						<a href="pay/{$transaction.sec_key}/?repay" class="btn btn-mini">{lang key='change_gateway'}</a>
					{/if}
					<a href="pay/{$transaction.sec_key}/?delete" class="btn btn-mini btn-danger js-cancel-invoice">{lang key='cancel'}</a>
				{elseif iaTransaction::PASSED == $transaction.status}
					<a href="{$smarty.const.IA_SELF}invoice/{$transaction.sec_key}/" class="btn btn-mini btn-info" target="_blank">{lang key='print_invoice'}</a>
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
	</table>

	{navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$pagination.limit aNumPageItems=5 aTruncateParam=1}

	{ia_add_js}
$(function() {
	$('.js-cancel-invoice').on('click', function(e) {
		e.preventDefault();

		intelli.confirm(_t('are_you_sure_to_cancel_invoice'), { url: $(this).attr('href') });
	});
});
	{/ia_add_js}
{else}
	<div class="alert alert-info">{lang key='no_transactions_records'}</div>
{/if}

<div class="row">
	<div class="col-md-6">
		<h3>{lang key='add_funds'}</h3>
		<p>{lang key='add_funds_text'}</p>
	</div>
	<div class="col-md-6">
		<form method="post" class="well ia-form">
			<table class="table table-condensed">
				<tbody>
					<tr>
						<td>Minimum deposit</td>
						<td>{$core.config.currency} {$core.config.funds_min_deposit}</td>
					</tr>
					<tr>
						<td>Maximum deposit</td>
						<td>{$core.config.currency} {$core.config.funds_max_deposit}</td>
					</tr>
					<tr>
						<td>Maximum balance</td>
						<td>{$core.config.currency} {$core.config.funds_max}</td>
					</tr>
				</tbody>
			</table>

			{preventCsrf}

			<div class="form-group">
				<div class="input-group">
					<span class="input-group-addon">{lang key='amount_to_add'}</span>
					<input class="form-control" type="text" name="amount" id="amount" placeholder="{$core.config.funds_min_deposit}">
					<span class="input-group-btn">
						<button class="btn btn-primary" type="submit">{lang key='add_funds'}</button>
					</span>
				</div>
			</div>
		</form>
	</div>
</div>