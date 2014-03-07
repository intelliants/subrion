{if isset($plans) && $plans}
	<div class="fieldset-wrapper">
		<div class="fieldset">
			<h3 class="title">{lang key='plans'}</legend></h3>

			<div class="content">
				<div id="plans_container" class="plans well-subrion">
					<div class="plan well-item">
						<label for="plan_0" class="radio">
							<input type="radio" name="plan_id" value="0" {if isset($item.plan) && $item.plan == 0}checked="checked"{/if} id="plan_0">
							<strong>{lang key='_not_assigned_'}</strong>
						</label>
						<input type="hidden" id="fields_0">
					</div>
					{foreach $plans as $plan}
						<div class="plan well-item">
							<label for="plan_{$plan.id}" class="radio">
								<input type="radio" name="plan_id" value="{$plan.id}"{if isset($item.sponsored_plan_id) && $plan.id == $item.sponsored_plan_id} checked="checked"{/if} id="plan_{$plan.id}">
								<strong>{lang key="plan_title_{$plan.id}"} - {$plan.cost} {$config.currency}</strong>
							</label>
							<div class="description">{lang key="plan_description_{$plan.id}"}</div>
							<input type="hidden" id="fields_{$plan.id}" value="{if isset($plan.fields)}{$plan.fields}{/if}" />
						</div>
					{/foreach}
				</div>
			</div>
		</div>
	</div>

{ia_add_js}
$(function()
{
	var container = $('#plans_container');
	$('input[type="radio"]', container).click(function()
	{
		var plan = $(this).val();
		var fields = $('#fields_'+plan).val().split(',');
		$('.for_plan').hide().addClass('hide');
		$.each(fields, function(index, item)
		{
			$('#'+item+'_fieldzone').show().removeClass('hide');
		});
		$('fieldset .fieldset-wrapper').each(function()
		{
			if ($('.fieldzone', this).length > 0 && $('.fieldzone:not(.for_plan),.fieldzone:not(.hide)', this).length == 0) {
				$(this).parent('fieldset').hide();
			} else {
				$(this).parent('fieldset').show();
			}
		});
	});

	if ($(':checked', container).length)
	{
		$(':checked', container).click();
	}
	else
	{
		$('input[type="radio"]:first', container).click();
	}
});
{/ia_add_js}
{/if}