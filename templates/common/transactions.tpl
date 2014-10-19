{if $transactions}
	<h2>{lang key='current_assets'}</h2>
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
			<td>{$transaction.date|date_format:$config.date_format}</td>
			<td>{$transaction.gateway}</td>
			<td>{$transaction.amount} {$transaction.currency}</td>
			<td>
				{if iaTransaction::PENDING == $transaction.status}
					{if empty($transaction.gateway)}
						<a href="pay/{$transaction.sec_key}/" class="btn btn-mini btn-primary">{lang key='pay'}</a>
					{else}
						<a href="pay/{$transaction.sec_key}/?repay" class="btn btn-mini">{lang key='change_gateway'}</a>
					{/if}
					<a href="pay/{$transaction.sec_key}/?delete" class="btn btn-mini btn-danger" onclick="return confirm(_f('are_you_sure_to_cancel_invoice'))">{lang key='cancel'}</a>
				{/if}
			</td>
		</tr>
	{/foreach}
	</tbody>
	</table>

	{navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$pagination.limit aNumPageItems=5 aTruncateParam=1}
{else}
	<div class="alert alert-info">{lang key='no_transactions_records'}</div>
{/if}

<form method="post" class="form-inline ia-form bordered">
	<div class="fieldset">
		<div class="content">
			{preventCsrf}
			{lang key='add_funds'} <input type="text" name="amount"> <button type="submit" class="btn btn-primary btn-plain">{lang key='add_funds'}</button>
		</div>
	</div>
</form>