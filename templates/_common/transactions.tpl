{if $transactions}
    <h3>{lang key='current_assets'}</h3>
    <table class="table table-striped">
    <thead>
        <tr>
            <th>{lang key='operation'}</th>
            <th>{lang key='total'}</th>
            <th>{lang key='date'}</th>
            <th>{lang key='status'}</th>
        </tr>
    </thead>
    <tbody>
    {foreach $transactions as $transaction}
        <tr>
            <td>
                <p><strong>{$transaction.operation}</strong></p>
                {if $transaction.gateway_icon}<img src="{$transaction.gateway_icon}" alt="{$transaction.gateway_title|escape}" height="18">{/if}
                {if $transaction.reference_id}
                    {lang key='reference_id'}: <strong>{$transaction.reference_id}</strong>
                {/if}
            </td>
            <td>{$transaction.amount} {$transaction.currency}</td>
            <td>{$transaction.date_created|date_format:$core.config.date_format}</td>
            <td class="{$transaction.status}">
                <p>{lang key=$transaction.status default=$transaction.status}</p>
                {if iaTransaction::PENDING == $transaction.status}
                    {if empty($transaction.gateway)}
                        <a href="pay/{$transaction.sec_key}/" class="btn btn-xs btn-primary">{lang key='pay'}</a>
                    {else}
                        <a href="pay/{$transaction.sec_key}/?repay" class="btn btn-xs">{lang key='change_gateway'}</a>
                    {/if}
                    <a href="pay/{$transaction.sec_key}/?delete" class="btn btn-xs btn-danger js-cancel-invoice">{lang key='cancel'}</a>
                {elseif iaTransaction::PASSED == $transaction.status}
                    <a href="{$smarty.const.IA_SELF}invoice/{$transaction.sec_key}/" class="btn btn-xs btn-info" target="_blank">{lang key='print_invoice'}</a>
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
                        <td>{lang key='min_deposit'}</td>
                        <td>{$core.config.currency} {$core.config.funds_min_deposit}</td>
                    </tr>
                    <tr>
                        <td>{lang key='max_deposit'}</td>
                        <td>{$core.config.currency} {$core.config.funds_max_deposit}</td>
                    </tr>
                    <tr>
                        <td>{lang key='max_balance'}</td>
                        <td>{$core.config.currency} {$core.config.funds_max}</td>
                    </tr>
                </tbody>
            </table>

            {preventCsrf}

            <div class="form-group">
                <label>{lang key='amount_to_add'}</label>
                <div class="input-group">
                    <input class="form-control" type="text" name="amount" id="amount" placeholder="{$core.config.funds_min_deposit}">
                    <span class="input-group-btn">
                        <button class="btn btn-primary" type="submit">{lang key='add_funds'}</button>
                    </span>
                </div>
            </div>
        </form>
    </div>
</div>