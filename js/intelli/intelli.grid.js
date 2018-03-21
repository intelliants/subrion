intelli.gridHelper = {
    constants: {
        ALIGN_CENTER: 'center',
        ALIGN_LEFT: 'left',

        WIDTH_ICON: 30
    },

    httpRequest: function (caller, data, action) {
        $.ajax({
            data: intelli.includeSecurityToken(data),
            dataType: 'json',
            failure: function () {
                Ext.MessageBox.alert(_t('error_saving_changes'));
            },
            type: 'POST',
            url: caller.url + (action ? action : 'edit') + '.json',
            success: function (response) {
                var result = false;
                switch (true) {
                    case ('boolean' === typeof response.result):
                        result = response.result;
                        break;
                    case ('boolean' === typeof response.error):
                        result = !response.error;
                }

                result ? caller.store.reload() : caller.store.rejectChanges();

                if ('undefined' !== typeof response.message) {
                    intelli.notifFloatBox({
                        msg: response.message,
                        type: result ? 'success' : 'error',
                        autohide: true
                    });
                }
            }
        });
    },

    renderer: {
        _pattern: '<i class=":class" title=":title"></i>',

        _iconRenderer: function (title, icon, value) {
            return intelli.gridHelper.renderer._pattern
                .replace(':title', title)
                .replace(':class', icon + ' ' + ('0' != value && '' != value ? 'grid-icon' : 'grid-icon-disabled'));
        },

        delete: function (value, metadata, record, rowIndex) {
            return intelli.gridHelper.renderer._iconRenderer(_t('remove'), 'i-remove', value);
        },

        update: function (value, metadata, record, rowIndex) {
            return intelli.gridHelper.renderer._iconRenderer(_t('edit'), 'i-pencil', value);
        },

        check: function (value, metadata, record, rowIndex) {
            if (0 != value) {
                return intelli.gridHelper.renderer._pattern
                    .replace(':title', '')
                    .replace(':class', 'i-checkmark-2');
            }

            return '';
        }
    },

    search: function (caller, isReset, isExcelExport, page) {
        page = page || 1;

        var data = {}, tb = {};

        for (var i in caller.toolbar.items.items) {
            var control = caller.toolbar.items.items[i];
            if (control.initialConfig.name) {
                if (isReset) {
                    ('function' !== typeof control.reset) || control.reset();
                } else {
                    var value = control.getValue();
                    if (value) {
                        data[control.initialConfig.name] = value;
                        tb[control.getId()] = value;
                    }
                }
            }
        }

        $.extend(data, (undefined === caller.params.storeParams) ? {} : caller.params.storeParams);
        if (isExcelExport) {
            data['export_excel'] = 1;
        }
        caller.store.getProxy().extraParams = data;
        caller.store.loadPage(page);

        caller.storage('t', JSON.stringify(tb));
    },

    listener: {
        specialKey: {
            specialkey: function (field, e) {
                if (e.ENTER === e.getKey()) Ext.getCmp('fltBtn').handler();
            }
        }
    },

    store: {
        ajax: function (url) {
            return Ext.create('Ext.data.JsonStore', {
                fields: ['value', 'title'],
                proxy: {
                    limitParam: null,
                    pageParam: null,
                    reader: {root: 'data', type: 'json'},
                    startParam: null,
                    type: 'ajax',
                    url: url
                }
            });
        }
    }
};

