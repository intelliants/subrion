{if isset($params)}
<form enctype="multipart/form-data" method="post" class="sap-form form-horizontal">
    {preventCsrf}
    <div class="wrap-list">
        <div class="wrap-group">
        {foreach $params as $entry}
            {if !empty($entry.options.show)}
                {assign field_show explode('|', $entry.options.show)}

                {capture assign='dependent_fields'}
                    data-id="js-{$field_show[0]}-{$field_show[1]}" {if (!empty($field_show[0]) && $core.config.{$field_show[0]} != $field_show[1])} style="display: none;"{/if}
                {/capture}
            {else}
                {assign dependent_fields ''}
            {/if}

            {if 'divider' == $entry.type}
                {if !$entry@first}
                    </div>
                    <div class="wrap-group" {$dependent_fields}>
                {/if}
                <a name="{$entry.name}"></a>
                <div class="wrap-group-heading" {$dependent_fields}>
                    {$entry.value|escape}

                    {if isset($tooltips[$entry.name])}
                        <a href="#" class="js-tooltip" data-placement="right" title="{$tooltips[$entry.name]}"><i class="i-info"></i></a>
                    {/if}
                </div>
            {elseif 'tpl' == $entry.type}
                {$customTpl = "{$smarty.const.IA_HOME}{$entry.multiple_values}"}
                {if file_exists($customTpl)}
                    {include $entry.multiple_values}
                {else}
                    {lang key='template_file_error' file=$entry.multiple_values}
                {/if}
            {elseif 'hidden' != $entry.type}
                <div class="row {$entry.class}" {$dependent_fields}>
                    <div class="col col-lg-2">
                        {$isMultilingual = isset($entry.options.multilingual) && $entry.options.multilingual}
                        {if $isMultilingual && count($core.languages) > 1}
                            <div class="btn-group btn-group-xs translate-group-actions">
                                <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-{$entry.name}"><span class="i-earth"></span></button>
                                <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-{$entry.name}"><span class="i-copy"></span></button>
                            </div>
                        {/if}
                        <label class="control-label" for="{$entry.name}">
                            {$entry.description|escape}
                            {if isset($tooltips[$entry.name])}
                                <a href="#" class="js-tooltip" data-html="true" title="{$tooltips[$entry.name]}"><i class="i-info"></i></a>
                            {/if}
                        </label>
                    </div>

                    {if 'textarea' == $entry.type}
                        <div class="col col-lg-8">
                    {else}
                        <div class="col col-lg-5">
                    {/if}

                    <input type="hidden" class="chck" name="c[{$entry.name}]" value="{if 'custom' != $entry.class}1{else}0{/if}" />
                    {if 'password' == $entry.type}
                        {if $custom}
                            <div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_password'}{else}***********{/if}</div>
                        {/if}

                        <div class="item-input">
                            <input type="password" class="js-input-password" name="v[{$entry.name}]" id="{$entry.name}" value="{$entry.value|escape}" autocomplete="new-password" />
                        </div>
                    {elseif 'text' == $entry.type}
                        {if 'captcha_preview' == $entry.name}
                            {captcha preview=true}
                        {else}
                            {if $custom}
                                <div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_value'}{else}{$entry.default|escape}{/if}</div>
                            {/if}

                            {$isMultilingual = isset($entry.options.multilingual) && $entry.options.multilingual}

                            <div class="translate-group item-input" id="language-group-{$entry.name}">
                                <div class="translate-group__default">
                                    <div class="translate-group__item">
                                        <input type="text" name="v[{$entry.name}]{if $isMultilingual}[{$core.language.iso}]{/if}" id="{$entry.name}-{$core.language.iso}" value="{{($isMultilingual) ? $entry.value[$core.language.iso] : $entry.value}|escape}" />
                                        {if $isMultilingual && count($core.languages) > 1}
                                            <div class="translate-group__item__code">
                                                {$core.language.title|escape}
                                            </div>
                                        {/if}
                                    </div>
                                </div>
                                {if $isMultilingual && count($core.languages) > 1}
                                    <div class="translate-group__langs">
                                        {foreach $core.languages as $iso => $language}
                                            {if $iso != $core.language.iso}
                                                <div class="translate-group__item">
                                                    <input type="text" name="v[{$entry.name}][{$iso}]" id="{$entry.name}-{$iso}" value="{$entry.value[$iso]|escape}" />
                                                    <span class="translate-group__item__code">{$language.title|escape}</span>
                                                </div>
                                            {/if}
                                        {/foreach}
                                    </div>
                                {/if}
                            </div>
                        {/if}
                    {elseif 'colorpicker' == $entry.type}
                        <div class="item-input">
                            <div id="cp_{$entry.name}" class="input-group colorpicker-component">
                                <input type="text" name="v[{$entry.name}]" id="{$entry.name}" value="{$entry.value|escape}" />
                                <span class="input-group-addon"><i></i></span>
                            </div>
                        </div>

                        {ia_add_media files='css:_IA_URL_js/bootstrap/css/bootstrap-colorpicker.min, js:bootstrap/js/bootstrap-colorpicker.min'}
                        {ia_add_js}
