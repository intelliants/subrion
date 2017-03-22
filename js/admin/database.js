intelli.database = [];

$(function () {
    if ($('#query_out').text()) {
        setTimeout((function () {
            $('#query_out').height(window.screen.height - $('#query_out').offset().top - 300)
        }), 1000);
    }

    $('a', '.js-selecting').on('click', function (e) {
        e.preventDefault();

        var $ctl = $('#tbl option');

        switch ($(this).data('action')) {
            case 'select':
                $ctl.prop('selected', true);
                break;
            case 'drop':
                $ctl.prop('selected', false);
                break;
            case 'invert':
                $ctl.each(function (i, obj) {
                    $(obj).prop('selected', !$(obj).prop('selected'));
                });
        }
    });

    $('#save_file').on('change', function () {
        var obj = $('#js-save-options');
        (1 == $(this).val()) ? obj.show() : obj.hide();
    });

    $('#form-dump').on('submit', function (e) {
        if (!$('#sql_structure').prop('checked') && !$('#sql_data').prop('checked')) {
            e.preventDefault();
            intelli.admin.alert({title: _t('error'), type: 'error', msg: _t('export_not_checked')});
        }
    });

    $('#js-cmd-import').on('click', function () {
        if ($('#sql_file').val().length > 0) {
            $('#run_update').val('1');
            $('#update').submit();

            return true;
        }
        else {
            intelli.admin.alert(
                {
                    title: _t('error'),
                    type: 'error',
                    msg: _t('choose_import_file')
                });
        }

        return false;
    });

    $('#addTableButton').on('click', function (e) {
        e.preventDefault();
        addData('table');
    });

    $('#table').dblclick(function () {
        addData('table');
    }).click(function () {
        var table = $(this).val();
        if (table) {
            if (!intelli.database[table]) {
                $.ajax(
                    {
                        url: intelli.config.admin_url + '/database.json',
                        data: {table: table},
                        success: function (data) {
                            var fields = $('#field')[0];

                            intelli.database[table] = data;

                            fields.options.length = 0;
                            for (var i = 0; i < data.length; i++) {
                                fields.options[fields.options.length] = new Option(data[i], data[i]);
                            }
                            fields.options[0].selected = true;

                            // Show dropdown and the button
                            $('#field').parent().fadeIn();
                        }
                    });
            }
            else {
                var items = intelli.database[table];
                var fields = $('#field')[0];

                fields.options.length = 0;

                for (var i = 0; i < items.length; i++) {
                    fields.options[fields.options.length] = new Option(items[i], items[i]);
                }

                fields.options[0].selected = true;

                // Show dropdown and the button
                $('#field').parent().fadeIn();
            }
        }
    });

    $('#addFieldButton').click(function (e) {
        e.preventDefault();
        addData('field');
    });

    $('#field').dblclick(function () {
        addData('field');
    });

    $('a', '#query_history').on('click', function (e) {
        e.preventDefault();
        $('#query').val($(this).closest('li').find('span').text()).focus();
        $(window).scrollTop(0);
    });

    $('#clearButton').on('click', function () {
        Ext.Msg.confirm(_t('confirm'), _t('clear_confirm'), function (btn, text) {
            if (btn == 'yes') {
                $('#query').prop('value', 'SELECT * FROM ');
                $('#field').parent().fadeOut();
            }
        });

        return true;
    });

    function addData(item) {
        var value = $('#' + item).val();

        if (value) {
            addText('`' + value + '`');
        }
        else {
            intelli.admin.alert(
                {
                    title: _t('error'),
                    type: 'error',
                    msg: 'Please choose any ' + item + '.'
                });
        }
    }

    // add text to query
    function addText(text) {
        text = ' ' + text + ' ';
        var query = $('#query');

        if (document.selection) {
            query.focus();

            sel = document.selection.createRange();
            sel.text = text;
        }
        else if (query.selectionStart || query.selectionStart == '0') {
            var startPos = query.selectionStart;
            var endPos = query.selectionEnd;
            var flag = false;

            if (query.value.length == startPos) flag = true;
            query.value = query.value.substring(0, startPos) + text + query.value.substring(endPos, query.value.length);
            if (flag) query.selectionStart = query.value.length;
        }
        else {
            query.val(query.val() + text);
        }

        focusCampo('query');
    }

    // sql template click
    $('a', '#sqlButtons').on('click', function (e) {
        e.preventDefault();
        addText($(this).text());
    });

    // reset tables
    $('#js-reset-all, #js-reset').on('click', function (e) {
        if ($(this).attr('id') == 'js-reset-all') {
            $('input[name="options[]"]').each(function () {
                $(this).prop('checked', true);
            });
        }
        else {
            if (!$('input:checkbox[name="options[]"]:checked').length) {
                intelli.notifBox({msg: _t('reset_choose_table'), type: 'error'});

                return false;
            }
        }

        var $self = $(this);
        Ext.Msg.confirm(_t('confirm'), _t('clear_reset'), function (btn, text) {
            if ('yes' == btn) {
                $self.closest('form').submit();
            }
        });
    });

    if ($('#query').length > 0) {
        focusCampo('query');
    }
});

function focusCampo(id) {
    var inputField = document.getElementById(id);
    if (inputField != null && inputField.value.length != 0) {
        if (inputField.createTextRange) {
            var FieldRange = inputField.createTextRange();
            FieldRange.moveStart('character', inputField.value.length);
            FieldRange.collapse();
            FieldRange.select();
        } else if (inputField.selectionStart || inputField.selectionStart == '0') {
            var elemLen = inputField.value.length;
            inputField.selectionStart = elemLen;
            inputField.selectionEnd = elemLen;
            inputField.focus();
        }
    } else {
        inputField.focus();
    }
}