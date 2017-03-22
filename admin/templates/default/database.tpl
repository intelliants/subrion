{switch $action}
    {case 'sql' break}
    {if isset($queryOut)}
    <div class="box-simple" id="query_box">{$queryOut}</div>
    {/if}

    <form method="post" class="sap-form form-horizontal">
        {preventCsrf}
        <div class="wrap-list">
            <div class="wrap-group">
                <div class="row gap">
                    <div class="col col-lg-8">
                        <p>{lang key='run_sql_queries'}</p>
                        <textarea style="height: 150px;" rows="6" cols="4" name="query" id="query" class="gap">{if !empty($smarty.post.show_query) && isset($sql) && $sql}{$sql}{else}SELECT * FROM {/if}</textarea>
                        <div id="sqlButtons">
                            <a href="#" class="btn btn-primary btn-sm">WHERE</a>
                            <a href="#" class="btn btn-primary btn-sm">SELECT * FROM</a>
                            <a href="#" class="btn btn-primary btn-sm">FROM</a>
                            <a href="#" class="btn btn-primary btn-sm">INSERT</a>
                            <a href="#" class="btn btn-primary btn-sm">UPDATE</a>
                            <a href="#" class="btn btn-primary btn-sm">SET</a>
                            <a href="#" class="btn btn-primary btn-sm">AND</a>
                            <a href="#" class="btn btn-primary btn-sm">OR</a>
                            <a href="#" class="btn btn-primary btn-sm">ORDER BY</a>
                            <a href="#" class="btn btn-primary btn-sm">GROUP BY</a>
                            <a href="#" class="btn btn-primary btn-sm">LIMIT</a>
                            <a href="#" class="btn btn-primary btn-sm">=</a>
                            <a href="#" class="btn btn-primary btn-sm">!=</a>
                            <a href="#" class="btn btn-primary btn-sm">LIKE</a>
                        </div>
                    </div>
                    <div class="col col-lg-2">
                        <p>{lang key='tables'}</p>
                        <select id="table" size="6" style="height: 150px;" class="gap">
                            {foreach $tables as $table}
                                <option value="{$table}">{$table}</option>
                            {/foreach}
                        </select>
                        <a href="#" id="addTableButton" class="btn btn-sm btn-success"><i class="i-double-angle-left"></i> {lang key='insert'}</a>
                    </div>
                    <div class="col col-lg-2" style="display: none;">
                        <p>{lang key='fields'}</p>
                        <select id="field" size="6" class="gap" style="height: 150px;"><option>&nbsp;</option></select>
                        <a href="#" id="addFieldButton" class="btn btn-sm btn-success"><i class="i-double-angle-left"></i> {lang key='insert'}</a>
                    </div>
                </div>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="show_query" value="1"{if isset($smarty.post.show_query) && $smarty.post.show_query == '1' || !$smarty.post} checked{/if}> {lang key='show_query_again'}
                    </label>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <input type="submit" value="{lang key='go'}" name="exec_query" class="btn btn-success">
            <input type="button" value="{lang key='clear'}" id="clearButton" class="btn btn-default">
        </div>
    </form>

    {if !empty($history)}
    <div id="query_history">
        <p class="help-block">{lang key='query_history'}</p>
        <div class="box-simple">
            <ol>
                {foreach $history as $query}
                    <li><a href="#" class="label label-success"><i class="i-double-angle-up"></i> {lang key='insert'}</a> <span>{$query}</span></li>
                {/foreach}
            </ol>
        </div>
    </div>
    {/if}

    {case 'import' break}
    <form method="post" class="sap-form form-horizontal" id="importFile">
        {preventCsrf}
        {if $dumpFiles}
            <div class="wrap-list">
                <div class="wrap-group">
                    <div class="wrap-group-heading">{lang key='available_dump_files'}</div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='choose_import_file'}</label>
                        <div class="col col-lg-4">
                            <select name="sqlfile" class="gap">
                            {foreach $dumpFiles as $group => $dumps}
                                <optgroup label="{$group}">
                                    {foreach $dumps as $dump}
                                        <option value="{$dump.filename}"{if isset($smarty.post.sqlfile) && $smarty.post.sqlfile == $dump.filename} selected{/if}>{$dump.title}</option>
                                    {/foreach}
                                </optgroup>
                            {/foreach}
                            </select>
                        </div>
                    </div>

                    <div class="form-actions">
                        <input type="submit" name="import" value="{lang key='go'}" class="btn btn-success">
                    </div>
                </div>
            </div>
        {else}
            <div class="alert alert-info">{lang key='no_upgrades'}</div>
        {/if}
    </form>

    <form enctype="multipart/form-data" method="post" name="update" id="update" class="sap-form">
        {preventCsrf}
        <div class="wrap-list">
            <div class="wrap-group">
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='choose_import_file'}</label>
                    <div class="col col-lg-4">
                        {ia_html_file name='sql_file' id='sql_file'}
                        <input type="hidden" name="import" id="run_update">
                        <p class="help-block">{lang key='location_sql_file'} <i>(Max: 2,048KB)</i></p>
                    </div>
                </div>

                <div class="form-actions">
                    <input type="button" id="js-cmd-import" value="{lang key='go'}" class="btn btn-success">
                </div>
            </div>
        </div>
    </form>

    <div class="row">
        <div class="col col-lg-6">
            <div class="widget widget-large" id="widget-usergroups">
                <div class="widget-header"><i class="i-database"></i> {lang key=migrations}</div>
                <div class="widget-content">
                    <table class="table table-light table-hover">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{lang key='name'}</th>
                            <th>{lang key='date'}</th>
                            <th>{lang key='status'}</th>
                            <th width="100">&nbsp;</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $migrations as $migration}
                            <tr>
                                <td>{$migration@iteration}</td>
                                <td>{$migration.name}</td>
                                <td>{$migration.date}</td>
                                <td>
                                    <span>{$migration.status}</span>
                                </td>
                                <td class="text-right">
                                    {if 'complete' != $migration.status}
                                        <button class="btn btn-xs btn-primary js-import-migration" data-filename="{$migration.filename}"><i class="i-chevron-right"></i> {lang key='run'}</button>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col col-lg-6">
            <div class="widget widget-large" id="widget-members">
                <div class="widget-header"><i class="i-lightning"></i> {lang key=migrations_not_applied}</div>
                <div class="widget-content">
                    {if isset($dumpFiles.Migrations)}
                        <table class="table table-light table-hover">
                            <thead>
                            <tr>
                                <th>{lang key='title'}</th>
                                <th>&nbsp;</th>
                            </tr>
                            </thead>
                            <tbody>
                            {foreach $dumpFiles.Migrations as $migration}
                                {if $migration.applied}{continue}{/if}
                                <tr>
                                    <td>{$migration.title}</td>
                                    <td class="text-right">
                                        <button class="btn btn-xs btn-primary js-import-migration" data-filename="{$migration.filename}"><i class="i-chevron-right"></i> {lang key='run'}</button>
                                    </td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    {else}
                        <div class="alert alert-info">{lang key='no_migrations'}</div>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    {ia_add_media files='css: _IA_URL_js/jquery/plugins/scrollbars/jquery.mCustomScrollbar'}
    {ia_print_js files='jquery/plugins/scrollbars/jquery.mCustomScrollbar.concat.min'}

    {ia_add_js}
