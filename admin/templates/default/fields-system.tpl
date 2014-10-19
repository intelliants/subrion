{if !isset($noSystemFields)}
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
						<option value="{$plan.id}"{if $plan.id == $item.sponsored_plan_id} selected{/if}>{lang key='plan_title_'|cat:$plan.id} - {$config.currency} {$plan.cost}</option>
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
						<input size="16" type="text" class="js-datepicker" value="{$item.sponsored_end}" name="sponsored_end" readonly>
						<span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
					</div>
				</div>
			</div>
		{/if}
	{/if}

	{if isset($item.featured)}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='featured'}</label>

			<div class="col col-lg-4">
				{html_radio_switcher name='featured' value=$item.featured|default:0}
			</div>
		</div>

		<div class="row" id="tr_featured"{if !$item.featured} style="display:none;"{/if}>
			<label class="col col-lg-2 control-label">{lang key='featured_end'}</label>

			<div class="col col-lg-4">
				<div class="input-group">
					<input type="text" class="js-datepicker" name="featured_end" value="{$item.featured_end}">
					<span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
				</div>
			</div>
		</div>
	{/if}

	{if isset($item.locked)}
		<div class="row">
			<label class="col col-lg-2 control-label">{lang key='locked'}</label>

			<div class="col col-lg-4">
				{html_radio_switcher name='locked' value=$item.locked|default:0}
			</div>
		</div>
	{/if}

	{if isset($item.date_added)}
		<div class="row">
			<label class="col col-lg-2 control-label" for="field_date_added">{lang key='date_added'}</label>

			<div class="col col-lg-4">
				<div class="input-group">
					<input type="text" class="datepicker js-datepicker" name="date_added" id="field_date_added" value="{if $item.date_added != '0000-00-00 00:00:00'}{$item.date_added|date_format:'%Y-%m-%d'}{/if}">
					<span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
				</div>
			</div>
		</div>
	{/if}

	{if isset($item.status)}
		{if !isset($statuses)}
			{assign statuses ['active','inactive']}
		{/if}

		<div class="row">
			<label class="col col-lg-2 control-label" for="status">{lang key='status'}</label>

			<div class="col col-lg-4">
				<select name="status" id="status">
				{foreach $statuses as $status}
					<option value="{$status}"{if isset($item.status) && $item.status == $status} selected{elseif isset($smarty.post.status) && $smarty.post.status == $status} selected{/if}>{lang key=$status}</option>
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
{/if}

{if isset($ugp)}
<div class="widget widget-default{if !$ugp_modified} collapsed{/if}" id="widget-permissions">
	<div class="widget-header"><i class="i-equalizer"></i> {lang key='permissions'}
		<ul class="nav nav-pills pull-right">
			<li><a href="{$smarty.const.IA_ADMIN_URL}permissions/"><i class="i-users-2"></i> {lang key='usergroup_management'}</a></li>
			<li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
		</ul>
	</div>
	<div class="widget-content">
		<ul class="widget-system-panel">
			<li><a href="#" rel="1"{if !$ugp_modified} class="disabled"{/if}>{lang key='select_all'}</a></li>
			<li><a href="#" rel="0"{if !$ugp_modified} class="disabled"{/if}>{lang key='select_none'}</a></li>
			<li><label class="checkbox"><input type="checkbox" name="permissions_defaults" id="input-ugp-default"{if !$ugp_modified} checked{/if}> {lang key='restore_defaults'}</label></li>
		</ul>

		{foreach $ugp as $entry}
		<div class="row">
			<label class="col col-lg-2">{$entry.title}{if !$entry.system} <i class="i-tools" title="Custom usergroup"></i>{/if}</label>

			<div class="col col-lg-4 p-table__actions js-toggler-group" data-id="{$entry.id}" data-default-access="{$entry.default}">
				<input type="hidden" id="js-ugp-{$entry.id}" name="permissions[{$entry.id}]" value="{$entry.access}">
				<span class="label label-{if !$ugp_modified}default disabled{elseif $entry.access}success{else}default{/if}" data-access="1"><i class="i-checkmark"></i> Yes</span>
				<span class="label label-{if !$ugp_modified}default disabled{elseif !$entry.access}danger{else}default{/if}" data-access="0"><i class="i-close"></i> No</span>
			</div>
		</div>
		{/foreach}
	</div>
</div>
{ia_add_js}
$(function()
{
	var usergroupAccess = { };

	$('#input-ugp-default').on('click', function()
	{
		var setDefaults = $(this).prop('checked');
		var $section = $('#widget-permissions');
		var $togglers = $('.js-toggler-group', $section);
		var $actions = $('.widget-system-panel a', $section);

		if (setDefaults)
		{
			$togglers.each(function()
			{
				var id = $(this).data('id');
				usergroupAccess[id] = $('#js-ugp-' + id).val();
			});
			setTogglerValue();
			$actions.addClass('disabled');
		}
		else
		{
			$togglers.each(function()
			{
				var id = $(this).data('id');
				var value = ('undefined' == typeof usergroupAccess[id])
					? $('#js-ugp-' + id).val()
					: usergroupAccess[id];

				setTogglerValue(value, $(this));
			});
			$actions.removeClass('disabled');
		}
	});

	$('.widget-system-panel a', '#widget-permissions').on('click', function(e)
	{
		e.preventDefault();
		$(this).hasClass('disabled') || setTogglerValue($(this).attr('rel'));
	});

	function setTogglerValue(value, $toggler)
	{
		$toggler = $toggler || $('#widget-permissions').find('.js-toggler-group');

		if ('undefined' != typeof value)
		{
			$toggler.each(function()
			{
				$('#js-ugp-' + $(this).data('id')).val(value);
				var status = (1 == value);

				$(this).find('span:eq(0)')
					.removeClass((status ? 'label-default' : 'label-success') + ' disabled')
					.addClass(status ? 'label-success' : 'label-default');
				$(this).find('span:eq(1)')
					.removeClass((status ? 'label-danger' : 'label-default') + ' disabled')
					.addClass(status ? 'label-default' : 'label-danger');
			});
		}
		else
		{
			$toggler.find('span')
				.removeClass('label-success label-danger')
				.addClass('label-default disabled');
		}
	}

	$('.label', '#widget-permissions').on('click', function(e)
	{
		$(this).hasClass('disabled') || setTogglerValue($(this).data('access'), $(this).parent());
	});
});
{/ia_add_js}
{/if}

{if !isset($noControls)}
	<div class="form-actions inline">
		<input type="submit" name="save" class="btn btn-primary" value="{if iaCore::ACTION_ADD == $pageAction}{lang key='add'}{else}{lang key='save'}{/if}">
		{include file='goto.tpl'}
	</div>
{/if}