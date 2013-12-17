{if $pageAction == 'edit' || $pageAction == 'add'}
	<form action="{$smarty.const.IA_SELF}{if $pageAction == 'edit'}?id={$field.id}{/if}" method="post" class="sap-form form-horizontal">
		{preventCsrf}
		<div class="wrap-list">
			<div class="wrap-group">
				<div class="wrap-group-heading">
					<h4>{lang key='options'}</h4>
				</div>

				{if 'edit' == $pageAction}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='name'}</label>

					<div class="col col-lg-4">
						<input type="text" disabled="disabled" value="{$field.name}">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='field_item'}</label>

					<div class="col col-lg-4">
						<input type="text" disabled="disabled" value="{$field.item}">
						<input type="hidden" name="item" value="{$field.item}">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='fields_type'}</label>

					<div class="col col-lg-4">
						<input type="text" disabled="disabled" value="{lang key="fields_type_{$field.type}" default=$field.type}">
						<input type="hidden" value="{$field.type}" name="field_type" id="type">
						<input type="hidden" name="id" value="{$field.id}">
						<input type="hidden" name="name" value="{$field.name}">
					</div>
				</div>
				{else}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='name'} <span class="required">*</span></label>

					<div class="col col-lg-4">
						<input type="text" name="name" value="{$field.name}">
						<p class="help-block">{lang key='unique_name'}</p>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='field_item'} <span class="required">*</span></label>

					<div class="col col-lg-4">
						<select name="item" id="js-item-name">
							<option value="">{lang key='_select_'}</option>
							{foreach $items as $item}
								<option value="{$item}"{if isset($smarty.post.item) && $smarty.post.item == $item || isset($smarty.get.item) && $smarty.get.item == $item} selected="selected"{/if}>{lang key=$item default=$item}</option>
							{/foreach}
						</select>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='fields_type'} <span class="required">*</span></label>

					<div class="col col-lg-4">
						<select name="field_type" id="type">
							<option value="">{lang key='_select_'}</option>
							{foreach $field_types as $type}
								<option value="{$type}"{if $field.type == $type} selected="selected"{/if}>{lang key='fields_type_'|cat:$type default=$type}</option>
							{/foreach}
						</select>

						<div id="field_type_tip">
							<p class="help-block" id="type_tip_checkbox" style="display: none;">{lang key='fields_type_tip_checkbox'}</p>
							<p class="help-block" id="type_tip_image" style="display: none;">{lang key='fields_type_tip_image'}</p>
							<p class="help-block" id="type_tip_number" style="display: none;">{lang key='fields_type_tip_number'}</p>
							<p class="help-block" id="type_tip_date" style="display: none;">{lang key='fields_type_tip_date'}</p>
							<p class="help-block" id="type_tip_pictures" style="display: none;">{lang key='fields_type_tip_pictures'}</p>
							<p class="help-block" id="type_tip_url" style="display: none;">{lang key='fields_type_tip_url'}</p>
						</div>
					</div>
				</div>
				{/if}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='field_group'}</label>

					<div class="col col-lg-4">
						<select name="fieldgroup_id" id="js-fieldgroup-selectbox"{if !$groups} disabled="disabled"{/if}>
							<option value="">{lang key='_select_'}</option>
							{foreach $groups as $code => $value}
								<option value="{$code}"{if $code == $field.fieldgroup_id} selected="selected"{/if}>{$value.name}</option>
							{/foreach}
						</select>
					</div>
				</div>
				{foreach $languages as $code => $language}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='title'} <span class="required">*</span> <span class="label label-info">{$language}</span></label>

					<div class="col col-lg-4">
						<input type="text" name="title[{$code}]" value="{if isset($field.title.$code) && trim($field.title.$code) != ''}{$field.title.$code}{else}{$field.name}{/if}">
					</div>
				</div>
				{/foreach}
				<div id="js-row-empty-text" class="row">
					<label class="col col-lg-2 control-label">{lang key='empty_field'} <a href="#" class="js-tooltip" title="{$tooltips.empty_field}"><i class="i-info"></i></a></label>

					<div class="col col-lg-4">
						<input type="text" name="empty_field" value="{$field.empty_field}">
					</div>
				</div>
				<div class="row" id="js-pages-list-row"{if $pageAction == 'add' && (!$smarty.post && !isset($smarty.get.item))} style="display: none;"{/if}>
					<label class="col col-lg-2 control-label">{lang key='shown_on_pages'} <span class="required">*</span></label>

					<div class="col col-lg-4">
						<div class="box-simple fieldset">
						{foreach $pages as $pageId => $entry}
							<div class="checkbox" data-item="{$entry.item}">
								<label>
									<input type="checkbox" value="{$entry.name}"{if in_array($entry.name, $field.pages)} checked="checked"{/if} name="pages[{$pageId}]">
									{$entry.title}
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
						{html_radio_switcher value=$field.adminonly name='adminonly'}
					</div>
				</div>
				{if $smarty.const.INTELLI_QDEBUG}
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='regular_field'}</label>

						<div class="col col-lg-4">
							<select name="relation" class="common" id="relation">
								<option value="regular"{if $field.relation == 'regular'} selected="selected"{/if}>{lang key='field_relation_regular'}</option>
								<option value="dependent"{if $field.relation == 'dependent'} selected="selected"{/if}>{lang key='field_relation_dependent'}</option>
								{if in_array($field.type, array('checkbox', 'radio', 'combo'))}
									<option value="parent"{if $field.relation == 'parent'} selected="selected"{/if}>{lang key='field_relation_parent'}</option>
								{/if}
							</select>
							<input type="hidden" name="relation_type" value="-1">
						</div>
					</div>
				{else}
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='regular_field'} <a href="#" class="js-tooltip" title="{$tooltips.regular_field}"><i class="i-info"></i></a></label>

						<div class="col col-lg-4">
							{html_radio_switcher value=$field.regular_field name='relation_type'}
						</div>
					</div>
				{/if}

				<div id="regular_field"{if $field.relation != 'dependent'} style="display: none;"{/if} class="row">
					<label class="col col-lg-2 control-label">{lang key='host_fields'}</label>

					<div class="col col-lg-4">
						{foreach $parents as $field_item => $item_list}
							<div class="field_pages pages" item="{$field_item}"{if $field.item != $field_item} style="display: none;"{/if}>
								{foreach $item_list as $field_name => $elements}
									<fieldset class="list">
										<legend><span class="text-up">{lang key='field_'|cat:$field_name}</span> {lang key='field_values'}</legend>
										<ul class="visible_common">
											{foreach $elements as $element}
												<li>
													<label>
														<input type="checkbox" value="1"{if isset($field.parents[$field_item][$field_name][$element])} checked="checked"{/if} name="parents[{$field_item}][{$field_name}][{$element}]" />
														{lang key="field_{$field_name}_{$element}"}
													</label>
												</li>
											{/foreach}
										</ul>
									</fieldset>
								{foreachelse}
									<div>{lang key='no_parent_fields'}</div>
								{/foreach}
							</div>
						{/foreach}
					</div>
				</div>

				<div class="row" id="for_plan_only" {if $field.required} style="display: none"{/if}>
					<label class="col col-lg-2 control-label">{lang key='for_plan_only'} <a href="#" class="js-tooltip" title="{$tooltips.for_plan_only}"><i class="i-info"></i></a></label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$field.for_plan name='for_plan'}
					</div>
				</div>

				<div class="row"{if $field.required} style="display: none"{/if}>
					<label class="col col-lg-2 control-label">{lang key='searchable'} <a href="#" class="js-tooltip" title="{$tooltips.searchable}"><i class="i-info"></i></a></label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$field.searchable name='searchable'}
					</div>
				</div>

				<div class="row" id="link-to-details" style="display: none">
					<label class="col col-lg-2 control-label">{lang key='link_to'} <a href="#" class="js-tooltip" title="{$tooltips.link_to_details}"><i class="i-info"></i></a></label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$field.link_to name='link_to'}
					</div>
				</div>

				{ia_hooker name='smartyAdminFieldsEdit'}

				<div id="text" class="field_type" style="display: none;">
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='field_length'}</label>

						<div class="col col-lg-4">
							<input type="text" name="text_length" value="{if !$field.length || $field.length > 255}100{else}{$field.length}{/if}">
							<p class="help-block">{lang key='digit_only'}</p>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='field_default'}</label>

						<div class="col col-lg-4">
							<input type="text" name="text_default" class="js-code-editor" value="{$field.default}">
						</div>
					</div>
				</div>

				<div id="textarea" class="field_type" style="display: none;">
					<div id="js-row-use-editor" class="row">
						<label class="col col-lg-2 control-label">{lang key='use_editor'}</label>

						<div class="col col-lg-4">
							{html_radio_switcher value=$field.use_editor name='use_editor'}
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='field_length'}</label>

						<div class="col col-lg-4">
							<input type="text" name="length" class="js-code-editor" value="{if isset($field.length)}{$field.length}{/if}">
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
								<input type="text" name="max_files" value="{if isset($field.length) && !empty($field.length)}{$field.length}{elseif isset($smarty.post.length)}{$smarty.post.length}{elseif !isset($field) && empty($smarty.post)}5{else}1{/if}">
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='file_types'} <span class="required">*</span></label>

							<div class="col col-lg-4">
								<textarea rows="3" id="file_types" name="file_types">{if isset($field.file_types)}{$field.file_types}{elseif isset($smarty.post.file_types)}{$smarty.post.file_types}{/if}</textarea>
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
								<input type="text" name="file_prefix" value="{if isset($field.file_prefix)}{$field.file_prefix}{elseif isset($smarty.post.file_prefix)}{$smarty.post.file_prefix}{/if}">
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

							<div class="col col-lg-4">
								<div class="row">
									<div class="col col-lg-6">
										<input type="text" name="image_width" value="{if isset($field.image_width)}{$field.image_width}{elseif isset($smarty.post.image_width)}{$smarty.post.image_width}{/if}">
									</div>
									<div class="col col-lg-6">
										<input type="text" name="image_height" value="{if isset($field.image_height)}{$field.image_height}{elseif isset($smarty.post.image_height)}{$smarty.post.image_height}{/if}">
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

							<div class="col col-lg-4">
								<div class="row">
									<div class="col col-lg-6">
										<input type="text" name="thumb_width" value="{if isset($field.thumb_width)}{$field.thumb_width}{elseif isset($smarty.post.thumb_width)}{$smarty.post.thumb_width}{/if}">
									</div>
									<div class="col col-lg-6">
										<input type="text" name="thumb_height" value="{if isset($field.thumb_height)}{$field.thumb_height}{elseif isset($smarty.post.thumb_height)}{$smarty.post.thumb_height}{/if}">
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

							<div class="col col-lg-4">
								<select name="resize_mode">
									<option value="crop" {if isset($field.resize_mode) && $field.resize_mode == 'crop' || isset($smarty.post.resize_mode) && $smarty.post.resize_mode == 'crop'}selected="selected"{/if}>{lang key='crop'}</option>
									<option value="fit" {if isset($field.resize_mode) && $field.resize_mode == 'fit' || isset($smarty.post.resize_mode) && $smarty.post.resize_mode == 'fit'}selected="selected"{/if}>{lang key='fit'}</option>
								</select>
								<p id="resize_mode_tip_crop" class="help-block" style="display: none;">{lang key='crop_tip'}</p>
								<p id="resize_mode_tip_fit" class="help-block" style="display: none;">{lang key='fit_tip'}</p>
							</div>
						</div>
					{/if}
				</div>

				<div id="multiple" class="field_type" style="display: none;">
					<div id="show_in_search_as" class="row"{if !$field.searchable} style="display:none"{/if}>
						<label class="col col-lg-2 control-label">{lang key='show_in_search_as'}</label>

						<div class="col col-lg-4">
							<select name="show_as" id="showAs">
								<option value="checkbox" {if isset($field.show_as) && $field.show_as == 'checkbox'}selected="selected"{elseif isset($smarty.post.show_as) && $smarty.post.show_as == 'checkbox'}selected="selected"{/if}>{lang key='checkboxes'}</option>
								<option value="radio" {if isset($field.show_as) && $field.show_as == 'radio'}selected="selected"{elseif isset($smarty.post.show_as) && $smarty.post.show_as == 'radio'}selected="selected"{/if}>{lang key='radios'}</option>
								<option value="combo" {if isset($field.show_as) && $field.show_as == 'combo'}selected="selected"{elseif isset($smarty.post.show_as) && $smarty.post.show_as == 'combo'}selected="selected"{/if}>{lang key='dropdown'}</option>
							</select>
						</div>
					</div>

					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='field_default'}</label>

						<div class="col col-lg-4">
							<input type="text" readonly="readonly" name="multiple_default" id="multiple_default" value="{if isset($field.default)}{$field.default}{elseif isset($smarty.post.multiple_default)}{$smarty.post.multiple_default}{/if}">
							<a href="#" class="js-actions label label-default pull-right" data-action="clearDefault"><i class="i-cancel-circle"></i> {lang key='clear_default'}</a>
						</div>
					</div>

					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='field_values'}</label>

						<div class="col col-lg-4">
							{if isset($field.values) && $field.values}
								{foreach $field.values as $key => $value name='values'}
									<div id="item-value-{$value}" class="wrap-row wrap-block" data-value-id="{$value}">
										<div class="row">
											<label class="col col-lg-4 control-label">{lang key='key'} <i>({lang key='not_required'})</i></label>
											<div class="col col-lg-8">
												<input type="text" name="keys[]" value="{if isset($field.keys.$key)}{$field.keys.$key}{else}{$value}{/if}">	
											</div>
										</div>
										{foreach $languages as $code => $language}
											<div class="row">
												<label class="col col-lg-4 control-label">{lang key='item_value'} <span class="label label-info">{$language}</span></label>
												<div class="col col-lg-8">
													{if $code == $smarty.const.IA_LANGUAGE}
														<input type="text" class="fvalue" name="values[]" value="{if !isset($field.values_titles.$value.$code)}{$value}{else}{$field.values_titles.$value.$code}{/if}">
													{else}
														<input type="text" name="lang_values[{$code}][]" value="{if isset($field.lang_values.$code.$key)}{$field.lang_values.$code.$key}{else}{$field.values_titles.$value.$code}{/if}">
													{/if}	
												</div>
											</div>
										{/foreach}
										<div class="actions-panel">
											<a href="#" class="js-actions label label-default" data-action="setDefault">{lang key='set_as_default_value'}</a>
											<a href="#" class="js-actions label label-default" data-action="removeDefault">{lang key='clear_default'}</a>
											<a href="#" class="js-actions label label-danger" data-action="removeItem" title="{lang key='remove'}"><i class="i-close"></i></a>
											<a href="#" class="js-actions label label-success itemUp" style="display: none;" data-action="itemUp" title="{lang key='item_up'}"><i class="i-chevron-up"></i></a>
											<a href="#" class="js-actions label label-success itemDown" style="display: none;" data-action="itemDown" title="{lang key='item_down'}"><i class="i-chevron-down"></i></a>
										</div>
										<div class="main_fields"{if $field.relation != 'parent'} style="display:none;"{/if}>
											{lang key='field_element_children'}: <span onclick="wfields(this)"><i class="i-fire"></i></span>
											<span class="list"></span>
											<input type="hidden" name="children[]">
										</div>
									</div>
								{/foreach}
								<a href="{$smarty.const.IA_SELF}#add_item" class="js-actions label pull-right label-success" id="add_item"><i class="i-plus"></i> {lang key='add_item_value'}</a>
							{else}
								<div id="item-value-default" class="wrap-row wrap-block">
									{foreach $field.values as $key => $value name='values'}
										<div class="row">
											<label class="col col-lg-4 control-label">{lang key='key'} <i>({lang key='not_required'})</i></label>
											<div class="col col-lg-8">
												<input type="text" name="keys[]" value="">
											</div>
										</div>
										{foreach $languages as $code => $language}
											<div class="row">
												<label class="col col-lg-4 control-label">{lang key='item_value'} <span class="label label-info">{$language}</span></label>
												<div class="col col-lg-8">
													{if $code == $smarty.const.IA_LANGUAGE}
														<input type="text" class="fvalue" name="values[]" value="">
													{else}
														<input type="text" name="lang_values[{$code}][]" value="">
													{/if}	
												</div>
											</div>
										{/foreach}
										<div class="actions-panel">
											<a href="#" class="js-actions label label-default" data-action="setDefault">{lang key='set_as_default_value'}</a>
											<a href="#" class="js-actions label label-default" data-action="removeDefault">{lang key='clear_default'}</a>
											<a href="#" class="js-actions label label-danger" data-action="removeItem" title="{lang key='remove'}"><i class="i-close"></i></a>
											<a href="#" class="js-actions label label-success itemUp" style="display: none;" data-action="itemUp" title="{lang key='item_up'}"><i class="i-chevron-up"></i></a>
											<a href="#" class="js-actions label label-success itemDown" style="display: none;" data-action="itemDown" title="{lang key='item_down'}"><i class="i-chevron-down"></i></a>
										</div>
										<div class="main_fields"{if $field.relation != 'parent'} style="display:none;"{/if}>
											{lang key='field_element_children'}: <span onclick="wfields(this)"><i class="i-fire"></i></span>
											{if isset($field.children[$smarty.foreach.values.index])}
												<span class="list">{$field.children[$smarty.foreach.values.index].titles}</span>
												<input type="hidden" value="{$field.children[$smarty.foreach.values.index].values}" name="children[]">
											{else}
												<span class="list"></span>
												<input type="hidden" name="children[]">
											{/if}
										</div>
									{/foreach}
								</div>
								<a href="{$smarty.const.IA_SELF}#add_item" class="js-actions label pull-right label-success" id="add_item"><i class="i-plus"></i> {lang key='add_item_value'}</a>
							{/if}
						</div>
					</div>
				</div>

				<div id="url" class="field_type" style="display: none;">
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='url_nofollow'}</label>

						<div class="col col-lg-4">
							{html_radio_switcher value=$field.url_nofollow name='url_nofollow'}
						</div>
					</div>
				</div>

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
								<input type="text" name="pic_max_images" value="{if isset($field.pic_max_images)}{$field.pic_max_images}{elseif isset($smarty.post.pic_max_images)}{$smarty.post.pic_max_images}{elseif !isset($field) && empty($smarty.post)}5{/if}">
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='file_prefix'}</label>

							<div class="col col-lg-4">
								<input type="text" name="pic_file_prefix" value="{if isset($field.file_prefix)}{$field.file_prefix}{elseif isset($smarty.post.pic_file_prefix)}{$smarty.post.pic_file_prefix}{/if}">
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

							<div class="col col-lg-4">
								<div class="row">
									<div class="col col-lg-6">
										<input type="text" name="pic_image_width" value="{if isset($field.image_width)}{$field.image_width}{elseif isset($smarty.post.pic_image_width)}{$smarty.post.pic_image_width}{/if}">
									</div>
									<div class="col col-lg-6">
										<input type="text" name="pic_image_height" value="{if isset($field.image_height)}{$field.image_height}{elseif isset($smarty.post.pic_image_height)}{$smarty.post.pic_image_height}{/if}">
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='thumb_width'} / {lang key='thumb_height'}</label>

							<div class="col col-lg-4">
								<div class="row">
									<div class="col col-lg-6">
										<input type="text" name="pic_thumb_width" value="{if isset($field.thumb_width)}{$field.thumb_width}{elseif isset($smarty.post.pic_thumb_width)}{$smarty.post.pic_thumb_width}{/if}">
									</div>
									<div class="col col-lg-6">
										<input type="text" name="pic_thumb_height" value="{if isset($field.thumb_height)}{$field.thumb_height}{elseif isset($smarty.post.pic_thumb_height)}{$smarty.post.pic_thumb_height}{/if}">
									</div>
								</div>
							</div>
						</div>
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

							<div class="col col-lg-4">
								<select name="pic_resize_mode" id="pic-resize-mode">
									<option value="crop"{if isset($field.pic_resize_mode) && $field.pic_resize_mode == 'crop' || isset($smarty.post.pic_resize_mode) && $smarty.post.pic_resize_mode == 'crop'} selected="selected"{/if}>{lang key='crop'}</option>
									<option value="fit"{if isset($field.pic_resize_mode) && $field.pic_resize_mode == 'fit' || isset($smarty.post.pic_resize_mode) && $smarty.post.pic_resize_mode == 'fit'} selected="selected"{/if}>{lang key='fit'}</option>
								</select>
								<p id="pic_resize_mode_tip_crop" class="help-block" style="display: none;">{lang key='crop_tip'}</p>
								<p id="pic_resize_mode_tip_fit" class="help-block" style="display: none;">{lang key='fit_tip'}</p>
							</div>
						</div>
					{/if}
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='required_field'}</label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$field.required name='required'}
					</div>
				</div>

				<div class="row" id="tr_required"{if !$field.required} style="display: none;"{/if}>
					<label class="col col-lg-2 control-label">{lang key='required_checks'} <a href="#" class="js-tooltip" title="{$tooltips.required_checks}"><i class="i-info"></i></a></label>
				
					<div class="col col-lg-8">
						<textarea name="required_checks" id="required_checks" class="js-code-editor">{$field.required_checks}</textarea>
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='extra_actions'} <a href="#" class="js-tooltip" title="{$tooltips.extra_actions}"><i class="i-info"></i></a></label>

					<div class="col col-lg-8">
						<textarea name="extra_actions" id="extra_actions" class="js-code-editor">{$field.extra_actions}</textarea>
					</div>
				</div>
			</div>
		</div>

		<div class="form-actions inline">
			<input type="hidden" name="categories" id="categories" value="{if isset($field_categories)}{$field_categories}{elseif isset($smarty.post.categories)}{$smarty.post.categories}{/if}">
			<input type="hidden" name="categories_parents" id="categories_parents" value="{if isset($field_categories_parents)}{$field_categories_parents}{elseif isset($smarty.post.categories_parents)}{$smarty.post.categories_parents}{/if}">
			<input type="submit" value="{lang key='save'}" class="btn btn-primary" name="data-field">
			{if $pageAction == 'add'}
				{include file='goto.tpl'}
			{/if}
		</div>
	</form>

	{ia_print_js files='admin/fields,utils/numeric,utils/edit_area/edit_area'}
{else}
	{include file='grid.tpl'}
	<input type="hidden" id="js-current-item-ph" value="{if isset($field_item)}{$field_item}{/if}">
{/if}