Ext.onReady(function () {
    var selectedLanguage = (intelli.urlVal('language') === null) ? intelli.config.lang : intelli.urlVal('language');

    var languages = [], j = 0;
    for (var i in intelli.languages) {
        languages[j++] = [i, intelli.languages[i].title];
    }

    var languagesStore = new Ext.data.SimpleStore({fields: ['value', 'title'], data: languages});
    var categoriesStore = new Ext.data.SimpleStore({
        fields: ['value', 'title'],
        data: [['admin', 'Administration Board'], ['frontend', 'User Frontend'], ['common', 'Common'], ['tooltip', 'Tooltip']]
    });

    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid({
            columns: [
                {name: 'key', title: _t('key'), width: 250, editor: 'text', renderer: function (value, metadata, row) {
                    if (1 == row.data.modified) {
                        metadata.css = 'grid-status-unconfirmed';
                    }
                    return value;
                }},
                {name: 'original', title: _t('original'), width: 250},
                {name: 'value', title: _t('value'), width: 1, editor: 'text-wide'},
                {name: 'code', title: _t('language'), width: 100, hidden: true},
                {name: 'category', title: _t('category'), width: 100, editor: Ext.create('Ext.form.ComboBox', {
                    typeAhead: true,
                    editable: false,
                    store: categoriesStore,
                    value: 'admin',
                    displayField: 'title',
                    valueField: 'value'
                })},
                'update',
                'delete'
            ],
            fields: ['original', 'modified'],
            storeParams: {lang: intelli.urlVal('language')},
            texts: {delete_multiple: _t('are_you_sure_to_delete_selected_phrases')}
        }, false);
        /*		intelli.language.bottomBar = ['-',
         {
         emptyText: _t('category'),
         xtype: 'combo',
         typeAhead: true,
         editable: false,
         lazyRender: true,
         store: categoriesStore,
         displayField: 'title',
         disabled: true,
         id: 'categoryCmb',
         valueField: 'value'
         },
         {
         text: '<i class="i-arrow-right-2"></i> ' + _t('do'),
         disabled: true,
         id: 'goBtn',
         handler: function()
         {
         var rows = intelli.language.grid.getSelectionModel().getSelections();
         var category = Ext.getCmp('categoryCmb').getValue();
         var ids = [];

         for (var i = 0; i < rows.length; i++)
         {
         ids[i] = rows[i].json.id;
         }

         Ext.Ajax.request(
         {
         url: url + 'update.json',
         method: 'POST',
         params:
         {
         action: 'update',
         'ids[]': ids,
         field: 'category',
         value: category
         },
         failure: function()
         {
         Ext.MessageBox.alert(_t('error_saving_changes'));
         },
         success: function(data)
         {
         var response = Ext.decode(data.responseText);
         var type = response.error ? 'error' : 'notif';

         intelli.notifBox({msg: response.msg, type: type, autohide: true});

         intelli.language.grid.getStore().reload();
         }
         });
         }
         }];*/

        grid.toolbar = Ext.create('Ext.Toolbar', {
            items: [
                {
                    emptyText: _t('key'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    name: 'key',
                    xtype: 'textfield'
                }, {
                    emptyText: _t('value'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    name: 'value',
                    xtype: 'textfield'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('category'),
                    name: 'category',
                    store: new Ext.data.SimpleStore(
                        {
                            fields: ['value', 'title'],
                            data: [['admin', 'Administration Board'], ['frontend', 'User Frontend'], ['common', 'Common'], ['tooltip', 'Tooltip']]
                        }),
                    typeAhead: true,
                    valueField: 'value',
                    xtype: 'combo'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('module'),
                    name: 'module',
                    store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/actions/options/module.json'),
                    typeAhead: true,
                    valueField: 'value',
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
                }, '->', {
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: languagesStore,
                    value: selectedLanguage,
                    displayField: 'title',
                    valueField: 'value',
                    id: 'languageFilter',
                    listeners: {
                        change: function () {
                            window.location = intelli.config.admin_url + '/phrases/?language=' + this.getSubmitValue();
                        }
                    }
                }]
        });

        grid.init();
    }

    $('.js-remove-lang-cmd').each(function () {
        $(this).on('click', function (e) {
            e.preventDefault();

            var $this = $(this);

            Ext.Msg.show(
                {
                    title: _t('confirm'),
                    msg: _t('are_you_sure_to_delete_selected_language'),
                    buttons: Ext.Msg.YESNO,
                    fn: function (btn) {
                        if ('yes' == btn) {
                            window.location = $this.data('href');
                        }
                    },
                    icon: Ext.MessageBox.QUESTION
                });
        });
    });

    if (Ext.get('languagesList')) {
        intelli.sortable('languagesList', {
            handle: '.uploads-list-item__drag-handle',
            animation: 150,
            onEnd: function() {
                var langs = $('.iso-val').map(function () {
                    return $(this).text();
                }).get();

                intelli.post(window.location.href + 'add.json', {sorting: 'save', langs: langs}, function(response) {
                    intelli.notifFloatBox({
                        msg: response.message,
                        type: (response.result ? 'success' : 'error'),
                        autohide: true,
                        pause: 1500
                    });
                });
            }
        });
    }

    if (Ext.get('js-comparison-grid')) {
        var comparisonRenderer = function (value, metadata, row) {
            if (null == value) {
                value = '<i><small>&lt;does not exist&gt;</small></i>';
            }

            return value;
        };

        intelli.languageComparison = new IntelliGrid(
            {
                columns: [
                    {name: 'key', title: _t('key'), width: 200},
                    {
                        name: 'lang1',
                        title: _t('default_language'),
                        width: 1,
                        editor: 'text-wide',
                        renderer: comparisonRenderer
                    },
                    {name: 'lang2', title: _t('language'), width: 1, editor: 'text-wide', renderer: comparisonRenderer},
                    {
                        name: 'category', title: _t('category'), width: 100, editor: Ext.create('Ext.form.ComboBox',
                        {
                            typeAhead: true,
                            editable: false,
                            store: categoriesStore,
                            value: 'admin',
                            displayField: 'title',
                            valueField: 'value'
                        })
                    }
                ],
                events: {
                    edit: function (editor, e) {
                        if (e.value == e.originalValue) {
                            return;
                        }

                        var data = {key: e.record.get('key')};
                        var fieldName = e.field;

                        if ('lang1' == fieldName || 'lang2' == fieldName) {
                            data['lang'] = intelli.languageComparison.store.getProxy().extraParams[fieldName];
                            fieldName = 'value';
                        }

                        data[fieldName] = e.value;

                        intelli.gridHelper.httpRequest(intelli.languageComparison, data);
                    }
                },
                storeParams: {get: 'comparison'},
                target: 'js-comparison-grid',
                texts: {delete_multiple: _t('are_you_sure_to_delete_selected_phrases')},
                url: intelli.config.admin_url + '/languages/'
            }, false);

        var tb1 = Ext.create('Ext.Toolbar', {
            items: [
                {
                    fieldLabel: _t('languages'),
                    xtype: 'combo',
                    allowBlank: false,
                    editable: false,
                    id: 'lang1',
                    listeners: {
                        select: function () {
                            var selectedLanguage = this.getValue();
                            languagesStore.each(function (record) {
                                if (record.get('value') != selectedLanguage) {
                                    Ext.getCmp('lang2').setValue(record.get('value'));
                                    return;
                                }
                            });
                        }
                    },
                    value: intelli.config.lang,
                    store: languagesStore,
                    displayField: 'title',
                    valueField: 'value'
                }, {
                    xtype: 'combo',
                    allowBlank: false,
                    editable: false,
                    id: 'lang2',
                    store: languagesStore,
                    displayField: 'title',
                    valueField: 'value'
                }, '->', {
                    handler: function () {
                        var legendPanel = Ext.getCmp('legend_panel');

                        if (!legendPanel) {
                            var $target = $('#js-legend-panel');
                            var content = $target.html();

                            $target.html('').css('display', 'inline');

                            legendPanel = new Ext.FormPanel(
                                {
                                    frame: true,
                                    title: _t('legend'),
                                    bodyStyle: 'margin-bottom: 20px; padding: 5px 5px 0',
                                    renderTo: 'js-legend-panel',
                                    id: 'legend_panel',
                                    items: [{html: content, xtype: 'panel'}]
                                });

                            legendPanel.show();

                            return
                        }

                        legendPanel.getEl().toggle();
                    },
                    text: '<i class="i-chevron-up"></i> ' + _t('legend')
                }]
        });

        var tb2 = Ext.create('Ext.Toolbar', {
            items: [
                {
                    emptyText: _t('value'),
                    fieldLabel: _t('search'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    id: 'value',
                    name: 'value',
                    xtype: 'textfield'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('category'),
                    id: 'category',
                    name: 'category',
                    store: new Ext.data.SimpleStore(
                        {
                            fields: ['value', 'title'],
                            data: [['admin', 'Administration Board'], ['frontend', 'User Frontend'], ['common', 'Common'], ['tooltip', 'Tooltip']]
                        }),
                    typeAhead: true,
                    valueField: 'value',
                    xtype: 'combo'
                }, {
                    displayField: 'title',
                    editable: false,
                    emptyText: _t('module'),
                    id: 'module',
                    name: 'module',
                    store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/actions/options/module.json'),
                    typeAhead: true,
                    valueField: 'value',
                    xtype: 'combo'
                }, {
                    handler: function () {
                        var cmb1 = Ext.getCmp('lang1');
                        var cmb2 = Ext.getCmp('lang2');

                        if ('' != cmb1.getValue() || '' != cmb2.getValue()) {
                            var language1 = cmb1.getValue();
                            var language2 = cmb2.getValue();

                            // notify if comparing same languages
                            if (language1 == language2) {
                                intelli.notifFloatBox({
                                    msg: _t('error_compare_same_languages'),
                                    type: 'error',
                                    autohide: true
                                });

                                return false;
                            }

                            var columns = intelli.languageComparison.grid.getView().getGridColumns();
                            columns[1].setText('&quot;' + cmb1.getRawValue().toUpperCase() + '&quot;');
                            columns[2].setText('&quot;' + cmb2.getRawValue().toUpperCase() + '&quot;');

                            intelli.languageComparison.store.getProxy().extraParams.lang1 = language1;
                            intelli.languageComparison.store.getProxy().extraParams.lang2 = language2;

                            intelli.languageComparison.store.getProxy().extraParams.key = Ext.getCmp('value').getValue();
                            intelli.languageComparison.store.getProxy().extraParams.category = Ext.getCmp('category').getValue();
                            intelli.languageComparison.store.getProxy().extraParams.module = Ext.getCmp('module').getValue();

                            intelli.languageComparison.store.loadPage(1);
                        }
                    },
                    id: 'fltBtn',
                    text: '<i class="i-search"></i> ' + _t('search')
                }, {
                    handler: function () {
                        var langSelector = Ext.getCmp('lang1');
                        langSelector.reset();
                        langSelector.fireEvent('select');

                        Ext.getCmp('value').reset();
                        Ext.getCmp('category').reset();
                        Ext.getCmp('module').reset();

                        intelli.languageComparison.store.getProxy().extraParams = {
                            get: 'comparison',
                            lang1: langSelector.getValue(),
                            lang2: Ext.getCmp('lang2').getValue()
                        };

                        intelli.languageComparison.store.loadPage(1);
                    },
                    text: '<i class="i-close"></i> ' + _t('reset')
                }]
        });

        intelli.languageComparison.toolbar = Ext.create('Ext.Panel', {items: [tb1, tb2]});
        Ext.getCmp('lang1').fireEvent('select');
        intelli.languageComparison.init(false);

        Ext.getCmp('fltBtn').getEl().dom.click();

        intelli.languageComparison.grid.getView().getRowClass = function (record, rowIndex, rowParams, store) {
            var phrase1 = record.get('lang1'),
                phrase2 = record.get('lang2');

            if (null === phrase2 || null === phrase1) {
                return 'grid-row-inactive';
            }
            if (phrase1 == phrase2) {
                return 'grid-row-highlight';
            }

            return '';
        };
    }
});