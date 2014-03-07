<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">{lang key='options'}</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='item'}</label>

				<div class="col col-lg-4">
					<select name="item">
						<option value="">{lang key='_select_'}</option>
						{foreach $items as $i}
							<option value="{$i}"{if (isset($plan.item) && $plan.item == $i) || (isset($smarty.post.item) && $smarty.post.item == $i)} selected="selected"{/if}>{lang key=$i default=$i}</option>
						{/foreach}
					</select>
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
																<input{if isset($plan.data.fields) && in_array($field, $plan.data.fields)} checked="checked"{/if} type="checkbox" value="{$field}" name="fields[]">
																{lang key='field_'|cat:$field}
															</label>
															{else}
															<label class="checkbox">
																<input checked="checked" type="checkbox" value="{$field}" disabled="disabled" name="checked_fields[]">
																{lang key='field_'|cat:$field}
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
				<label class="col col-lg-2 control-label">{lang key='cost'}</label>

				<div class="col col-lg-4">
					<input type="text" name="cost"value="{if isset($plan.cost)}{$plan.cost}{elseif isset($smarty.post.cost)}{$smarty.post.cost|escape:'html'}{/if}">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='days'}</label>

				<div class="col col-lg-4">
					<input type="text" name="days" value="{if isset($plan.days)}{$plan.days}{elseif isset($smarty.post.days)}{$smarty.post.days|escape:'html'}{/if}">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='status'}</label>

				<div class="col col-lg-4">
					<select name="status">
						<option value="active" {if isset($plan.status) && $plan.status == 'active'}selected="selected"{/if}>{lang key='active'}</option>
						<option value="inactive" {if isset($plan.status) && $plan.status == 'inactive'}selected="selected"{/if}>{lang key='inactive'}</option>
					</select>
				</div>
			</div>

			<div class="row js-items-list" id="js-item-members" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='change_usergroup_to'}</label>

				<div class="col col-lg-4">
					<select name="usergroup">
						<option value="0">{lang key='no_usergroup'}</option>
						{foreach $usergroups as $uid => $usergroup}
							<option value="{$uid}"{if $uid == $plan.usergroup} selected="selected"{/if}>{$usergroup}</option>
						{/foreach}
					</select>
				</div>
			</div>

			{ia_hooker name='adminPlanItemOptions'}

			<hr>

			<div id="ckeditor" class="row">
				<ul class="nav nav-tabs">
					{foreach $languages as $code => $language}
						<li{if $language@iteration == 1} class="active"{/if}><a href="#tab-language-{$code}" data-toggle="tab" data-language="{$code}">{$language}</a></li>
					{/foreach}
				</ul>

				<div class="tab-content">
					{foreach $languages as $code => $language}
						<div class="tab-pane{if $language@iteration == 1} active{/if}" id="tab-language-{$code}">
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='title'} {lang key='field_required'}</label>

								<div class="col col-lg-10">
									<input type="text" name="title[{$code}]" value="{if isset($plan.id)}{lang key="plan_title_{$plan.id}"}{elseif isset($smarty.post.title.$code)}{$smarty.post.title.$code}{/if}">
								</div>
							</div>
							<div class="row">
								<label class="col col-lg-2 control-label">{lang key='description'}</label>

								<div class="col col-lg-10">
									<textarea id="description_{$language}" rows="30" name="description[{$code}]" class="js-wysiwyg">{if isset($plan.id)}{lang key="plan_description_{$plan.id}"}{elseif isset($smarty.post.description.$code)}{$smarty.post.description.$code}{/if}</textarea>
								</div>
							</div>
						</div>
					{/foreach}
				</div>
			</div>

		</div>
	</div>

	<div class="form-actions inline">
		<input type="submit" name="save" class="btn btn-primary" value="{lang key='save'}">
		{include file='goto.tpl'}
	</div>
</form>

{ia_print_js files='ckeditor/ckeditor, admin/plans'}