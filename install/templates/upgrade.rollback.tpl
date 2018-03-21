<?php if (isset($this->error)): ?>
<div class="alert alert-error"><?= $this->error ?></div>
<?php endif ?>
<?php if (isset($this->success)): ?>
<div class="alert alert-success">Completed successfully.</div>
<?php endif ?>

<form method="post">
    <div class="widget widget-default">
        <div class="widget-content">
            Below is the list of backups available to rollback:
            <select name="backup">
                <option value="">...</option>
            <?php foreach ($this->backups as $version => $options): ?>
                <optgroup label="Version <?= $version ?>">
                    <?php foreach ($options as $fileName => $date): ?>
                    <option value="<?= $fileName ?>"<?php if (!empty($_POST['backup']) && $fileName == $_POST['backup']): ?> selected<?php endif ?>>Of <?= $date ?></option>
                    <?php endforeach ?>
                </optgroup>
            <?php endforeach ?>
            </select>
            <hr>
            <p>Please do not forget that it will completely restore the script contents at the older date.</p>
        </div>
    </div>

    <div class="form-actions">
        <input type="submit" class="btn btn-lg btn-primary" value="Start" id="js-btn-submit" disabled>
    </div>
</form>

<script type="text/javascript">
$(function() {
    $('select[name="backup"]').on('change', function() {
        var value = $('option:selected', this).val();
        $('#js-btn-submit').prop('disabled', !value)
    }).change();

    $('#js-btn-submit').on('click', function(e) {
        confirm('This will erase all the data added past upgrade. Existing files will be rewritten.\n\nDo you really want to continue?') || e.preventDefault();
    });
});
</script>