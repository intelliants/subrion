{$type = $field.type}
{$fieldName = $field.name}
{$name = "field_{$field.item}_{$field.name}"}

{if isset($field_before[$fieldName])}{$field_before.$fieldName}{/if}

{if isset($item[$fieldName])}
    {if iaField::CHECKBOX == $type}
        {$value = ','|explode:$item[$fieldName]}
    {else}
        {$value = $item[$fieldName]}
    {/if}
{else}
    {$value = $field.default}
{/if}

{if !empty($field.disabled)}
    <input type="hidden" name="{$fieldName}" value="{$value|escape}">
{/if}

<div class="form-group{if iaField::TEXTAREA == $type} form-group--textarea{/if} {$field.class} {$field.relation}{if $field.for_plan && !$field.required} form-group--plan" style="display:none;{/if}" id="{$fieldName}_fieldzone">
    <label for="{$name}">
        {$field.title|escape}:
        {if $field.required}<span class="is-required">*</span>{/if}
    </label>

    {switch $type}
        {case iaField::TEXT break}
            {if $field.multilingual && isset($item["{$fieldName}_{$core.language.iso}"])}{$value = $item["{$fieldName}_{$core.language.iso}"]}{/if}
            <input class="form-control" type="text" name="{$fieldName}{if $field.multilingual}[{$core.language.iso}]{/if}" value="{if $value}{$value|escape}{else}{$field.default|escape}{/if}" id="{$name}" maxlength="{$field.length}">

        {case iaField::NUMBER break}
            <input class="form-control js-filter-numeric" type="text" name="{$fieldName}" value="{$value|escape}" id="{$name}" maxlength="{$field.length}">

        {case iaField::CURRENCY break}
            <div class="input-group">
                <div class="input-group-addon">{$core.defaultCurrency.code|escape}</div>
                <input class="form-control js-filter-numeric" type="text" name="{$fieldName}" value="{if $value}{$value|escape}{else}{$field.default|escape}{/if}" id="{$name}" maxlength="{$field.length}">
            </div>

        {case iaField::TEXTAREA break}
            {if $field.multilingual && isset($item["{$fieldName}_{$core.language.iso}"])}{$value = $item["{$fieldName}_{$core.language.iso}"]}{/if}
            {if !$field.use_editor}
                <textarea class="form-control" name="{$fieldName}{if $field.multilingual}[{$core.language.iso}]{/if}" rows="8" id="{$name}">{$value|escape}</textarea>
                {if $field.length > 0}
                    {ia_add_js}
