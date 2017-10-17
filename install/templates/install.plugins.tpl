<div id="js-page-initial">
    <?php if ($this->plugins): ?>
        <div class="widget widget-default">
            <div class="widget-header">
                Available plugins
            </div>
            <div class="widget-content">
                <div class="row">
                    <div class="col col-lg-8">
                        <div class="plugins-list">
                            <?php foreach ($this->plugins as $name => $entry): ?>
                                <div class="item-plugin">
                                    <p class="date">Last Updated: <?= date('M d, Y', $entry->date) ?></p>
                                    <div class="item-plugin-image">
                                        <img src="<?= $entry->logo ?>" alt="<?= $entry->title ?>" width="100" />
                                    </div>
                                    <div class="item-plugin-desc">
                                        <h4><?= $entry->title ?></h4>
                                        <p><?= $entry->description ?></p>
                                        <div class="item-plugin-actions">
                                            <input type="checkbox" name="plugins[]" value="<?= $name ?>" id="cb_<?= $name ?>" rel="<?= $entry->title ?>" />
                                            <?php if (!isset($entry->installed)): ?>
                                                <a href="#" class="btn btn-xs btn-success js-plugin-check">Select</a>
                                            <?php else: ?>
                                                <a href="#" class="btn btn-xs btn-info btn-disabled" disabled>Plugin already installed</a>
                                            <?php endif ?>
                                            <a href="<?= $entry->url ?>" target="_blank" class="btn btn-default btn-xs">Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                    <div class="col col-lg-4">
                        <div id="plugins-control">
                            <p class="help-block">You can select plugins you want to install on the left side. Selected plugins will be downloaded from the site of Subrion CMS. Sure, you are able to manage plugins in Admin Panel later.</p>
                            <p class="btn-group btn-group-justified js-selection-controls">
                                <a href="#" class="btn btn-default btn-xs" rel="select">Select all</a>
                                <a href="#" class="btn btn-default btn-xs" rel="invert">Invert</a>
                                <a href="#" class="btn btn-default btn-xs" rel="drop">Clear</a>
                            </p>
                            <button class="btn btn-block btn-lg btn-success" id="js-btn-proceed" disabled="disabled"><i class="i-box-add"></i> Install selected plugins <strong id="js-counter" class="badge">0</strong></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif ?>

    <div class="form-actions">
        <a href="<?= URL_ADMIN_PANEL ?>" class="btn btn-lg btn-primary"><i class="i-gauge"></i> to Admin panel</a>
        <a href="<?= URL_HOME ?>" class="btn btn-lg btn-primary"><i class="i-screen"></i> to Home page</a>
    </div>
</div>

<div id="js-page-installation-log" style="display: none;">
    <div class="widget widget-default">
        <div class="widget-content">
            <div id="js-progress-bar" class="alert alert-info">
                Installation is in progress...
                <strong><span id="js-counter-current">1</span> of <span id="js-counter-total"></span></strong>
                <img src="<?= URL_ASSETS ?>templates/img/loading.gif" alt="loading..." />
            </div>
            <p>Check the status of plugins you selected to install.</p>
            <p>Missed a plugin? <a href="<?= URL_INSTALL ?><?= $this->module ?>/<?= $this->step ?>/">Show the list again</a>.</p>
        </div>
    </div>

    <div class="widget widget-default">
        <div class="widget-header">
            Plugins installation log
        </div>
        <div class="widget-content">
            <ol class="unstyled" id="js-log">
                <li>Started batch installation...</li>
            </ol>
        </div>
    </div>

    <div class="form-actions">
        <a href="<?= URL_ADMIN_PANEL ?>" class="btn btn-lg btn-primary">to Admin panel <i class="i-gauge"></i></a>
        <a href="<?= URL_HOME ?>" class="btn btn-lg btn-primary">to Home page <i class="i-screen"></i></a>
    </div>
</div>

<script type="text/javascript">
$(function() {
    // Check button functionality
    function checkPlugin(which)
    {
        which.addClass('checked')
             .find('input[type="checkbox"]')
             .prop('checked', true)
             .trigger('change');

        which.find('.js-plugin-check').text('Deselect');
    }
    function uncheckPlugin(which)
    {
        which.removeClass('checked')
             .find('input[type="checkbox"]')
             .prop('checked', false)
             .trigger('change');

        which.find('.js-plugin-check').text('Select');
    }

    $('.js-plugin-check').on('click', function(e)
    {
        e.preventDefault();
        var $obj = $(this).closest('.item-plugin');
        var $check = $obj.find('input[type="checkbox"]');

        $check.is(':checked')
            ? uncheckPlugin($obj)
            : checkPlugin($obj);
    });

    $('input[name="plugins[]"]').on('change', function()
    {
        var count = $('input[name="plugins[]"]:checked').length;
        $('#js-counter').text(count);
        count ? $('#js-btn-proceed').removeAttr('disabled') : $('#js-btn-proceed').attr('disabled', 'disabled');
    });

    $('.js-selection-controls .btn').on('click', function(e)
    {
        e.preventDefault();

        var scope = $('input[name="plugins[]"]');
        switch ($(this).attr('rel'))
        {
            case 'drop':
                scope.prop('checked', false);
                uncheckPlugin(scope.closest('.item-plugin'));
                break;
            case 'invert':
                scope.each(function(i, entry)
                {
                    if ($(entry).is(':checked'))
                    {
                        uncheckPlugin($(entry).closest('.item-plugin'));
                    } else
                    {
                        checkPlugin($(entry).closest('.item-plugin'));
                    }
                });
                break;
            default:
                scope.prop('checked', true);
                checkPlugin(scope.closest('.item-plugin'));
        }
        $('input[name="plugins[]"]:first').trigger('change');
    });

    $('#js-btn-proceed').on('click', function(e)
    {
        e.preventDefault();

        $('#js-page-initial').hide();
        $('#js-page-installation-log').fadeIn('slow', function()
        {
            var counter = 0;
            var elementsSet = $('input[name="plugins[]"]:checked');
            $('#js-counter-total').text(elementsSet.length);
            elementsSet.each(function(i, item)
            {
                item = $(item);
                $.ajax(
                {
                    type: 'POST',
                    url: '<?= URL_INSTALL ?>install/plugins/',
                    data: {plugin: item.val()},
                    dataType: 'html',
                    success: function(resultMessage) {
                        counter++;
                        $('#js-counter-current').text(counter + 1);
                        $('<li>').html('<span class="label label-info">' + item.attr('rel') + '</span> ' + resultMessage).appendTo('#js-log');
                        if (elementsSet.length == counter)
                        {
                            $('#js-progress-bar')
                                .toggleClass('alert-info alert-success')
                                .text('Installation completed.');
                            $('<li>').text('Finished.').appendTo('#js-log');
                        }
                    }
                });
            });
        });
    });

    /* react to scroll event on window */
    $('#plugins-control').sticky({topSpacing: 20, bottomSpacing: 150, getWidthFrom: '.widget-content .col-lg-4'});
});
</script>