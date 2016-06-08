<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			{if iaCore::ACTION_EDIT == $pageAction}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>

				<div class="col col-lg-4">
					<input type="text" value="{$item.name|escape:'html'}" disabled>
					<input type="hidden" name="name" value="{$item.name|escape:'html'}">
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='field_item'}</label>

				<div class="col col-lg-4">
					<input type="text" value="{$item.item|escape:'html'}" disabled>
					<input type="hidden" name="item" id="input-item" value="{$item.item|escape:'html'}">
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='field_type'}</label>

				<div class="col col-lg-4">
					<input type="text" value="{lang key="field_type_{$item.type}" default=$item.type}" disabled>
					<input type="hidden" name="type" id="input-type" value="{$item.type|escape:'html'}">
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
							<option value="{$type}"{if $item.type == $type} selected{/if} data-annotation="{lang key="field_type_tip_{$type}" default=''}">{lang key="field_type_{$type}" default=$type}</option>
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
						{foreach $groups as $code => $value}
							<option value="{$code}"{if $code == $item.fieldgroup_id} selected{/if}>{$value.name|escape:'html'}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div id="js-row-empty-text" class="row">
				<label class="col col-lg-2 control-label">{lang key='empty_field'} <a href="#" class="js-tooltip" title="{$tooltips.empty_field}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					<input type="text" name="empty_field" value="{if isset($item.empty_field)}{$item.empty_field|escape:'html'}{/if}">
				</div>
			</div>
			{if $pages}
				<div class="row" id="js-pages-list-row"{if iaCore::ACTION_ADD == $pageAction && (!$smarty.post && !isset($smarty.get.item))} style="display: none;"{/if}>
					<label class="col col-lg-2 control-label">{lang key='shown_on_pages'}</label>

					<div class="col col-lg-4">
						<div class="box-simple fieldset">
						{foreach $pages as $pageId => $entry}
							<div class="checkbox" data-item="{$entry.item|escape:'html'}"{if $item.item != $entry.item} style="display: none;"{/if}>
								<label>
									<input type="checkbox" value="{$entry.name}"{if in_array($entry.name, $item.pages)} checked{/if} name="pages[{$pageId}]">
									{$entry.title}
								</label>
							</div>
						{/foreach}
						</div>
						<a href="#" id="toggle-pages" class="label label-default pull-right"><i class="i-lightning"></i> {lang key='select_all'}</a>
					</div>
				</div>
			{/if}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='visible_for_admin'} <a href="#" class="js-tooltip" title="{$tooltips.adminonly}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.adminonly|default:0 name='adminonly'}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='regular_field'} <a href="#" class="js-tooltip" title="{$tooltips.regular_field}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					<select name="relation" class="common" id="js-field-relation">
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

					{foreach $parents as $field_item => $item_list}
						<div class="js-dependent-fields-list" data-item="{$field_item}"{if $item.item != $field_item} style="display: none;"{/if}>
							<div class="list-group list-group-accordion">
								{foreach $item_list as $field_name => $elements}
									<a href="#" class="list-group-item{if $elements@first} active{/if}">
										<p class="list-group-item-heading"><b>{lang key="field_{$field_name}"} {lang key='field_values'}</b></p>
									</a>
									<div class="list-group-item fields-list"{if !$elements@first} style="display:none;"{/if}>
										{foreach $elements as $element}
											<div class="checkbox">
												<label>
													<input type="checkbox" value="1"{if isset($item.parents[$field_item][$field_name][$element])} checked{/if} name="parents[{$field_item}][{$field_name}][{$element}]">
													{lang key="field_{$field_name}_{$element}"}
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

			<div class="row" id="for_plan_only" {if $item.required} style="display: none"{/if}>
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

			<div class="row" id="link-to-details" style="display: none">
				<label class="col col-lg-2 control-label">{lang key='link_to'} <a href="#" class="js-tooltip" title="{$tooltips.link_to_details}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.link_to|default:0 name='link_to'}
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
						<input type="text" name="length" class="js-code-editor" value="{$item.length|escape:'html'}">
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
						<input class="js-filter-numeric" type="text" name="max_files" value="{$item.length|escape:'html'}">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='file_types'} <span class="required">*</span></label>

					<div class="col col-lg-4">
						<textarea rows="3" id="file_types" name="file_types">{if isset($item.file_types)}{$item.file_types|escape:'html'}{/if}</textarea>
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
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='file_prefix'}</label>

						<div class="col col-lg-4">
							<input type="text" name="file_prefix" value="{if isset($item.file_prefix)}{$item.file_prefix|escape:'html'}{/if}">
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

						<div class="col col-lg-4">
							<div class="row">
								<div class="col col-lg-6">
									<input type="text" name="image_width" value="{if isset($item.image_width)}{$item.image_width|escape:'html'}{else}900{/if}">
								</div>
								<div class="col col-lg-6">
									<input type="text" name="image_height" value="{if isset($item.image_height)}{$item.image_height|escape:'html'}{else}600{/if}">
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

						<div class="col col-lg-4">
							<div class="row">
								<div class="col col-lg-6">
									<input type="text" name="thumb_width" value="{if isset($item.thumb_width)}{$item.thumb_width|escape:'html'}{else}{$core.config.thumb_w}{/if}">
								</div>
								<div class="col col-lg-6">
									<input type="text" name="thumb_height" value="{if isset($item.thumb_height)}{$item.thumb_height|escape:'html'}{else}{$core.config.thumb_h}{/if}">
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

						<div class="col col-lg-4">
							<select name="resize_mode">
								<option value="crop"{if isset($item.resize_mode) && iaPicture::CROP == $item.resize_mode} selected{/if} data-annotation="{lang key='crop_tip'}">{lang key='crop'}</option>
								<option value="fit"{if isset($item.resize_mode) && iaPicture::FIT == $item.resize_mode} selected{/if} data-annotation="{lang key='fit_tip'}">{lang key='fit'}</option>
							</select>
							<p class="help-block"></p>
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
						<span class="help-inline pull-right">{lang key='drag_to_reorder'}</span>

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
						<input type="text" readonly name="multiple_default" id="multiple_default" value="{if isset($item.default)}{$item.default|escape:'html'}{/if}">
						<a href="#" class="js-actions label label-default pull-right" data-action="clearDefault"><i class="i-cancel-circle"></i> {lang key='clear_default'}</a>
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='field_values'}</label>

					<div class="col col-lg-4">
						{if isset($item.values) && $item.values}
							{foreach $item.values as $key => $value}
								<div id="item-value-{$value}" class="wrap-row wrap-block" data-value-id="{$value}">
									<div class="row">
										<label class="col col-lg-4 control-label">{lang key='key'} <i>({lang key='not_required'})</i></label>
										<div class="col col-lg-8">
											<input type="text" name="keys[]" value="{if isset($item.keys.$key)}{$item.keys.$key}{else}{$value}{/if}">
										</div>
									</div>
									{foreach $core.languages as $code => $language}
										<div class="row">
											<label class="col col-lg-4 control-label">{lang key='item_value'} <span class="label label-info">{$language.title}</span></label>
											<div class="col col-lg-8">
												{if $smarty.const.IA_LANGUAGE == $code}
													<input type="text" class="fvalue" name="values[]" value="{if !isset($item.values_titles.$value.$code)}{$value}{else}{$item.values_titles.$value.$code}{/if}">
												{else}
													<input type="text" name="lang_values[{$code}][]" value="{if isset($item.lang_values.$code.$key)}{$item.lang_values.$code.$key}{elseif isset($item.values_titles.$value.$code)}{$item.values_titles.$value.$code}{/if}">
												{/if}
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
										<span class="list"></span>
										<input type="hidden" name="children[]">
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
											{if $code == $smarty.const.IA_LANGUAGE}
												<input type="text" class="fvalue" name="values[]">
											{else}
												<input type="text" name="lang_values[{$code}][]">
											{/if}
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
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='max_num_images'}</label>

						<div class="col col-lg-4">
							<input type="text" name="pic_max_images" value="{if isset($item.pic_max_images)}{$item.pic_max_images|escape:'html'}{else}5{/if}">
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='file_prefix'}</label>

						<div class="col col-lg-4">
							<input type="text" name="pic_file_prefix" value="{if isset($item.file_prefix)}{$item.file_prefix|escape:'html'}{/if}">
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

						<div class="col col-lg-4">
							<div class="row">
								<div class="col col-lg-6">
									<input type="text" name="pic_image_width" value="{if isset($item.image_width)}{$item.image_width|escape:'html'}{else}900{/if}">
								</div>
								<div class="col col-lg-6">
									<input type="text" name="pic_image_height" value="{if isset($item.image_height)}{$item.image_height|escape:'html'}{else}600{/if}">
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

						<div class="col col-lg-4">
							<div class="row">
								<div class="col col-lg-6">
									<input type="text" name="pic_thumb_width" value="{if isset($item.thumb_width)}{$item.thumb_width|escape:'html'}{else}{$core.config.thumb_w}{/if}">
								</div>
								<div class="col col-lg-6">
									<input type="text" name="pic_thumb_height" value="{if isset($item.thumb_height)}{$item.thumb_height|escape:'html'}{else}{$core.config.thumb_h}{/if}">
								</div>
							</div>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

						<div class="col col-lg-4">
							<select name="pic_resize_mode">
								<option value="crop"{if isset($item.resize_mode) && iaPicture::CROP == $item.resize_mode} selected{/if} data-annotation="{lang key='crop_tip'}">{lang key='crop'}</option>
								<option value="fit"{if isset($item.resize_mode) && iaPicture::FIT == $item.resize_mode} selected{/if} data-annotation="{lang key='fit_tip'}">{lang key='fit'}</option>
							</select>
							<p class="help-block"></p>
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

			<div class="row" id="tr_required"{if !$item.required} style="display: none;"{/if}>
				<label class="col col-lg-2 control-label">{lang key='required_checks'} <a href="#" class="js-tooltip" title="{$tooltips.required_checks}"><i class="i-info"></i></a></label>

				<div class="col col-lg-8">
					<textarea name="required_checks" class="js-code-editor">{if isset($item.required_checks)}{$item.required_checks|escape:'html'}{/if}</textarea>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='extra_actions'} <a href="#" class="js-tooltip" title="{$tooltips.extra_actions}"><i class="i-info"></i></a></label>

				<div class="col col-lg-8">
					<textarea name="extra_actions" class="js-code-editor">{if isset($item.extra_actions)}{$item.extra_actions|escape:'html'}{/if}</textarea>
				</div>
			</div>

			<div class="row">
				<ul class="nav nav-tabs">
					{foreach $core.languages as $code => $language}
						<li{if $language@iteration == 1} class="active"{/if}><a href="#tab-language-{$code}" data-toggle="tab" data-language="{$code}">{$language.title}</a></li>
					{/foreach}
				</ul>

				<div class="tab-content">
					{foreach $core.languages as $code => $language}
						<div class="tab-pane{if $language@first} active{/if}" id="tab-language-{$code}">
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='title'} <span class="required">*</span></label>
								<div class="col col-lg-10">
									<input type="text" name="title[{$code}]"{if isset($item.title.$code)} value="{$item.title.$code|escape:'html'}"{/if}>
								</div>
							</div>
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='tooltip'}</label>
								<div class="col col-lg-10">
									<input type="text" name="annotation[{$code}]"{if isset($item.annotation.$code)} value="{$item.annotation.$code|escape:'html'}"{/if}>
								</div>
							</div>

						</div>
					{/foreach}
				</div>
			</div>
		</div>

		{include file='fields-system.tpl'}
	</div>
</form>
{ia_add_media files='tree'}
{ia_print_js files='jquery/plugins/jquery.numeric,utils/edit_area/edit_area,admin/fields'}