$(function() {
    $('#{$name}').dodosTextCounter({$field.length}, {
        counterDisplayElement: 'span',
        counterDisplayClass: 'textcounter_{$fieldName}'
    });
    $('.textcounter_{$fieldName}').addClass('textcounter').wrap('<p class="help-block"></p>').before('{lang key='chars_left'} ');
});
                    {/ia_add_js}
                    {ia_print_js files='jquery/plugins/jquery.textcounter'}
                {/if}
            {else}
                {ia_wysiwyg value=$value name="{$field.name}{if $field.multilingual}[{$core.language.iso}]{/if}"}
            {/if}

        {case iaField::URL break}
            {if !is_array($value)}
                {$value = '|'|explode:$value}
            {/if}

            <div class="row">
                <div class="col-md-6">
                    <label for="{$fieldName}[title]">{lang key='title'}:</label>
                    <input class="form-control" type="text" name="{$fieldName}[title]" value="{if isset($value['title'])}{$value['title']|escape}{elseif !empty($value[1])}{$value[1]|escape}{/if}">
                </div>
                <div class="col-md-6">
                    <label for="{$fieldName}[url]">{lang key='url'}:</label>
                    <input class="form-control" type="text" name="{$fieldName}[url]" value="{if isset($value['url'])}{$value['url']}{elseif !empty($value[0])}{$value[0]}{else}http://{/if}">
                </div>
            </div>

        {case iaField::DATE break}
            {$default_date = ($value && !in_array($value, ['0000-00-00', '0000-00-00 00:00:00'])) ? {$value|escape} : ''}

            <div class="row">
                <div class="col-md-6">
                    <div class="input-group date" id="field_date_{$fieldName}">
                        <input class="form-control js-datepicker" type="text" name="{$fieldName}" {if $field.timepicker} data-date-format="YYYY-MM-DD HH:mm:ss"{else}data-date-format="YYYY-MM-DD"{/if} id="{$name}" value="{$default_date}">
                        <span class="input-group-addon js-datepicker-toggle"><span class="fa fa-calendar"></span></span>
                    </div>
                </div>
            </div>

            {ia_add_media files='moment,datepicker'}

        {case iaField::IMAGE break}
            {if $value}
                {if is_string($value)}{$value = unserialize($value)}{/if}
                <div class="thumbnail">
                    <div class="thumbnail__actions">
                        <button type="button" class="btn btn-danger btn-sm js-delete-file" data-item="{$field.item}" data-field="{$fieldName}" data-item-id="{$item.id|default:''}" data-file="{$value.file|escape}" title="{lang key='delete'}"><span class="fa fa-times"></span></button>
                    </div>

                    {if $field.thumb_width == $field.image_width && $field.thumb_height == $field.image_height}
                        {ia_image file=$value field=$field width=$field.thumb_width height=$field.thumb_height}
                    {else}
                        <a href="{ia_image file=$value field=$field url=true large=true}" rel="ia_lightbox[{$fieldName}]" style="max-width: {$field.thumb_width}px;">
                            {ia_image file=$value field=$field width=$field.thumb_width height=$field.thumb_height}
                        </a>
                    {/if}

                    {foreach $value as $k => $v}
                    <input type="hidden" name="{$fieldName}[{$k}]" value="{$v|escape}">
                    {/foreach}
                </div>
            {/if}

            <div class="input-group js-files">
                <span class="input-group-btn">
                    <span class="btn btn-primary btn-file">
                        {lang key='browse'} <input type="file" name="{$fieldName}[]" id="{$name}">
                    </span>
                </span>
                <input type="text" class="form-control js-file-name" readonly value="{if $value}{$value.file}{/if}">
            </div>

        {case iaField::PICTURES break}
            {ia_add_media files='js:bootstrap/js/bootstrap-editable.min, css:_IA_URL_js/bootstrap/css/bootstrap-editable' order=5}

            {if $value}
                {if is_string($value)}{$value = unserialize($value)}{/if}
                <div class="row upload-items" id="{$fieldName}_upload_list">
                    {foreach $value as $entry}
                        <div class="col-md-4">
                            <div class="thumbnail upload-items__item">
                                <div class="btn-group thumbnail__actions">
                                    <span class="btn btn-default btn-sm drag-handle"><span class="fa fa-arrows"></span></span>
                                    <button type="button" class="btn btn-sm btn-danger js-delete-file" data-item="{$field.item}" data-field="{$fieldName}" data-item-id="{$item.id|default:''}" data-file="{$entry.file}" title="{lang key='delete'}"><span class="fa fa-times"></span></button>
                                </div>

                                <a class="thumbnail__image" href="{ia_image file=$entry field=$field url=true large=true}" rel="ia_lightbox[{$fieldName}]">
                                    {ia_image file=$entry field=$field class='img-responsive'}
                                </a>

                                {*<div class="caption">
                                    <h5><a href="#" id="{$fieldName}_{$entry@index}" data-type="text" data-item="{$field.item}" data-field="{$fieldName}" data-item-id="{$item.id}" data-picture-path="{$entry.path}" data-pk="1" data-emptytext="{lang key='empty_image_title'}" class="js-edit-picture-title editable editable-click">{$entry.title|escape}</a></h5>
                                </div>

                                <input type="hidden" name="{$fieldName}[{$entry@index}][title]" value="{$entry.title|escape}">*}
                                {foreach $entry as $k => $v}
                                <input type="hidden" name="{$fieldName}[{$entry@index}][{$k}]" value="{$v|escape}">
                                {/foreach}
                            </div>
                        </div>
                    {/foreach}
                </div>

                {ia_add_js}
