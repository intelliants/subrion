{if iaCore::ACTION_READ == $pageAction}
    {include 'grid.tpl'}

    {ia_add_media files='js:intelli/intelli.grid,js:admin/phrases'}

    {ia_add_js}
        intelli.config.language = '{$smarty.const.IA_LANGUAGE}';
    {/ia_add_js}
{else}
    <form method="post" class="sap-form form-horizontal">
        {preventCsrf}

        <div class="wrap-list">
            <div class="wrap-group">
                <div class="wrap-group-heading">{lang key='options'}</div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='key'}</label>

                    <div class="col col-lg-4">
                        <input id="input-key" type="text" name="key" value="{$item.key}"{if iaCore::ACTION_EDIT == $pageAction} disabled{/if}>
                        <p class="help-block">{lang key='unique_name'}</p>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='category'}</label>

                    <div class="col col-lg-4">
                        <select name="category">
                            {foreach $categories as $key => $category}
                                <option value="{$key}"{if $key == $item.category} selected{/if}>{$category}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='module'}</label>

                    <div class="col col-lg-4">
                        <select name="module"{if iaCore::ACTION_EDIT == $pageAction} disabled{/if}>
                            {foreach $modules as $name => $title}
                                <option value="{$name}"{if $name == $item.module} selected{/if}>{$title}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>

                <hr>

                <div class="row">
                    <div class="col col-lg-2">
                        {* if count($core.languages) > 1}
                            <div class="btn-group btn-group-xs translate-group-actions">
                                <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-value"><span class="i-earth"></span></button>
                                <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-value"><span class="i-copy"></span></button>
                            </div>
                        {/if*}
                        <label class="control-label">{lang key='value'}</label>
                    </div>
                    <div class="col col-lg-6">
                        <div class="translate-group" id="language-group-value">
                            {*<div class="translate-group__default">*}
                                <div class="translate-group__item">
                                    <textarea name="value[{$core.language.iso}]" rows="5">{if isset($item.value[$core.language.iso])}{$item.value[$core.language.iso]|escape}{/if}</textarea>
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            {*</div>*}
                            {*<div class="translate-group__langs">*}
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <textarea name="value[{$iso}]" rows="5">{if isset($item.value[$iso])}{$item.value[$iso]|escape}{/if}</textarea>
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            {*</div>*}
                        </div>
                    </div>
                </div>
            </div>

            {include 'fields-system.tpl' noSystemFields=true}
        </div>
    </form>
{/if}
