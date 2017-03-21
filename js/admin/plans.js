Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        intelli.plans = new IntelliGrid(
            {
                columns: [
                    'selection',
                    'expander',
                    {name: 'title', title: _t('title'), sortable: false, width: 1},
                    {name: 'item', title: _t('item'), width: 100},
                    {name: 'cost', title: _t('cost'), width: 100, editor: 'decimal'},
                    {name: 'duration', title: _t('duration'), width: 150},
                    {name: 'recurring', title: _t('recurring'), width: 70, renderer: intelli.gridHelper.renderer.check},
                    {name: 'order', title: _t('order'), editor: 'number', width: 70},
                    'status',
                    'update',
                    'delete'
                ],
                expanderTemplate: '{description}',
                fields: ['description'],
                texts: {delete_single: _t('are_you_sure_to_delete_this_plan')}
            });
    }
});

$(function () {
    'use_strict';

    var checkAll = true;
    $('input[name="fields[]"]').each(function () {
        if (!$(this).prop('checked')) checkAll = false;
    });

    $('#check_all_fields')
        .prop('checked', checkAll)
        .on('click', function () {
            var checked = $(this).prop('checked');
            $('input[name="fields[]"]').each(function () {
                $(this).prop('checked', checked);
            });
        });

    $('select[name="item"]').on('change', function () {
        $('.js-fields-list, .js-items-list').hide();
        $('#js-fields-empty').hide();

        var value = $(this).val();

        var $options = $('#js-plan-options');
        $('.row', $options).hide();
        $('.row[data-item="' + value + '"]', $options).show();
        '' == value ? $('.help-block', $options).show() : $('.help-block', $options).hide();

        if ('' == value) value = 'empty';
        $('#js-fields-' + value).show();
        $('#js-item-' + value).show();
    }).change().on('change', function () {
        var $statusesSelect = $('select[name="expiration_status"]');
        $('option[value!=""]', $statusesSelect).remove();
        var statuses = $statusesSelect.data($(this).val());
        if (typeof statuses != 'undefined') {
            statuses = statuses.split(',');
            for (var i in statuses) {
                $statusesSelect.append('<option value="' + statuses[i] + '">' + _t(statuses[i]) + '</option>');
            }
        }
    });

    $('#recurring').on('change', function () {
        if (1 == $(this).val()) {
            var $tip = $('#js-cycles-tip');
            $tip.length ? $tip.show() : $('#js-cycles-option').show();
        }
        else {
            $('#js-cycles-tip, #js-cycles-option').hide();
        }
    });

    $('a', '#js-cycles-tip').on('click', function (e) {
        e.preventDefault();
        $('#js-cycles-tip').remove();
        $('#js-cycles-option').show();
    });

    $('.js-input-switch input[type="checkbox"]', '#js-plan-options').on('change', function () {
        var $priceInput = $(this).closest('.js-input-switch').next();
        1 == this.value ? $priceInput.show() : $priceInput.hide();
    });
});