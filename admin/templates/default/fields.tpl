<form method="post" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            {if iaCore::ACTION_EDIT == $pageAction}
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='name'}</label>

                    <div class="col col-lg-4">
                        <input type="text" value="{$item.name|escape}" disabled>
                        <input type="hidden" name="name" value="{$item.name|escape}">
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_item'}</label>

                    <div class="col col-lg-4">
                        <input type="text" value="{$item.item|escape}" disabled>
                        <input type="hidden" name="item" id="input-item" value="{$item.item|escape}">
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_type'}</label>

                    <div class="col col-lg-4">
                        <input type="text" value="{lang key="field_type_{$item.type}" default=$item.type}" disabled>
                        <input type="hidden" name="type" id="input-type" value="{$item.type|escape}">
                    </div>
                </div>
            {else}
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='name'} <span class="required">*</span></label>

                    <div class="col col-lg-4">
                        <input type="text" name="name" value="{$item.name}">
                        <p class="help-block">{lang key='unique_name'}</p>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_item'} <span class="required">*</span></label>

                    <div class="col col-lg-4">
                        <select name="item" id="input-item">
                            <option value="">{lang key='_select_'}</option>
                            {foreach $items as $entry}
                                <option value="{$entry}"{if $item.item == $entry} selected{/if}>{lang key=$entry default=$entry}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_type'} <span class="required">*</span></label>

                    <div class="col col-lg-4">
                        <select name="type" id="input-type">
                            <option value="">{lang key='_select_'}</option>
                            {foreach $fieldTypes as $type}
                                <option value="{$type}"{if $item.type == $type} selected{/if} data-tooltip="{lang key="field_type_tip_{$type}" default=''}">{lang key="field_type_{$type}" default=$type}</option>
                            {/foreach}
                        </select>
                        <p class="help-block"></p>
                    </div>
                </div>
            {/if}

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='field_group'}</label>

                <div class="col col-lg-4">
                    <select name="fieldgroup_id" id="input-fieldgroup"{if !$groups} disabled{/if}>
                        <option value="">{lang key='_select_'}</option>
                        {foreach $groups as $group}
                            <option value="{$group.id}"{if $group.id == $item.fieldgroup_id} selected{/if}>{$group.title|escape}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <div id="js-row-empty-text" class="row">
                <label class="col col-lg-2 control-label">{lang key='empty_field'} <a href="#" class="js-tooltip" title="{$tooltips.empty_field}"><span class="fa fa-info-circle"></span></a></label>

                <div class="col col-lg-4">
                    <input type="text" name="empty_field" value="{if isset($item.empty_field)}{$item.empty_field|escape}{/if}">
                </div>
            </div>

            <div class="row" id="js-row-pages-list"{if iaCore::ACTION_ADD == $pageAction && (!$smarty.post && !isset($smarty.get.item))} style="display: none;"{/if}>
                <label class="col col-lg-2 control-label">{lang key='shown_on_pages'}</label>

                <div class="col col-lg-4">
                    <div class="box-simple fieldset">
                        {foreach $pages as $entry}
                            <div class="checkbox" data-item="{$entry.item|escape}"{if $item.item != $entry.item} style="display: none;"{/if}>
                                <label>
                                    <input type="checkbox" value="{$entry.name}"{if in_array($entry.name, $item.pages)} checked{/if} name="pages[]">
                                    {$entry.title|escape}
                                </label>
                            </div>
                        {/foreach}
                    </div>
                    <a href="#" id="toggle-pages" class="label label-default pull-right"><i class="i-lightning"></i> {lang key='select_all'}</a>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='visible_for_admin'} <a href="#" class="js-tooltip" title="{$tooltips.adminonly}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.adminonly|default:0 name='adminonly'}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='regular_field'} <a href="#" class="js-tooltip" title="{$tooltips.regular_field}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    <select name="relation" id="js-field-relation">
                        <option value="regular"{if iaField::RELATION_REGULAR == $item.relation} selected{/if}>{lang key='field_relation_regular'}</option>
                        <option value="parent"{if iaField::RELATION_PARENT == $item.relation} selected{/if}>{lang key='field_relation_parent'}</option>
                        <option value="dependent"{if iaField::RELATION_DEPENDENT == $item.relation} selected{/if}>{lang key='field_relation_dependent'}</option>
                    </select>
                </div>
            </div>

            <div class="row" id="regular_field"{if iaField::RELATION_DEPENDENT != $item.relation} style="display: none;"{/if}>
                <label class="col col-lg-2 control-label">{lang key='host_fields'}</label>

                <div class="col col-lg-4">
                    {ia_hooker name='adminHostFieldSelectorBefore' item=$item}

                    {foreach $parents as $fieldItem => $itemsList}
                        <div class="js-dependent-fields-list" data-item="{$fieldItem}"{if $item.item != $fieldItem} style="display: none;"{/if}>
                            <div class="list-group list-group-accordion">
                                {foreach $itemsList as $fieldName => $elements}
                                    {$fieldId = $elements[0]}
                                    {$options = $elements[1]}
                                    <a href="#" class="list-group-item{if $elements@first} active{/if}">
                                        <p class="list-group-item-heading"><b>{lang key="field_{$fieldItem}_{$fieldName}"} {lang key='field_values'}</b></p>
                                    </a>
                                    <div class="list-group-item fields-list"{if !$elements@first} style="display:none;"{/if}>
                                        {foreach $options as $option}
                                            <div class="checkbox">
                                                <label>
                                                    <input type="checkbox" value="1"{if isset($item.parents[$fieldId][$option])} checked{/if} name="parents[{$fieldItem}][{$fieldName}][{$option}]">
                                                    {{lang key="field_{$fieldItem}_{$fieldName}+{$option}"}|escape}
                                                </label>
                                            </div>
                                        {/foreach}
                                    </div>
                                {foreachelse}
                                    <span class="list-group-item list-group-item-info">{lang key='no_parent_fields'}</span>
                                {/foreach}
                            </div>
                        </div>
                    {/foreach}
                </div>
            </div>

            <div class="row" id="js-row-multilingual">
                <label class="col col-lg-2 control-label">{lang key='multilingual'} <a href="#" class="js-tooltip" title="{$tooltips.multilingual_field}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.multilingual|default:0 name='multilingual'}
                </div>
            </div>

            {ia_hooker name='smartyAdminFieldsEdit'}

            <div id="text" class="field_type" style="display: none;">
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_length'}</label>

                    <div class="col col-lg-4">
                        <input class="js-filter-numeric" type="text" name="text_length" value="{if !$item.length || $item.length > 255}255{else}{$item.length}{/if}">
                        <p class="help-block">{lang key='digits_only'}</p>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_default'}</label>

                    <div class="col col-lg-4">
                        <input type="text" name="text_default" class="js-code-editor" value="{$item.default}">
                    </div>
                </div>
            </div>

            <div id="textarea" class="field_type" style="display: none;">
                <div id="js-row-use-editor" class="row">
                    <label class="col col-lg-2 control-label">{lang key='use_editor'}</label>

                    <div class="col col-lg-4">
                        {html_radio_switcher value=$item.use_editor|default:1 name='use_editor'}
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_length'}</label>

                    <div class="col col-lg-4">
                        <input type="text" name="length" class="js-code-editor" value="{$item.length|escape}">
                    </div>
                </div>
            </div>

            <div id="storage" class="field_type" style="display: none;">
                {if !is_writable($smarty.const.IA_UPLOADS)}
                    <div class="row">
                        <label class="col col-lg-2 control-label"></label>

                        <div class="col col-lg-4">
                            <div class="alert alert-warning">{lang key='upload_writable_permission'}</div>
                        </div>
                    </div>
                {else}
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='max_files'}</label>

                        <div class="col col-lg-4">
                            <input class="js-filter-numeric" type="text" name="max_files" value="{$item.length|escape}">
                        </div>
                    </div>
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='file_types'} <span class="required">*</span></label>

                        <div class="col col-lg-4">
                            <textarea rows="3" id="file_types" name="file_types">{if isset($item.file_types)}{$item.file_types|escape}{/if}</textarea>
                        </div>
                    </div>
                {/if}
            </div>

            <div id="image" class="field_type" style="display: none;">
                {if !is_writable($smarty.const.IA_UPLOADS)}
                    <div class="row">
                        <label class="col col-lg-2 control-label"></label>

                        <div class="col col-lg-4">
                            <div class="alert alert-warning">{lang key='upload_writable_permission'}</div>
                        </div>
                    </div>
                {else}
                    <hr>

                    <input type="hidden" name="use_img_types" value="{$item.timepicker|intval}">
                    <input type="hidden" name="imagetype_primary"{if $item.timepicker} value="{$item.imagetype_primary|escape}"{/if}>
                    <input type="hidden" name="imagetype_thumbnail"{if $item.timepicker}  value="{$item.imagetype_thumbnail|escape}"{/if}>

                    <div class="row" id="js-image-field-setup-by-imgtypes"{if !$item.timepicker} style="display: none;"{/if}>
                        <label class="col col-lg-2 control-label">{lang key='image_types'} <span class="required">*</span></label>

                        <div class="col col-lg-4">
                            {if $imageTypes}
                                {foreach $imageTypes as $imageType}
                                    <div class="input-group image-type-control">
                                        <span class="input-group-addon">
                                            <input name="image_types[]" value="{$imageType.id}" data-name="{$imageType.name}" type="checkbox"
                                            {if $item.imagetype_primary == $imageType.name} data-type="primary"
                                            {elseif $item.imagetype_thumbnail == $imageType.name} data-type="thumbnail"{/if}
                                            {if isset($item.image_types) && in_array($imageType.id, $item.image_types)} checked{/if}>
                                        </span>
                                        <div class="form-control-static">
                                            {$imageType.name|escape} <span>({$imageType.width}/{$imageType.height}/{$imageType.resize_mode})</span>

                                            <span class="label label-info" data-type="primary" {if $item.imagetype_primary == $imageType.name} style="display: block;"{/if}>{lang key='primary'}</span>
                                            <span class="label label-info" data-type="thumbnail" {if $item.imagetype_thumbnail == $imageType.name} style="display: block;"{/if}>{lang key='thumbnail'}</span>
                                        </div>
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"{if empty($item.image_types) || !in_array($imageType.id, $item.image_types)} disabled{/if}><span class="fa fa-ellipsis-h"></span></button>
                                            <ul class="dropdown-menu dropdown-menu-right has-icons">
                                                <li{if $item.imagetype_primary == $imageType.name} class="disabled"{/if}><a class="js-set-image-type" data-type="primary" href="#">{lang key='set_as_primary'}</a></li>
                                                <li{if $item.imagetype_thumbnail == $imageType.name} class="disabled"{/if}><a class="js-set-image-type" data-type="thumbnail" href="#">{lang key='set_as_thumbnail'}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                {/foreach}
                            {else}
                                <div class="alert alert-info">{lang key='no_image_types'}</div>
                            {/if}
                            <p class="help-block"><a href="#" class="pull-right js-cmd-toggle-image-setup" style="text-decoration: none; border-bottom: 1px dashed;" data-type="0">Use standard settings</a></p>
                        </div>
                    </div>

                    <div id="js-block-image-field-setup-by-settings"{if $item.timepicker} style="display: none;"{/if}>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

                            <div class="col col-lg-4">
                                <div class="row">
                                    <div class="col col-lg-6">
                                        <input type="text" name="image_width" value="{if isset($item.image_width)}{$item.image_width|escape}{else}900{/if}">
                                    </div>
                                    <div class="col col-lg-6">
                                        <input type="text" name="image_height" value="{if isset($item.image_height)}{$item.image_height|escape}{else}600{/if}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

                            <div class="col col-lg-4">
                                <div class="row">
                                    <div class="col col-lg-6">
                                        <input type="text" name="thumb_width" value="{if isset($item.thumb_width)}{$item.thumb_width|escape}{else}{$core.config.thumb_w}{/if}">
                                    </div>
                                    <div class="col col-lg-6">
                                        <input type="text" name="thumb_height" value="{if isset($item.thumb_height)}{$item.thumb_height|escape}{else}{$core.config.thumb_h}{/if}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

                            <div class="col col-lg-4">
                                <select name="resize_mode">
                                    <option value="crop"{if isset($item.resize_mode) && iaPicture::CROP == $item.resize_mode} selected{/if} data-tooltip="{lang key='crop_tip'}">{lang key='crop'}</option>
                                    <option value="fit"{if isset($item.resize_mode) && iaPicture::FIT == $item.resize_mode} selected{/if} data-tooltip="{lang key='fit_tip'}">{lang key='fit'}</option>
                                </select>
                                <p class="help-block"></p>
                                <p class="help-block"><a href="#" class="pull-right js-cmd-toggle-image-setup" style="text-decoration: none; border-bottom: 1px dashed;" data-type="1">Use image types instead</a></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='file_prefix'}</label>

                        <div class="col col-lg-4">
                            <input type="text" name="file_prefix"{if isset($item.file_prefix)} value="{$item.file_prefix|escape}"{/if}>
                        </div>
                    </div>
                {/if}
            </div>

            <div id="tree" class="field_type" style="display: none;">
                <a href="#tree"></a>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='nodes'}</label>

                    <div class="col col-lg-4">
                        <button class="js-tree-action btn btn-xs btn-success" data-action="create"><i class="i-plus"></i> Add Node</button>
                        <button class="js-tree-action btn btn-xs btn-danger disabled" data-action="delete"><i class="i-minus"></i> Delete</button>
                        <button class="js-tree-action btn btn-xs btn-info disabled" data-action="update"><i class="i-edit"></i> Rename</button>
                        <em class="help-inline pull-right">{lang key='drag_to_reorder'}</em>

                        <input type="hidden" name="nodes"{if iaField::TREE == $item.type} value="{$item.values|escape}"{/if}>
                        <div class="categories-tree" id="input-nodes" data-action="{$core.page.info.action}"></div>
                    </div>
                </div>
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='multiple_selection'} <a href="#" class="js-tooltip" title="{$tooltips.multiple_selection}"><i class="i-info"></i></a></label>

                    <div class="col col-lg-4">
                        {html_radio_switcher value=$item.timepicker|default:0 name='multiple'}
                    </div>
                </div>
            </div>

            <div id="js-multiple" class="field_type" style="display: none;">
                <div id="show_in_search_as" class="row"{if !$item.searchable} style="display:none"{/if}>
                    <label class="col col-lg-2 control-label">{lang key='show_in_search_as'}</label>

                    <div class="col col-lg-4">
                        <select name="show_as" id="showAs">
                            <option value="checkbox"{if isset($item.show_as) && iaField::CHECKBOX == $item.show_as} selected{/if}>{lang key='checkboxes'}</option>
                            <option value="radio"{if isset($item.show_as) && iaField::RADIO == $item.show_as} selected{/if}>{lang key='radios'}</option>
                            <option value="combo"{if isset($item.show_as) && iaField::COMBO == $item.show_as} selected{/if}>{lang key='dropdown'}</option>
                        </select>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_default'}</label>

                    <div class="col col-lg-4">
                        <input type="text" readonly name="multiple_default" id="multiple_default" value="{if isset($item.default)}{$item.default|escape}{/if}">
                        <a href="#" class="js-actions label label-default pull-right" data-action="clearDefault"><i class="i-cancel-circle"></i> {lang key='clear_default'}</a>
                    </div>
                </div>

                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='field_values'}</label>

                    <div class="col col-lg-4">
                        {if $values}
                            {foreach $values as $key => $value}
                                <div id="item-value-{$value}" class="wrap-row wrap-block" data-value-id="{$value}">
                                    <div class="row">
                                        <label class="col col-lg-4 control-label">{lang key='key'} <i>({lang key='not_required'})</i></label>
                                        <div class="col col-lg-8">
                                            <input type="text" name="keys[]" value="{$value|escape}">
                                        </div>
                                    </div>
                                    {foreach $core.languages as $code => $language}
                                        <div class="row">
                                            <label class="col col-lg-4 control-label">{lang key='item_value'} <span class="label label-info">{$language.title|escape}</span></label>
                                            <div class="col col-lg-8">
                                                <input type="text" name="values[{$code}][]"{if isset($titles.$value.$code)} value="{$titles.$value.$code|escape}"{/if}>
                                            </div>
                                        </div>
                                    {/foreach}
                                    <div class="actions-panel">
                                        <a href="#" class="js-actions label label-default" data-action="setDefault">{lang key='set_as_default_value'}</a>
                                        <a href="#" class="js-actions label label-danger" data-action="removeItem" title="{lang key='remove'}"><i class="i-close"></i></a>
                                        <a href="#" class="js-actions label label-success itemUp" style="display: none;" data-action="itemUp" title="{lang key='item_up'}"><i class="i-chevron-up"></i></a>
                                        <a href="#" class="js-actions label label-success itemDown" style="display: none;" data-action="itemDown" title="{lang key='item_down'}"><i class="i-chevron-down"></i></a>
                                    </div>
                                    <div class="main_fields"{if $item.relation != iaField::RELATION_PARENT} style="display:none;"{/if}>
                                        {lang key='field_element_children'}: <a href="#" class="js-cmd-configure-dependent-field"><i class="i-fire"></i></a>
                                        {if isset($children[$value])}
                                            <span class="list">{$children[$value].titles}</span>
                                            <input type="hidden" name="children[]" value="{$children[$value].values}">
                                        {else}
                                            <span class="list"></span>
                                            <input type="hidden" name="children[]">
                                        {/if}
                                    </div>
                                </div>
                            {/foreach}
                            <a href="#" class="js-actions label pull-right label-success" id="js-cmd-add-value"><i class="i-plus"></i> {lang key='add_item_value'}</a>
                        {else}
                            <div id="item-value-default" class="wrap-row wrap-block">
                                <div class="row">
                                    <label class="col col-lg-4 control-label">{lang key='key'} <i>({lang key='not_required'})</i></label>
                                    <div class="col col-lg-8">
                                        <input type="text" name="keys[]">
                                    </div>
                                </div>
                                {foreach $core.languages as $code => $language}
                                    <div class="row">
                                        <label class="col col-lg-4 control-label">{lang key='item_value'} <span class="label label-info">{$language.title}</span></label>
                                        <div class="col col-lg-8">
                                            <input type="text" name="values[{$code}][]">
                                        </div>
                                    </div>
                                {/foreach}
                                <div class="actions-panel">
                                    <a href="#" class="js-actions label label-default" data-action="setDefault">{lang key='set_as_default_value'}</a>
                                    <a href="#" class="js-actions label label-danger" data-action="removeItem" title="{lang key='remove'}"><i class="i-close"></i></a>
                                    <a href="#" class="js-actions label label-success itemUp" style="display: none;" data-action="itemUp" title="{lang key='item_up'}"><i class="i-chevron-up"></i></a>
                                    <a href="#" class="js-actions label label-success itemDown" style="display: none;" data-action="itemDown" title="{lang key='item_down'}"><i class="i-chevron-down"></i></a>
                                </div>
                                <div class="main_fields"{if $item.relation != iaField::RELATION_PARENT} style="display:none;"{/if}>
                                    {lang key='field_element_children'}: <span onclick="wfields(this)"><i class="i-fire"></i></span>
                                    {if isset($item.children[$smarty.foreach.values.index])}
                                        <span class="list">{$item.children[$smarty.foreach.values.index].titles}</span>
                                        <input type="hidden" value="{$item.children[$smarty.foreach.values.index].values}" name="children[]">
                                    {else}
                                        <span class="list"></span>
                                        <input type="hidden" name="children[]">
                                    {/if}
                                </div>
                            </div>
                            <a href="#" class="js-actions label pull-right label-success" id="js-cmd-add-value"><i class="i-plus"></i> {lang key='add_item_value'}</a>
                        {/if}
                    </div>
                </div>
            </div>

            <div id="url" class="field_type" style="display: none;">
                <div class="row">
                    <label class="col col-lg-2 control-label">{lang key='url_nofollow'}</label>

                    <div class="col col-lg-4">
                        {html_radio_switcher value=$item.url_nofollow|default:0 name='url_nofollow'}
                    </div>
                </div>
            </div>

            {if iaCore::ACTION_EDIT != $pageAction}
                <div id="date" class="field_type" style="display: none;">
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='timepicker'}</label>

                        <div class="col col-lg-4">
                            {html_radio_switcher value=$item.timepicker|default:0 name='timepicker'}
                        </div>
                    </div>
                </div>
            {else}
                <input type="hidden" name="timepicker" value="{$item.timepicker}">
            {/if}

            <div id="pictures" class="field_type" style="display: none;">
                {if !is_writeable($smarty.const.IA_UPLOADS)}
                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='pictures'}</label>

                        <div class="col col-lg-4">
                            <div class="alert alert-warning">{lang key='upload_writable_permission'}</div>
                        </div>
                    </div>
                {else}
                    <hr>

                    <input type="hidden" name="pic_use_img_types" value="{$item.timepicker|intval}">
                    <input type="hidden" name="pic_imagetype_primary"{if $item.timepicker} value="{$item.imagetype_primary|escape}"{/if}>
                    <input type="hidden" name="pic_imagetype_thumbnail"{if $item.timepicker}  value="{$item.imagetype_thumbnail|escape}"{/if}>

                    <div class="row" id="js-gallery-field-setup-by-imgtypes"{if !$item.timepicker} style="display: none;"{/if}>
                        <label class="col col-lg-2 control-label">{lang key='image_types'} <span class="required">*</span></label>

                        <div class="col col-lg-4">
                            {if $imageTypes}
                                {foreach $imageTypes as $imageType}
                                    <div class="input-group image-type-control">
                                        <span class="input-group-addon">
                                            <input name="pic_image_types[]" value="{$imageType.id}" data-name="{$imageType.name}" type="checkbox"
                                            {if $item.imagetype_primary == $imageType.name} data-type="primary"
                                            {elseif $item.imagetype_thumbnail == $imageType.name} data-type="thumbnail"{/if}
                                            {if isset($item.image_types) && in_array($imageType.id, $item.image_types)} checked{/if}>
                                        </span>
                                        <div class="form-control-static">
                                            {$imageType.name|escape} <span>({$imageType.width}/{$imageType.height}/{$imageType.resize_mode})</span>

                                            <span class="label label-info" data-type="primary" {if $item.imagetype_primary == $imageType.name} style="display: block;"{/if}>{lang key='primary'}</span>
                                            <span class="label label-info" data-type="thumbnail" {if $item.imagetype_thumbnail == $imageType.name} style="display: block;"{/if}>{lang key='thumbnail'}</span>
                                        </div>
                                        <div class="input-group-btn">
                                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"{if empty($item.image_types) || !in_array($imageType.id, $item.image_types)} disabled{/if}><span class="fa fa-ellipsis-h"></span></button>
                                            <ul class="dropdown-menu dropdown-menu-right has-icons">
                                                <li{if $item.imagetype_primary == $imageType.name} class="disabled"{/if}><a class="js-set-image-type" data-type="primary" href="#">{lang key='set_as_primary'}</a></li>
                                                <li{if $item.imagetype_thumbnail == $imageType.name} class="disabled"{/if}><a class="js-set-image-type" data-type="thumbnail" href="#">{lang key='set_as_thumbnail'}</a></li>
                                            </ul>
                                        </div>
                                    </div>
                                {/foreach}
                            {else}
                                <div class="alert alert-info">{lang key='no_image_types'}</div>
                            {/if}
                            <p class="help-block"><a href="#" class="pull-right js-cmd-toggle-gallery-setup" style="text-decoration: none; border-bottom: 1px dashed;" data-type="0">Use standard settings</a></p>
                        </div>
                    </div>

                    <div id="js-block-gallery-field-setup-by-settings"{if $item.timepicker} style="display: none;"{/if}>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

                            <div class="col col-lg-4">
                                <div class="row">
                                    <div class="col col-lg-6">
                                        <input type="text" name="pic_image_width" value="{if isset($item.image_width)}{$item.image_width|escape}{else}900{/if}">
                                    </div>
                                    <div class="col col-lg-6">
                                        <input type="text" name="pic_image_height" value="{if isset($item.image_height)}{$item.image_height|escape}{else}600{/if}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

                            <div class="col col-lg-4">
                                <div class="row">
                                    <div class="col col-lg-6">
                                        <input type="text" name="pic_thumb_width" value="{if isset($item.thumb_width)}{$item.thumb_width|escape}{else}{$core.config.thumb_w}{/if}">
                                    </div>
                                    <div class="col col-lg-6">
                                        <input type="text" name="pic_thumb_height" value="{if isset($item.thumb_height)}{$item.thumb_height|escape}{else}{$core.config.thumb_h}{/if}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

                            <div class="col col-lg-4">
                                <select name="pic_resize_mode">
                                    <option value="crop"{if isset($item.resize_mode) && iaPicture::CROP == $item.resize_mode} selected{/if} data-tooltip="{lang key='crop_tip'}">{lang key='crop'}</option>
                                    <option value="fit"{if isset($item.resize_mode) && iaPicture::FIT == $item.resize_mode} selected{/if} data-tooltip="{lang key='fit_tip'}">{lang key='fit'}</option>
                                </select>
                                <p class="help-block"></p>
                                <p class="help-block"><a href="#" class="pull-right js-cmd-toggle-gallery-setup" style="text-decoration: none; border-bottom: 1px dashed;" data-type="1">Use image types instead</a></p>
                            </div>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='max_num_images'}</label>

                        <div class="col col-lg-4">
                            <input type="text" name="pic_max_images" value="{if isset($item.pic_max_images)}{$item.pic_max_images|escape}{else}5{/if}">
                        </div>
                    </div>

                    <div class="row">
                        <label class="col col-lg-2 control-label">{lang key='file_prefix'}</label>

                        <div class="col col-lg-4">
                            <input type="text" name="pic_file_prefix" value="{if isset($item.file_prefix)}{$item.file_prefix|escape}{/if}">
                        </div>
                    </div>
                {/if}
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='required_field'}</label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.required name='required'}
                </div>
            </div>

            <div class="row" id="js-row-plan-only" {if $item.required} style="display: none"{/if}>
                <label class="col col-lg-2 control-label">{lang key='for_plan_only'} <a href="#" class="js-tooltip" title="{$tooltips.for_plan_only}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.for_plan|default:0 name='for_plan'}
                </div>
            </div>

            <div class="row"{if $item.required} style="display: none"{/if}>
                <label class="col col-lg-2 control-label">{lang key='searchable'} <a href="#" class="js-tooltip" title="{$tooltips.searchable}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=$item.searchable name='searchable'}
                </div>
            </div>

            <div class="row" id="js-row-validation-code"{if !$item.required} style="display: none;"{/if}>
                <label class="col col-lg-2 control-label">{lang key='required_checks'} <a href="#" class="js-tooltip" title="{$tooltips.required_checks}"><i class="i-info"></i></a></label>

                <div class="col col-lg-8">
                    <textarea name="required_checks" id="required_checks" class="js-code-editor">{if isset($item.required_checks)}{$item.required_checks|escape}{/if}</textarea>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='extra_actions'} <a href="#" class="js-tooltip" title="{$tooltips.extra_actions}"><i class="i-info"></i></a></label>

                <div class="col col-lg-8">
                    <textarea name="extra_actions" id="extra_actions" class="js-code-editor">{if isset($item.extra_actions)}{$item.extra_actions|escape}{/if}</textarea>
                </div>
            </div>

            <hr class="m-y">

            <div class="row">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                        <div class="btn-group btn-group-xs translate-group-actions">
                            <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-title"><span class="i-earth"></span></button>
                            <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-title"><span class="i-copy"></span></button>
                        </div>
                    {/if}
                    <label class="control-label">{lang key='title'} <span class="required">*</span></label>
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
                                            <input type="text" name="title[{$iso}]"{if isset($item.title.$iso)} value="{$item.title.$iso|escape}"{/if}>
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
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                        <div class="btn-group btn-group-xs translate-group-actions">
                            <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-tooltip"><span class="i-earth"></span></button>
                            <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-tooltip"><span class="i-copy"></span></button>
                        </div>
                    {/if}
                    <label class="control-label">{lang key='tooltip'}</label>
                </div>
                <div class="col col-lg-4">
                    {if count($core.languages) > 1}
                        <div class="translate-group" id="language-group-tooltip">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <input type="text" name="tooltip[{$core.language.iso}]"{if isset($item.tooltip[$core.language.iso])} value="{$item.tooltip[$core.language.iso]|escape}"{/if}>
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            </div>
                            <div class="translate-group__langs">
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <input type="text" name="tooltip[{$iso}]"{if isset($item.tooltip.$iso)} value="{$item.tooltip.$iso|escape}"{/if}>
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {else}
                        <input type="text" name="tooltip[{$core.language.iso}]"{if isset($item.tooltip[$core.language.iso])} value="{$item.tooltip[$core.language.iso]|escape}"{/if}>
                    {/if}
                </div>
            </div>
        </div>

        {include 'fields-system.tpl'}
    </div>
</form>
{ia_add_media files='tree'}
{ia_print_js files='jquery/plugins/jquery.numeric,utils/edit_area/edit_area,admin/fields'}