{$type = $field.type}
{$fieldName = $field.name}
{$name = "field_{$field.item}_{$field.name}"}
{$value = $item[$fieldName]}

{if isset($field_before[$fieldName])}{$field_before.$fieldName}{/if}

<div id="{$fieldName}_fieldzone" class="row {$field.relation}">
    <div class="col col-lg-2">
        {if $field.multilingual && count($core.languages) > 1}
            <div class="btn-group btn-group-xs translate-group-actions">
                <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-{$fieldName}"><span class="i-earth"></span></button>
                <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-{$fieldName}"{if $field.use_editor} data-wysiwyg-enabled="true" data-name="{$name}"{/if}><span class="i-copy"></span></button>
            </div>
        {/if}
        <label class="control-label">{$field.title|escape} {if $field.required}{lang key='field_required'}{/if}</label>
        {if (iaField::PICTURES == $type || iaField::IMAGE == $type) && !$field.timepicker}
            <div class="help-block">
                {lang key='thumb_dimensions'}: {$field.thumb_width}x{$field.thumb_height}<br>
                {lang key='image_dimensions'}: {$field.image_width}x{$field.image_height}
            </div>
        {/if}
        {$tooltip = {lang key="field_tooltip_{$field.item}_{$field.name}" default=''}}
        {if $tooltip}<div class="help-block">{$tooltip}</div>{/if}
    </div>

    {if iaField::TEXTAREA == $type || iaField::PICTURES == $type}
        <div class="col col-lg-8">
    {else}
        <div class="col col-lg-4">
    {/if}

    {if isset($field_inner[$fieldName])}
        {$field_inner[$fieldName]}
    {else}

    {switch $type}
        {case iaField::TEXT break}
            {if $field.multilingual}
                <div class="translate-group" id="language-group-{$fieldName}">
                    <div class="translate-group__default">
                        <div class="translate-group__item">
                            <input type="text" name="{$fieldName}[{$core.masterLanguage.iso}]" id="{$name}" value="{if empty($item["{$fieldName}_{$core.masterLanguage.iso}"])}{$field.default|escape}{else}{$item["{$fieldName}_{$core.masterLanguage.iso}"]|escape}{/if}" maxlength="{$field.length}">
                            {if count($core.languages) > 1}<div class="translate-group__item__code">{$core.masterLanguage.title|escape}</div>{/if}
                        </div>
                    </div>
                    <div class="translate-group__langs">
                        {foreach $core.languages as $iso => $language}
                            {if $iso != $core.masterLanguage.code}
                                <div class="translate-group__item">
                                    <input type="text" name="{$fieldName}[{$iso}]" id="{$name}-{$iso}" value="{if empty($item["{$fieldName}_{$iso}"])}{$field.default|escape}{else}{$item["{$fieldName}_{$iso}"]|escape}{/if}" maxlength="{$field.length}">
                                    <span class="translate-group__item__code">{$language.title|escape}</span>
                                </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
            {else}
                <input type="text" name="{$fieldName}" value="{$value|escape}" id="{$name}" maxlength="{$field.length}">
            {/if}

        {case iaField::DATE break}
            {assign var='default_date' value=($value && !in_array($value, ['0000-00-00', '0000-00-00 00:00:00'])) ? {$value|escape} : ''}

            <div class="input-group date" id="field_date_{$fieldName}">
                <input type="text" class="js-datepicker" name="{$fieldName}" id="{$name}" value="{$default_date}" {if $field.timepicker}data-date-format="YYYY-MM-DD HH:mm:ss"{else}data-date-format="YYYY-MM-DD"{/if}>
                <span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
            </div>

        {case iaField::NUMBER break}
            <input class="js-filter-numeric" type="text" name="{$fieldName}" value="{$value|escape}" id="{$name}" maxlength="{$field.length}">

        {case iaField::CURRENCY break}
            <div class="input-group col-md-8">
                <span class="input-group-addon">{$core.defaultCurrency.code|escape}</span>
                <input class="form-control span2 js-filter-numeric" type="text" name="{$fieldName}"{if $value} value="{$value|floatval}"{/if} id="{$name}" maxlength="{$field.length + 2}">
            </div>

        {case iaField::URL break}
            {if !is_array($value)}
                {$value = explode('|', $value)}
            {/if}
            <div class="row control-group-inner">
                <div class="col col-lg-6">
                    <label for="{$field.name}[url]" class="control-label">{lang key='url'}:</label>
                    <input type="text" name="{$field.name}[url]" value="{if isset($value['url'])}{$value['url']}{elseif !empty($value[0])}{$value[0]}{else}http://{/if}">
                </div>

                <div class="col col-lg-6">
                    <label for="{$field.name}[title]" class="control-label">{lang key='title'}:</label>
                    <input type="text" name="{$field.name}[title]" value="{if isset($value['title'])}{$value['title']|escape}{elseif !empty($value[1])}{$value[1]|escape}{/if}">
                    <p class="help-block">({lang key='optional'})</p>
                </div>
            </div>

        {case iaField::ICONPICKER break}
            {$value = ($value) ? $value : 'fa-folder'}
            <div class="input-group iconpicker-container">
                <input type="text" data-placement="bottomRight" class="js-iconpicker" name="{$fieldName}" id="{$name}" value="{$value}">
                <span class="input-group-addon"><i class="fa {$value}"></i></span>
            </div>

        {case iaField::TEXTAREA break}
            {if !$field.use_editor}
                {if $field.multilingual}
                <div class="translate-group" id="language-group-{$fieldName}">
                    <div class="translate-group__default">
                        <div class="translate-group__item">
                            <textarea name="{$fieldName}[{$core.masterLanguage.iso}]" id="{$name}" rows="5">{if empty($item["{$fieldName}_{$core.masterLanguage.iso}"])}{$field.default|escape}{else}{$item["{$fieldName}_{$core.masterLanguage.iso}"]|escape}{/if}</textarea>
                            {if count($core.languages) > 1}<div class="translate-group__item__code">{$core.masterLanguage.title|escape}</div>{/if}
                        </div>
                    </div>
                    <div class="translate-group__langs">
                        {foreach $core.languages as $iso => $language}
                            {if $iso != $core.masterLanguage.iso}
                            <div class="translate-group__item">
                                <textarea name="{$fieldName}[{$iso}]" id="{$name}-{$iso}" rows="5">{if empty($item["{$fieldName}_{$iso}"])}{$field.default|escape}{else}{$item["{$fieldName}_{$iso}"]|escape}{/if}</textarea>
                                <span class="translate-group__item__code">{$language.title|escape}</span>
                            </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
                {else}
                <textarea name="{$fieldName}" rows="8" id="{$name}">{$value|escape}</textarea>
                {if $field.length > 0}
                    {ia_add_js}
