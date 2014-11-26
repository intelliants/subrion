{if isset($plans) && $plans}
	<div class="fieldset-wrapper">
		<div class="fieldset">
			<h3 class="title">{lang key='plans'}</legend></h3>

			<div class="content">
				<div id="js-plans-list" class="plans well-subrion">
					<div class="plan well-item">
						<label for="input-plan0" class="radio">
							<input type="radio" name="plan_id" value="0"{if empty($item.sponsored_plan_id)} checked{/if}
								id="input-plan0" data-fields="">
							<strong>{lang key='_not_assigned_'}</strong>
							{if isset($item.sponsored_plan_id) && $item.sponsored_plan_id > 0}
							<span class="label label-info">{lang key='paid_subscription_will_cancel'}</span>
							{/if}
						</label>
					</div>
					{foreach $plans as $plan}
						<div class="plan well-item">
							<label for="input-plan{$plan.id}" class="radio">
								<input type="radio" name="plan_id" value="{$plan.id}"{if isset($item.sponsored_plan_id) && $plan.id == $item.sponsored_plan_id} checked{/if}
									id="input-plan{$plan.id}"
									data-fields="{if isset($plan.fields)}{$plan.fields|escape:'html'}{/if}">
								<strong>{lang key="plan_title_{$plan.id}"} - {$plan.cost} {$config.currency}</strong>
							</label>
							<div class="description">{lang key="plan_description_{$plan.id}"}</div>
						</div>
					{/foreach}
				</div>
			</div>
		</div>
	</div>

	{ia_add_js}
$(function()
{
	var $container = $('#js-plans-list');
	$('input[type="radio"]', $container).on('click', function()
	{
		var $this = $(this);
		var plan = $this.val();
		var fields = $this.data('fields').split(',');
		
		$('.for_plan').hide().addClass('hide');

		$.each(fields, function(index, item)
		{
			$('#'+item+'_fieldzone').show().removeClass('hide');
		});

		$('.fieldset-wrapper').each(function()
		{
			if ($('.fieldzone', this).length > 0 && $('.fieldzone:not(.for_plan),.fieldzone:not(.hide)', this).length == 0) {
				$(this).parent('fieldset').hide();
			} else {
				$(this).parent('fieldset').show();
			}
		});
	});

	$(':checked', $container).length
		? $(':checked', $container).click()
		: $('input[type="radio"]:first', $container).click();
});
	{/ia_add_js}
{/if}