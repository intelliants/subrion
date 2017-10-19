<div class="row">
    <label class="col col-lg-2 control-label">&nbsp;</label>
    <div class="col col-lg-8">
        <button type="button" class="btn btn-primary btn-sm" id="js-cmd-send-test-email" data-loading-test="{lang key='sending'}">{lang key='send_test_email'}</button>
        <span class="label label-info" style="display: none">{lang key='save_changes_to_send_test_email'}</span>
        <p class="help-block">{lang key='send_test_email_note'}</p>
    </div>
</div>
{ia_add_js}
$(function() {
    var $btn = $('#js-cmd-send-test-email'),
        $form = $btn.closest('form');

    intelli.dataHash = $form.serialize();

    $('input, textarea, select', $form).change(function() {
        var changed = intelli.dataHash !== $form.serialize(),
            $msg = $btn.next();

        $btn.prop('disabled', changed);
        changed ? $msg.fadeIn() : $msg.fadeOut();
    });

    $btn.on('click', function() {
        $btn.button('loading');

        intelli.post(intelli.config.admin_url + '/actions.json', { action: 'send-test-email' }, function(response) {
            $btn.button('reset');
            intelli.notifFloatBox({ msg: response.message, type: response.result ? 'success' : 'error', autohide: true });
        });
    });
});
{/ia_add_js}