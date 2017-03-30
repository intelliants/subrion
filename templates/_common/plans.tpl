{if !empty($plans)}
    <div class="fieldset">
        <div class="fieldset__header">{lang key='plans'}</div>

        <div class="fieldset__content">
            <div id="js-plans-list" class="plans">
                <div class="plans__item">
                    <label for="input-plan0" class="plans__item__header radio">
                        <input type="radio" name="plan_id" value="0"{if empty($item.sponsored_plan_id)} checked{/if} id="input-plan0" data-fields="">
                        <strong>{lang key='_not_assigned_'}</strong>
                        {if isset($item.sponsored_plan_id) && $item.sponsored_plan_id > 0}
                            <span class="label label-info">{lang key='paid_subscription_will_cancel'}</span>
                        {/if}
                    </label>
                </div>
                {foreach $plans as $plan}
                    <div class="plans__item">
                        <label for="input-plan{$plan.id}" class="plans__item__header radio">
                            <input type="radio" name="plan_id" value="{$plan.id}"{if isset($item.sponsored_plan_id) && $plan.id == $item.sponsored_plan_id} checked{/if} id="input-plan{$plan.id}" data-fields="{if isset($plan.fields)}{$plan.fields|escape}{/if}">
                            <strong>{lang key="plan_title_{$plan.id}"} &mdash; {$plan.cost} {$core.config.currency}</strong>
                        </label>
                        <div class="plans__item__body">{lang key="plan_description_{$plan.id}"}</div>
                        {if $plan.options}
                        <div class="well">
                            <ul>
                                {foreach $plan.options as $option}
                                    <li>{lang key="plan_option_{$option.item}_{$option.name}"} —
                                    {if 'bool' == $option.type}
                                        {if $option.value}{lang key='yes'}{else}{lang key='no'}{/if}
                                    {else}
                                        {$option.value|escape}
                                    {/if}
                                    </li>
                                {/foreach}
                            </ul>
                        </div>
                        {/if}
                    </div>
                {/foreach}
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

        $this.closest('.plans__item').addClass('active')
            .siblings().removeClass('active');

        $('.form-group--plan').hide().addClass('hide');

        $.each(fields, function(index, item)
        {
            $('#'+item+'_fieldzone').show().removeClass('hide');
        });

        $('.fieldset__content').each(function()
        {
            if ($('.fieldzone', this).length > 0 && $('.fieldzone:not(.form-group--plan),.fieldzone:not(.hide)', this).length == 0) {
                $(this).parent('.fieldset').hide();
            } else {
                $(this).parent('.fieldset').show();
            }
        });
    });

    $(':checked', $container).length
        ? $(':checked', $container).click()
        : $('input[type="radio"]:first', $container).click();
});
    {/ia_add_js}
{/if}