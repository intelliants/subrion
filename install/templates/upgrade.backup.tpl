<form method="get" action="<?= URL_INSTALL ?><?= $this->module ?>/finish/">
    <div class="widget widget-default">
        <div class="widget-content">
            <p>We are pretty sure that everything will be fine. Whatever, it's good to have a backup just in case.</p>
            <p>Script's files are to be zipped into single one. Complete DB backup to be included into as well.</p>
            <p>
                <a class="btn btn-default" id="js-cmd-backup" href="#"><i class="i-box-add"></i> Create backup</a> &nbsp;
                <small id="js-notification-area">Backup is to be saved to <em><?= $this->backupFile ?></em></small>.
            </p>
            <hr>
            <p>Sure, you may skip this step, just consider the options below and click the <code>&laquo;Perform Upgrade&raquo;</code> button then.</p>
        </div>
    </div>

    <div class="widget widget-default">
        <div class="widget-header">Advanced upgrade options</div>
        <div class="widget-content">
            <ul class="list-unstyled upgrade-options">
                <li>
                    <label for="option-force" class="checkbox">
                        <input type="checkbox" name="options[]" id="option-force" value="force-mode"> Force file re-upload
                    </label>
                    <p class="help-block">During the upgrade process, the script detects modified files. In regular mode, script will leave them untouched. This option forces the script to rewrite files differ from the original ones. If you applied custom changes to your script, then enabling this feature will remove them!</p>
                </li>
            </ul>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-lg btn-primary"><i class="i-settings"></i> Perform Upgrade</button>
    </div>
</form>

<script type="text/javascript">
$(function() {
    var resetStyles;

    $('#js-cmd-backup').on('click', function(e) {
        e.preventDefault();

        var $notificationArea = $('#js-notification-area');
        var $btnStart = $(this);

        $notificationArea.data('color') || $notificationArea.data('color', $notificationArea.css('color'));
        if (resetStyles) {
            $notificationArea.css('color', $notificationArea.data('color'));
        }

        $btnStart.addClass('disabled');
        $notificationArea
            .css('font-weight', 'bold')
            .html('Started to create backup... <img src="<?= URL_HOME ?>templates/_common/img/preloader.gif"> This may take a while depending on your script\'s content.');

        $.ajax({
            type: 'POST',
            url: window.location.href,
            dataType: 'html',
            success: function(response)
            {
                if ('success' == response) {
                    $notificationArea.css('color', 'green').text('Completed.');
                } else {
                    $notificationArea.css('color', 'red').text(response);
                    $btnStart.removeClass('disabled');
                    resetStyles = true;
                }
            }
        });
    });
});
</script>