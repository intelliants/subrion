function dialog_package(type, module, url) {
    intelli.config.default_package = $('#js-default-package-value').val();

    var defaultPackage =
        '<div class="alert alert-info">' + _t('root_old').replace(/:name/gi, intelli.config.default_package) + '</div>' +
        '<p><code>' + intelli.config.ia_url + '</code> <input type="text" name="url[0]" class="common"> <code>/</code></p>';
    var formText =
        '<div class="url_type">' +
        '<label for="subdomain_type"><input type="radio" value="1" name="type" id="subdomain_type"> ' + _t('subdomain_title') + '</label>' +
        '<div class="url_type_info"><p>' + _t('subdomain_about') + '</p><code>http://</code> <input type="text" value="' + module + '" name="url[1]" class="common"> <code>.' + location.hostname + '/</code></div>' +
        '</div>' +
        '<div class="url_type">' +
        '<label for="subdirectory_type"><input type="radio" value="2" name="type"' + (intelli.config.default_package ? ' checked' : '') + ' id="subdirectory_type"> ' + _t('subdirectory_title') + '</label>' +
        '<div class="url_type_info"><p>' + _t('subdirectory_about') + '</p><code>' + intelli.config.ia_url + '</code> <input type="text" value="' + module + '" name="url[2]" class="common"> <code>/</code></div>' +
        '</div>';

    if (intelli.setupDialog) {
        intelli.setupDialog.remove();
    }
    var html = '';

    if ('install' == type) {
        html = '<div class="url_type"><label for="root_type"><input type="radio" value="0" name="type" id="root_type"'
            + (intelli.config.default_package ? '' : ' checked') + '> ' + _t('root_title') + '</label><div class="url_type_info"><p>' + _t('root_about') + '</p>'
            + (intelli.config.default_package ? defaultPackage : '') +
            '</div></div>' + formText;
    }
    else if ('set_default' == type) {
        if (intelli.config.default_package != '') {
            html = '<div class="url_type">' + defaultPackage + '</div>';
        }
        else {
            return false;
        }
    }
    else if ('reset' == type) {
        html = '<div class="url_type">' + _t('reset_default_package') + '</div>' + formText;
    }
    html = '<form action="' + url + '" id="package_form">' + html + '</form>';

    intelli.setupDialog = new Ext.Window(
        {
            title: _t('module_installation'),
            closable: true,
            html: html,
            maxWidth: 600,
            bodyPadding: 10,
            autoScroll: true,
            buttons: [
                {
                    text: _t(type),
                    handler: function () {
                        $('#package_form').submit();
                    }
                }, {
                    text: _t('cancel'),
                    handler: function () {
                        intelli.setupDialog.hide();
                    }
                }]
        }).show();

    $('input[name="url[2]"]').on('change', function () {
        $('#subdirectory_type').prop('checked', true);
    });

    $('input[type="radio"]:checked', '#package_form').parent().addClass('selected');
    $('input[type="radio"]', '#package_form').on('change', function () {
        if ($(this).is(':checked')) {
            $('input[type="radio"]', '#package_form').parent().removeClass('selected');
            $('input[type="radio"]:checked', '#package_form').parent().addClass('selected');
        }
    });
}
function setDefault(item) {
    dialog_package('set_default', item, intelli.config.default_package);
}
function resetUrl(item, packageName) {
    dialog_package('reset', item, packageName);
}

intelli.modules = {
    url: intelli.config.admin_url + '/modules/',
    failure: function () {
        intelli.notifFloatBox({msg: _t('error_saving_changes'), type: 'error', autohide: true});
    },
    refresh: function (response) {
        intelli.notifFloatBox({msg: response.message, type: response.result ? 'success' : 'error', autohide: true});

        if (response.result) {
            synchronizeAdminMenu('plugins', response.groups);
        }
    }
};

