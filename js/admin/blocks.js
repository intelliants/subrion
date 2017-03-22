Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var positionsStore = intelli.gridHelper.store.ajax(intelli.config.admin_url + '/blocks/positions.json');

        var grid = new IntelliGrid(
            {
                columns: [
                    'selection',
                    'expander',
                    {name: 'title', title: _t('title'), width: 2, editor: 'text'},
                    {
                        name: 'position', title: _t('position'), width: 85, editor: Ext.create('Ext.form.ComboBox',
                        {
                            typeAhead: true,
                            editable: false,
                            lazyRender: true,
                            store: positionsStore,
                            displayField: 'title',
                            valueField: 'value'
                        })
                    },
                    {name: 'module', title: _t('module'), width: 150},
                    {name: 'type', title: _t('type'), width: 85},
                    'status',
                    {name: 'order', title: _t('order'), width: 50, editor: 'number'},
                    'update',
                    'delete'
                ],
                expanderTemplate: '<pre style="font-size: 0.9em">{contents}</pre>',
                fields: ['contents'],
                sorters: [{property: 'title'}],
                texts: {delete_single: _t('are_you_sure_to_delete_this_block')}
            }, false);

        grid.toolbar = Ext.create('Ext.Toolbar', {
            items: [
                {
                    xtype: 'textfield',
                    name: 'title',
                    emptyText: _t('title'),
                    listeners: intelli.gridHelper.listener.specialKey,
                    width: 130
                }, {
                    emptyText: _t('status'),
                    name: 'status',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: grid.stores.statuses,
                    displayField: 'title',
                    valueField: 'value',
                    width: 90
                }, {
                    emptyText: _t('type'),
                    name: 'type',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: new Ext.data.SimpleStore(
                        {
                            fields: ['value', 'title'],
                            data: [['plain', 'plain'], ['smarty', 'smarty'], ['php', 'php'], ['html', 'html'], ['menu', 'menu']]
                        }),
                    displayField: 'title',
                    valueField: 'value',
                    width: 90
                }, {
                    emptyText: _t('position'),
                    name: 'position',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: positionsStore,
                    displayField: 'title',
                    valueField: 'value',
                    width: 110
                }, {
                    emptyText: _t('module'),
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/actions/options/module.json'),
                    displayField: 'title',
                    name: 'module',
                    valueField: 'value',
                    width: 120
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
    }
    else {
        $('#input-block-type').on('change', function () {
            $(this).next().html($('option:selected', this).data('tip'));
            $('#pages').hide();

            var type = $(this).val();

            var $externalRow = $('#js-external-row'),
                $contentDynamic = $('#js-content-dynamic'),
                $contentStatic = $('#js-content-static');

            if ('php' == type || 'smarty' == type) {
                $('#frame_input-content').length > 0 ||
                eAL.init({
                    id: 'input-content', start_highlight: true, allow_resize: 'yes', allow_toggle: true,
                    syntax: 'php', toolbar: 'search, go_to_line, |, undo, redo', min_height: 350
                });

                $contentDynamic.show();
                $externalRow.show();
            }
            else {
                eAL.toggle_off('input-content');
                $('#EditAreaArroundInfos_input-content').hide();

                if ('html' == type) {
                    $('textarea.js-ckeditor').each(function () {
                        intelli.ckeditor($(this).attr('id'), {toolbar: 'extended', height: '400px'});
                    });
                }
                else {
                    $.each(CKEDITOR.instances, function (i, o) {
                        o.destroy();
                    });
                }

                $contentDynamic.hide();
                $externalRow.hide().bootstrapSwitch('setState', 0);
            }

            $contentDynamic.is(':visible') ? $contentStatic.hide() : $contentStatic.show();
        }).change();

        $('#sticky').on('change', function () {
            $(this).closest('.col').find('p.help-block').hide()
                .filter('[data-sticky="' + ($(this).is(':checked') ? '1' : '0') + '"]').show();
        }).change();

        $('#external').change(function () {
            var enabled = $(this).is(':checked');

            var $rowContent = $('#js-row-dynamic-content'),
                $rowFilename = $('#js-row-external-file-name');

            enabled ? $rowContent.hide() : $rowContent.show();
            enabled ? $rowFilename.show() : $rowFilename.hide();
        });

        /* TEMPORARILY DISABLED FOR FUTURE IMPLEMENTATION

         $('.subpages').click(function()
         {
         var temp = $(this).attr('rel').split('::');
         var div = $('#subpage_' + temp[1]);
         var url = intelli.config.admin_url + '/' + temp[0] + '.json?a=subpages&ids=' + div.val();
         var tree = new Ext.tree.TreePanel(
         {
         height: 465,
         width: 335,
         useArrows:true,
         autoScroll:true,
         animate:true,
         enableDD:true,
         containerScroll: true,
         rootVisible: false,
         frame: true,
         root: {nodeType: 'async'},
         dataUrl: url,
         buttons: [{
         text: _t('reset'),
         handler: function(){
         tree.getRootNode().cascade(function(n) {
         var ui = n.getUI();
         ui.toggleCheck(false);
         });
         div.val('');
         win.close();
         }
         },{
         text: _t('cancel'),
         handler: function()
         {
         temp = false;
         win.close();
         }
         },{
         text: _t('save'),
         handler: function()
         {
         var msg = '', selNodes = tree.getChecked();
         Ext.each(selNodes, function(node){
         if (msg.length > 0){
         msg += '-';
         }
         msg += node.id;
         });

         div.val(msg);
         win.close();
         }
         }]
         });

         var win = new Ext.Window({
         title: 'Subpages List',
         closable: true,
         width: 352,
         autoScroll: true,
         height: 500,
         plain:true,
         listeners:
         {
         beforeclose: function(panel)
         {
         var msg = '', selNodes = tree.getChecked();
         Ext.each(selNodes, function(node){
         if (msg.length > 0){
         msg += '-';
         }
         msg += node.id;
         });

         if (div.val() != msg && temp)
         {
         Ext.Msg.show({
         title: _t('save_changes') + '?',
         msg: _t('closing_window_with_unsaved_changes'),
         buttons: Ext.Msg.YESNO,
         fn: function(btnID)
         {
         if (btnID == 'yes')
         {
         div.val(msg);
         return true;
         }
         else if (btnID == 'no')
         {
         return true;
         }
         return false;
         },
         icon: Ext.MessageBox.QUESTION
         });
         }
         temp = true;
         return true;
         }
         },
         items: [tree]
         });
         tree.getRootNode().expand();

         win.show();
         });
         */

        var pagesCount = $('input[name^="pages"]', '#js-pages-list').length,
            selectedPagesCount = $('input[name^="pages"]:checked', '#js-pages-list').length;

        if (selectedPagesCount > 0 && pagesCount == selectedPagesCount) {
            $('#js-pages-select-all').prop('checked', true).click();
        }

        $('input[name^="pages"]', '#js-pages-list').on('click', function () {
            var checked = (pagesCount == $('input[name^="pages"]:checked', '#js-pages-list').length);
            $('#js-pages-select-all').prop('checked', checked);
        });

        $('#js-pages-select-all').on('click', function () {
            $('input[type="checkbox"]', '#js-pages-list').prop('checked', $(this).prop('checked')).change();
        });

        $('#all_pages').on('click', function () {
            $('input[type="checkbox"]', '#pages').prop('checked', $(this).prop('checked')).change();
        });

        $('input[name^="select_all_"], input[name^="all_pages_"]', '#js-pages-list').on('click', function () {
            $('input.' + $(this).data('group')).prop('checked', $(this).is(':checked')).change();
        });

        $('#header').on('change', function () {
            var collapsible = $('input[name="collapsible"]').closest('.row');
            $(this).val() == 1 ? collapsible.show() : collapsible.hide();

            var collapsed = $('input[name="collapsed"]').closest('.row');
            $(this).val() == 1 && $('#collapsible').val() == 1 ? collapsed.show() : collapsed.hide();
        }).change();

        $('#collapsible').on('change', function () {
            var obj = $('input[name="collapsed"]').closest('.row');
            $(this).val() == 1 ? obj.show() : obj.hide();
        }).change();
    }

    $('#js-delete-block').on('click', function () {
        Ext.Msg.confirm(_t('confirm'), _t('are_you_sure_to_delete_this_block'), function (btn, text) {
            if (btn == 'yes') {
                var pageUrl = window.location.href;
                $.ajax(
                    {
                        data: {'id[]': $('input[name="id"]').val()},
                        dataType: 'json',
                        failure: function () {
                            Ext.MessageBox.alert(_t('error'));
                        },
                        type: 'POST',
                        url: pageUrl + 'delete.json',
                        success: function (response) {
                            if ('boolean' == typeof response.result && response.result) {
                                intelli.notifFloatBox({
                                    msg: response.message,
                                    type: response.result ? 'success' : 'error'
                                });
                                document.location = pageUrl;
                            }
                        }
                    });
            }
        });
    });
});