<form action="<?= URL_INSTALL ?><?= $this->module ?>/configuration/" method="post" class="sap-form form-horizontal">

    <div class="widget widget-default">
        <div class="widget-header">
            General
        </div>
        <div class="widget-content">
            <div class="row">
                <div class="col col-lg-5">
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Installation URL:</label>
                        <div class="col-lg-8">
                            <input type="text" value="<?= URL_HOME ?>" disabled="disabled" class="disabled" />
                        </div>
                    </div>
                    <div class="form-group" id="err_template">
                        <label class="col-lg-4 control-label">Default Template:</label>
                        <div class="col-lg-8">
                            <select name="tmpl" id="tmpl"<?php if (count($this->templates) == 1): ?> readonly<?php endif ?>>
                                <?php foreach ($this->templates as $entry): ?>
                                <option value="<?= $entry ?>"<?php if ($this->template == $entry): ?> selected="selected"<?php endif ?>><?= ucfirst($entry) ?></option>
                                <?php endforeach ?>
                            </select>
                            <p class="help-block" style="display: none;">Please choose another template.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">Debug Mode:</label>
                        <div class="col-lg-8">
                            <select name="debug">
                                <option value="0"<?php if (iaHelper::getPost('debug', 0) == 0): ?> selected="selected"<?php endif ?>>Do not display errors</option>
                                <option value="1"<?php if (iaHelper::getPost('debug', 0) == 1): ?> selected="selected"<?php endif ?>>Display errors in a special block</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="col col-lg-4">
                    <div class="widget-annotation">
                        <p>Configure correct paths and URLs to your Subrion CMS.</p>
                        <p>Please select a template from a list of available templates uploaded to your templates directory.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="widget widget-default">
        <div class="widget-header">
            Database
        </div>
        <div class="widget-content">
            <div class="row">
                <div class="col col-lg-5">
                    <div class="form-group" id="err_dbhost">
                        <label class="col-lg-4 control-label">DB Hostname:</label>
                        <div class="col-lg-8">
                            <input type="text" name="dbhost" id="dbhost" value="<?= iaHelper::getPost('dbhost', 'localhost') ?>">
                            <p class="help-block" style="display: none;">Please input correct hostname.</p>
                        </div>
                    </div>
                    <div class="form-group" id="err_dbuser">
                        <label class="col-lg-4 control-label">DB Username:</label>
                        <div class="col-lg-8">
                            <input type="text" name="dbuser" id="dbuser" value="<?= iaHelper::getPost('dbuser') ?>">
                            <p class="help-block" style="display: none;">Please input correct username.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">DB Password:</label>
                        <div class="col-lg-8">
                            <input type="password" name="dbpwd" id="dbpwd">
                        </div>
                    </div>
                    <div class="form-group" id="err_dbname">
                        <label class="col-lg-4 control-label">DB Name:</label>
                        <div class="col-lg-8">
                            <input type="text" name="dbname" id="dbname" value="<?= iaHelper::getPost('dbname') ?>">
                            <p class="help-block" style="display: none;">Please input correct db name.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label">DB Port:</label>
                        <div class="col-lg-8">
                            <input type="text" name="dbport" id="dbport" value="<?= (int)iaHelper::getPost('dbport', 3306) ?>">
                        </div>
                    </div>
                    <div class="form-group" id="err_prefix">
                        <label class="col-lg-4 control-label">Table Prefix:</label>
                        <div class="col-lg-8">
                            <input type="text" name="prefix" id="prefix" value="<?= iaHelper::getPost('prefix', 'sbr' . substr(IA_VER, 0, 3) . '_', false) ?>">
                            <p class="help-block" style="display: none;">Please specify the table prefix.</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-lg-4 control-label" for="delete_tables">Drop Tables:</label>
                        <div class="col-lg-8">
                            <label class="checkbox">
                                <input type="checkbox" id="delete_tables" name="delete_tables"<?php if (iaHelper::getPost('delete_tables', false)): ?> checked="checked"<?php endif ?>>
                                Your existing tables will be dropped if checked.
                            </label>
                        </div>
                    </div>
                    <input type="hidden" name="db_action" id="db_action" value="1">
                </div>
                <div class="col col-lg-4">
                    <div class="widget-annotation">
                        <p>Enter the MySQL, MariaDB, Percona Server, or equivalent database connection and database name you wish to use with Subrion CMS.</p>
                        <p>Enter the a table name prefix to be used by Subrion CMS and select what to do with existing tables from former installations.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="widget widget-default">
        <div class="widget-header">
            Administrator Configuration
        </div>
        <div class="widget-content">
            <div class="row">
                <div class="col col-lg-5">
                    <div class="form-group" id="err_admin_username">
                        <label class="col-lg-4 control-label">Username:</label>
                        <div class="col-lg-8">
                            <input type="text" name="admin_username" id="admin_username" value="<?= iaHelper::getPost('admin_username', 'admin') ?>" />
                            <p class="help-block" style="display: none;">Please input correct username.</p>
                        </div>
                    </div>
                    <div class="form-group" id="err_admin_password">
                        <label class="col-lg-4 control-label">Password:</label>
                        <div class="col-lg-8">
                            <input type="password" name="admin_password" id="admin_password" />
                            <p class="help-block" style="display: none;">Please input password.</p>
                        </div>
                    </div>
                    <div class="form-group" id="err_admin_password2">
                        <label class="col-lg-4 control-label">Confirm Password:</label>
                        <div class="col-lg-8">
                            <input type="password" name="admin_password2" id="admin_password2" />
                            <p class="help-block" style="display: none;">Passwords do not match.</p>
                        </div>
                    </div>
                    <div class="form-group" id="err_admin_email">
                        <label class="col-lg-4 control-label">Email:</label>
                        <div class="col-lg-8">
                            <input type="text" name="admin_email" id="admin_email" value="<?= iaHelper::getPost('admin_email') ?>" />
                            <p class="help-block" style="display: none;">Please input correct email.</p>
                        </div>
                    </div>
                </div>
                <div class="col col-lg-4">
                    <div class="widget-annotation">
                        <p>Please set your admin username. It will be used for logging into your admin dashboard.</p>
                        <p>You should input admin password. Make sure your entered passwords match each other.</p>
                        <p>Input your email. All the notifications will be sent from this email. It can be changed in your admin panel later.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= URL_INSTALL ?><?= $this->module ?>/license/" class="btn btn-lg btn-default" tabindex="-1"><i class="i-chevron-left"></i> Back</a>
        <button type="submit" class="btn btn-lg btn-primary">Install <i class="i-checkmark"></i></button>
    </div>
</form>

<?php if ($this->errorList): ?>
    <script type="text/javascript">
        <?php foreach ($this->errorList as $field): ?>
            document.getElementById('err_<?= $field ?>').className += ' has-error';
            document.getElementById('err_<?= $field ?>').getElementsByTagName('p')[0].style.display = 'block';
        <?php endforeach ?>
        document.getElementById('<?= $field[0] ?>').focus();
    </script>
<?php endif ?>