$(function()
{
    $('.widget-content').mCustomScrollbar({ theme: 'dark-thin' });

    $('.js-import-migration').on('click', function(){

        $('select[name="sqlfile"]').val($(this).data('filename'));
        $('input[name="import"]').click();
    });
});
    {/ia_add_js}

    {case 'export' break}

    {if isset($outerSql)}
        <div class="box-simple box-simple-large">
            <pre>{$outerSql}</pre>
        </div>
        <hr>
    {/if}

    <form method="post" class="sap-form form-horizontal" id="form-dump">
        {preventCsrf}
        <div class="wrap-list">
            <div class="wrap-group">
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='export'}</label>
                    <div class="col col-lg-4">
                        <select name="tbl[]" id="tbl" size="7" multiple="multiple" class="gap">
                            {foreach $tables as $table}
                                <option value="{$table}" selected>{$table}</option>
                            {/foreach}
                        </select>
                        <div class="js-selecting">
                            <a href="#" data-action="select" class="btn btn-default btn-small">{lang key='select_all'}</a>
                            <a href="#" data-action="invert" class="btn btn-default btn-small">{lang key='invert'}</a>
                            <a href="#" data-action="drop" class="btn btn-default btn-small">{lang key='select_none'}</a>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='mysql_options'}</label>
                    <div class="col col-lg-6">
                        <div class="row">
                            <div class="col col-lg-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="sql_structure" value="structure" id="sql_structure"{if isset($smarty.post.sql_structure) || !$smarty.post} checked{/if}> <b>{lang key='structure'}:</b>
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="drop" value="1"{if isset($smarty.post.drop) && $smarty.post.drop} checked{/if} id="dump_drop"> {lang key='add_drop_table'}
                                    </label>
                                </div>
                            </div>
                            <div class="col col-lg-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="sql_data" value="data" id="sql_data"{if isset($smarty.post.sql_data) || !$smarty.post} checked{/if}> <b>Data:</b>
                                    </label>
                                </div>
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="showcolumns" value="1"{if isset($smarty.post.showcolumns) && $smarty.post.showcolumns} checked{/if} id="dump_showcolumns"> {lang key='complete_inserts'}
                                    </label>
                                </div>
                            </div>
                            <div class="col col-lg-4">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" name="real_prefix" id="real_prefix"{if isset($smarty.post.real_prefix) || !$smarty.post} checked{/if}> <b>{lang key='use_real_prefix'}</b>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='save_as_file'}</label>
                    <div class="col col-lg-4">
                        <div class="gap">{html_radio_switcher value=0 name='save_file'}</div>

                        <div id="js-save-options" style="display: none;">
                            <div class="radio">
                                <label>
                                    <input type="radio" name="savetype" value="server"{if isset($unable_to_save)} disabled{/if}>
                                    {lang key='save_to_server'}
                                </label>
                            </div>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="savetype" value="client"{if isset($smarty.post.savetype) && $smarty.post.savetype == 'client' || !$smarty.post} checked{/if}>
                                    {lang key='save_to_pc'}
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <input type="submit" name="export" value="{lang key='go'}" class="btn btn-primary">
        </div>
    </form>

    {case 'consistency' break}
    <form class="sap-form form-horizontal">
        <div class="wrap-list">
            <div class="wrap-group">
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='repair_tables'}</label>
                    <div class="col col-lg-4">
                        <button type="submit" class="btn btn-success btn-small" name="type" value="repair">{lang key='start'}</button>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='optimize_tables'}</label>
                    <div class="col col-lg-4">
                        <button type="submit" class="btn btn-success btn-small" name="type" value="optimize">{lang key='start'}</button>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='sync_multilingual_fields'}</label>
                    <div class="col col-lg-4">
                        <button type="submit" class="btn btn-success btn-small" name="type" value="syncmlfields"{if 1 == count($core.languages)} disabled{/if}>{lang key='start'}</button>
                    </div>
                </div>
                {ia_hooker name='adminDatabaseConsistency'}
            </div>
        </div>
    </form>

    {case 'reset' break}
    <form method="post" class="sap-form form-horizontal">
        {preventCsrf}
        <div class="wrap-list">
            <div class="wrap-group">
                {foreach $options as $key => $option}
                <div class="row">
                    <label class="col col-lg-2 control-label" for="option_{$key}">{$option}</label>
                    <div class="col col-lg-4">
                        <div class="checkbox">
                            <input type="checkbox" id="option_{$key}" name="options[]" value="{$key}">
                        </div>
                    </div>
                </div>
                {/foreach}
            </div>
        </div>
        <div class="form-actions">
            <input type="hidden" name="reset">
            <input type="button" id="js-reset" class="btn btn-warning" value="{lang key='reset'}">
            <input type="button" id="js-reset-all" class="btn btn-danger" value="{lang key='reset_all'}">
        </div>
    </form>
{/switch}
{ia_hooker name='tplAdminDatabaseBeforeFooter'}
{ia_print_js files='admin/database'}