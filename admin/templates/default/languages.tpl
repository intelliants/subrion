<div id="js-add-phrase-dialog-placeholder" style="margin:0;height:0;overflow:hidden;"></div>

{if iaCore::ACTION_READ != $pageAction}
    <form method="post" class="sap-form form-horizontal">
        {preventCsrf}

        <div class="wrap-list">
            <div class="wrap-group">
                <div class="wrap-group-heading">
                    {if iaCore::ACTION_EDIT == $pageAction}
                        {lang key='edit_language'}
                    {else}
                        {lang key='copy_master_language_to' lang=$core.languages[$core.config.lang].title}
                    {/if}
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='language_iso_code'} <a href="#" class="js-tooltip" title="{$tooltips.language_iso_code}"><i class="i-info"></i></a></label>

                    <div class="col col-lg-4">
                        <input id="input-code" size="2" maxlength="2" type="text" name="code" value="{$item.code}"{if iaCore::ACTION_EDIT == $pageAction} disabled{/if}>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='title'} <a href="#" class="js-tooltip" title="{$tooltips.language_iso_title}"><i class="i-info"></i></a></label>

                    <div class="col col-lg-4">
                        <input id="input-title" size="10" maxlength="40" type="text" name="title" value="{$item.title}">
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='language_locale'} <a href="#" class="js-tooltip" title="{$tooltips.language_locale}"><i class="i-info"></i></a></label>

                    <div class="col col-lg-4">
                        <input id="input-locale" type="text" name="locale" value="{$item.locale}">
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='language_date_format'}</label>

                    <div class="col col-lg-4">
                        <input id="input-date_format" type="text" name="date_format" value="{$item.date_format}">
                        <p class="help-block">{lang key='language_date_format_tooltip'}</p>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='language_time_format'}</label>

                    <div class="col col-lg-4">
                        <input id="input-time_format" type="text" name="time_format" value="{$item.time_format}">
                        <p class="help-block">{lang key='language_time_format_tooltip'}</p>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='language_direction'}</label>

                    <div class="col col-lg-4">
                        <select name="direction">
                            {foreach ['ltr', 'rtl'] as $direction}
                                <option value="{$direction}"{if $direction == $item.direction} selected{/if}>{lang key="language_direction_{$direction}"}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            </div>

            {include 'fields-system.tpl'}
        </div>
    </form>
{else}
    {if 'list' == $action}
        <div class="widget widget-default">
            <div class="widget-content">
                <table cellspacing="0" cellpadding="0" class="table table-light">
                    <thead>
                        <tr>
                            <th width="30"></th>
                            <th>{lang key='language'}</th>
                            <th>{lang key='language_iso_code'}</th>
                            <th>{lang key='language_locale'}</th>
                            <th>{lang key='language_date_format'}</th>
                            <th>{lang key='language_time_format'}</th>
                            <th>{lang key='language_direction'}</th>
                            <th>{lang key='master'}</th>
                            <th>{lang key='default'}</th>
                            <th>{lang key='status'}</th>
                            <th>{lang key='actions'}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="languagesList">
                        {foreach $core.languages as $code => $language}
                            <tr>
                                <td>
                                    <span class="btn btn-default uploads-list-item__drag-handle"><i class="i-list-2"></i></span>
                                </td>
                                <td>
                                    {$language.title} - <a href="{$smarty.const.IA_ADMIN_URL}phrases/?language={$code}">{lang key='edit_phrases'}</a>
                                </td>
                                <td class="iso-val">{$language.iso}</td>
                                <td>{$language.locale}</td>
                                <td>{$language.date_format}</td>
                                <td>{$language.time_format}</td>
                                <td>{$language.direction}</td>
                                <td>
                                    {if $language.master}
                                        <span class="btn btn-xs disabled"><i class="i-checkmark"></i></span>
                                    {/if}
                                </td>
                                <td>
                                    {if $code == $core.config.lang}
                                        <span class="btn btn-xs disabled"><i class="i-checkmark"></i></span>
                                    {elseif iaCore::STATUS_ACTIVE == $language.status}
                                        <a class="btn btn-success btn-xs" href="{$smarty.const.IA_ADMIN_URL}languages/default/{$code}/">{lang key='set_default'}</a>
                                    {/if}
                                </td>
                                <td>{$language.status}</td>
                                <td>
                                    <a class="btn btn-default btn-xs" href="{$smarty.const.IA_ADMIN_URL}languages/download/{$code}/" title="{lang key='download'}"><i class="i-box-add"></i></a>
                                    <a class="btn btn-default btn-xs" href="{$smarty.const.IA_ADMIN_URL}languages/edit/{$language.id}/" title="{lang key='settings'}"><i class="i-cog"></i></a>

                                    {if count($core.languages) > 1 && $code != $core.config.lang}
                                        <button class="btn btn-danger btn-xs js-remove-lang-cmd" data-href="{$smarty.const.IA_ADMIN_URL}languages/rm/{$code}/" title="{lang key='delete'}"><i class="i-close"></i></button>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
    {elseif 'phrases' == $action}
        {include 'grid.tpl'}
    {elseif 'download' == $action}
        <form method="post" class="sap-form form-horizontal">
            {preventCsrf}
            <div class="wrap-list">
                <div class="wrap-group">
                    <div class="wrap-group-heading">{lang key='download'}</div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='language'}</label>
                        <div class="col col-lg-4">
                            <select name="lang">
                                {foreach $core.languages as $code => $language}
                                    <option value="{$code}">{$language.title}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='file_format'}</label>
                        <div class="col col-lg-4">
                            <select name="file_format">
                                <option value="csv"{if isset($smarty.post.file_format) && $smarty.post.file_format == 'csv'} selected{/if}>{lang key='csv_format'}</option>
                                <option value="sql"{if isset($smarty.post.file_format) && $smarty.post.file_format == 'sql'} selected{/if}>{lang key='sql_format'}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='filename'}</label>
                        <div class="col col-lg-4">
                            <input type="text" name="filename" value="{if isset($smarty.post.filename) && $smarty.post.filename}{$smarty.post.filename|escape}{else}subrion_{$smarty.const.IA_VERSION}_{$smarty.const.IA_LANGUAGE}{/if}">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <input type="submit" class="btn btn-success" value="{lang key='download'}">
            </div>
        </form>

        <form action="{$smarty.const.IA_ADMIN_URL}languages/import/" method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
            {preventCsrf}
            <div class="wrap-list">
                <div class="wrap-group">
                    <div class="wrap-group-heading">{lang key='import'}</div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='file_format'}</label>
                        <div class="col col-lg-4">
                            <select name="format">
                                <option value="csv"{if isset($smarty.post.format) && $smarty.post.format == 'csv'} selected="selected"{/if}>{lang key='csv_format'}</option>
                                <option value="sql"{if isset($smarty.post.format) && $smarty.post.format == 'sql'} selected="selected"{/if}>{lang key='sql_format'}</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='title'}</label>
                        <div class="col col-lg-4">
                            <input type="text" name="title">
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='import_from_pc'}</label>
                        <div class="col col-lg-4">
                            {ia_html_file name='language_file'}
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='import_from_server'}</label>
                        <div class="col col-lg-4">
                            <input type="text" name="language_file2" value="../updates/">
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <input type="submit" class="btn btn-success" value="{lang key='import'}" name="form-import">
            </div>
        </form>
    {elseif 'comparison' == $action}
        {if count($core.languages) > 1}
            {ia_add_media files='css:grid-extra'}

            <div id="js-legend-panel" style="display: none;">
                <p><span style="background-color: #e0f2f7; display: inline-block; height: 20px; width: 40px;"></span> Identical phrases pair</p>
                <p><span style="background-color: #eaeaea; display: inline-block; height: 20px; width: 40px;"></span> Incomplete phrases pair</p>
            </div>

            <div id="js-comparison-grid"></div>
        {/if}
    {/if}

    {ia_add_media files='js:intelli/intelli.grid,js:admin/languages'}

    {ia_add_js}
intelli.config.language = '{$smarty.const.IA_LANGUAGE}';
    {/ia_add_js}
{/if}
