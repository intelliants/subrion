{ia_hooker name='smartyAdminStatisticsPage' package=$package}
<div class="row">
    <div class="col col-lg-6">
        {foreach $statistics as $itemName => $entry}
        <div class="widget-block">
            <div class="widget widget-package" id="widget-{$itemName}-stats">
                <div class="widget-content">
                    <div class="widget-total-stats">
                        <span><a href="{$smarty.const.IA_ADMIN_URL}{$entry.url}">{$entry.total}</a></span> {lang key=$entry.item default=$entry.item}
                    </div>
                    <div class="widget-icon"><i class="i-{$entry.icon}"></i></div>
                    <hr>
                    <div class="widget-stats">
                        <table>
                            <tbody>
                            {foreach $entry.rows as $key => $value}
                                <tr>
                                    <td><a href="{$smarty.const.IA_ADMIN_URL}{$entry.url}?status={$key}">{lang key=$key}</a>:</td>
                                    <td>{$value}</td>
                                </tr>
                            {/foreach}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        {/foreach}
    </div>

    <div class="col col-lg-6">
        <div class="widget widget-large" id="widget-{$package}-recent-activity">
            <div class="widget-header"><i class="i-clock-2"></i> {lang key='recent_package_activity' package=$package}</div>
            <div class="widget-content">
                <div class="widget-activity">
                    {foreach $timeline as $entry}
                        <div class="widget-activity-item status-{$entry.style}">
                            <div class="icon"><i class="i-{$entry.icon}"></i></div>
                            <div class="date">{$entry.date}</div>
                            <p>{$entry.description}</p>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>
    </div>
</div>

{ia_add_media files='css: _IA_URL_js/jquery/plugins/scrollbars/jquery.mCustomScrollbar'}
{ia_print_js files='admin/statistics, jquery/plugins/jquery.sparkline.min, jquery/plugins/scrollbars/jquery.mCustomScrollbar.concat.min'}