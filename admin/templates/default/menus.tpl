<form method="post" id="js-form-menus" class="sap-form form-horizontal">
    {preventCsrf}
    <input type="hidden" id="js-menu-data" name="menus"{if isset($treeData)} value="{$treeData}"{/if}>

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='name'} {lang key='field_required'}</label>

                <div class="col col-lg-4">
                    {if iaCore::ACTION_ADD == $pageAction}
                        <input type="text" value="{$item.name}" id="input-name" name="name">
                        <p class="help-block">{lang key='unique_name'}</p>
                    {else}
                        <input type="text" value="{$item.name}" class="disabled" disabled>
                        <input type="hidden" value="{$item.name}" id="input-name" name="name">
                        <input type="hidden" id="js-input-id" value="{$id}">
                    {/if}
                </div>
            </div>

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

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='menu_configuration'}</label>

                <div class="col col-lg-4">
                    <div class="row">
                        <div class="col col-lg-6">
                            <h4>{lang key='menus'}</h4>
                            <div id="js-placeholder-menus" class="box-simple box-simple-small" style="height: 240px;"></div>
                        </div>
                        <div class="col col-lg-6">
                            <h4>{lang key='pages'}</h4>
                            <div id="js-placeholder-pages" class="box-simple box-simple-small" style="height: 240px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='place'}</label>

                <div class="col col-lg-4">
                    <select class="common" id="position" name="position">
                        {foreach $positions as $position}
                            <option value="{$position.name}"{if $item.position == $position.name} selected{/if}>{$position.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='css_class_name'}</label>

                <div class="col col-lg-4">
                    <input type="text" name="classname" value="{$item.classname|escape}">
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
                <label class="col col-lg-2 control-label">{lang key='menu_visible_everywhere'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.sticky name='sticky'}
                    <p class="js-visibility-visible help-block">{lang key='menu_visibility_exceptions_visible'}</p>
                    <p class="js-visibility-hidden help-block">{lang key='menu_visibility_exceptions_hidden'}</p>
                </div>
            </div>

            <div id="js-pages-list" class="row">
                <label class="col col-lg-2 control-label"></label>

                <div class="col col-lg-8">
                    <ul class="nav nav-tabs">
                        {foreach $pagesGroup as $group => $row}
                            <li{if $row@iteration == 1} class="active"{/if}><a href="#tab-pages_{$row.name}" data-toggle="tab">{$row.title}</a></li>
                        {/foreach}
                    </ul>

                    <div class="tab-content">
                        {foreach $pagesGroup as $group => $row}
                            {$post_key = "all_pages_{$row.name}"}
                            {$classname = "pages_{$row.name}"}
                            <div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
                                <div class="checkbox checkbox-all">
                                    <label>
                                        <input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="{$post_key}" id="{$post_key}"{if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'} checked{/if}> {lang key='select_all_in_tab'}
                                    </label>
                                </div>

                                {foreach $pages as $key => $page}
                                    {if $page.group == $group}
                                    <div class="checkbox">
                                        <label>
                                            <input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}"{if in_array($page.name, $visibleOn)} checked{/if}>
                                            {if empty($page.title)}{$page.name}{else}{$page.title|escape}{/if}

                                            {if $page.suburl}
                                                <div class="subpages" style="display: none" rel="{$page.suburl}::{$key}">&nbsp;</div>
                                                <input type="hidden" name="subpages[{$page.name}]" value="{if isset($block.subpages[$page.name])}{$block.subpages[$page.name]}{elseif isset($smarty.post.subpages[$page.name])}{$smarty.post.subpages[$page.name]}{/if}" id="subpage_{$key}">
                                            {/if}
                                        </label>
                                    </div>
                                    {/if}
                                {/foreach}
                            </div>
                        {/foreach}
                    </div>

                    <div class="checkbox checkbox-all">
                        <label>
                            <input type="checkbox" value="1" name="all_pages" id="js-select-all-pages"{if isset($smarty.post.all_pages) && $smarty.post.all_pages == 1} checked{/if}> {lang key='select_all'}
                        </label>
                    </div>
                </div>
            </div>
        </div>

        {include 'fields-system.tpl'}
    </div>
</form>
{ia_print_js files='admin/menus'}