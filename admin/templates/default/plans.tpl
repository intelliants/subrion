<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">{lang key='general'}</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='item'} {lang key='field_required'}</label>

				<div class="col col-lg-4">
					<select name="item">
						<option value="">{lang key='_select_'}</option>
						{foreach $items as $i}
							<option value="{$i}"{if (isset($item.item) && $item.item == $i) || (isset($smarty.post.item) && $smarty.post.item == $i)} selected{/if}>{lang key=$i default=$i}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='cost'}</label>

				<div class="col col-lg-4">
					<div class="input-group">
						<input class="js-filter-numeric" type="text" name="cost" value="{$item.cost|escape:'html'}" maxlength="11">
						<div class="input-group-addon">{$core.config.currency}</div>
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='duration'} {lang key='field_required'} <a href="#" class="js-tooltip" title="{$tooltips.plan_duration}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					<div class="row">
						<div class="col-lg-3">
							<input class="js-filter-numeric" type="text" name="duration" value="{$item.duration|escape:'html'}" maxlength="10">
						</div>
						<div class="col-lg-8 col-lg-offset-1">
							<select name="unit">
								{foreach $units as $unit}
									<option value="{$unit}"{if $unit == $item.unit} selected{/if}>{lang key="{$unit}s"}</option>
								{/foreach}
							</select>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='plans_fields'}</label>

				<div class="col col-lg-4">
					<div id="js-fields-empty" class="alert alert-info">{lang key='empty'}</div>
					<div class="row">
						{foreach $fields as $i => $f}
							<div id="js-fields-{$i}" class="js-fields-list" style="display:none;">
								{foreach $f as $for_plan => $fp}
									<div class="col col-lg-6">
										{if !empty($fp)}
											<h4>
												{if $for_plan == 0}
													{lang key='items_fields'}
												{elseif $for_plan == 1}
													{lang key='fields_for_plans'}
												{elseif $for_plan == 2}
													{lang key='required_fields'}
												{/if}
											</h4>
											<div class="box-simple fieldset">
												<ul class="list-unstyled">
													{foreach $fp as $field}
														<li>
															{if $for_plan != 2}
																<label class="checkbox">
																	<input{if isset($item.data.fields) && in_array($field, $item.data.fields)} checked{/if} type="checkbox" value="{$field}" name="fields[]">
																	{lang key="field_{$field}"}
																</label>
															{else}
																<label class="checkbox">
																	<input checked type="checkbox" value="{$field}" disabled name="checked_fields[]">
																	{lang key="field_{$field}"}
																</label>
															{/if}
														</li>
													{/foreach}
												</ul>
											</div>
											{if $for_plan == 0}
												<p class="help-block text-danger">{lang key='warning_fields_become_for_plan_only'}</p>
											{elseif $for_plan == 1}
												<p class="help-block text-danger">{lang key='this_fields_displayed_only_for_plans'}</p>
											{/if}
										{/if}
									</div>
								{/foreach}
							</div>
						{/foreach}
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='change_status_to'}</label>

				<div class="col col-lg-4">
					<select name="expiration_status"{foreach $expiration_statuses as $key => $value} data-{$key}="{$value}"{/foreach}>
						<option value=""{if empty($item.expiration_status)} selected{/if}>{lang key='_do_not_change_'}</option>
						{if iaCore::ACTION_EDIT == $pageAction && $item.item}
							{assign values ','|explode:$expiration_statuses[$item.item]}
							{foreach $values as $value}
								<option value="{$value}"{if $item.expiration_status == $value} selected{/if}>{lang key=$value}</option>
							{/foreach}
						{/if}
					</select>
				</div>
			</div>

			<div class="js-items-list" id="js-item-members" style="display: none;">
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='change_usergroup_to'}</label>

					<div class="col col-lg-4">
						<select name="usergroup">
							<option value="0">{lang key='_do_not_change_'}</option>
							{foreach $usergroups as $uid => $name}
								<option value="{$uid}"{if $uid == $item.usergroup} selected{/if}>{lang key="usergroup_{$name}"}</option>
							{/foreach}
						</select>
					</div>
				</div>

				{ia_hooker name='adminPlanMemberOptions'}
			</div>

			{ia_hooker name='adminPlanItemOptions'}
		</div>

		<div class="wrap-group">
			<div class="wrap-group-heading">{lang key='recurring_options'}</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='recurring'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.recurring name='recurring'}
					<p class="help-block" id="js-cycles-tip"{if !$item.recurring || $item.cycles != -1} style="display: none;"{/if}>You may <a href="#">set limited number of cycles</a> for this plan.</p>
				</div>
			</div>

			<div class="row" id="js-cycles-option"{if !$item.recurring || -1 == $item.cycles} style="display: none;"{/if}>
				<label class="col col-lg-2 control-label">{lang key='number_of_cycles'} <a href="#" class="js-tooltip" title="{$tooltips.plan_cycles}"><i class="i-info"></i></a></label>

				<div class="col col-lg-1">
					<input type="text" name="cycles" class="js-filter-numeric" value="{$item.cycles|escape:'html'}" maxlength="5">
				</div>
			</div>
		</div>

		<div class="wrap-group">
			<div id="ckeditor" class="row">
				<ul class="nav nav-tabs">
					{foreach $core.languages as $code => $language}
						<li{if $language@first} class="active"{/if}><a href="#tab-language-{$code}" data-toggle="tab" data-language="{$code}">{$language.title}</a></li>
					{/foreach}
				</ul>

				<div class="tab-content">
					{foreach $core.languages as $code => $language}
						<div class="tab-pane{if $language@first} active{/if}" id="tab-language-{$code}">
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='title'} {lang key='field_required'}</label>

								<div class="col col-lg-10">
									<input type="text" name="title[{$code}]" value="{$item.title.$code|default:''|escape:'html'}">
								</div>
							</div>
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='description'} {lang key='field_required'}</label>

								<div class="col col-lg-10">
									<textarea id="description_{$code}" rows="30" name="description[{$code}]" class="js-wysiwyg">{$item.description.$code|default:''|escape:'html'}</textarea>
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
{ia_print_js files='ckeditor/ckeditor,jquery/plugins/jquery.numeric,admin/plans'}