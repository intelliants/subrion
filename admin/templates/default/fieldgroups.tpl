<form method="post" class="sap-form form-horizontal">
    {preventCsrf}
    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='name'} {lang key='field_required'}</label>

                <div class="col col-lg-4">
                    {if iaCore::ACTION_ADD == $pageAction}
                        <input type="text" name="name" id="input-name" value="{$item.name|escape}">
                        <p class="help-block">{lang key='unique_name'}</p>
                    {else}
                        <input type="text" class="disabled" value="{$item.name|escape}" disabled>
                        <input type="hidden" name="name" id="input-name" value="{$item.name|escape}">
                    {/if}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='item'} {lang key='field_required'}</label>

                <div class="col col-lg-4">
                    {if iaCore::ACTION_ADD == $pageAction}
                        <select name="item" id="input-item">
                            <option value="">{lang key='_select_'}</option>
                            {foreach $items as $itemName}
                                <option value="{$itemName}"{if isset($smarty.post.item) && $smarty.post.item == $itemName || isset($smarty.get.item) && $smarty.get.item == $itemName} selected{/if}>{lang key=$itemName default=$itemName}</option>
                            {/foreach}
                        </select>
                    {else}
                        <select class="disabled" disabled><option>{lang key=$item.item}</option></select>
                        <input type="hidden" name="item" id="input-item" value="{$item.item}">
                    {/if}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='view_as_tab'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.tabview|default:0 name='tabview'}
                </div>
            </div>

            <div class="row" id="js-tab-container">
                <label class="col col-lg-2 control-label">{lang key='tab_container'}</label>

                <div class="col col-lg-4">
                    <input type="hidden" id="tabcontainer" value="{$item.tabcontainer|escape}">
                    <select name="tabcontainer" id="js-fieldgroup-selectbox">
                        <option value="">{lang key='_select_'}</option>
                    </select>
                </div>
            </div>

            <div class="row" id="js-collapsible">
                <label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.collapsible|default:0 name='collapsible'}
                </div>
            </div>

            <div class="row" id="js-collapsed">
                <label class="col col-lg-2 control-label">{lang key='collapsed'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.collapsed|default:0 name='collapsed'}
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
                    <label class="control-label">{lang key='title'} {lang key='field_required'}</label>
                </div>
                <div class="col col-lg-4">
                    {if count($core.languages) > 1}
                        <div class="translate-group" id="language-group-title">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <input type="text" name="titles[{$core.language.iso}]" value="{if isset($smarty.post.titles[$core.language.iso])}{$smarty.post.titles[$core.language.iso]|escape}{elseif isset($item.titles[$core.language.iso])}{$item.titles[$core.language.iso]}{/if}">
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            </div>
                            <div class="translate-group__langs">
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <input type="text" name="titles[{$iso}]" value="{if isset($smarty.post.titles[$iso])}{$smarty.post.titles[$iso]|escape}{elseif isset($item.titles.$iso)}{$item.titles.$iso}{/if}">
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {else}
                        <input type="text" name="titles[{$core.language.iso}]" value="{if isset($smarty.post.titles[$core.language.iso])}{$smarty.post.titles[$core.language.iso]|escape}{elseif isset($item.titles[$core.language.iso])}{$item.titles[$core.language.iso]}{/if}">
                    {/if}
                </div>
            </div>

            <div class="row">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                        <div class="btn-group btn-group-xs translate-group-actions">
                            <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-description"><span class="i-earth"></span></button>
                            <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-description"><span class="i-copy"></span></button>
                        </div>
                    {/if}
                    <label class="control-label">{lang key='description'}</label>
                </div>
                <div class="col col-lg-4">
                    {if count($core.languages) > 1}
                        <div class="translate-group" id="language-group-description">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <textarea rows="6" name="description[{$core.language.iso}]">{if isset($smarty.post.description[$core.language.iso])}{$smarty.post.description[$core.language.iso]|escape}{elseif isset($item.description[$core.language.iso])}{$item.description[$core.language.iso]}{/if}</textarea>
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            </div>
                            <div class="translate-group__langs">
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <textarea rows="6" name="description[{$iso}]">{if isset($smarty.post.description.$iso)}{$smarty.post.description.$iso|escape}{elseif isset($item.description.$iso)}{$item.description.$iso}{/if}</textarea>
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {else}
                        <textarea rows="6" name="description[{$core.language.iso}]">{if isset($smarty.post.description[$core.language.iso])}{$smarty.post.description[$core.language.iso]|escape}{elseif isset($item.description[$core.language.iso])}{$item.description[$core.language.iso]}{/if}</textarea>
                    {/if}
                </div>
            </div>
        </div>

        {include 'fields-system.tpl'}
    </div>
</form>

{ia_add_media files='js:admin/fieldgroups'}