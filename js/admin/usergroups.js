Ext.onReady(function () {
    intelli.usergroups = new IntelliGrid(
        {
            columns: [
                'numberer',
                {name: 'id', title: _t('id'), width: 40, hidden: true},
                {name: 'name', title: _t('name'), width: 150, hidden: true},
                {
                    name: 'title',
                    title: _t('title'),
                    width: 150,
                    sortable: false,
                    renderer: function (value, metadata, record) {
                        if (1 == record.get('admin')) {
                            value = '<b style="color:green;">' + value + '</b>';
                        }
                        return value;
                    }
                }, {
                    name: 'members', title: _t('all_members'), width: 2, renderer: function (value, metadata, record) {
                        return value
                        ? value.replace(/, $/, '')
                        : '<span style="color:red;font-style:italic;">' + _t('no_members') + '</span>';
                    }
                },
                {name: 'count', title: _t('members'), width: 68, align: 'right'},
                {
                    name: 'assignable',
                    title: _t('assignable'),
                    width: 76,
                    align: intelli.gridHelper.constants.ALIGN_CENTER,
                    renderer: intelli.gridHelper.renderer.check,
                    editor: Ext.create('Ext.form.ComboBox', {
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
                }, {
                    name: 'visible',
                    title: _t('visible'),
                    width: 76,
                    align: intelli.gridHelper.constants.ALIGN_CENTER,
                    renderer: intelli.gridHelper.renderer.check,
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
                {name: 'order', title: _t('order'), width: 80, editor: 'number'},
                {
                    name: 'admin', title: _t('admin_panel'), width: 110, renderer: function (value, metadata, record) {
                        return (1 == record.get('admin'))
                            ? '<span style="color:green;">' + _t('allowed') + '</span>'
                            : '<span style="color:red;">' + _t('not_allowed') + '</span>';
                    }
                }, {
                    name: 'permissions',
                    title: _t('permissions'),
                    href: intelli.config.admin_url + '/permissions/?group={id}',
                    icon: 'lock'
                }, {
                    name: 'config',
                    title: _t('go_to_config'),
                    href: intelli.config.admin_url + '/configuration/?group={id}',
                    icon: 'cogs'
                },
                'delete'
            ],
            texts: {delete_single: _t('are_you_sure_to_delete_this_usergroup')}
        });
});