<form method="post" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='general'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='invoice_id'} {lang key='field_required'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="id"{if iaCore::ACTION_EDIT == $pageAction} value="{$id}" disabled{else} value="{$item.id}"{/if}>
                </div>
            </div>

            {if iaCore::ACTION_EDIT == $pageAction}
                {if $item.transaction_id}
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='transaction_id'}</label>
                    <div class="col col-lg-4 form-control-static">{$item.transaction_id}</div>
                </div>
                {/if}

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='date_created'}</label>
                <div class="col col-lg-4 form-control-static">{$item.date_created|date_format:$core.config.date_format}</div>
            </div>
            {/if}

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='date_due'} {lang key='field_required'}</label>
                <div class="col col-lg-4">
                    {$value = $item.date_due}
                    {assign var='default_date' value=($value && !in_array($value, ['0000-00-00', '0000-00-00 00:00:00'])) ? {$value|escape} : ''}

                    <div class="input-group date">
                        <input type="text" class="js-datepicker" name="date_due" id="field_date_due" value="{$default_date}" data-date-format="YYYY-MM-DD HH:mm:ss">
                        <span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
                    </div>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='owner'} {lang key='field_required'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="fullname" value="{$item.fullname|escape}" autocomplete="off">
                    <input type="hidden" name="member_id" id="member-id" {if !empty($item.member_id)}value="{$item.member_id}"{/if}>
                </div>
            </div>

            <hr>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='address_line'} 1</label>
                <div class="col col-lg-4">
                    <input type="text" name="address1" maxlength="255" value="{$item.address1|escape}">
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='address_line'} 2</label>
                <div class="col col-lg-4">
                    <input type="text" name="address2" maxlength="255" value="{$item.address2|escape}">
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='zip'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="zip" maxlength="12" value="{$item.zip|escape}">
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='country'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="country" maxlength="32" value="{$item.country|escape}">
                </div>
            </div>

            <hr>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='notes'}</label>
                <div class="col col-lg-4">
                    <textarea name="notes" rows="5" id="notes">{$item.notes|escape}</textarea>
                    <span class="help-block">{lang key='visible_for_admin'}</span>
                </div>
            </div>
        </div>

        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='product_items'}</div>

            <table class="table" id="js-items-table">
                <tr>
                    <th class="form-control-static" width="30">#</th>
                    <th>{lang key='item'}</th>
                    <th width="80">{lang key='price'}</th>
                    <th width="50">{lang key='quantity'}</th>
                    <th width="70">{lang key='subtotal'}</th>
                    <th width="100">{lang key='tax'}</th>
                    <th width="70">{lang key='tax'}</th>
                    <th width="90">{lang key='total'}</th>
                    <th width="20"></th>
                </tr>
                {foreach $items as $entry}
                <tr>
                    <td><span></span></td>
                    <td><input type="text" name="items[title][]" value="{$entry.title|escape}"></td>
                    <td><input type="text" name="items[price][]" class="js-field-price" value="{$entry.price}"></td>
                    <td><input type="text" name="items[quantity][]" class="js-field-quantity" value="{$entry.quantity}"></td>
                    <td><span></span></td>
                    <td>
                        <div class="input-group">
                            <input type="text" name="items[tax][]" class="js-field-tax" value="{$entry.tax}">
                            <span class="input-group-addon">%</span>
                        </div>
                    </td>
                    <td><span></span></td>
                    <td><span></span></td>
                    <td><button type="button" class="btn btn-sm btn-danger js-cmd-remove-line" title="{lang key='delete'}"><i class="i-minus-alt"></i></button></td>
                </tr>
                {/foreach}
                <tr>
                    <td><span></span></td>
                    <td><input type="text" name="items[title][]"></td>
                    <td><input type="text" name="items[price][]" class="js-field-price"></td>
                    <td><input type="text" name="items[quantity][]" class="js-field-quantity" value="1"></td>
                    <td><span></span></td>
                    <td>
                        <div class="input-group">
                            <input type="text" name="items[tax][]" class="js-field-tax" value="0">
                            <span class="input-group-addon">%</span>
                        </div>
                    </td>
                    <td><span></span></td>
                    <td><span></span></td>
                    <td><button type="button" class="btn btn-sm btn-danger js-cmd-remove-line" title="{lang key='delete'}"><i class="i-minus-alt"></i></button></td>
                </tr>
            </table>
            <div class="text-right">
                <button type="button" class="btn btn-sm btn-info" id="js-cmd-add-line"><i class="i-plus"></i> {lang key='add_line'}</button>
            </div>
        </div>

        {include 'fields-system.tpl' noSystemFields=true}
    </div>
</form>
{ia_add_media files='moment, datepicker'}
{ia_print_js files='ckeditor/ckeditor,jquery/plugins/jquery.numeric,admin/invoices'}