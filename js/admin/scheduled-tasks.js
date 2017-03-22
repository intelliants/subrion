Ext.onReady(function () {
    var grid = new IntelliGrid(
        {
            columns: [
                'selection',
                'expander',
                {name: 'name', title: _t('task'), width: 1},
                {name: 'date_prev_launch', title: _t('previous_launch'), width: 190},
                {name: 'date_next_launch', title: _t('next_launch'), width: 190},
                {
                    name: 'active',
                    title: _t('active'),
                    renderer: intelli.gridHelper.renderer.check,
                    align: intelli.gridHelper.constants.ALIGN_CENTER,
                    width: 50,
                    editor: Ext.create('Ext.form.ComboBox',
                        {
                            typeAhead: false,
                            editable: false,
                            lazyRender: true,
                            store: Ext.create('Ext.data.SimpleStore', {
                                fields: ['value', 'title'],
                                data: [[0, _t('no')], [1, _t('yes')]]
                            }),
                            displayField: 'title',
                            valueField: 'value'
                        })
                },
                //{name: 'interval', title: _t('interval'), width: 100},
                {name: 'module', title: _t('module'), width: 105},
                {
                    name: 'run', title: _t('launch_manually'), icon: 'lightning', click: function (record, field) {
                    window.location = record.get(field);
                }
                }
            ],
            expanderTemplate: '{description}',
            fields: ['description']
        }, false);

    grid.init();
});