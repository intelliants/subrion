$(function () {
    var tagsWindow = new Ext.Window({
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

    $('#js-view-tags').on('click', function(e) {
        e.preventDefault();
        tagsWindow.show();
    });

    $('#input-name').on('change', function() {
        var name = $(this).val();

        var $active = $('#input-active'),
            $patterns = $('#js-patterns'),
            $subject = $('#input-subject');

        // hide if none selected
        if (!name) {
            $subject.val('').prop('disabled', true);
            CKEDITOR.instances.body.setData('');
            $active.hide();
            $patterns.hide();
            $('button[type="submit"]', '#js-email-template-form').prop('disabled', true);

            return;
        }

        // get actual values
        $.get(window.location.href + 'read.json', {name: name}, function(response) {
            $subject.val(response.subject);
            CKEDITOR.instances.body.setData(response.body);

            $('#input-active').bootstrapSwitch('setState', response.config);

            if ('undefined' !== typeof response.variables) {
                var i, html = '';
                for (i in response.variables) {
                    if (i !== '') html += '<strong>{$' + i + '}</strong>' + ' — ' + response.variables[i] + '<br>';
                }

                $('div:first', $patterns).html(html);
                $patterns.show()
            }
            else {
                $('div:first', $patterns).html('');
                $patterns.hide();
            }
        },
        'json');

        $active.show();

        $('#input-subject, #js-email-template-form button[type="submit"]').prop('disabled', false);
    });

    $('#js-email-template-form').on('submit', function (e) {
        e.preventDefault();

        if ('object' === typeof CKEDITOR.instances.body) {
            CKEDITOR.instances.body.updateElement();
        }

        var data = {};
        $(this).serializeArray().map(function (x) {
            data[x.name] = x.value;
        });

        if (!data.name) {
            return;
        }

        $.post(window.location.href + 'edit.json', data, function(response) {
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