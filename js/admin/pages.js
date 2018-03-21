intelli.pagesUrl = intelli.config.admin_url + '/pages/';

function fillUrlBox() {
    var externalUrl = $('#unique').prop('checked');
    var customUrl = $('#input-custom-url').val();
    var name = $('#input-name').val();

    var params = {
        name: name,
        url: $('#input-alias').val(),
        parent: $('#input-parent').val(),
        ext: $('input[name="extension"]').val()
    };

    if (externalUrl && '' != customUrl) {
        sendQuery(params);
    }
    else if (!externalUrl && '' != name) {
        sendQuery(params);
    }
}

function sendQuery(params) {
    $.get(intelli.pagesUrl + 'url.json', params, function (response) {
        var $placeholder = $('.text-danger', '#js-alias-placeholder');
        if ('string' === typeof response.url) {
            $placeholder
                .text(response.url)
                .fadeIn();

            response.exists
                ? $placeholder.append('<div class="alert alert-info" id="js-exist-url-alert">' + _t('page_alias_exists') + '</div>')
                : $('#js-exist-url-alert').remove();
        }
        else {
            $placeholder.fadeOut();
        }
    });
}

Ext.onReady(function () {
    if (Ext.get('js-grid-placeholder')) {
        var grid = new IntelliGrid({
            columns: [
                'selection',
                'expander',
                {name: 'name', title: _t('name'), width: 150},
                {name: 'title', id: 'titleCol', title: _t('title'), width: 1, sortable: false},
                {name: 'url', title: _t('url'), width: 1},
                'status',
                {name: 'last_updated', title: _t('last_updated'), width: 170},
                'update',
                'delete'
            ],
            expanderTemplate: '{content}',
            fields: ['content', 'default'],
            statuses: ['active', 'inactive', 'draft'],
            texts: {
                delete_single: _t('are_you_sure_to_delete_this_page'),
                delete_multiple: _t('are_you_sure_to_delete_selected_pages')
            }
        }, false);

        grid.toolbar = new Ext.Toolbar({
            items: [
                {
                    emptyText: _t('name'),
                    xtype: 'textfield',
                    name: 'name',
                    listeners: intelli.gridHelper.listener.specialKey
                }, {
                    emptyText: _t('keywords'),
                    xtype: 'textfield',
                    name: 'text',
                    listeners: intelli.gridHelper.listener.specialKey
                }, {
                    emptyText: _t('module'),
                    xtype: 'combo',
                    typeAhead: true,
                    editable: false,
                    store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/actions/options/module.json'),
                    displayField: 'title',
                    name: 'module',
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

        grid.grid.getView().getRowClass = function (record, rowIndex, rowParams, store) {
            if (1 == record.get('default')) {
                return 'grid-row-customly-highlighted';
            }

            return '';
        }
    }
});

$(function () {
    $('#js-delete-page').on('click', function () {
        Ext.Msg.confirm(_t('confirm'), _t('are_you_sure_to_delete_this_page'), function (btn, text) {
            if (btn === 'yes') {
                $.ajax(
                    {
                        data: {'id[]': $('input[name="id"]').val()},
                        dataType: 'json',
                        failure: function () {
                            Ext.MessageBox.alert(_t('error'));
                        },
                        type: 'POST',
                        url: intelli.pagesUrl + 'delete.json',
                        success: function (response) {
                            if ('boolean' == typeof response.result && response.result) {
                                intelli.notifFloatBox({
                                    msg: response.message,
                                    type: response.result ? 'success' : 'error'
                                });
                                document.location = intelli.pagesUrl;
                            }
                        }
                    });
            }
        });
    });

    $('input[name="preview"]').on('click', function () {
        $('#page_form').attr('target', '_blank');
    });

    $('input[name="save"]').on('click', function (e) {
        $('#page_form').removeAttr('target');
    });

    $('input[name="unique"]').on('change', function () {
        var isRemoteUrl = (1 == this.value);

        if ($.trim($('#input-name').val()).length > 0) {
            fillUrlBox();
        }

        if (isRemoteUrl) {
            $('.js-local-url-field').hide();
            $('.js-page-content-field').hide();
            $('#js-field-remote-url').show();
        }
        else {
            $('.js-local-url-field').show();
            $('.js-page-content-field').show();
            $('#js-field-remote-url').hide();
        }
    }).trigger('change');

    // Page custom template
    $('input[name="custom_tpl"]').on('change', function () {
        $obj = $('#js-field-tpl-filename');

        (1 == this.value) ? $obj.show() : $obj.hide();
    }).trigger('change');

    $('#input-name, #input-alias').on('blur', fillUrlBox);
    $('#input-parent').on('change', fillUrlBox);

    // Init CKEDITORs
    $('textarea[name^="content"]').each(function () {
        var $this = $(this),
            lngCode = $this.data('language');

        CKEDITOR.instances['content[' + lngCode + ']']
        || intelli.ckeditor('content[' + lngCode + ']', {toolbar: 'extended'});
    });

    // page extension dropdown
    $('a', '#js-page-extension-list').on('click', function (e) {
        e.preventDefault();

        var text = $(this).text();
        var value = $(this).data('extension');

        $('input[name="extension"]').val(value);
        $(this).parent().addClass('active').siblings().removeClass('active');
        $(this).closest('div').find('button').html(text + ' <span class="caret"></span>');

        fillUrlBox();
    });
});