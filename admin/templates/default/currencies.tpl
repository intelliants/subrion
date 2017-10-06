{if iaCore::ACTION_READ == $core.page.info.action}
    <div class="widget widget-default">
        <div class="widget-content">
            <table cellspacing="0" cellpadding="0" class="table table-light">
                <thead>
                <tr>
                    <th width="30"></th>
                    <th>{lang key='currency'}</th>
                    <th>{lang key='format_example'}</th>
                    <th width="80">{lang key='default'}</th>
                    <th width="120">{lang key='status'}</th>
                    <th width="160"></th>
                </tr>
                </thead>
                <tbody id="js-currencies-list">
                {foreach $currencies as $entry}
                    <tr>
                        <td>
                            <span class="btn btn-default uploads-list-item__drag-handle"><i class="i-list-2"></i></span>
                        </td>
                        <td>
                            <strong class="js-currency-code">{$entry.code}</strong> - {$entry.title|escape}
                        </td>
                        <td>{$entry.format|escape}</td>
                        <td>
                            {if $entry.default}
                                <span class="btn btn-xs disabled"><i class="i-checkmark"></i></span>
                            {elseif iaCore::STATUS_ACTIVE == $entry.status}
                                <a class="btn btn-success btn-xs" href="{$smarty.const.IA_ADMIN_URL}currencies/default/{$entry.code}/"><i class="i-checkmark"></i></a>
                            {/if}
                        </td>
                        <td>{lang key=$entry.status}</td>
                        <td>
                            <a class="btn btn-default btn-xs" href="{$smarty.const.IA_ADMIN_URL}currencies/edit/{$entry.code}/"><i class="i-cog"></i> {lang key='settings'}</a>

                            {if count($currencies) > 1 && !$entry.default}
                                <button type="button" class="btn btn-danger btn-xs js-cmd-delete" data-href="{$smarty.const.IA_ADMIN_URL}currencies/delete/{$entry.code}/"><i class="i-close"></i> {lang key='delete'}</button>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    </div>
    {ia_print_js files='admin/currencies'}
{else}
    <form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
        {preventCsrf}

        <div class="wrap-list">
            <div class="wrap-group">
                <div class="wrap-group-heading">{lang key='general'}</div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-code">{lang key='code'} {lang key='field_required'}</label>
                    </div>
                    <div class="col col-lg-2">
                        <input type="text" name="code" value="{$item.code|escape}" id="input-code" maxlength="3">
                    </div>
                </div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-title">{lang key='title'} {lang key='field_required'}</label>
                    </div>
                    <div class="col col-lg-4">
                        <input type="text" name="title" value="{$item.title|escape}" id="input-title" maxlength="30">
                    </div>
                </div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-sym">{lang key='currency_symbol'}</label>
                    </div>
                    <div class="col col-lg-2">
                        <input type="text" name="symbol" value="{$item.symbol|escape}" id="input-sym" maxlength="5">
                    </div>
                    <div class="col col-lg-2">
                        <select name="sym_pos">
                            <option value="pre"{if 'pre' == $item.sym_pos} selected{/if}>{lang key='currency_symbol_position_before'}</option>
                            <option value="post"{if 'post' == $item.sym_pos} selected{/if}>{lang key='currency_symbol_position_after'}</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="wrap-group">
                <div class="wrap-group-heading">{lang key='rate'}</div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-rate">{lang key='exchange_rate'} {lang key='field_required'}</label>
                    </div>
                    <div class="col col-lg-4">
                        <input type="text" name="rate" value="{$item.rate}" id="input-rate" maxlength="8">
                    </div>
                </div>
            </div>

            <div class="wrap-group">
                <div class="wrap-group-heading">{lang key='format'}</div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-num-decimals">{lang key='number_of_decimal_places'}</label>
                    </div>
                    <div class="col col-lg-1">
                        <input type="text" name="fmt_num_decimals" value="{$item.fmt_num_decimals|intval}" id="input-num-decimals" maxlength="2">
                    </div>
                </div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-dec-point">{lang key='decimal_point'}</label>
                    </div>
                    <div class="col col-lg-1">
                        <input type="text" name="fmt_dec_point" value="{$item.fmt_dec_point|escape}" id="input-dec-point" maxlength="1">
                    </div>
                </div>

                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-thousand-sep">{lang key='thousand_separator'}</label>
                    </div>
                    <div class="col col-lg-1">
                        <input type="text" name="fmt_thousand_sep" value="{$item.fmt_thousand_sep|escape}" id="input-thousand-sep" maxlength="1">
                    </div>
                </div>
            </div>

            {capture name='systems' append='fieldset_before'}
                <div class="row">
                    <div class="col col-lg-2">
                        <label class="control-label" for="input-default">{lang key='default'}</label>
                    </div>
                    <div class="col col-lg-4">
                        {html_radio_switcher value=$item.default name='default'}
                    </div>
                </div>
            {/capture}

            {include 'fields-system.tpl'}
        </div>
    </form>
{/if}