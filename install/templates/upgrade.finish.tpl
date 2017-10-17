<?php if (!isset($this->message)): ?>
    <div class="alert alert-success">Upgrade completed.</div>

    <div class="widget widget-default">
        <div class="widget-content">
            <p>Installation log has been also saved to <em>uploads</em> folder.</p>
            <div id="upgrade-log" class="box box-simple"><?= $this->log ?></div>
            <div id="upgrade-log-legend">
                <h5>Legend</h5>
                <p><span class="label label-success">SUCCESS</span> <b>File successfully written</b> &mdash; <i>file has been overwritten by the file that comes in the patch</i>.</p>
                <p><span class="label label-danger">ERROR</span> <b>The checksum is not equal</b> &mdash; <i>file md5() hash checksum does not match the default one that comes with Subrion software. It's possible this file was modified on your server</i>.</p>
            </div>
        </div>
    </div>
<?php endif ?>

<div class="form-actions">
    <a href="<?= URL_HOME . $this->adminPath ?>/" class="btn btn-lg btn-primary"><i class="i-gauge"></i> to Admin panel</a>
    <a href="<?= URL_HOME ?>" class="btn btn-lg btn-primary"><i class="i-screen"></i> to Home page</a>
</div>