intelli.visual = {form: null, panel: null};

Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid({
            columns: [
                'selection',
                {name: 'user', title: _t('username'), width: 1},
                {
                    name: 'operation', title: _t('plan'), width: 1, renderer: function (value, metadata, record) {
                        return (value && record.get('plan_id') > 0)
                            ? '<b><a href="' + intelli.config.admin_url + '/plans/edit/' + record.get('plan_id') + '/">' + value + '</a></b>'
                            : '<b>' + value + '</b>';
                    }
                },
                {name: 'item', title: _t('item'), width: 100},
                {name: 'item_id', title: _t('item_id'), width: 60},
                {name: 'reference_id', title: _t('reference_id'), editor: 'text', width: 1},
                {name: 'amount', title: _t('total'), width: 100},
                {name: 'gateway', title: _t('gateway'), width: 70},
                'status',
                {name: 'date_created', title: _t('date_created'), width: 170},
                'delete'
            ],
            fields: ['plan_id'],
            sorters: [{property: 'date_created', direction: 'DESC'}],
            statuses: ['pending', 'passed', 'failed', 'refunded'],
            texts: {
                delete_single: _t('are_you_sure_to_delete_this_transaction'),
                delete_multiple: _t('are_you_sure_to_delete_transactions')
            }
        }, false);

        grid.toolbar = Ext.create('Ext.Toolbar', {
            items: [{
                xtype: 'textfield',
                name: 'username',
                emptyText: _t('username'),
                listeners: intelli.gridHelper.listener.specialKey,
                width: 120
            }, {
                xtype: 'textfield',
                name: 'reference_id',
                emptyText: _t('reference_id'),
                listeners: intelli.gridHelper.listener.specialKey,
                width: 100
            }, {
                emptyText: _t('item'),
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: intelli.gridHelper.store.ajax(window.location.href + 'items.json'),
                displayField: 'title',
                name: 'item',
                valueField: 'value',
                width: 90
            }, {
                emptyText: _t('gateway'),
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: intelli.gridHelper.store.ajax(window.location.href + 'gateways.json'),
                displayField: 'title',
                name: 'gateway',
                valueField: 'value',
                width: 95
            }, {
                emptyText: _t('status'),
                name: 'status',
                id: 'fltStatus',
                xtype: 'combo',
                typeAhead: true,
                editable: false,
                store: grid.stores.statuses,
                displayField: 'title',
                valueField: 'value',
                width: 80
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
            }, {
                handler: function () {
                    intelli.gridHelper.search(grid, false, true);
                    intelli.gridHelper.search(grid, true);
                },
                text: '<i class="i-box-remove"></i> ' + _t('export_to_excel')
            }]
        });

        if (searchParam) {
            Ext.getCmp('fltStatus').setValue(searchParam);
        }

        grid.init();

        var searchParam = intelli.urlVal('status');
        if (searchParam) {
            Ext.getCmp('fltStatus').setValue(searchParam);
            intelli.gridHelper.search(grid)
        }
    }

    $('#js-add-transaction-cmd').on('click', function (e) {
        e.preventDefault();

        if (!intelli.visual.panel) {
            var items = new Ext.form.ComboBox({
                fieldLabel: _t('item'),
                typeAhead: true,
                allowBlank: false,
                anchor: '100%',
                labelWidth: 140,
                editable: false,
                id: 'item_name',
                lazyRender: true,
                store: intelli.gridHelper.store.ajax(window.location.href + 'items.json'),
                displayField: 'title',
                listeners: {
                    select: function (combo) {
                        var itemName = combo.getValue(),
                            itemPlanCtl = Ext.getCmp('item_plan');

                        ('members' == itemName) ? Ext.getCmp('itemid').hide() : Ext.getCmp('itemid').show();

                        itemPlanCtl.clearValue();

                        itemPlanCtl.getStore().proxy.extraParams = {itemname: itemName};
                        itemPlanCtl.getStore().load();
                    }
                },
                valueField: 'value'
            });

            var plans = new Ext.form.ComboBox({
                fieldLabel: _t('plan'),
                typeAhead: true,
                allowBlank: false,
                id: 'item_plan',
                editable: false,
                name: 'plan',
                lazyRender: true,
                store: intelli.gridHelper.store.ajax(window.location.href + 'plans.json'),
                displayField: 'title',
                anchor: '100%',
                labelWidth: 140,
                valueField: 'value'
            });

            var gateways = new Ext.form.ComboBox({
                fieldLabel: _t('payment_gateway'),
                typeAhead: true,
                allowBlank: true,
                name: 'gateway',
                lazyRender: true,
                store: intelli.gridHelper.store.ajax(window.location.href + 'gateways.json'),
                displayField: 'title',
                anchor: '100%',
                labelWidth: 140,
                valueField: 'value'
            });

            var members = new Ext.form.ComboBox({
                fieldLabel: _t('member'),
                typeAhead: true,
                allowBlank: false,
                editable: true,
                name: 'username',
                lazyRender: true,
                store: intelli.gridHelper.store.ajax(window.location.href + 'members.json'),
                displayField: 'title',
                anchor: '100%',
                labelWidth: 140
            });

            var dateFields = [
                new Ext.form.DateField({
                    name: 'date',
                    editable: true,
                    format: 'Y-m-d',
                    value: new Date(),
                    flex: 1
                }), {
                    xtype: 'splitter'
                }, new Ext.form.TimeField({
                    name: 'time',
                    editable: true,
                    format: 'H:i:s',
                    increment: 30,
                    value: new Date(),
                    flex: 1
                })
            ];

            intelli.visual.form = new Ext.FormPanel({
                width: 400,
                autoHeight: true,
                bodyStyle: 'padding: 15px 15px 10px;',
                renderTo: Ext.getCmp('visual_panel'),
                items: [items, plans, gateways, {
                    fieldLabel: _t('reference_id'),
                    name: 'reference_id',
                    xtype: 'textfield',
                    anchor: '100%',
                    labelWidth: 140
                }, members, {
                    anchor: '100%',
                    fieldLabel: _t('email'),
                    labelWidth: 140,
                    name: 'email',
                    vtype: 'email',
                    xtype: 'textfield'
                }, {
                    fieldLabel: _t('total'),
                    name: 'amount',
                    allowBlank: false,
                    xtype: 'textfield',
                    anchor: '100%',
                    labelWidth: 140
                }, {
                    fieldLabel: _t('id'),
                    name: 'itemid',
                    allowBlank: true,
                    hidden: true,
                    id: 'itemid',
                    xtype: 'numberfield',
                    labelWidth: 140
                }, {
                    fieldLabel: _t('date'),
                    xtype: 'fieldcontainer',
                    layout: 'hbox',
                    labelWidth: 140,
                    width: '100%',
                    items: dateFields
                }, {
                    xtype: 'hidden',
                    name: intelli.securityTokenKey,
                    value: intelli.securityToken,
                }]
            });

            intelli.visual.panel = new Ext.Window({
                title: _t('add_transaction'),
                id: 'visual_panel',
                items: intelli.visual.form,
                closeAction: 'hide',
                buttons: [{
                    text: _t('save'),
                    autoWidth: true,
                    handler: function () {
                        var f = intelli.visual.form.getForm();

                        if (f.isValid()) {
                            f.submit({
                                url: window.location.href + 'add.json',
                                success: function (form, data) {
                                    Ext.Msg.show({
                                        title: _t('confirm'),
                                        msg: _t('add_new_transaction'),
                                        buttons: Ext.Msg.YESNO,
                                        icon: Ext.Msg.QUESTION,
                                        fn: function (btn) {
                                            'yes' == btn || intelli.visual.panel.hide();
                                            f.reset();
                                        }
                                    });

                                    grid.store.reload();
                                },
                                failure: function (form, data) {
                                    intelli.notifBox({
                                        msg: data.result.message,
                                        type: 'error',
                                        autohide: true
                                    });
                                }
                            });
                        }
                    }
                }, {
                    text: _t('cancel'),
                    autoWidth: true,
                    handler: function () {
                        intelli.visual.panel.hide();
                        intelli.visual.form.getForm().reset();
                    }
                }]
            });
        }

        intelli.visual.panel.show();
    });
});