Ext.onReady(function () {

    $('.js-install').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            module = $this.data('module'),
            type = $this.data('type'),
            remote = $this.data['remote'],
            url = $this.attr('href');

        if ('packages' == type) {
            dialog_package('install', module, url);

            return;
        }
        else if ('templates' == type) {
            Ext.Msg.show({
                title: _t('confirm'),
                msg: _t('are_you_sure_install_module'),
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function (btn) {
                    if ('yes' != btn) return;

                    document.location = url;
                }
            });

            return;
        }

        $.ajax({
            data: {name: module, type: type, remote: remote},
            failure: intelli.modules.failure,
            type: 'POST',
            url: intelli.modules.url + type + '/' + module + '/install.json',
            success: function (response) {
                intelli.modules.refresh(response);

                if (response.result) {
                    var installedStatusHtml = '<span class="card__actions__status"><span class="fa fa-check"></span> ' + _t('installed') + '</span>';

                    $this.closest('.card').addClass('card--active');
                    $this.replaceWith(installedStatusHtml);
                }
            }
        });
    });

    $('.js-reinstall').on('click', function (e) {
        e.preventDefault();

        var $this = $(this),
            module = $this.data('module'),
            type = $this.data('type'),
            url = $this.attr('href');

        Ext.Msg.show(
            {
                title: _t('confirm'),
                msg: _t('are_you_sure_reinstall_module'),
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function (btn) {
                    if ('yes' != btn) {
                        return;
                    }

                    if ('templates' == type) {
                        document.location = url;
                        return;
                    }

                    $.ajax(
                        {
                            data: {name: module},
                            failure: intelli.modules.failure,
                            type: 'POST',
                            url: intelli.modules.url + type + '/' + module + '/reinstall.json',
                            success: intelli.modules.refresh
                        });
                }
            });
    });

    $('.js-uninstall').click(function (e) {
        e.preventDefault();

        var $this = $(this),
            module = $this.data('module'),
            type = $this.data('type'),
            remote = $this.data['remote'],
            url = $this.attr('href');

        if ('packages' == type) {
            Ext.Msg.show({
                title: _t('confirm'),
                msg: _t('are_you_sure_to_uninstall_selected_package'),
                buttons: Ext.Msg.YESNO,
                icon: Ext.Msg.QUESTION,
                fn: function (btn) {
                    if ('yes' == btn) {
                        document.location = url;
                    }
                }
            });

            return;
        }
        else {
            Ext.Msg.show(
                {
                    title: _t('confirm'),
                    msg: _t('are_you_sure_to_uninstall_selected_plugin'),
                    buttons: Ext.Msg.YESNO,
                    icon: Ext.Msg.QUESTION,
                    fn: function (btn) {
                        if ('yes' != btn) {
                            return;
                        }

                        $.ajax(
                            {
                                data: {name: module},
                                failure: intelli.modules.failure,
                                type: 'POST',
                                url: intelli.modules.url + type + '/' + module + '/uninstall.json',
                                success: function (response) {
                                    intelli.modules.refresh(response);

                                    if (response.result) {
                                        var installBtnHtml = '<a href="' + intelli.modules.url + type + '/' + module + '/install/" class="btn btn-success btn-xs pull-right js-install" data-module="' + module + '" data-type="' + type + '" data-remote="' + remote + '">' + _t('install') + '</a>';

                                        $this.closest('.card').removeClass('card--active')
                                            .find('.card__actions__status').replaceWith(installBtnHtml);

                                        // hide buttons
                                        $this.closest('ul').find('.js-reinstall').hide();
                                        $this.closest('li').hide();
                                    }
                                }
                            });
                    }
                });
        }
    });

    $('.js-upgrade').on('click', function (e) {
        e.preventDefault();

        var module = $(this).data('module');

        $.ajax({
            data: {action: 'install', name: record.get('file')},
            failure: intelli.plugins.failure,
            type: 'POST',
            url: intelli.plugins.url + 'install.json',
            success: intelli.plugins.refresh
        });
    });

    $('.js-readme').on('click', function (e) {
        e.preventDefault();

        var module = $(this).data('module');

        Ext.Ajax.request(
            {
                url: window.location.href + 'documentation.json',
                method: 'GET',
                params: {name: module},
                failure: function () {
                    Ext.MessageBox.alert(_t('error_while_doc_tabs'));
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    var tabs = response.tabs;
                    var info = response.info;

                    if (null != tabs) {
                        var packageTabs = new Ext.TabPanel(
                            {
                                region: 'center',
                                bodyStyle: 'padding: 5px;',
                                activeTab: 0,
                                defaults: {autoScroll: true},
                                items: tabs
                            });

                        var packageInfo = new Ext.Panel(
                            {
                                region: 'east',
                                split: true,
                                minWidth: 200,
                                collapsible: true,
                                html: info,
                                bodyStyle: 'padding: 5px;'
                            });

                        var win = new Ext.Window(
                            {
                                title: _t('module_documentation'),
                                closable: true,
                                width: 800,
                                height: 550,
                                border: false,
                                plain: true,
                                layout: 'border',
                                items: [packageTabs, packageInfo]
                            });

                        win.show();
                    }
                    else {
                        Ext.Msg.show(
                            {
                                title: _t('error'),
                                msg: _t('documentation_not_available'),
                                buttons: Ext.Msg.OK,
                                icon: Ext.Msg.ERROR
                            });
                    }
                }
            });
    });

    // Live filter on Modules pages
    $('.js-filter-modules-text').on('keyup', function () {
        var $this = $(this),
            text = $this.val(),
            $collection = $('.card--module');

        if (text != '') {
            var rx = RegExp(text, 'i')

            $collection.each(function () {
                var $item = $(this),
                    name = String($('.card__item__body > h4', $item).text());

                (name.match(rx) !== null) ? $item.show() : $item.hide();
            });
        } else {
            $collection.show();
        }
    });

    $('.js-filter-modules').click(function (e) {
        e.preventDefault();

        var $this = $(this),
            type = $this.data('type'),
            filtered = $this.data('filtered');

        if (filtered == 'no') {
            $this.data('filtered', 'yes');
            $('.card--' + type).hide();
        } else {
            $this.data('filtered', 'no');
            $('.card--' + type).show();
        }

        $('.fa', $this).toggleClass('fa-circle-thin fa-check');
    });

    $('.js-filter-modules-reset').click(function (e) {
        e.preventDefault();

        $('.js-filter-modules-text').val('').trigger('keyup');
        $('.js-filter-modules').data('filtered', 'no')
            .find('.fa')
            .removeClass('fa-circle-thin')
            .addClass('fa-check');
    });
});

var synchronizeAdminMenu = function (currentPage, extensionGroups) {
    currentPage = currentPage || 'plugins';

    $.ajax({
        data: {action: 'menu', page: currentPage},
        success: function (response) {
            var $menuSection = $('#panel-center'),
                $menus = $(response.menus);

            if (typeof extensionGroups != 'undefined') {
                $.each(extensionGroups, function (i, val) {
                    $('#menu-section-' + val + ' a').append('<span class="menu-updated animated bounceIn"></span>');
                });
            }

            $('ul', $menuSection).remove();
            $menus.appendTo($menuSection);
        },
        type: 'POST',
        url: intelli.config.admin_url + '/index/read.json'
    });
};