<?php if ($this->errorCode): ?>
    <?php if ('authorization' == $this->errorCode): ?>
        <div class="alert alert-danger">Please log in as an administrator to proceed.</div>
    <?php elseif ('version' == $this->errorCode): ?>
        <div class="alert alert-warning">Incorrect upgrade version specified.</div>
    <?php elseif ('remote' == $this->errorCode): ?>
        <div class="alert alert-danger">Could not continue.</div>
        <p>The patch file could not be downloaded because of server settings.</p>
        <p>You have to modify the server settings in order to be able to go ahead.</p>

        <h3>Settings to be modified</h3>
        <ul>
            <li>PHP option <em>allow_url_fopen</em></li>
            <li>PHP extension <em>cUrl</em></li>
        </ul>
    <?php endif ?>

    <div class="form-actions">
        <a href="<?= URL_INSTALL ?><?= $this->module ?>/" class="btn btn-lg btn-primary"><i class="i-loop"></i> Refresh</a>
        <?php if (!$this->errorCode): ?>
        <a class="pull-right btn btn-primary btn-lg" href="<?= URL_INSTALL ?><?= $this->module ?>/rollback/"><i class="i-box-remove"></i> Rollback upgrade</a>
        <?php endif ?>
    </div>
<?php else: ?>
    <div class="widget widget-default">
        <div class="widget-content">
            <p>Getting ready to download the patch file from the Intelliants servers...</p>
            <p>Click <code>&laquo;Next&raquo;</code> button to continue.</p>
            <p><a href="#changelog-details" data-toggle="modal" class="btn btn-default"><i class="i-list"></i> Show changelog details</a></p>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= URL_INSTALL ?><?= $this->module ?>/download/" class="btn btn-lg btn-primary js-btn-download">Next <i class="icon-arrow-right icon-white"></i></a>
        <a class="pull-right btn btn-primary btn-lg" href="<?= URL_INSTALL ?><?= $this->module ?>/rollback/"><i class="i-box-remove"></i> Rollback upgrade</a>
    </div>

    <div class="modal fade" id="changelog-details">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title">Changelog Details</h4>
                </div>
                <div class="modal-body">
                    <div class="alert alert-block">Could not get changelog details from Intelliants.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal-dialog -->
    </div><!-- /.modal -->

    <script type="text/javascript">
    $(function() {
        $('#changelog-details').appendTo('body');
        $.getJSON('https://tools.subrion.org/changelog.json?fn=?', {version: '<?= $this->version ?>'}, function(response) {
            $('.modal-body:first', '#changelog-details').html(response.html);
        });

        $('.js-btn-download').on('click', function(e) {
            var $this = $(this);
            var spinner = '<div class="vm-spinner"><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div></div>';

            if (!$this.hasClass('loading')) {
                $this.addClass('disabled loading').html(spinner);
            }
        });
    });
    </script>
<?php endif ?>