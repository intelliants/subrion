Ext.onReady(function () {
    if ($('#js-grid-placeholder').length) {
        intelli.fieldgroups = new IntelliGrid(
            {
                columns: [
                    'selection',
                    {name: 'id', title: _t('id'), width: 40},
                    {name: 'name', title: _t('name'), width: 150},
                    {name: 'title', title: _t('title'), width: 1, editor: 'text'},
                    {name: 'item', title: _t('item'), width: 130},
                    {name: 'module', title: _t('module'), width: 110},
                    {
                        name: 'tabview',
                        title: _t('view_as_tab'),
                        width: 100,
                        align: intelli.gridHelper.constants.ALIGN_CENTER,
                        renderer: intelli.gridHelper.renderer.check
                    },
                    {
                        name: 'collapsible',
                        title: _t('collapsible'),
                        width: 80,
                        align: intelli.gridHelper.constants.ALIGN_CENTER,
                        renderer: intelli.gridHelper.renderer.check
                    },
                    {name: 'order', title: _t('order'), width: 70, editor: 'number'},
                    'update',
                    'delete'
                ],
                texts: {delete_single: _t('are_you_sure_to_delete_fieldgroup')}
            }, false);

        intelli.fieldgroups.toolbar = new Ext.Toolbar({
            items: [
                {
                    allowDecimals: false,
                    allowNegative: false,
                    emptyText: _t('id'),
                    name: 'id',
                    listeners: intelli.gridHelper.listener.specialKey,
                    width: 90,
                    xtype: 'numberfield'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('fields_item_filter'),
                    name: 'item',
                    store: new Ext.data.SimpleStore({fields: ['value', 'title'], data: intelli.config.items}),
                    typeAhead: true,
                    valueField: 'value',
                    xtype: 'combo'
                }, {
                    handler: function () {
                        intelli.gridHelper.search(intelli.fieldgroups)
                    },
                    id: 'fltBtn',
                    text: '<i class="i-search"></i> ' + _t('search')
                }, {
                    handler: function () {
                        intelli.gridHelper.search(intelli.fieldgroups, true)
                    },
                    text: '<i class="i-close"></i> ' + _t('reset')
                }]
        });

        intelli.fieldgroups.init();
    }
});

$(function () {
    $('#tabview, #input-item').on('change', function () {
        var $fieldGroups = $('#js-fieldgroup-selectbox');
        var $tabContainer = $('#js-tab-container');
        var $collapsible = $('#js-collapsible');

        if (0 == $('#tabview').val()) {
            var item = $('#input-item').val();
            var name = $('#input-name').val();

            $fieldGroups.prop('disabled', true);
            if (item) {
                $.get(intelli.config.admin_url + '/fieldgroups/tabs.json', {
                    item: item,
                    name: name
                }, function (response) {
                    $fieldGroups.children('option:not(:first)').remove();

                    if (response.length > 0) {
                        var selected = $('#tabcontainer').val();
                        $.each(response, function (i, name) {
                            $fieldGroups.append($('<option>').val(name).text(_t('fieldgroup_' + item + '_' + name)));
                        });
                        $fieldGroups.val(selected);

                        $fieldGroups.prop('disabled', false);
                        $tabContainer.show();
                    }
                });

                $collapsible.show();
            }
        }
        else {
            $collapsible.hide();
            $tabContainer.hide();
        }
    }).change();

    $('#collapsible').on('change', function () {
        (0 == $(this).val()) ? $('#js-collapsed').hide() : $('#js-collapsed').show();
    }).change();
});