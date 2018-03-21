<?php if (!file_exists(IA_HOME . '.htaccess')): ?>
    <div class="alert alert-error">No .htaccess found in your Subrion core folder. More details <a href="https://subrion.org/desk/index.php?/Knowledgebase/Article/View/28/3/no-htaccess-in-subrion-folder" target="_blank">here</a>.</div>
<?php endif ?>

<?php foreach ($this->sections as $name => $item): ?>
    <div class="widget widget-default">
        <div class="widget-header">
            <?= $item['title'] ?>
        </div>
        <div class="widget-content">
            <div class="row">
                <div class="col col-lg-5">
                    <table class="table table-bordered pre-install">
                        <?php if ('recommended' == $name): ?>
                        <thead>
                        <tr>
                            <th>Directive</th>
                            <th>Recommended</th>
                            <th>Actual</th>
                        </tr>
                        </thead>
                        <?php endif ?>
                        <tbody>
                        <?php foreach ($this->checks[$name] as $key => $check): ?>
                        <tr>
                            <td<?php if (isset($check['class'])): ?> class="elem"<?php endif ?> style="width: 220px;">
                            <?= $check['name'] ?>
                            <?php if (isset($check['required'])): ?><span class="label label-warning">Required</span> <?php endif ?>
                            </td>
                            <?= $check['value'] ?>
                        </tr>
                        <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
                <div class="col col-lg-4">
                    <div class="widget-annotation"><?= $item['desc'] ?></div>
                </div>
            </div>
        </div>
    </div>
<?php endforeach ?>

<div class="form-actions">
    <a href="<?= URL_INSTALL . $this->module ?>/" class="btn btn-lg btn-success"><i class="i-loop"></i> Check</a>
    <?php if ($this->nextButton): ?>
    <a href="<?= URL_INSTALL . $this->module ?>/license/" class="btn btn-lg btn-primary">Next <i class="i-chevron-right"></i></a>
    <?php else: ?>
    <a href="<?= URL_INSTALL . $this->module ?>/" class="btn btn-lg btn-danger disabled">Next <i class="i-remove-sign"></i></a>
    <?php endif ?>
</div>