$(function($)
{
    $('#{$name}').dodosTextCounter({$field.length},
    {
        counterDisplayElement: 'span',
        counterDisplayClass: 'textcounter_{$fieldName}',
        addLineBreak: false
    });

    $('.textcounter_{$fieldName}').wrap('<p class="help-block text-right">').addClass('textcounter').after(' ' + _t('chars_left'));
});
                    {/ia_add_js}
                    {ia_print_js files='jquery/plugins/jquery.textcounter'}
                {/if}
                {/if}
            {else}
                {if $field.multilingual}
                <div class="translate-group" id="language-group-{$fieldName}">
                    <div class="translate-group__default">
                        <div class="translate-group__item">
                            {$value = {(empty($item["{$fieldName}_{$core.masterLanguage.iso}"])) ? $field.default : $item["{$fieldName}_{$core.masterLanguage.iso}"]}}
                            {ia_wysiwyg value=$value name="{$fieldName}[{$core.masterLanguage.iso}]" id="{$name}"}
                            {if count($core.languages) > 1}<div class="translate-group__item__code">{$core.masterLanguage.title|escape}</div>{/if}
                        </div>
                    </div>
                    <div class="translate-group__langs">
                        {foreach $core.languages as $iso => $language}
                            {if $iso != $core.masterLanguage.iso}
                            <div class="translate-group__item">
                                {$value = {(empty($item["{$fieldName}_{$iso}"])) ? $field.default : $item["{$fieldName}_{$iso}"]}}
                                {ia_wysiwyg value=$value name="{$fieldName}[{$iso}]" id="{$name}-{$iso}"}
                                <span class="translate-group__item__code">{$language.title|escape}</span>
                            </div>
                            {/if}
                        {/foreach}
                    </div>
                </div>
                {else}
                    {ia_wysiwyg value=$value name=$field.name}
                {/if}
            {/if}

        {case iaField::IMAGE break}
            <div id="{$fieldName}_dropzone" class="js-dropzone s-dropzone dropzone"
                data-item="{$field.item}" data-item_id="{$id|default:''}" data-field="{$fieldName}" data-imagetype-primary="{$field.imagetype_primary}"
                data-imagetype-thumbnail="{$field.imagetype_thumbnail}" data-max_num="1" data-submit_btn_text="{if iaCore::ACTION_ADD == $pageAction}add{else}save{/if}"
                data-values='{if $value}{json_encode(array($value), 4)}{/if}'></div>
            {ia_add_media files='css: _IA_URL_js/dropzone/dropzone'}
            {ia_print_js files='dropzone/dropzone'}

        {case iaField::PICTURES break}
            <div id="{$fieldName}_dropzone" class="js-dropzone s-dropzone dropzone"
                data-item="{$field.item}" data-item_id="{$id|default:''}" data-field="{$fieldName}" data-imagetype-primary="{$field.imagetype_primary}"
                data-imagetype-thumbnail="{$field.imagetype_thumbnail}" data-max_num="{$field.length}" data-submit_btn_text="{if iaCore::ACTION_ADD == $pageAction}add{else}save{/if}"
                data-values='{if $value}{json_encode(array_values($value), 4)}{/if}'></div>
            {ia_add_media files='css: _IA_URL_js/dropzone/dropzone'}
            {ia_print_js files='dropzone/dropzone'}

        {case iaField::STORAGE break}
            {if $value}
                <div class="uploads-list" id="{$fieldName}_upload_list">
                    {foreach $value as $i => $entry}
                        <div class="uploads-list-item">
                            <span class="uploads-list-item__thumb uploads-list-item__thumb--file"><i class="i-file-2"></i></span>
                            <div class="uploads-list-item__body">
                                <div class="input-group">
                                    {foreach $entry as $k => $v}
                                    <input type="hidden" name="{$fieldName}[{$i}][{$k}]" value="{$v|escape}">
                                    {/foreach}
                                    <input type="text" name="{$fieldName}[{$i}][title]" value="{$entry.title|escape}" id="{$fieldName}_{$entry@index}">

                                    <span class="input-group-btn">
                                        <a class="btn btn-success uploads-list-item__img" href="{$core.page.nonProtocolUrl}uploads/{$entry.path}{$entry.file}" title="{$entry.title|escape}" download><i class="i-box-add"></i></a>
                                        <a class="btn btn-danger js-cmd-delete-file" href="#" title="{lang key='delete'}" data-file="{$entry.file}" data-item="{$field.item}" data-field="{$field.name}" data-id="{$id}"><span class="fa fa-remove"></span></a>
                                        <span class="btn btn-default uploads-list-item__drag-handle"><span class="fa fa-reorder"></span></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                    {/foreach}
                </div>

                {ia_add_js}
