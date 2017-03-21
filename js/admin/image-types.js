Ext.onReady(function () {
    var grid = new IntelliGrid(
        {
            columns: [
                'selection',
                {name: 'id', title: _t('id'), width: 50},
                {name: 'name', title: _t('name'), width: 1},
                {name: 'width', title: _t('image_width'), width: 100, editor: 'text'},
                {name: 'height', title: _t('image_height'), width: 100, editor: 'text'},
                {name: 'resize_mode', title: _t('resize_mode'), width: 100},
                'update',
                'delete'
            ],
            sorters: [{property: 'id', direction: 'ASC'}]
        }, false);

    grid.toolbar = new Ext.Toolbar({
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
                emptyText: 'Name',
                id: 'fltName',
                name: 'name',
                listeners: intelli.gridHelper.listener.specialKey,
                width: 220,
                xtype: 'textfield'
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
});