function IntelliGrid(params, autoInit) {
    this.columns = [];
    this.config = {
        height: params.height || 525,
        minHeight: params.minHeight || 250,
        pageSize: params.pageSize || 15,
        resizer: ('boolean' === typeof params.resizer) ? params.resizer : true,
        rowselect: ('function' === typeof params.rowselect) ? params.rowselect : null,
        rowdeselect: ('function' === typeof params.rowdeselect) ? params.rowdeselect : null,
        selectionType: params.selectionType || 'rowmodel',
        target: params.target || 'js-grid-placeholder',
        title: params.title || null,
        xtype: params.xtype || null
    };
    this.descriptor = Math.floor(Math.random() * Math.ceil((25 / Math.random() + 1)));
    this.fields = ['id'];
    this.grid = null;
    this.params = params;
    this.plugins = [Ext.create('Ext.grid.plugin.CellEditing', {clicksToEdit: 2})];
    this.stores = {
        paging: Ext.create('Ext.data.SimpleStore', {
            fields: ['value', 'title'],
            data: [[10, '10'], [15, '15'], [20, '20'], [25, '25'], [30, '30'], [35, '35'], [40, '40'], [45, '45'], [50, '50']]
        }),
        statuses: null
    };
    this.store = null;
    this.texts = {
        delete_single: _t('are_you_sure_to_delete_this_item'),
        delete_multiple: _t('are_you_sure_to_delete_selected_items')
    };
    this.toolbar = null;
    this.url = params.url || window.location.pathname;

    var stateId = window.location.href,
        bases = document.getElementsByTagName('base'),
        urlBase = bases.length > 0 ? bases[0].href : intelli.config.clear_url;
    stateId = stateId.replace(urlBase, '');
    stateId = stateId.replace(/\//g, '');

    if (params.texts) {
        for (i in params.texts) {
            if (this.texts[i] !== undefined) {
                this.texts[i] = params.texts[i];
            }
        }
    }

    var statuses = [['active', _t('active')], ['inactive', _t('inactive')]]; // default statuses
    if ('object' === typeof this.params.statuses) {
        statuses = [];
        for (var i in this.params.statuses) {
            var status = this.params.statuses[i];
            statuses.push([status, _t(status)]);
        }
    }

    this.stores.statuses = Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: statuses});

    var self = this;

    this.init = function (autoLoad) {
        _setupColumns();
        _setupStore('boolean' !== typeof autoLoad ? true : autoLoad);
        _setupToolbar();
        _setupGrid();
    };

    this.storage = function (key, value) {
        if (localStorage) {
            var k = stateId + key;

            if ('undefined' === typeof value) {
                return localStorage.getItem(k);
            }
            else if (null === value) {
                localStorage.removeItem(k);
            }
            else {
                localStorage.setItem(k, value);
            }
        }
    }

    var _setupColumns = function () {
        if ('object' === typeof self.params.columns) {
            for (i in self.params.columns) {
                var item = self.params.columns[i];
                if ('string' === typeof item) {
                    item = __getActionColumns(item);
                    if (!item) continue;
                }
                // add editable icon
                if ('undefined' !== typeof item.editor) {
                    item.title = item.title + ' <span class="i-edit" title="' + _t('editable') + '">';
                }
                var entry = ('undefined' === typeof item.$className) ? __prepareColumn(item) : item;
                if (entry.dataIndex !== undefined) {
                    self.columns.push(entry);
                    self.fields.push(entry.dataIndex);
                }
            }
        }
    };

    var _setupStore = function (autoLoad) {
        if (self.params.fields !== undefined) {
            self.fields = $.unique(self.fields.concat(self.params.fields));
        }

        self.store = Ext.create('Ext.data.Store', {
            autoDestroy: true,
            autoLoad: autoLoad,
            currentPage: parseInt(self.storage('p')) || 1,
            fields: self.fields,
            pageSize: self.storage('n') || self.config.pageSize,
            proxy: {
                buildRequest: function (operation) {
                    var params = Ext.applyIf(operation.params || {}, this.extraParams || {}), request;
                    params = Ext.applyIf(params, this.getParams(operation));

                    if (operation.id && !params.id) {
                        params.id = operation.id;
                    }

                    if (params.sort) {
                        sortingParams = JSON.parse(params.sort);
                        params.sort = sortingParams[0]['property'];
                        params.dir = sortingParams[0]['direction'];
                    }

                    request = Ext.create('Ext.data.Request', {
                        params: params,
                        action: operation.action,
                        records: operation.records,
                        operation: operation,
                        url: operation.url,
                        success: function (response) {
                            try {
                                var respObj = JSON.parse(response.responseText);
                            } catch (e) {
                                return false;
                            }

                            if ('boolean' === typeof respObj.result && respObj.result) {
                                window.location.href = respObj.redirect_url || intelli.config.admin_url + '/';
                            }
                        }
                    });
                    request.url = this.buildUrl(request);
                    operation.request = request;

                    return request;
                },
                extraParams: (self.params.storeParams !== undefined) ? self.params.storeParams : null,
                pageParam: null,
                reader: {type: 'json', root: 'data', totalProperty: 'total'},
                type: 'ajax',
                url: self.url + 'read.json'
            },
            remoteSort: true,
            sorters: (self.params.sorters !== undefined) ? self.params.sorters : null
        });
    };

    var _setupToolbar = function () {
        self.config.bottomBar = self.params.bottomBar;

        if (self.params.toolbar) {
            self.toolbar = self.params.toolbar;
        }

        var tbValues = self.storage('t');
        if (tbValues) {
            tbValues = JSON.parse(tbValues);

            for (var controlName in tbValues) {
                var i = self.toolbar.items.indexMap[controlName];
                self.toolbar.items.items[i].setValue(tbValues[controlName]);
            }

            intelli.gridHelper.search(self, false, false, self.storage('p'));
        }
    };

    var _setupGrid = function () {
        var plugins = [];
        if ('undefined' === typeof self.params.progressBar || self.params.progressBar) {
            plugins.push(Ext.create('Ext.ux.ProgressBarPager', {defaultText: _t('loading'), width: 190}));
        }

        var pagingBar = ['-',
            _t('items_per_page') + ':',
            {
                displayField: 'title',
                editable: false,
                id: 'cmbPageSize' + self.descriptor,
                lazyRender: true,
                listeners: {
                    change: function (field, newValue, oldValue) {
                        self.store.pageSize = parseInt(newValue);
                        self.store.loadPage(1);

                        self.storage('n', self.store.pageSize);
                        self.storage('p', null);
                    }
                },
                store: self.stores.paging,
                typeAhead: true,
                value: self.storage('n') || self.config.pageSize,
                width: 70,
                xtype: 'combo'
            }
        ];
        if ('undefined' === typeof self.config.bottomBar
            || ('boolean' === typeof self.config.bottomBar && self.config.bottomBar)) {
            if (self.fields.indexOf('delete') > -1) {
                pagingBar.push('-', {
                    disabled: true,
                    handler: function () {
                        var selection = self.grid.getSelectionModel().getSelection();
                        Ext.Msg.show({
                            title: _t('confirm'),
                            msg: selection.length > 1 ? self.texts.delete_multiple : self.texts.delete_single,
                            buttons: Ext.Msg.YESNO,
                            icon: Ext.Msg.QUESTION,
                            fn: function (btn) {
                                if ('yes' === btn) {
                                    var ids = [];
                                    for (var i = 0; i < selection.length; i++) {
                                        if (1 == selection[i].data.delete) ids.push(selection[i].data.id);
                                    }

                                    intelli.gridHelper.httpRequest(self, {id: ids}, 'delete');
                                }
                            }
                        });
                    },
                    id: 'btnMassDelete',
                    text: '<i class="i-close"></i> ' + _t('remove')
                });
            }
            if (self.fields.indexOf('status') > -1) {
                pagingBar.push('-', {
                    disabled: true,
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('status'),
                    id: 'cmbStatus',
                    lazyRender: true,
                    listeners: {
                        select: function (combo, records, eOpts) {
                            var value = combo.getValue();
                            var control = Ext.getCmp('btnMassStatusUpdate');
                            value ? control.enable() : control.disable();
                        }
                    },
                    store: self.stores.statuses,
                    valueField: 'value',
                    typeAhead: true,
                    xtype: 'combo'
                }, {
                    disabled: true,
                    handler: function () {
                        var selection = self.grid.getSelectionModel().getSelection();
                        var ids = [];
                        for (var i = 0; i < selection.length; i++) ids.push(selection[i].data.id);

                        intelli.gridHelper.httpRequest(self, {id: ids, status: Ext.getCmp('cmbStatus').getValue()});

                        Ext.getCmp('cmbStatus').reset();
                        this.disable();
                    },
                    id: 'btnMassStatusUpdate',
                    text: '<i class="i-checkmark"></i> ' + _t('go')
                });
            }
        }

        if ('object' === typeof self.config.bottomBar) {
            pagingBar.push(self.config.bottomBar);
        }

        Ext.state.Manager.setProvider(Ext.create(Ext.supports.LocalStorage ? 'Ext.state.LocalStorageProvider' : 'Ext.state.CookieProvider'));

        self.grid = Ext.create('Ext.grid.Panel', {
            allowDeselect: true,
            bbar: Ext.create('Ext.PagingToolbar', {
                afterPageText: _t('grid_of'),
                beforePageText: _t('page'),
                store: self.store,
                displayInfo: true,
                plugins: plugins,
                items: pagingBar,
                listeners: {
                    change: function (paging, pageData, options) {
                        self.storage('p', pageData ? pageData.currentPage : null);
                    }
                }
            }),
            columns: self.columns,
            height: self.config.height,
            minHeight: self.config.minHeight,
            multiColumnSort: true,
            plugins: self.plugins,
            renderTo: self.config.target,
            selType: self.config.selectionType,
            selMode: {checkOnly: true},
            stateful: true,
            stateId: stateId,
            store: self.store,
            tbar: self.toolbar,
            xtype: self.config.xtype,
            frame: true,
            title: self.config.title
        });

        var events = (undefined === self.params.events) ? {} : self.params.events;

        self.grid.on('cellclick', events.click || function (view, cell, columnIndex, store, row, rowIndex, e) {
            var field = view.getGridColumns()[columnIndex].dataIndex;
            var record = view.getRecord(row);

            if (0 == record.get(field)) {
                return;
            }

            switch (field) {
                case 'update':
                    window.location = self.url + 'edit/' + record.get('id') + '/';
                    return;
                case 'delete':
                    Ext.Msg.show({
                        title: _t('confirm'),
                        msg: self.texts.delete_single,
                        buttons: Ext.Msg.YESNO,
                        icon: Ext.Msg.QUESTION,
                        fn: function (btn) {
                            if ('yes' === btn) {
                                intelli.gridHelper.httpRequest(self, {id: [record.get('id')]}, 'delete');
                                self.storage('p', null);
                            }
                        }
                    });
                    break;
                default:
                    for (var column = null, i = self.columns.length - 1; i >= 0; i--) {
                        if (field == self.columns[i].dataIndex) {
                            column = self.columns[i];
                            break;
                        }
                    }

                    if (!column) {
                        return;
                    }

                    switch (true) {
                        case ('string' === typeof column.href):
                            window.location = column.href
                                .replace('{id}', record.get('id'))
                                .replace('{value}', record.get(field));
                            return;
                        case ('function' === typeof column.click):
                            column.click(record, field);
                            return;
                    }
            }
        });

        self.grid.on('selectionchange', events.select || function (model, selected, eOpts) {
            var selection = model.getSelection();

            var control = Ext.getCmp('cmbStatus');
            if (control) {
                selection.length ? control.enable() : control.disable();
                0 < selection.length || Ext.getCmp('btnMassStatusUpdate').disable();
            }

            if (control = Ext.getCmp('btnMassDelete')) {
                var flag = false;
                for (var i = 0; i < selection.length; i++) {
                    if (selection[i].data.delete == 1) {
                        flag = true;
                        break;
                    }
                }

                (flag && selection.length) ? control.enable() : control.disable();
            }

            if (selection.length > 0 && self.config.rowselect) {
                self.config.rowselect(self);
            }

            if (0 === selection.length && self.config.rowdeselect) {
                self.config.rowdeselect(self);
            }
        });

        self.grid.on('edit', events.edit || function (editor, e) {
                if (e.value == e.originalValue) {
                    return;
                }

                var data = {id: [e.record.get('id')]};
                data[e.field] = e.value;

                // non-scalar values should be handled here
                switch (true) {
                    case (e.value instanceof Date):
                        var date = e.value;
                        data[e.field] = date.getFullYear() + '-' + (date.getMonth() + 1) + '-' + date.getDate();
                        break;
                }

                intelli.gridHelper.httpRequest(self, data);
            });

        if ('function' === typeof events.beforeedit) {
            self.grid.on('beforeedit', events.beforeedit);
        }
        if ('function' === typeof events.load) {
            self.store.on('load', events.load);
        }

        Ext.EventManager.onWindowResize(self.grid.doLayout, self.grid);

        if (self.config.resizer) {
            Ext.create('Ext.resizer.Resizer', {handles: 's', target: self.grid, pinned: true});
        }
    };

    var __prepareColumn = function (columnData) {
        var result = null;

        if (columnData.name) {
            result = {
                align: columnData.align || intelli.gridHelper.constants.ALIGN_LEFT,
                click: ('function' === typeof columnData.click) ? columnData.click : null,
                dataIndex: columnData.name,
                hidden: ('boolean' === typeof columnData.hidden) ? columnData.hidden : false,
                hideable: ('boolean' === typeof columnData.hideable) ? columnData.hideable : true,
                id: columnData.id || null,
                menuDisabled: ('boolean' === typeof columnData.menu) ? !columnData.menu : false,
                renderer: ('function' === typeof columnData.renderer) ? columnData.renderer : null,
                sortable: ('boolean' === typeof columnData.sortable) ? columnData.sortable : true,
                text: columnData.title || '',
                xtype: columnData.xtype || null
            };

            columnData.width <= 3
                ? result.flex = columnData.width
                : result.width = columnData.width;

            switch (typeof columnData.editor) {
                case 'object':
                    result.editor = columnData.editor;
                    break;
                case 'string':
                    result.editor = __getColumnEditor(columnData.editor);
                    break;
            }

            if ('string' === typeof columnData.href) {
                result.href = columnData.href;
            }

            if ('string' === typeof columnData.icon && !result.renderer) {
                result.align = intelli.gridHelper.constants.ALIGN_LEFT;
                result.hideable = false;
                result.menuDisabled = true;
                result.renderer = function (value, metadata, record, rowIndex) {
                    return intelli.gridHelper.renderer._iconRenderer(columnData.title, 'i-' + columnData.icon, value)
                };
                result.sortable = false;
                result.text = '';
                result.width = intelli.gridHelper.constants.WIDTH_ICON;
            }
        }

        return result;
    };

    var __getColumnEditor = function (type) {
        switch (type) {
            case 'text':
                return Ext.create('Ext.form.TextField', {allowBlank: false});
            case 'text-wide':
                return Ext.create('Ext.form.field.TextArea', {allowBlank: false, grow: true});
            case 'date':
                return Ext.create('Ext.form.DateField', {allowBlank: false, format: 'Y-m-d'});
            case 'number':
                return Ext.create('Ext.form.NumberField', {
                    allowBlank: false,
                    allowDecimals: false,
                    allowNegative: false
                });
            case 'decimal':
                return Ext.create('Ext.form.NumberField', {
                    allowBlank: false,
                    allowDecimals: true,
                    allowNegative: false
                });
        }
    };

    var __getActionColumns = function (name) {
        switch (name) {
            case 'delete':
                return {
                    align: intelli.gridHelper.constants.ALIGN_CENTER,
                    hideable: false,
                    menu: false,
                    name: name,
                    renderer: intelli.gridHelper.renderer[name],
                    sortable: false,
                    width: intelli.gridHelper.constants.WIDTH_ICON
                };
            case 'expander':
                self.plugins.push({
                    ptype: 'rowexpander',
                    expandOnDblClick: false,
                    rowBodyTpl: [self.params.expanderTemplate]
                });
                break;
            case 'numberer':
                return Ext.create('Ext.grid.RowNumberer', {width: 30});
            case 'update':
                return {
                    align: intelli.gridHelper.constants.ALIGN_CENTER,
                    hideable: false,
                    menu: false,
                    name: name,
                    renderer: intelli.gridHelper.renderer[name],
                    sortable: false,
                    width: intelli.gridHelper.constants.WIDTH_ICON
                };
            case 'status':
                return {
                    editor: Ext.create('Ext.form.ComboBox',
                        {
                            typeAhead: true,
                            editable: false,
                            lazyRender: true,
                            store: self.stores.statuses,
                            displayField: 'title',
                            valueField: 'value'
                        }),
                    name: 'status',
                    renderer: function (value, metadata) {
                        metadata.css = 'grid-status-' + value;
                        return _t(value);
                    },
                    title: _t('status'),
                    width: 80
                };
            case 'selection':
                self.config.selectionType = 'checkboxmodel';
        }
    };

    if ('undefined' === typeof autoInit || autoInit) {
        this.init();
    }
}