$(function() {
    $('#cp_{$entry.name}').colorpicker();
});
                        {/ia_add_js}
                    {elseif 'textarea' == $entry.type}
                        {if $custom}
                            <div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_value'}{else}{$entry.default}{/if}</div>
                        {/if}

                        {$isMultilingual = isset($entry.options.multilingual) && $entry.options.multilingual}

                        <div class="translate-group item-input" id="language-group-{$entry.name}">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <textarea name="v[{$entry.name}]{if $isMultilingual}[{$core.language.iso}]{/if}" id="{$entry.name}" class="{if $entry.options.wysiwyg == 1}js-wysiwyg {elseif $entry.options.code_editor}js-code-editor {/if}common" cols="45" rows="7">{{($isMultilingual) ? $entry.value[$core.language.iso] : $entry.value}|escape}</textarea>
                                    {if $isMultilingual && count($core.languages) > 1}
                                        <div class="translate-group__item__code">
                                            {$core.language.title|escape}
                                        </div>
                                    {/if}
                                </div>
                            </div>
                            {if $isMultilingual && count($core.languages) > 1}
                                <div class="translate-group__langs">
                                    {foreach $core.languages as $iso => $language}
                                        {if $iso != $core.language.iso}
                                            <div class="translate-group__item">
                                                <textarea name="v[{$entry.name}][{$iso}]" id="{$entry.name}-{$iso}" class="{if $entry.options.wysiwyg == 1}js-wysiwyg {elseif $entry.options.code_editor}js-code-editor {/if}common" cols="45" rows="7">{$entry.value[$iso]|escape}</textarea>
                                                <span class="translate-group__item__code">{$language.title|escape}</span>
                                            </div>
                                        {/if}
                                    {/foreach}
                                </div>
                            {/if}
                        </div>
                    {elseif 'image' == $entry.type}
                        {if !is_writeable($smarty.const.IA_UPLOADS)}
                            <div class="alert alert-info">{lang key='upload_writable_permission'}</div>
                        {else}
                            {if !empty($entry.value) || $entry.name == 'site_logo'}
                                <div class="thumbnail">
                                    {if !empty($entry.value)}
                                        <img src="{$core.page.nonProtocolUrl}uploads/{$entry.value}">
                                    {elseif $entry.name == 'site_logo'}
                                        <img src="{$core.page.nonProtocolUrl}templates/{$core.config.tmpl}/img/logo.png">
                                    {/if}
                                </div>

                                {if !empty($entry.value)}
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="delete[{$entry.name}]"> {lang key='delete'}</label>
                                    </div>
                                {/if}
                            {/if}

                            {ia_html_file name=$entry.name value=$entry.value}
                        {/if}
                    {elseif 'checkbox' == $entry.type}
                        <div class="item-input">
                            <input type="checkbox" name="v[{$entry.name}]" id="{$entry.name}">
                        </div>
                    {elseif 'radio' == $entry.type}
                        {if $custom}
                            <div class="form-control disabled item-val">{if $entry.default == 1}ON{else}OFF{/if}</div>
                        {/if}

                        <div class="item-input">
                            {html_radio_switcher value=$entry.value name=$entry.name conf=true}
                        </div>
                    {elseif 'select' == $entry.type}
                        {if $custom}
                            <div class="form-control disabled item-val">{if $entry.name == 'lang'}{$entry.values[$entry.default].title|escape}{else}{$entry.default}{/if}</div>
                        {/if}

                        <div class="item-input">
                            <select name="v[{$entry.name}]" id="{$entry.name}"{if 1 == count($entry.values)} disabled{/if}>
                                {foreach $entry.values as $k => $v}
                                    {if 'lang' == $entry.name || 'currency' == $entry.name}
                                        <option value="{$k}"{if $k == $entry.value || $v == $entry.value} selected{/if}>{$v.title|escape}</option>
                                    {elseif is_array($v)}
                                        <optgroup label="{$k}">
                                            {foreach $v as $subkey => $subvalue}
                                                <option value="{$subkey}"{if $subkey == $entry.value} selected{/if}>{$subvalue}</option>
                                            {/foreach}
                                        </optgroup>
                                    {else}
                                        <option value="{$k}"{if $k == $entry.value} selected{/if}>{$v|escape}</option>
                                    {/if}
                                {/foreach}
                            </select>
                        </div>
                    {elseif $entry.type == 'itemscheckbox' && !$custom}
                        <input type="hidden" name="v[{$entry.name}][]">
                        {if isset($entry.items[0])}
                            <div class="item-input">
                                {foreach $entry.items[0] as $item}
                                    <p>
                                        <input type="checkbox" id="icb_{$entry.name}_{$item.name}" name="v[{$entry.name}][]" value="{$item.name}"{if $item.checked} checked{/if}>
                                        <label for="icb_{$entry.name}_{$item.name}">{$item.title}</label>
                                    </p>
                                {/foreach}
                            </div>
                        {else}
                            <div class="alert alert-info">{lang key='no_implemented_packages'}</div>
                        {/if}
                        {if isset($entry.items[1])}
                        <hr>
                        <div class="item-input">
                            {foreach $entry.items[1] as $item}
                                <p>
                                    <input type="checkbox" id="icb_{$entry.name}_{$item.name}" name="v[{$entry.name}][]" value="{$item.name}"{if $item.checked} checked{/if}>
                                    <label for="icb_{$entry.name}_{$item.name}">{$item.title}</label>
                                    <small class="text-muted">(not supported explicitly)</small>
                                </p>
                            {/foreach}
                        </div>
                        {/if}
                    {/if}
                    </div> <!-- /.col -->
                    {if $custom}
                        <div class="col col-lg-2">
                            <div class="custom-item-actions">
                                <span class="btn btn-default btn-xs set-custom js-tooltip" data-value="1" title="{lang key='config_set_custom'}" data-toggle="tooltip"><span class="fa fa-pencil"></span></span>
                                <span class="btn btn-default btn-xs set-default js-tooltip" data-value="0" title="{lang key='config_set_default'}" data-toggle="tooltip"><span class="fa fa-rotate-left"></span></span>
                            </div>
                        </div>
                    {/if}
                </div><!-- /.row -->
            {/if}
        {/foreach}
    </div>

    <div class="form-actions">
        <input type="submit" name="save" id="save" class="btn btn-primary" value="{lang key='save_changes'}">
    </div>
</form>
{/if}

{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/configuration'}