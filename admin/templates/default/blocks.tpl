<form method="post" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='name'}</label>

                <div class="col col-lg-4">
                    {if iaCore::ACTION_ADD == $core.page.info.action}
                        <input type="text" name="name" value="{$item.name|escape}">
                        <p class="help-block">{lang key='unique_name'}</p>
                    {else}
                        <input type="text" value="{$item.name|escape}" disabled>
                    {/if}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='type'}</label>

                <div class="col col-lg-4">
                    <select name="type" id="input-block-type">
                        {foreach $types as $type}
                            {if iaBlock::TYPE_MENU != $type}
                                {access object='admin_page' id='blocks' action=$type}
                                {$tip = {lang key="block_type_tip_{$type}"}}
                                <option value="{$type}" data-tip="{$tip|escape}"{if $type == $item.type} selected{/if}>{$type}</option>
                                {/access}
                            {/if}
                        {/foreach}
                    </select>
                    <p class="help-block"></p>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='position'}</label>

                <div class="col col-lg-4">
                    <select name="position">
                        {foreach $positions as $position}
                            <option value="{$position.name}"{if isset($item.position) && $item.position == $position.name} selected{/if}>{$position.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='css_class_name'}</label>

                <div class="col col-lg-4">
                    <input type="text" name="classname" value="{if isset($item.classname)}{$item.classname}{/if}">
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='show_header'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.header name='header'}
                </div>
            </div>

            <div class="row" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.collapsible name='collapsible'}
                </div>
            </div>

            <div class="row" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='collapsed'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.collapsed name='collapsed'}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='block_visible_everywhere'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.sticky name='sticky'}
                    <p class="help-block" data-sticky="0">{lang key='block_visibility_exceptions_visible'}</p>
                    <p class="help-block" data-sticky="1">{lang key='block_visibility_exceptions_hidden'}</p>
                </div>
            </div>

            <div class="row" id="js-pages-list">
                <label class="col col-lg-2 control-label"></label>

                <div class="col col-lg-8">
                    <ul class="nav nav-tabs">
                        {foreach $pagesGroup as $group => $row}
                            <li{if $row@iteration == 1} class="active"{/if}><a href="#tab-visible_{$row.name}" data-toggle="tab">{$row.title}</a></li>
                        {/foreach}
                    </ul>

                    <div class="tab-content">
                        {foreach $pagesGroup as $group => $row}
                            {assign post_key "select_all_{$row.name}"}
                            {assign classname "visible_{$row.name}"}
                            <div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
                                <div class="checkbox checkbox-all">
                                    <label>
                                        <input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="select_all_{$classname}" id="select_all_{$classname}"{if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'} checked{/if}> {lang key='select_all_in_tab'}
                                    </label>
                                </div>

                                {foreach $pages as $key => $page}
                                    {if $page.group == $group}
                                        <div class="checkbox">
                                            <label>
                                                <input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}" id="page_{$key}"{if in_array($page.name, $item.pages)} checked{/if}> {$page.title|escape}
                                            </label>
                                        </div>
                                        {if $page.suburl}
                                            <div class="subpages" style="display:none" rel="{$page.suburl}::{$key}">&nbsp;</div>
                                            <input type="hidden" name="subpages[{$page.name}]" value="{if isset($item.subpages[$page.name])}{$item.subpages[$page.name]}{elseif isset($smarty.post.subpages[$page.name])}{$smarty.post.subpages[$page.name]}{/if}" id="subpage_{$key}">
                                        {/if}
                                    {/if}
                                {/foreach}
                            </div>
                        {/foreach}
                    </div>

                    <div class="checkbox checkbox-all">
                        <label>
                            <input type="checkbox" value="1" name="select_all" id="js-pages-select-all"{if isset($smarty.post.select_all) && $smarty.post.select_all == '1'} checked{/if}> {lang key='select_all'}
                        </label>
                    </div>
                </div>
            </div>

            <div id="pages" class="row" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='pages_contains'}</label>

                <div class="col col-lg-4">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" value="1" name="all_pages" id="all_pages"{if isset($smarty.post.all_pages) && $smarty.post.all_pages == '1'} checked{/if}> {lang key='select_all'}
                        </label>
                    </div>

                    <ul class="nav nav-tabs">
                        {foreach $pagesGroup as $group => $row}
                            <li{if $row@iteration == 1} class="active"{/if}><a href="#tab-pages_{$row.name}" data-toggle="tab">{$row.title}</a></li>
                        {/foreach}
                    </ul>

                    <div class="tab-content">
                        {foreach $pagesGroup as $group => $row}
                            {assign post_key "all_pages_{$row.name}"}
                            {assign classname "pages_{$row.name}"}
                            <div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
                                <div class="checkbox">
                                    <label>
                                        <input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="{$post_key}" id="{$post_key}"{if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'} checked{/if}>
                                    </label>
                                </div>

                                {foreach $pages as $key => $page}
                                    {if $page.group == $group}
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}"{if in_array($page.name, $menuPages, true)} checked{/if}>
                                            {if empty($page.title)}{$page.name}{else}{$page.title}{/if}
                                        </label>
                                    </div>
                                    {/if}
                                {/foreach}
                            </div>
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='block_contents'}</div>

            <div class="row">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                        <div class="btn-group btn-group-xs translate-group-actions">
                            <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-title"><span class="i-earth"></span></button>
                            <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-title"><span class="i-copy"></span></button>
                        </div>
                    {/if}
                    <label class="control-label">{lang key='title'}</label>
                </div>
                <div class="col col-lg-4">
                    {if count($core.languages) > 1}
                        <div class="translate-group" id="language-group-title">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <input type="text" name="title[{$core.language.iso}]"{if isset($item.title[$core.language.iso])} value="{$item.title[$core.language.iso]|escape}"{/if}>
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            </div>
                            <div class="translate-group__langs">
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <input type="text" name="title[{$iso}]"{if isset($item.title[$iso])} value="{$item.title[$iso]|escape}"{/if}>
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {else}
                        <input type="text" name="title[{$core.language.iso}]"{if isset($item.title[$core.language.iso])} value="{$item.title[$core.language.iso]|escape}"{/if}>
                    {/if}
                </div>
            </div>

            <div class="wrap-row" id="js-content-dynamic">
                <div class="row" id="js-external-row">
                    <label class="col col-lg-2 control-label">{lang key='external_file'}</label>

                    <div class="col col-lg-4">
                        {html_radio_switcher value=$item.external name='external'}
                    </div>
                </div>

                <div class="row" id="js-row-external-file-name"{if !$item.external} style="display: none"{/if}>
                    <label class="col col-lg-2 control-label">{lang key='filename'}</label>

                    <div class="col col-lg-4">
                        <input type="text" name="filename" value="{$item.filename|escape}">
                        {if iaCore::ACTION_ADD == $core.page.info.action}
                            <p class="help-block">{lang key='filename_notification'}</p>
                        {/if}
                    </div>
                </div>

                <div class="row" id="js-row-dynamic-content"{if $item.external} style="display: none"{/if}>
                    <label class="col col-lg-2 control-label">{lang key='contents'}</label>

                    <div class="col col-lg-8">
                        <textarea name="contents" id="input-content" rows="8">{$item.contents|escape}</textarea>
                    </div>
                </div>
            </div>

            <div class="wrap-row" id="js-content-static">
                <div class="row">
                    <div class="col col-lg-2">
                        {if count($core.languages) > 1}
                            <div class="btn-group btn-group-xs translate-group-actions">
                                <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-content"><span class="i-earth"></span></button>
                                <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-content"><span class="i-copy"></span></button>
                            </div>
                        {/if}
                        <label class="control-label">{lang key='contents'}</label>
                    </div>
                    <div class="col col-lg-8">
                        {if count($core.languages) > 1}
                            <div class="translate-group" id="language-group-content">
                                <div class="translate-group__default">
                                    <div class="translate-group__item">
                                        <textarea name="content[{$core.language.iso}]" id="content_{$core.language.iso}" class="js-ckeditor resizable" rows="8">{if isset($item.content[$core.language.iso])}{$item.content[$core.language.iso]|escape}{/if}</textarea>
                                        <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                    </div>
                                </div>
                                <div class="translate-group__langs">
                                    {foreach $core.languages as $iso => $language}
                                        {if $iso != $core.language.iso}
                                            <div class="translate-group__item">
                                                <textarea name="content[{$iso}]" id="content_{$iso}" class="js-ckeditor resizable" rows="8">{if isset($item.content[$iso])}{$item.content[$iso]|escape}{/if}</textarea>
                                                <span class="translate-group__item__code">{$language.title|escape}</span>
                                            </div>
                                        {/if}
                                    {/foreach}
                                </div>
                            </div>
                        {else}
                            <textarea name="content[{$core.language.iso}]" id="content_{$core.language.iso}" class="js-ckeditor resizable" rows="8">{if isset($item.content[$core.language.iso])}{$item.content[$core.language.iso]|escape}{/if}</textarea>
                        {/if}
                    </div>
                </div>
            </div>
        </div>

        {include 'fields-system.tpl'}
    </div>
</form>
{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/blocks'}