$(function() {
    var params = {
        handle: '.drag-handle'
    }

    intelli.sortable('{$fieldName}_upload_list', params);
});
                {/ia_add_js}

                {$max_num = ($field.length - count($value))}
            {else}
                {$max_num = $field.length}
            {/if}

            <div class="upload-list" id="wrap_{$fieldName}" {if $max_num <= 0}style="display: none;"{/if}>
                <div class="upload-list__item">
                    <div class="input-group js-files">
                        <div class="input-group-btn">
                            <span class="btn btn-primary btn-file">
                                {lang key='browse'} <input type="file" name="{$fieldName}[]">
                            </span>
                        </div>
                        <input type="text" readonly class="form-control js-file-name">
                        {if $max_num > 0}
                            <div class="input-group-btn">
                                <button type="button" class="js-add-img btn btn-default"><span class="fa fa-plus"></span></button>
                                <button type="button" class="js-remove-img btn btn-default"><span class="fa fa-minus"></span></button>
                            </div>
                        {/if}
                    </div>
                </div>

                <input type="hidden" value="{$max_num}" id="{$fieldName}">
            </div>

        {case iaField::STORAGE break}
            {if $value}
                {if is_string($value)}{$value = unserialize($value)}{/if}
                <div class="upload-items upload-items--files" id="{$fieldName}_upload_list">
                    {foreach $value as $entry}
                        <div class="input-group upload-items__item">
                            <input type="text" class="form-control" name="{$fieldName}[{$entry@index}][title]" value="{$entry.title|escape}">
                            <input type="hidden" name="{$fieldName}[{$entry@index}][path]" value="{$entry.path|escape}">
                            <input type="hidden" name="{$fieldName}[{$entry@index}][file]" value="{$entry.file|escape}">
                            <div class="input-group-btn">
                                <a class="btn btn-default" href="{$core.page.nonProtocolUrl}uploads/{$entry.path}{$entry.file}" title="{lang key='download'}" download><span class="fa fa-cloud-download"></span> {lang key='download'}</a>
                                <span class="btn btn-default drag-handle"><span class="fa fa-arrows-v"></span></span>
                                <button type="button" class="btn btn-danger js-delete-file" data-item="{$field.item}" data-field="{$fieldName}" data-item-id="{$item.id|default:''}" data-file="{$entry.file|escape}">{lang key='delete'}</button>
                            </div>
                        </div>
                    {/foreach}
                </div>
                {$max_num = ($field.length - count($value))}
            {else}
                {$max_num = $field.length}
            {/if}

            <div class="upload-list" id="wrap_{$fieldName}" {if $max_num <= 0}style="display: none;"{/if}>
                <div class="row upload-list__item">
                    <div class="col-md-6">
                        <div class="input-group js-files">
                            <div class="input-group-btn">
                                <span class="btn btn-primary btn-file">
                                    {lang key='browse'} <input type="file" name="{$fieldName}[]">
                                </span>
                            </div>
                            <input type="text" readonly class="form-control js-file-name">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="{lang key='title'}" name="{$fieldName}_title[]" maxlength="100">
                            {if $max_num > 0}
                                <div class="input-group-btn">
                                    <button type="button" class="js-add-img btn btn-default"><span class="fa fa-plus"></span></button>
                                    <button type="button" class="js-remove-img btn btn-default"><span class="fa fa-minus"></span></button>
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                <input type="hidden" value="{$max_num}" id="{$fieldName}">
            </div>

        {case iaField::TREE}
            <input class="form-control" type="text" id="label-{$fieldName}" disabled>
            <input type="hidden" name="{$fieldName}" id="input-{$fieldName}" value="{$value|escape}">
            <div class="js-tree categories-tree" data-field="{$fieldName}" data-nodes="{$field.values|escape}" data-multiple="{$field.timepicker}"></div>
            {ia_add_media files='tree'}
            {ia_add_js order=5}
