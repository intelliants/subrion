Ext.onReady(function () {
    var grid = new IntelliGrid(
        {
            columns: [
                'selection',
                {name: 'id', title: _t('id'), width: 80},
                {name: 'username', title: _t('username'), width: 150, editor: 'text'},
                {name: 'fullname', title: _t('fullname'), width: 1, editor: 'text'},
                {
                    name: 'usergroup',
                    title: _t('usergroup'),
                    width: 150,
                    renderer: function (value, metadata, record) {
                        switch (record.get('usergroup_id')) {
                            case '4':
                            case '8':
                                return '<span style="color: grey;">' + value + '</span>';
                            case '1':
                            case '2':
                                return '<span style="color: green;">' + value + '</span>';
                            default:
                                return value;
                        }
                    }
                },
                {name: 'email', title: _t('email'), width: 180, editor: 'text'},
                'status',
                {name: 'date_reg', title: _t('date'), width: 120},
                {name: 'date_logged', title: _t('last_login_date'), hidden: true, width: 100},
                {
                    name: 'login',
                    title: _t('login'),
                    href: intelli.config.admin_url + '/members/login/{id}',
                    icon: 'key'
                },
                {
                    name: 'permissions',
                    title: _t('permissions'),
                    href: intelli.config.admin_url + '/permissions/?user={id}',
                    icon: 'lock'
                },
                {
                    name: 'config',
                    title: _t('go_to_config'),
                    href: intelli.config.admin_url + '/configuration/?user={id}',
                    icon: 'cogs'
                },
                'update',
                'delete'
            ],
            fields: ['usergroup_id'],
            statuses: ['active', 'approval', 'suspended', 'unconfirmed'],
            sorters: [{property: 'date_reg', direction: 'DESC'}],
            texts: {
                delete_single: _t('are_you_sure_to_delete_this_member'),
                delete_multiple: _t('are_you_sure_to_delete_selected_members')
            }
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
                emptyText: 'Username, Fullname, or Email',
                id: 'fltName',
                name: 'name',
                listeners: intelli.gridHelper.listener.specialKey,
                width: 220,
                xtype: 'textfield'
            }, {
                name: 'status',
                emptyText: _t('status'),
                id: 'fltStatus',
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: grid.stores.statuses,
                width: 140,
                displayField: 'title',
                valueField: 'value'
            }, {
                displayField: 'title',
                editable: false,
                emptyText: _t('usergroup'),
                name: 'usergroup_id',
                store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/usergroups/store.json'),
                typeAhead: true,
                valueField: 'value',
                width: 150,
                xtype: 'combo'
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

    var searchStatus = intelli.urlVal('status');

    if (searchStatus) {
        Ext.getCmp('fltStatus').setValue(searchStatus);
        intelli.gridHelper.search(grid);
    }
});