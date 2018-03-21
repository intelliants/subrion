intelli.admin = function () {
    /*
     * Constants
     */

    /**
     * AJAX loader box id
     * @type String
     */
    var BOX_AJAX_ID = 'js-ajax-loader';

    // use on login page
    if (typeof Ext != 'undefined') {
        Ext.Ajax.defaultHeaders = {'X-FlagToPreventCSRF': 'using ExtJS'};
    }

    $.ajaxSetup(
        {
            global: true,
            beforeSend: function (xhr) {
                xhr.setRequestHeader('X-FlagToPreventCSRF', 'using jQuery');
            }
        });

    function ajaxLoader() {
        /* show and hide ajax loader box */
        var loaderBox = Ext.get(BOX_AJAX_ID);

        Ext.Ajax.on('beforerequest', loaderBox.show);
        Ext.Ajax.on('requestcomplete', function () {
            loaderBox.hide({duration: '1'});
        });

        $('#' + BOX_AJAX_ID)
            .ajaxStart(function () {
                $(this).fadeIn('1000');
            })
            .ajaxStop(function () {
                $(this).fadeOut('1000');
            });

        return loaderBox;
    }

    return {
        /**
         * Assign event for displaying AJAX actions
         *
         * @return object of box
         */
        initAjaxLoader: ajaxLoader,
        /**
         * Show or hide element
         *
         * @opt array array of options
         * @el string id of element
         * @action string the action (show|hide|auto)
         *
         * @return object of element
         */
        display: function (opt) {
            if (!opt.el) {
                return false;
            }

            var obj = ('string' == typeof opt.el) ? Ext.get(opt.el) : opt.el;
            var act = opt.action || 'auto';

            if ('auto' == act) {
                act = obj.isVisible() ? 'hide' : 'show';
            }

            obj[act]();

            return obj;
        },

        /**
         * Show alert notification message
         *
         * @opt array array of options
         * @msg string the message
         * @title string the title of box
         * @type string the type of message
         *
         * @return void
         */
        alert: function (opt) {
            if (Ext.isEmpty(opt.msg)) {
                return false;
            }

            opt.title = (Ext.isEmpty(opt.title)) ? 'Alert Message' : opt.title;
            opt.type = intelli.inArray(opt.type, ['error', 'notif']) ? opt.type : 'notif';

            var icon = ('error' == opt.type) ? Ext.MessageBox.ERROR : Ext.MessageBox.WARNING;

            Ext.Msg.show({title: opt.title, msg: opt.msg, buttons: Ext.Msg.OK, icon: icon});
        },

        removeFile: function (file, link, item, field, itemid) {
            Ext.Msg.confirm(_t('confirm'), _t('sure_rm_file'), function (btn, text) {
                if ('yes' === btn) {
                    intelli.post(intelli.config.admin_url + '/actions/read.json',
                        {action: 'delete-file', item: item, field: field, file: file, itemid: itemid},
                        function (data) {
                            if ('boolean' === typeof data.error && !data.error) {
                                if ($(link).closest('.input-group').hasClass('thumbnail-single')) {
                                    $('#field_' + field).closest('.input-group').find('input[type="text"]').attr('placeholder', _t('file_click_to_upload'));
                                    $(link).closest('.input-group').remove();
                                }

                                $(link).closest('.file-upload, .uploads-list-item').remove();

                                var counter = $('#' + field);

                                try {
                                    counter.val(parseInt(counter.val()) + 1);
                                    if (counter.val() > 0) {
                                        $('.file-upload:hidden', '#upload-group-' + field).show();
                                    }
                                }
                                catch (e) {
                                }

                                intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
                            } else {
                                intelli.notifFloatBox({msg: data.message, type: response.result ? 'success' : 'error', autohide: true});
                            }
                        }
                    );
                }
            });

            return false;
        }
    }
}();

intelli.admin.lang = {};