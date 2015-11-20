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
			<td>{$transaction.date|date_format:$core.config.date_format}</td>
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

<form method="post" class="form-inline ia-form">
	{preventCsrf}
	<div class="form-group">
		<label for="amount">{lang key='add_funds'}</label>
		<input class="form-control" type="text" name="amount" id="amount">
	</div>
	<button type="submit" class="btn btn-primary">{lang key='add_funds'}</button>
</form>