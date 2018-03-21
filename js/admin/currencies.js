$(function() {
    $('.js-cmd-delete').on('click', function(e) {
        e.preventDefault();

        var url = $(this).data('href');

        Ext.Msg.show({
            title: _t('confirm'),
            msg: _t('are_you_sure_to_delete_currency'),
            buttons: Ext.Msg.YESNO,
            icon: Ext.Msg.QUESTION,
            fn: function (btn) {
                if ('yes' === btn) {
                    window.location.href = url;
                }
            }
        });
    });

    intelli.sortable('js-currencies-list', {
        handle: '.uploads-list-item__drag-handle',
        animation: 150,
        onEnd: function() {
            var codes = $('.js-currency-code').map(function() {
                return $(this).text();
            }).get();

            intelli.post(window.location.href + 'sorting.json', {codes: codes}, function(response) {
                intelli.notifFloatBox({
                    msg: response.message,
                    type: response.result ? 'success' : 'error',
                    autohide: true,
                    pause: 1500
                });
            });
        }
    });
})