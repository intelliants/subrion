Ext.onReady(function () {
    if ($('#js-grid-placeholder').length) {
        var grid = new IntelliGrid({
            columns: [
                'selection',
                {name: 'name', title: _t('name'), hidden: true, width: 160},
                {name: 'title', title: _t('title'), sortable: false, width: 2},
                {name: 'item', title: _t('item'), width: 100, renderer: function (value) {
                    return _t(value, value);
                }},
                {name: 'group', title: _t('field_group'), width: 120},
                {name: 'type', title: _t('field_type'), width: 130, renderer: function (value) {
                    return _t('field_type_' + value, value);
                }},
                {name: 'relation', title: _t('field_relation'), hidden: true, width: 80},
                {name: 'length', title: _t('field_length'), width: 80},
                {name: 'order', title: _t('order'), width: 50, editor: 'number'},
                'status',
                'update',
                'delete'
            ],
            events: {
                beforeedit: function (editor, e) {
                    if (0 == e.record.get('delete')) {
                        this.store.rejectChanges();
                        e.cancel = true;

                        intelli.notifFloatBox({autohide: true, msg: _t('status_change_not_allowed'), pause: 1400});
                    }
                }
            },
            texts: {delete_single: _t('are_you_sure_to_delete_field')}
        }, false);

        grid.toolbar = Ext.create('Ext.Toolbar', {
            items: [
                {
                    emptyText: _t('fields_item_filter'),
                    name: 'item',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: new Ext.data.SimpleStore({fields: ['value', 'title'], data: intelli.config.items}),
                    displayField: 'title',
                    valueField: 'value'
                }, {
                    emptyText: _t('field_relation'),
                    name: 'relation',
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: new Ext.data.SimpleStore(
                        {
                            fields: ['value', 'title'],
                            data: [['regular', _t('field_relation_regular')], ['dependent', _t('field_relation_dependent')], ['parent', _t('field_relation_parent')]]
                        }),
                    displayField: 'title',
                    valueField: 'value'
                }, {
                    handler: function () {
                        intelli.gridHelper.search(grid)
                    },
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
});

intelli.translateTreeNode = function (o) {
    var tree = $.jstree.reference(o.reference),
        node = tree.get_node(o.reference);

    var data = {
        item: $('#input-item').val(),
        field: $('input[name="name"]').val(),
        id: node.id
    };

    $.ajax({
        dataType: 'json',
        url: intelli.config.admin_url + '/fields/tree.json',
        data: data,
        success: function (response) {
            if (0 === response.length) {
                return;
            }

            response.push({xtype: 'hidden', name: intelli.securityTokenKey, value: intelli.securityToken});

            var translationModal = new Ext.Window({
                title: _t('translate'),
                layout: 'fit',
                width: 350,
                autoHeight: true,
                plain: true,
                items: [new Ext.FormPanel({
                    id: 'form-translate',
                    labelWidth: 75,
                    url: intelli.config.admin_url + '/fields/tree.json?item=' + data.item + '&field=' + data.field + '&id=' + data.id,
                    frame: true,
                    width: 350,
                    autoHeight: true,
                    defaults: {width: 230},
                    defaultType: 'textfield',
                    items: response,
                    buttons: [{
                        text: _t('save'),
                        handler: function () {
                            Ext.getCmp('form-translate').getForm().submit({
                                waitMsg: _t('saving'),
                                success: function () {
                                    var formText = Ext.getCmp('form-translate').getForm().getValues()[intelli.config.lang];
                                    if (formText == '') {
                                        return;
                                    }
                                    tree.rename_node(node, formText);
                                    translationModal.close();
                                }
                            });
                        }
                    }, {
                        text: _t('cancel'),
                        handler: function () {
                            translationModal.close();
                        }
                    }]
                })]
            }).show();
        }
    });
};

$(function () {
    if ($('#js-grid-placeholder').length) {
        return;
    }

    intelli.displayUpdown = function () {
        $('div[id^="item"] .itemUp, div[id^="item"] .itemDown').show();
        $('div[id^="item"] .itemUp:first').hide();
        $('div[id^="item"] .itemDown:last').hide();
    };

    $('#input-type').on('change', function () {
        var type = $(this).val();

        $('div.field_type').css('display', 'none');
        $('#js-row-use-editor').css('display', ('textarea' !== type ? 'none' : 'block'));

        var $o = $('#js-row-empty-text');
        ($.inArray(type, ['text', 'textarea', 'number']) !== -1) ? $o.show() : $o.hide();

        $o = $('#js-row-multilingual');
        ('text' === type || 'textarea' === type) ? $o.show() : $o.hide();

        if (type && $.inArray(type, ['textarea', 'text', 'number', 'storage', 'image', 'url', 'date', 'pictures', 'tree', 'currency']) !== -1) {
            $('#' + type).css('display', 'block');
            if ($('#searchable').val() == '1' && ('textarea' == type || 'text' == type) && 'none' == $('#fulltext_search_zone').css('display')) {
                $('#fulltext_search_zone').fadeIn('slow');
            }
        }
        else if (type && $.inArray(type, ['combo', 'radio', 'checkbox']) !== -1) {
            $('#js-multiple').css('display', 'block');
        }

        var tooltip = $('option:selected', this).data('tooltip');
        var $helpBlock = $(this).next();
        tooltip ? $helpBlock.text(tooltip).show() : $helpBlock.hide();
    }).change();

    $('#js-field-relation').change(function () {
        var value = $(this).val();

        (value == 'parent') ? $('.main_fields').show() : $('.main_fields').hide();
        (value == 'dependent') ? $('#regular_field').show() : $('#regular_field').hide();
    });

    $('#searchable').on('change', function () {
        var $ctl = $('#show_in_search_as');
        ($(this).val() == 1) ? $ctl.show() : $ctl.hide();
    }).change();

    $('#js-cmd-add-value').on('click', function (e) {
        e.preventDefault();

        $('div[id^="item-value-"]:first')
            .clone(true)
            .attr('id', 'item-value-' + Math.ceil(Math.random() * 10000))
            .insertBefore($(this))
            .find('input').each(function () {
            $(this).val('');
        });

        intelli.displayUpdown();
    });

    $('select[name="resize_mode"], select[name="pic_resize_mode"]').on('change', function () {
        $(this).next().text($('option:selected', this).data('tooltip'));
    }).change();

    $('.js-actions').on('click', function (e) {
        e.preventDefault();

        var action = $(this).data('action');
        var type = $('#input-type').val();
        var val = $(this).parent().parent().find('input[name*="values["]:first').val();
        var defaultVal = $('#multiple_default').val();
        var allDefault = defaultVal.split('|');

        if ('removeItem' == action) {
            $(this).parents('.wrap-row').remove();
        }
        else if ('clearDefault' == action) {
            $('#multiple_default').val('');
        }
        else if ('setDefault' == action) {
            if ('' != val) {
                if ('checkbox' == type) {
                    if ('' != defaultVal) {
                        if (!intelli.inArray(val, allDefault)) {
                            allDefault[allDefault.length++] = val;
                        }

                        $('#multiple_default').val(allDefault.join('|'));
                    }
                    else {
                        $('#multiple_default').val(val);
                    }
                }
                else {
                    $('#multiple_default').val(val);
                }
            }
        }
        else if ('removeDefault' == action) {
            if ('' != defaultVal) {
                if (allDefault.length > 1) {
                    var array = [];
                    for (i = 0; i < allDefault.length; i++) {
                        if (allDefault[i] != val) {
                            array[array.length] = allDefault[i];
                        }
                    }
                    $('#multiple_default').val(array.join('|'));
                }
                else if (defaultVal == val) {
                    $('#multiple_default').val('');
                }
            }
        }
        else if ('itemUp' == action || 'itemDown' == action) {
            var current = {
                id: $(this).parents('.wrap-row').attr('id'),
                item: $(this).parents('.wrap-row'),
                index: null
            };
            var parent = current.item.parent();
            var items = parent.children('.wrap-row');

            $.each(items, function (index, item) {
                if ($(item).attr('id') == current.id) {
                    current.index = index;
                }
            });
            if (action == 'itemUp') {
                if (current.index >= 1) {
                    current.index--;
                    $('.wrap-row:eq(' + current.index + ')', parent).before($(current.item).clone(true));
                    $(current.item).remove();
                }
            }
            else {
                if (current.index < items.length) {
                    current.index++;
                    $('.wrap-row:eq(' + current.index + ')', parent).after($(current.item).clone(true));
                    $(current.item).remove();
                }
            }
        }
        else if ('removeNumItem' == action) {
            $(this).parent().remove();

            if ('' != defaultVal) {
                if (allDefault.length > 1) {
                    var array = [];
                    for (i = 0; i < allDefault.length; i++) {
                        if (allDefault[i] != val) {
                            array[array.length] = allDefault[i];
                        }
                    }
                    $('#multiple_default').val(array.join('|'));
                }
                else if (defaultVal == val) {
                    $('#multiple_default').val('');
                }
            }
        }
        intelli.displayUpdown();
    });

    intelli.displayUpdown();

    $('#toggle-pages').data('checked', true).click(function (e) {
        e.preventDefault();

        var checked = $(this).data('checked');

        $('input[type="checkbox"]:visible', '#js-row-pages-list').prop('checked', checked);
        $(this)
            .html('<i class="i-lightning"></i> ' + _t(checked ? 'select_none' : 'select_all'))
            .data('checked', !checked);
    });

    $('input[name="relation_type"]').on('change', function () {
        var $field = $('#regular_field');
        (this.value == 0) ? $field.show() : $field.hide();
    });

    $('input[name="required"]').on('change', function () {
        if (this.value == 1) {
            $('#js-row-validation-code').show();
            $('#js-row-plan-only').hide();
        }
        else {
            $('#js-row-validation-code').hide();
            $('#js-row-plan-only').show();
        }
    });

    // populate & activate field groups select
    $('#input-item').on('change', function () {
        var $fieldGroup = $('#input-fieldgroup'),
            $pagesList = $('#js-row-pages-list');

        $fieldGroup.empty().append('<option value="" selected>' + _t('_select_') + '</option>').prop('disabled', true);

        var itemName = $(this).val();

        if (itemName) {
            $('.checkbox', $pagesList).each(function () {
                $(this).data('item') == itemName ? $(this).show() : $(this).hide();
            });

            $pagesList.is(':visible') || $pagesList.slideDown();

            $('.js-dependent-fields-list').each(function () {
                var $this = $(this);
                ($this.data('item') == itemName) ? $this.show() : $this.hide();
            });

            // get item field groups
            $.get(intelli.config.admin_url + '/fields/read.json', {get: 'groups', item: itemName}, function (response) {
                if (response.length > 0) {
                    $.each(response, function (i, r) {
                        $fieldGroup.append($('<option>').val(r.id).text(r.title));
                    });

                    $fieldGroup.prop('disabled', false);
                }
            });
        }
        else {
            $pagesList.slideUp();
        }

        $('input[type="checkbox"]:not(:visible)', $pagesList).prop('checked', false);
    });

    // accordion for host fields
    $('.js-dependent-fields-list').on('click', 'a.list-group-item', function (e) {
        e.preventDefault();

        var $this = $(this);

        if (!$this.hasClass('active')) {
            $this.siblings('.active').removeClass('active').next().slideUp('fast', function () {
                $this.addClass('active').next().slideDown('fast');
            });
        }
    });


    // tree field
    var $tree = $('#input-nodes');

    $('.js-tree-action').on('click', function (e) {
        e.preventDefault();

        var tree = $tree.jstree(true),
            selection = tree.get_selected();

        switch ($(this).data('action')) {
            case 'create':
                selection = selection.length > 0 ? selection[0] : null;
                selection = tree.create_node(selection, {type: 'file'});
                if (selection) tree.edit(selection);
                break;
            case 'update':
                tree.edit(selection[0]);
                break;
            case 'delete':
                tree.delete_node(selection);
        }
    });

    var nodes = $('input[name="nodes"]').val();

    $tree.jstree(
        {
            core: {check_callback: true, data: ('' != nodes) ? JSON.parse(nodes) : null},
            plugins: ['dnd', 'contextmenu'],
            contextmenu: {
                items: function () {
                    return {
                        translate: {
                            label: _t('translate'),
                            action: intelli.translateTreeNode,
                            _disabled: 'edit' != $('#input-nodes').data('action')
                        }
                    };
                }
            }
        })
        .on('changed.jstree', function (e, data) {
            var actionButtons = $('.js-tree-action[data-action="update"], .js-tree-action[data-action="delete"]');
            data.selected.length > 0 ? actionButtons.removeClass('disabled') : actionButtons.addClass('disabled');
        })
        .on('create_node.jstree rename_node.jstree delete_node.jstree move_node.jstree copy_node.jstree', function (e, data) {
            $('input[name="nodes"]').val(JSON.stringify(data.instance.get_json('#', {flat: true})));
        });

    $('.js-cmd-toggle-image-setup').on('click', function (e) {
        e.preventDefault();
        $('#js-image-field-setup-by-imgtypes, #js-block-image-field-setup-by-settings').toggle();
        $('input[name="use_img_types"]').val($(this).data('type'));
    });

    $('.js-cmd-toggle-gallery-setup').on('click', function (e) {
        e.preventDefault();
        $('#js-gallery-field-setup-by-imgtypes, #js-block-gallery-field-setup-by-settings').toggle();
        $('input[name="pic_use_img_types"]').val($(this).data('type'));
    });

    var $imgFieldImageTypesSetup = $('#js-image-field-setup-by-imgtypes, #js-gallery-field-setup-by-imgtypes');

    $('input[type="checkbox"]', $imgFieldImageTypesSetup).on('change', function () {
        var $this = $(this);

        $this.closest('.image-type-control').find('.dropdown-toggle').prop('disabled', !$this.is(':checked'))
    });

    $('.js-set-image-type').click(function (e) {
        e.preventDefault();

        if ($(this).parent().hasClass('disabled')) {
            return;
        }

        var $this = $(this),
            type = $this.data('type'),
            $checkbox = $this.closest('.image-type-control').find('input[type="checkbox"]'),
            $label = $this.closest('.image-type-control').find('span[data-type="' + type + '"]');

        $checkbox.data('type', type);

        var inputName = ('primary' == type) ? 'imagetype_primary' : 'imagetype_thumbnail';

        if ($this.closest('#pictures').length) {
            inputName = 'pic_' + inputName;
        }

        var $input = $('input[name="' + inputName + '"]'),
            value = $checkbox.data('name');

        $input.val(value);
        $('input[value="' + value + '"]').not($input).val('');

        $this.parent().addClass('disabled').siblings().removeClass('disabled');
        $label.show().siblings('.label').hide();
        $this.closest('.image-type-control').siblings().find('.label[data-type="' + type + '"]').hide();
        $this.closest('.image-type-control').siblings().find('.js-set-image-type[data-type="' + type + '"]').parent().removeClass('disabled');
    });

    $('.js-cmd-configure-dependent-field').on('click', function(e){
        e.preventDefault();

        var temp,
            $div = $(this).parent().find('input:first'),
            $info = $(this).parent().find('.list:first');

        var tree = new Ext.tree.TreePanel(
        {
            height: 442,
            width: 375,
            useArrows: true,
            autoScroll: true,
            animate: true,
            enableDD: true,
            containerScroll: true,
            rootVisible: false,
            store: Ext.create('Ext.data.TreeStore', {proxy: {type: 'ajax', url: intelli.config.admin_url + '/fields/relations.json?item=' + $('#input-item').val()+'&ids=' + $div.val()}}),
            buttons: [{
                text: _t('reset'),
                handler: function(){
                    tree.getRootNode().eachChild(function(childNode){
                        childNode.set('checked', false);
                    });
                    $div.val('');
                    $info.html('');
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
                    var msg = [], title = [];
                    Ext.each(tree.getChecked(), function(node)
                    {
                        msg.push(node.data.id);
                        title.push(node.data.text);
                    });

                    $div.val(msg.join(','));
                    $info.html(title.join(', '));
                    win.close();
                }
            }]
        });

        var win = new Ext.Window(
        {
            title: _t('fields_list'),
            closable: true,
            width: 388,
            autoScroll: true,
            height: 486,
            plain: true,
            listeners: {
                beforeclose: function()
                {
                    var msg = [];
                    Ext.each(tree.getChecked(), function(node){msg.push(node.id);});
                    msg = msg.join(', ');

                    if ($div.val() != msg && temp)
                    {
                        Ext.Msg.show({
                            title: _t('save_changes')+'?',
                            msg: _t('closing_window_with_unsaved_changes'),
                            buttons: Ext.Msg.YESNO,
                            fn: function(btnID)
                            {
                                if (btnID == 'yes')
                                {
                                    $div.val(msg);
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
});