$(function()
{
    var params = {
        handle: '.uploads-list-item__drag-handle'
    }

    intelli.sortable('{$fieldName}_upload_list', params);
});
                {/ia_add_js}

                {assign var='max_num' value=($field.length - count($value))}
            {else}
                {assign max_num $field.length}
            {/if}

            {ia_html_file name=$fieldName id=$fieldName multiple=true max_num=$max_num title=true}

        {case iaField::COMBO break}
            <select name="{$fieldName}" id="{$name}">
                <option value="">{lang key='_select_'}</option>
                {html_options options=$field.values selected=$value}
            </select>

            {if 'parent' == $field.relation && $field.children}
                {ia_add_js order=5}
$(function()
{
    $('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
    $('#{$name}').on('change', function()
    {
        var value = $(this).val();
        $('.hide_{$field.name}').hide();
        {foreach $field.children as $_field => $_values}
        if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
        {/foreach}
        $('fieldset').show().each(function(index, item)
        {
            if ($('.fieldset-wrapper', item).length > 0)
            {
                $('.fieldset-wrapper div.fieldzone:visible, .fieldset-wrapper div.fieldzone.regular', item).length == 0
                    ? $(this).hide()
                    : $(this).show();
            }
        });
    }).change();
});
                {/ia_add_js}
            {/if}

        {case iaField::RADIO break}
            {if !empty($field.values)}
                {html_radios assign='radios' name=$fieldName options=$field.values selected=$value separator='</div>'}

                <div class="radio">{'<div class="radio">'|implode:$radios}
            {/if}

            {if iaField::RELATION_PARENT == $field.relation && $field.children}
                {ia_add_js order=5}
$(function()
{
    $('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
    $('input[name="{$fieldName}"]').on('change', function()
    {
        var $this = $(this),
            value = $this.val();

        if ($this.is(':checked'))
        {
            {foreach $field.children as $_field => $_values}
                if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1)
                {
                    $('#{$_field}_fieldzone').show();
                }
                else
                {
                    $('#{$_field}_fieldzone').hide();
                }
            {/foreach}
        }
    }).change();
});
                {/ia_add_js}
            {/if}

        {case iaField::CHECKBOX break}
            {if !empty($field.values)}
                {html_checkboxes assign='checkboxes' name=$fieldName options=$field.values selected=$value separator='</div>'}

                <div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
            {/if}

            {if iaField::RELATION_PARENT == $field.relation && $field.children}
                {ia_add_js order=5}
$(function()
{
    $('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
    $('input[name="{$fieldName}[]"]').on('change', function()
    {
        $('.hide_{$field.name}').hide();

        $('input[type="checkbox"]:checked', '#type_fieldzone').each(function()
        {
            var value = $(this).val();

            {foreach $field.children as $_field => $_values}
                if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
            {/foreach}
        });
    }).change();
});
                {/ia_add_js}
            {/if}

        {case iaField::TREE break}
            <input type="text" id="label-{$fieldName}" disabled>
            <input type="hidden" name="{$fieldName}" id="input-{$fieldName}" value="{$value|escape}">
            <div class="js-tree categories-tree" data-field="{$fieldName}" data-nodes="{$field.values|escape}" data-multiple="{$field.timepicker}"></div>
            {ia_add_media files='tree'}
            {ia_add_js order=5}
$(function()
{
    'use strict';

    $('.js-tree').each(function()
    {
        var data = $(this).data(),
            options = { core:{ data: data.nodes, multiple: data.multiple}};

        if (data.multiple) options.plugins = ['checkbox'];

        $(this).jstree(options)
        .on('changed.jstree', function(e, d)
        {
            var nodes = [], ids = [];
            for (var i = 0; i < d.selected.length; i++)
            {
                var node = d.instance.get_node(d.selected[i]);
                nodes.push(node.text.trim());
                ids.push(node.id);
            }

            var fieldName = $(this).data('field');

            $('#label-' + fieldName).val(nodes.join(', '));
            $('#input-' + fieldName).val(ids.join(', '));
        })
        .on('ready.jstree', function(e, d)
        {
            var nodes = $('#input-' + $(this).data('field')).val().split(',');
            d.instance.open_all();
            for (var i in nodes)
            {
                d.instance.select_node(nodes[i]);
            }
        })
    });
});
            {/ia_add_js}
    {/switch}
    {/if}
    </div>
</div>

{if isset($field_after[$fieldName])}{$field_after.$fieldName}{/if}