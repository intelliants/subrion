{if !isset($noSystemFields)}
<div class="wrap-group">
    <div class="wrap-group-heading">{lang key='system_fields'}</div>

    {if isset($fieldset_before.systems)}{$fieldset_before.systems}{/if}

    {if isset($item.owner)}
        <div class="row">
            <label class="col col-lg-2 control-label">{lang key='owner'}</label>

            <div class="col col-lg-4">
                <input type="text" autocomplete="off" id="js-owner-autocomplete" name="owner" value="{$item.owner|escape}" maxlength="255">
                <input type="hidden" name="member_id" id="member-id"{if !empty($item.member_id)} value="{$item.member_id}"{/if}>
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
            <label class="col col-lg-2 control-label" for="input-plan">{lang key='plan'}</label>

            <div class="col col-lg-4">
                {if !empty($plans)}
                    <select name="plan_id" id="input-plan">
                        {foreach $plans as $plan}
                            <option value="{$plan.id}"{if isset($item.sponsored_plan_id) && $plan.id == $item.sponsored_plan_id} selected{/if} data-date="{$plan.defaultEndDate}">{lang key="plan_title_{$plan.id}"} - {$core.config.currency} {$plan.cost}</option>
                        {/foreach}
                    </select>
                {else}
                    <span class="label label-info">{lang key='no_plans'}</span>
                {/if}
            </div>
        </div>

        {if !empty($plans) && !isset($noSponsoredEnd)}
            <div class="row" id="js-row-sponsored-end"{if $item.sponsored != 1} style="display:none"{/if}>
                <label class="col col-lg-2 control-label">{lang key='sponsored_end'}</label>

                <div class="col col-lg-4">
                    <div class="input-group">
                        <input size="16" type="text" class="js-datepicker" value="{if isset($item.sponsored_end)}{$item.sponsored_end}{/if}" data-date-format="YYYY-MM-DD HH:mm" name="sponsored_end" readonly>
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

        <div class="row"{if !isset($noFeaturedEnd)} id="js-row-featured-end"{/if}{if !$item.featured || isset($noFeaturedEnd)} style="display:none;"{/if}>
            <label class="col col-lg-2 control-label">{lang key='featured_end'}</label>

            <div class="col col-lg-4">
                <div class="input-group">
                    <input type="text" class="js-datepicker" name="featured_end" value="{$item.featured_end}" data-date-format="YYYY-MM-DD HH:mm">
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
        {capture assign=datevalue}
            {if isset($datetime)}
                value="{if $item.date_added != '0000-00-00 00:00:00'}{$item.date_added|date_format:'%Y-%m-%d %H:%M'}{/if}" data-date-format="YYYY-MM-DD HH:mm:ss"
            {else}
                value="{if $item.date_added != '0000-00-00 00:00:00'}{$item.date_added|date_format:'%Y-%m-%d'}{/if}"
            {/if}
        {/capture}

        <div class="row">
            <label class="col col-lg-2 control-label" for="field_date_added">{lang key='date_added'}</label>

            <div class="col col-lg-4">
                <div class="input-group">
                    <input type="text" class="datepicker js-datepicker" name="date_added" id="field_date_added" {$datevalue}>
                    <span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
                </div>
            </div>
        </div>
    {/if}

    {if isset($item.status)}
        {if !isset($statuses)}
            {assign statuses [iaCore::STATUS_ACTIVE, iaCore::STATUS_INACTIVE]}
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

    {ia_add_media files='moment, datepicker'}
    {ia_add_js}
$(function(){
    var $sponsoredEnd = $('input[name="sponsored_end"]'),
        $inputPlan = $('#input-plan');

    $inputPlan.on('change', function(){
        $sponsoredEnd.data('DateTimePicker').date($('option:selected', this).data('date'));
    });

    if ('' == $sponsoredEnd.val()){
        $inputPlan.trigger('change');
    }
    // sponsored switchers
    $('input[name="sponsored"]').on('change', function(){
        (1 == this.value) ? $('#plans, #js-row-sponsored-end').show() : $('#plans, #js-row-sponsored-end').hide();
    });

    // featured switchers
    $('input[name="featured"]').on('change', function(){
        (1 == this.value) ? $('#js-row-featured-end').show() : $('#js-row-featured-end').hide();
    });

    var objects = [];
        var items = [];

    $('#js-owner-autocomplete').typeahead({
        source: function(query, process){
            $.ajax({
                url: intelli.config.url + 'actions.json',
                type: 'get',
                dataType: 'json',
                data: { q: query, action: 'assign-owner' },
                success: function(response){
                    objects = items = [];
                    $.each(response, function(i, object){
                        items[object.fullname] = object;
                        objects.push(object.fullname);
                    });

                    return process(objects);
                }
            })
        },
        updater: function(item){
            $('#member-id').val(items[item].id);
            return item;
        },
        matcher: function(){
            return true;
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
            <label class="col col-lg-2">{$entry.title}{if !$entry.system} <i class="i-tools" title=""></i>{/if}</label>

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
$(function(){
    var usergroupAccess = { };

    $('#input-ugp-default').on('click', function(){
        var setDefaults = $(this).prop('checked');
        var $section = $('#widget-permissions');
        var $togglers = $('.js-toggler-group', $section);
        var $actions = $('.widget-system-panel a', $section);

        if (setDefaults){
            $togglers.each(function(){
                var id = $(this).data('id');
                usergroupAccess[id] = $('#js-ugp-' + id).val();
            });
            setTogglerValue();
            $actions.addClass('disabled');
        } else {
            $togglers.each(function() {
                var id = $(this).data('id');
                var value = ('undefined' === typeof usergroupAccess[id])
                    ? $('#js-ugp-' + id).val()
                    : usergroupAccess[id];

                setTogglerValue(value, $(this));
            });
            $actions.removeClass('disabled');
        }
    });

    $('.widget-system-panel a', '#widget-permissions').on('click', function(e){
        e.preventDefault();
        $(this).hasClass('disabled') || setTogglerValue($(this).attr('rel'));
    });

    function setTogglerValue(value, $toggler){
        $toggler = $toggler || $('#widget-permissions').find('.js-toggler-group');

        if ('undefined' !== typeof value){
            $toggler.each(function(){
                $('#js-ugp-' + $(this).data('id')).val(value);
                var status = (1 == value);

                $(this).find('span:eq(0)')
                    .removeClass((status ? 'label-default' : 'label-success') + ' disabled')
                    .addClass(status ? 'label-success' : 'label-default');
                $(this).find('span:eq(1)')
                    .removeClass((status ? 'label-danger' : 'label-default') + ' disabled')
                    .addClass(status ? 'label-default' : 'label-danger');
            });
        } else {
            $toggler.find('span')
                .removeClass('label-success label-danger')
                .addClass('label-default disabled');
        }
    }

    $('.label', '#widget-permissions').on('click', function(){
        $(this).hasClass('disabled') || setTogglerValue($(this).data('access'), $(this).parent());
    });
});
{/ia_add_js}
{/if}

{if !isset($noControls)}
    <div class="form-actions inline">
        <input type="hidden" name="save" value="1">
        <button type="submit" class="btn btn-primary form-actions__btn-submit js-btn-submit">
            {if iaCore::ACTION_ADD == $pageAction}{lang key='add'}{else}{lang key='save'}{/if}
        </button>
        {include 'goto.tpl'}
        {ia_hooker name='adminSubmitOptions'}
    </div>
{/if}