$(function () {
    var tagsWindow = new Ext.Window(
        {
            title: _t('email_templates_tags'),
            layout: 'fit',
            modal: false,
            closeAction: 'hide',
            contentEl: 'template-tags',
            buttons: [
                {
                    text: _t('close'),
                    handler: function () {
                        tagsWindow.hide();
                    }
                }]
        });

    $('#js-view-tags').on('click', function (e) {
        e.preventDefault();
        tagsWindow.show();
    });

    $('#input-id').on('change', function (e) {
        var id = $(this).val();
        var $switchers = $('#enable_sending, #use_signature'),
            $patterns = $('#js-patterns'),
            $subject = $('#input-subject');

        // hide if none selected
        if (!id) {
            $subject.val('').prop('disabled', true);
            CKEDITOR.instances.body.setData('');
            $switchers.hide();
            $patterns.hide();
            $('button[type="submit"]', '#js-email-template-form').prop('disabled', true);

            return;
        }

        // get actual values
        $.get(window.location.href + 'read.json', {id: id}, function (response) {
                $subject.val(response.subject);
                CKEDITOR.instances.body.setData(response.body);

                $('#enable_sending').bootstrapSwitch('setState', response.config);
                $('#use_signature').bootstrapSwitch('setState', response.signature);

                if ('undefined' != typeof response.patterns) {
                    var i, html = '';
                    for (i in response.patterns) {
                        if (i != '') html += '<strong>{%' + i.toUpperCase() + '%}</strong>' + ' — ' + response.patterns[i] + '<br>';
                    }

                    $('div:first', $patterns).html(html);
                    $patterns.show()
                }
                else {
                    $patterns.hide();
                    $('div:first', $patterns).html('');
                }
            },
            'json');

        $switchers.show();
        $('#input-subject, #js-email-template-form button[type="submit"]').prop('disabled', false);
    });

    $('#js-email-template-form').on('submit', function (e) {
        e.preventDefault();

        if ('object' == typeof CKEDITOR.instances.body) {
            CKEDITOR.instances.body.updateElement();
        }

        var data = {};
        $(this).serializeArray().map(function (x) {
            data[x.name] = x.value;
        });

        if ('' == data.id) {
            return;
        }

        $.post(window.location.href + 'edit.json', data, function (response) {
            if (response.result) {
                intelli.notifFloatBox({msg: _t('saved'), type: 'success', autohide: true, pause: 1500});
            }
        });
    });

    $('ul.js-tags a').on('click', function (e) {
        e.preventDefault();
        CKEDITOR.instances.body.insertHtml($(this).text());
    });
});