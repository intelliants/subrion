Ext.onReady(function () {
    var grid = new IntelliGrid(
        {
            columns: [
                'numberer',
                {name: 'name', title: _t('name'), width: 1, editor: 'text'},
                {name: 'module', title: _t('module'), width: 130, editor: 'text'},
                {
                    name: 'type', title: _t('type'), width: 100, editor: Ext.create('Ext.form.ComboBox',
                    {
                        typeAhead: true,
                        editable: false,
                        lazyRender: true,
                        store: Ext.create('Ext.data.SimpleStore', {
                            fields: ['value', 'title'],
                            data: [['php', 'PHP'], ['smarty', 'Smarty'], ['html', 'HTML'], ['plain', 'Plain Text']]
                        }),
                        displayField: 'title',
                        valueField: 'value'
                    })
                },
                {name: 'order', title: _t('order'), width: 50, editor: 'text'},
                'status',
                {name: 'filename', title: _t('filename'), width: 120, editor: 'text'},
                {
                    name: 'open', title: _t('edit'), icon: 'pencil', click: function (record, field) {
                    $.ajax(
                        {
                            url: window.location.href + 'get.json',
                            data: {id: record.get('id')},
                            type: 'get',
                            success: function (response) {
                                $('.wrap-list').show();
                                editAreaLoader.openFile('codeContainer', {
                                    id: record.get('id'),
                                    text: response.code,
                                    syntax: record.get('type'),
                                    title: record.get('name') + ' | ' + record.get('module')
                                });
                            }
                        });
                }
                },
                'delete'
            ]
        }, false);

    grid.toolbar = Ext.create('Ext.Toolbar', {
        items: [
            {
                emptyText: _t('name'),
                xtype: 'textfield',
                name: 'name',
                listeners: intelli.gridHelper.listener.specialKey
            }, {
                emptyText: _t('module'),
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: intelli.config.extras}),
                displayField: 'title',
                name: 'item',
                valueField: 'value'
            }, {
                emptyText: _t('type'),
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: Ext.create('Ext.data.SimpleStore', {
                    fields: ['value', 'title'],
                    data: [['php', 'PHP'], ['smarty', 'Smarty'], ['html', 'HTML'], ['plain', 'Plain Text']]
                }),
                displayField: 'title',
                name: 'type',
                valueField: 'value'
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

intelli.handlerSaveHook = function () {
    var params = {
        id: editAreaLoader.getCurrentFile('codeContainer').id,
        code: editAreaLoader.getValue('codeContainer')
    };

    intelli.post(window.location.href + 'set.json', params, function (response) {
        intelli.notifFloatBox({msg: response.message, type: response.result ? 'success' : 'error', autohide: true});
    });
};

$(function () {
    editAreaLoader.init(
        {
            id: 'codeContainer',
            syntax: 'php',
            start_highlight: true,
            allow_resize: 'yes',
            min_height: 300,
            toolbar: 'save, search, go_to_line, |, undo, redo',
            save_callback: 'intelli.handlerSaveHook',
            allow_toggle: false
        });

    $('#js-save-cmd').on('click', intelli.handlerSaveHook);
    $('#js-close-cmd').on('click', function () {
        var hooks = editAreaLoader.getAllFiles('codeContainer');
        if (hooks) {
            for (var hook in hooks) {
                editAreaLoader.closeFile('codeContainer', hook);
            }
        }
    });
});