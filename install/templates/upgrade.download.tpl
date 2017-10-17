<?php if ($this->error): ?>
    <div class="widget widget-default">
        <div class="widget-content">
            <div class="alert alert-error">Unable to download the patch file.</div>
            <p>Could not continue.</p>
        </div>
    </div>
    <div class="form-actions">
        <a href="<?= URL_INSTALL ?><?= $this->module ?>/<?= $this->step ?>/" class="btn btn-default btn-lg"><i class="i-loop"></i> Try again</a>
    </div>
<?php else: ?>
    <div class="widget widget-default">
        <div class="widget-content">
            <div class="alert alert-info">Patch file successfully downloaded.</div>
            <p>File size is <code><?= number_format($this->size / 1024) ?> Kb</code>.</p>
            <hr>
            <p>Click <code>&laquo;Next&raquo;</code> button to proceed.</p>
        </div>
    </div>
    <div class="form-actions">
        <a href="<?= URL_INSTALL ?><?= $this->module ?>/backup/" class="btn btn-lg btn-primary">Next</a>
    </div>
<?php endif ?>