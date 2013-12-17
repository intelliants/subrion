<div class="wrap-group">
	<div class="wrap-group-heading">
		<h4>{lang key='system_fields'}</h4>
	</div>

	{if isset($fieldset_before.systems)}{$fieldset_before.systems}{/if}

	{if isset($item.owner)}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='owner'}</label>

			<div class="col col-lg-4">
				<input type="text" class="common text" autocomplete="off" id="js-owner-autocomplete" name="owner" value="{$item.owner}" maxlength="50" size="50">
			</div>
		</div>
	{/if}

	{if isset($item.sponsored) && !is_null($item.sponsored)}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='sponsored'}</label>

			<div class="col col-lg-4">
				{html_radio_switcher value=$item.sponsored|default:0 name='sponsored'}
			</div>
		</div>

		<div class="row" id="plans"{if $item.sponsored != 1} style="display: none;"{/if}>
			<label class="col col-lg-2 control-label" for="plan-selector">{lang key='plan'}</label>

			<div class="col col-lg-4">
			{if isset($plans) && $plans}
				<select name="plan_id" id="plan-selector">
				{foreach $plans as $plan}
					<option value="{$plan.id}"{if $plan.id == $item.sponsored_plan_id} selected="selected"{/if}>{lang key='plan_title_'|cat:$plan.id} - {$config.currency} {$plan.cost}</option>
				{/foreach}
				</select>
			{else}
				<span class="label label-info">{lang key='no_plans'}</span>
			{/if}
			</div>
		</div>

		{if isset($plans) && $plans}
		<div class="row" id="sponsored-end-tr"{if $item.sponsored != 1} style="display:none"{/if}>
			<label class="col col-lg-2 control-label">{lang key='sponsored_end'}</label>

			<div class="col col-lg-4">
				<div class="input-group">
					<input size="16" type="text" class="js-datetimepicker-system-field" value="{$item.sponsored_end}" name="sponsored_end" readonly>
					<span class="input-group-addon js-datetimepicker-toggle"><i class="i-calendar"></i></span>
				</div>
			</div>
		</div>
		{/if}
	{/if}

	{if isset($item.featured)}
	<div class="row">
		<label class="col col-lg-2 control-label">{lang key='featured'}</label>

		<div class="col col-lg-4">
			{html_radio_switcher value=$item.featured|default:0 name='featured'}
		</div>
	</div>

	<div class="row" id="tr_featured"{if !$item.featured} style="display:none;"{/if}>
		<label class="col col-lg-2 control-label">{lang key='featured_end'}</label>

		<div class="col col-lg-4">
			<div class="input-group">
				<input type="text" class="js-datetimepicker-system-field" name="featured_end" value="{$item.featured_end}">
				<span class="input-group-addon js-datetimepicker-toggle"><i class="i-calendar"></i></span>
			</div>
		</div>
	</div>
	{/if}

	{if isset($item.locked)}
	<div class="row">
		<label class="col col-lg-2 control-label">{lang key='locked'}</label>

		<div class="col col-lg-4">
			{html_radio_switcher value=$item.locked|default:0 name='locked'}
		</div>
	</div>
	{/if}

	{if isset($item.date_added)}
	<div class="row">
		<label class="col col-lg-2 control-label" for="field_date_added">{lang key='date_added'}</label>

		<div class="col col-lg-4">
			<div class="input-group">
				<input type="text" class="js-datetimepicker-system-field" name="date_added" id="field_date_added" value="{if $item.date_added != '0000-00-00 00:00:00'}{$item.date_added|date_format:'%Y-%m-%d'}{/if}">
				<span class="input-group-addon js-datetimepicker-toggle"><i class="i-calendar"></i></span>
			</div>
		</div>
	</div>
	{/if}

	{if isset($item.status)}
		{if !isset($statuses)}
			{assign var='statuses' value=['active','inactive']}
		{/if}

		<div class="row">
			<label class="col col-lg-2 control-label" for="status">{lang key='status'}</label>

			<div class="col col-lg-4">
				<select name="status" id="status">
				{foreach $statuses as $status}
					<option value="{$status}"{if isset($item.status) && $item.status == $status} selected="selected"{elseif isset($smarty.post.status) && $smarty.post.status == $status} selected="selected"{/if}>{lang key=$status}</option>
				{/foreach}
				</select>
			</div>
		</div>
	{/if}

	{if isset($fieldset_after.systems)}{$fieldset_after.systems}{/if}

	{ia_add_media files='datepicker'}
	{ia_add_js}
		$(function()
		{
			// sponsored switchers
			$('input[name="sponsored"]').on('change', function()
			{
				(1 == this.value) ? $('#plans, #sponsored-end-tr').show() : $('#plans, #sponsored-end-tr').hide();
			});

			// featured switchers
			$('input[name="featured"]').on('change', function()
			{
				(1 == this.value) ? $('#tr_featured').show() : $('#tr_featured').hide();
			});

			$('.js-datetimepicker-system-field').datetimepicker(
			{
				format: 'yyyy-mm-dd hh:ii',
				pickerPosition: 'top-left',
				autoclose: true
			});

			$('#js-owner-autocomplete').typeahead(
			{
				source: function(query, process)
				{
					return $.ajax(
					{
						url: intelli.config.ia_url + 'members.json',
						type: 'get',
						dataType: 'json',
						data: { q: query },
						success: function(response)
						{
							return process(response);
						}
					});
				}
			});
		});
	{/ia_add_js}
</div>

{if !isset($noControls)}
	<div class="form-actions inline">
		<input type="submit" name="save" class="btn btn-primary" value="{lang key='save'}" />
		{include file='goto.tpl'}
	</div>
{/if}