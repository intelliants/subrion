<div class="sap-form form-horizontal">
    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='general'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='invoice_id'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.id}</div>
            </div>

            {if $invoice.transaction_id}
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='transaction_id'}</label>
                    <div class="col col-lg-4 form-control-static">{$invoice.transaction_id}</div>
                </div>
            {/if}

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='date_created'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.date_created|date_format:$core.config.date_format}</div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='date_due'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.date_due|date_format:$core.config.date_format}</div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='fullname'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.fullname|escape}</div>
            </div>

            <hr>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='address_line'} 1</label>
                <div class="col col-lg-4 form-control-static">{$invoice.address1|escape}</div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='address_line'} 2</label>
                <div class="col col-lg-4 form-control-static">{$invoice.address2|escape}</div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='zip'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.zip|escape}</div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='country'}</label>
                <div class="col col-lg-4 form-control-static">{$invoice.country|escape}</div>
            </div>
        </div>

        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='product_items'}</div>

            {if $items}
            <table class="table">
                <tr>
                    <th width="30">#</th>
                    <th>{lang key='item'}</th>
                    <th class="text-right" width="80">{lang key='price'}</th>
                    <th class="text-right" width="50">{lang key='quantity'}</th>
                    <th class="text-right" width="100">{lang key='subtotal'}</th>
                    <th class="text-right" width="70">{lang key='tax'}</th>
                    <th class="text-right" width="70">{lang key='tax'}</th>
                    <th class="text-right" width="110">{lang key='total'}</th>
                </tr>
                {$amount = 0}
                {foreach $items as $entry}
                    <tr>
                        <td>{$entry@iteration}</td>
                        <td>{$entry.title|escape}</td>
                        <td class="text-right">{$entry.price}</td>
                        <td class="text-right">{$entry.quantity}</td>
                        <td class="text-right">
                            {$subtotal = $entry.price * $entry.quantity}
                            {$subtotal|number_format:2}
                        </td>
                        <td class="text-right">{$entry.tax}</td>
                        <td class="text-right">
                            {$tax = $subtotal / 100 * $entry.tax}
                            {$tax|number_format:2}
                        </td>
                        <td class="text-right">
                            {$total = $subtotal + $tax}
                            {$amount = $amount + $total}
                            <strong>{$total|number_format:2}</strong>
                        </td>
                    </tr>
                {/foreach}
                <tr>
                    <td colspan="7" class="text-right"><strong>{lang key='total'}:</strong></td>
                    <td class="text-right"><strong>{$amount|number_format:2}</strong></td>
                </tr>
            </table>
            {else}
            <p class="help-block">{lang key='no_items'}</p>
            {/if}
        </div>

        <div class="form-actions inline">
            <a class="btn btn-primary" href="{$smarty.const.IA_ADMIN_URL}invoices/">{lang key='back'}</a>
        </div>
    </div>
</div>