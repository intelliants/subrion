Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid({
            columns: [
                'selection',
                'expander',
                {name: 'id', title: _t('id'),sortable: false, width: 50},
                {name: 'member_name', title: _t('member_name'), width: 1},
                {name: 'ip_address', title: _t('ip_address'), width: 1},
                {name: 'entry_date', title: _t('entry_date'), width: 200},
                'delete'
            ],
            expanderTemplate: '{user_agent}',
            fields: ['user_agent'],
            texts: {delete_single: _t('are_you_sure_to_delete_this_plan')}
        }, false);

        grid.toolbar = new Ext.Toolbar({
            items: [
                {
                    emptyText: _t('member_name'),
                    xtype: 'textfield',
                    name: 'member_name',
                    listeners: intelli.gridHelper.listener.specialKey
                }, {
                    emptyText: _t('ip_address'),
                    xtype: 'textfield',
                    name: 'ip_address',
                    listeners: intelli.gridHelper.listener.specialKey
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid)
                    },
                    id: 'fltBtn',
                    text: '<i class="i-search"></i> ' + _t('search')
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid, true)
                    },
                    text: '<i class="i-close"></i> ' + _t('reset')
                }]
        });

        grid.init();

        grid.grid.getView().getRowClass = function (record, rowIndex, rowParams, store) {
            if (1 == record.get('default')) {
                return 'grid-row-customly-highlighted';
            }

            return '';
        }
    }
});