$(function() {
    'use strict';

    $('.js-tree').each(function() {
        var data = $(this).data(),
            options = { core: { data: data.nodes, multiple: data.multiple } };

        if (data.multiple) options.plugins = ['checkbox'];

        $(this).jstree(options)
        .on('changed.jstree', function(e, d) {
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
        .on('ready.jstree', function(e, d) {
            var nodes = $('#input-' + $(this).data('field')).val().split(',');
            d.instance.open_all();
            for (var i in nodes) {
                d.instance.select_node(nodes[i]);
            }
        })
    });
});
            {/ia_add_js}
    {/switch}

        {if $type == iaField::COMBO}
            <select class="form-control" name="{$fieldName}" id="{$name}"{if isset($field.disabled) && $field.disabled} disabled{/if}>
                <option value="">{lang key='_select_'}</option>
                {if !empty($field.values)}
                    {html_options options=$field.values selected=$value}
                {/if}
            </select>

            {if $field.relation == 'parent' && $field.children}
                {ia_add_js order=5}
$(function() {
$('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$fieldName}');
$('#{$name}').on('change', function() {
    var value = $(this).val();
    $('.hide_{$fieldName}').hide();
    {foreach $field.children as $_field => $_values}
    if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
    {/foreach}
    $('fieldset').show().each(function(index, item) {
        if ($('.fieldset-wrapper', item).length > 0) {
            $('.fieldset-wrapper div.fieldzone:visible, .fieldset-wrapper div.fieldzone.regular', item).length == 0
                ? $(this).hide()
                : $(this).show();
        }
    });
}).change();
});
                {/ia_add_js}
            {/if}

        {elseif $type == iaField::RADIO}
            <div class="radios-list">
                {if !empty($field.values)}
                    {html_radios assign='radios' name=$fieldName id=$name options=$field.values selected=$value separator='</div>'}
                    <div class="radio">{'<div class="radio">'|implode:$radios}
                {/if}
            </div>

            {if $field.relation == 'parent' && $field.children}
                {ia_add_js order=5}
$(function() {
    $('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$fieldName}');

    $('input[name="{$fieldName}"]').on('change', function() {
        var $this = $(this),
            value = $(this).val();

        if ($this.is(':checked')) {
            $('hide_{$fieldName}').hide();

            {foreach $field.children as $_field => $_values}
                if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}]) != -1) {
                    $('#{$_field}_fieldzone').show();
                } else {
                    $('#{$_field}_fieldzone').hide();
                }
            {/foreach}
        }
    }).change();
});
                {/ia_add_js}
            {/if}

        {elseif $type == iaField::CHECKBOX}
            <div class="radios-list">
                {if !empty($field.values)}
                    {html_checkboxes assign='checkboxes' name=$fieldName id=$name options=$field.values selected=$value separator='</div>'}
                    <div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
                {/if}
            </div>

            {if $field.relation == 'parent' && $field.children}
            {ia_add_js order=5}
$(function() {
    $('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$fieldName}');
    $('input[name="{$fieldName}[]"]').on('change', function() {
        $('.hide_{$fieldName}').hide();
        $('input[type="checkbox"]:checked', '#type_fieldzone').each(function() {
            var value = $(this).val();
            {foreach $field.children as $_field => $_values}
            if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
            {/foreach}
        });
    }).change();
});
            {/ia_add_js}
            {/if}
        {/if}

        {assign tooltip {lang key="field_tooltip_{$field.item}_{$field.name}" default=''}}
        {if $tooltip}<p class="help-block help-block--tooltip">{$tooltip}</p>{/if}
</div>
{if isset($field_after[$fieldName])}{$field_after.$fieldName}{/if}