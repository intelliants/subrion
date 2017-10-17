<div class="widget widget-default">
    <div class="widget-header">
        Installation log
    </div>
    <div class="widget-content">
        <div class="row">
            <div class="col-lg-5">
                    <div>
                        <h4>Database Installation</h4>
                        <?php if ($this->message): ?>
                            <div class="alert alert-danger">Error during MySQL queries execution:</div>
                            <?= $this->message ?>
                            A copy of the configuration file will be downloaded
                            to your computer when you click the button 'Download config file'.
                            You should upload this file to the same directory where you have Subrion CMS.
                            Once this is done you should log in using the admin credentials you provided on the previous form and
                            configure the software according to your needs.
                        <?php else: ?>
                            <div class="alert alert-success">Successful.</div>
                        <?php endif ?>
                    </div>
                    <div>
                        <h4>Configuration File</h4>
                        <?php if ($this->description): ?>
                            <div class="alert alert-danger">Error during configuration write:</div>
                            <?= $this->description ?><br />
                            You MUST save config.inc.php file to your local PC and then upload to Subrion CMS includes directory.
                        <?php else: ?>
                            <div class="alert alert-success">Configuration file has been saved. Please change permissions to unwritable for secure reason!</div>
                        <?php endif ?>
                        <form method="post" action="<?= URL_INSTALL ?><?= $this->module ?>/download/">
                            <input type="hidden" value="<?= iaHelper::_html($this->config) ?>" name="config_content">
                            <button type="submit" class="btn btn-success btn-plain">Download config file</button>
                            &nbsp;&nbsp;or&nbsp;&nbsp;
                            <a href="javascript:void(0);" onclick="if (document.getElementById('file_content').style.display=='block') { document.getElementById('file_content').style.display='none';} else {document.getElementById('file_content').style.display='block'}" class="btn btn-default">View config file</a>
                        </form>
                    </div>
            </div>
            <div class="col-lg-7">
                <div style="<?php if (empty($this->description)): ?>display: none; '<?php endif ?>" id="file_content">
                    <div class="box-simple"><?= highlight_string($this->config, true) ?></div>
                    <?php if (empty($this->description)): ?>
                        <p class="help-block">You can also copy the content to that file.</p>
                    <?php endif ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="widget widget-default">
    <div class="widget-header">
        Installation Folder
    </div>
    <div class="widget-content">
        <p>For safety purposes, please remove the file <code>/install/modules/module.install.php</code></p>
    </div>
</div>

<div class="form-actions">
    <a href="<?= URL_INSTALL ?><?= $this->module ?>/configuration/" class="btn btn-lg btn-default"><i class="i-chevron-left"></i> Back</a>
    <a href="<?= URL_INSTALL ?><?= $this->module ?>/plugins/" class="btn btn-lg btn-info"><i class="i-lab"></i> Install Plugins</a>
    <a href="<?= URL_ADMIN_PANEL ?>" class="btn btn-lg btn-primary"><i class="i-gauge"></i> to Admin panel</a>
    <a href="<?= URL_HOME ?>" class="btn btn-lg btn-primary"><i class="i-screen"></i> to Home page</a>
</div>