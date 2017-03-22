{if isset($text_content)}
    <div style="width:99%" xmlns="http://www.w3.org/1999/html">{$text_content}</div>
{else}

{if !isset($customization_mode)}
    <div id="widget-preloader" class="text-muted">
        <div class="spinner"><i class="i-spinner"></i></div>
        <p>{lang key='loading_widgets'}</p>
    </div>
{else}
    <input type="hidden" id="js-disabled-widgets-list" value="{$disabled_widgets|implode:','}">
{/if}

{ia_hooker name='smartyDashboardBeforeContent'}

{if !empty($updatesInfo)}
    {foreach $updatesInfo as $message}
        {if !isset($smarty.cookies["alert-{$message[0]}"]) || 'closed' != $smarty.cookies["alert-{$message[0]}"]}
            <div class="alert alert-danger fade in imp-alert js-imp-alert" data-id="alert-{$message[0]}">
                <i class="i-warning"></i>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                {$message[1]}
            </div>
        {/if}
    {/foreach}
{/if}

<div class="row animated-widgets">
    <div class="col col-lg-7">
        <div class="row">
            {foreach $statistics.medium as $itemName => $info}
                <div class="col-md-6">
                    <div class="widget widget-medium" id="widget-{$itemName}">
                        <div class="widget-content">
                            <div class="widget-total-stats">
                                <span><a href="{$info.url}">{$info.total}</a></span> {$info.item}
                            </div>
                            <div class="widget-icon"><i class="i-{$info.icon}"></i></div>
                            <hr>
                            <div class="widget-stats">
                                <table>
                                    {foreach $info.rows as $key => $value}
                                    <tr>
                                        <td><a href="{$info.url}?status={$key}">{lang key=$key default=$key}</a>:</td>
                                        <td>{$value}</td>
                                    </tr>
                                    {/foreach}
                                </table>
                            </div>
                            {if isset($info.data)}
                            <div class="widget-chart">
                                <div class="js-stats"{if isset($info.data)}{foreach $info.data as $key => $value} data-{$key}="{$value}"{/foreach}{/if}></div>
                                <div class="widget-chart-weekdays">
                                    <span>Su</span>
                                    <span>Mo</span>
                                    <span>Tu</span>
                                    <span>We</span>
                                    <span>Th</span>
                                    <span>Fr</span>
                                    <span>Sa</span>
                                </div>
                            </div>
                            {/if}
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>

        {if !empty($transactions)}
            <div class="widget widget-large" id="widget-latest-transactions">
                <div class="widget-header"><i class="i-coin"></i> {lang key='transactions'}
                    <ul class="nav nav-pills pull-right">
                        <li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
                    </ul>
                </div>
                <div class="widget-content">
                    <table class="table table-light">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>{lang key='reference_id'}</th>
                            <th>{lang key='member'}</th>
                            <th>{lang key='operation'}</th>
                            <th>{lang key='gateway'}</th>
                            <th>{lang key='date'}</th>
                            <th>{lang key='status'}</th>
                            <th>{lang key='amount'}</th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $transactions as $transaction}
                            <tr>
                                <td>{$transaction.id}</td>
                                <td>{$transaction.reference_id}</td>
                                <td>{$transaction.user}</td>
                                <td>{$transaction.operation}</td>
                                <td>{$transaction.gateway}</td>
                                <td>{$transaction.date_created}</td>
                                <td>{$transaction.status}</td>
                                <td>{$transaction.amount}</td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        {/if}

        {foreach $statistics.package as $itemName => $info}
        <div class="widget-block">
            <div class="widget widget-package" id="widget-{$itemName}">
                <div class="widget-content">
                    <div class="widget-total-stats">
                        <span><a href="{$info.url}">{$info.total}</a></span> {lang key=$info.item default=$info.item}
                    </div>
                    <div class="widget-icon"><i class="i-{$info.icon}"></i></div>
                    <hr>
                    <div class="widget-stats">
                        <table>
                            {foreach $info.rows as $key => $value}
                            <tr>
                                <td><a href="{$info.url}?status={$key}">{lang key=$key default=$key}</a>:</td>
                                <td>{$value}</td>
                            </tr>
                            {/foreach}
                        </table>
                    </div>
                    {if isset($info.data)}
                    <div class="widget-chart">
                        <div class="js-stats"{if isset($info.data)}{foreach $info.data as $key => $value} data-{$key}="{$value}"{/foreach}{/if}></div>
                        <div class="widget-chart-weekdays">
                            <span>Su</span>
                            <span>Mo</span>
                            <span>Tu</span>
                            <span>We</span>
                            <span>Th</span>
                            <span>Fr</span>
                            <span>Sa</span>
                        </div>
                    </div>
                    {/if}
                </div>
            </div>
        </div>
        {/foreach}

        <div class="row">
            {foreach $statistics.small as $itemName => $info}
            <div class="col col-lg-4">
                <div class="widget widget-small" id="widget-{$itemName}">
                    <div class="widget-content">
                        <div class="widget-total-stats">
                            <span class="main-stat"><a href="{$info.url}">{$info.total}</a> {$info.item}</span>
                            {foreach $info.rows as $key => $value}
                            <span><a href="{$info.url}?status={$key}">{$value}</a> {lang key=$key default=$key}</span>
                            {/foreach}
                        </div>
                        <div class="widget-icon"><i class="i-{$info.icon}"></i></div>
                        <div class="widget-name">{$info.caption}</div>
                    </div>
                </div>
            </div>
            {/foreach}

            {ia_hooker name='smartyDashboardAfterStatistics'}

            <div class="col col-lg-4">
                <div class="widget widget-small widget-small-config">
                    <div class="widget-content">
                        <div class="widget-icon"><i class="i-lab"></i></div>
                        <div class="widget-name">{lang key='add_plugin'}</div>
                    </div>
                </div>
            </div>
        </div>

        {if isset($timeline)}
        <div class="widget widget-large" id="widget-twitter">
            <div class="widget-header"><i class="i-twitter"></i> {lang key='twitter_news'}
                <ul class="nav nav-pills pull-right">
                    <li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
                </ul>
            </div>
            <div class="widget-content">
                <div class="widget-activity">
                    {foreach $timeline as $tweet}
                    <div class="widget-activity-item">
                        <div class="icon">
                            <img src="{$img}logo-intelliants-80-inverse.png" alt="Intelliants">
                        </div>
                        <div class="date">{$tweet.created_at|date_format:$core.config.date_format}</div>
                        <p>{$tweet.text}</p>
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
        {/if}

        {ia_hooker name='smartyDashboardContentLeft'}
    </div>

    <div class="col col-lg-5">

        {if isset($activity_log)}
        <div class="widget widget-large" id="widget-recent-activity">
            <div class="widget-header"><i class="i-clock-2"></i> {lang key='recent_activity'}
                <ul class="nav nav-pills pull-right">
                    <li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
                </ul>
            </div>
            <div class="widget-content">
                <div class="widget-activity">
                    {foreach $activity_log as $entry}
                    <div class="widget-activity-item status-{$entry.style}">
                        <div class="icon"><i class="i-{$entry.icon}"></i></div>
                        <div class="date">{$entry.date}</div>
                        <p>{$entry.description}</p>
                    </div>
                    {/foreach}
                </div>
            </div>
        </div>
        {/if}

        {if isset($online_members)}
        <div class="widget widget-large" id="widget-website-visits">
            <div class="widget-header"><i class="i-users"></i> {lang key='online_members'}
                <ul class="nav nav-pills pull-right">
                    <li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
                </ul>
            </div>
            <div class="widget-content">
                <table class="table table-light">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>{lang key='member'}</th>
                            <th>{lang key='ip_address'}</th>
                            <th>{lang key='current_page'}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $online_members as $member}
                        <tr>
                            <td>{$member@index + 1}</td>
                            <td><a href="{$member.link}" target="_blank">{$member.fullname}</a></td>
                            <td>{long2ip($member.ip)}</td>
                            <td class="page-url"><a href="{$member.page}" target="_blank">{$member.page}</a></td>
                        </tr>
                        {/foreach}
                    </tbody>
                </table>
            </div>
        </div>
        {/if}

        {if isset($changelog)}
            <div class="widget widget-large" id="widget-changelog">
                <div class="widget-header"><i class="i-lightning"></i> {lang key='changelog'}
                    <ul class="nav nav-pills pull-right">
                        <li class="dropdown hidden-xs hidden-sm">
                            <a class="dropdown-toggle" data-toggle="dropdown" href="#">
                                <span class="fa fa-list"></span>
                                <span class="caret"></span>
                            </a>
                            <ul class="dropdown-menu pull-right">
                                {foreach $changelog_titles as $item => $index}
                                    <li{if $index@iteration == 1} class="active"{/if}><a href="#" data-item="#changelog-item-{$index}">{$item}</a></li>

                                    {if $index@iteration == 10}
                                        <li class="divider"></li>
                                        <li><a href="https://github.com/intelliants/subrion/milestones" target="_blank" title="{lang key='view_roadmap'}"><i class="i-flow-branch"></i> {lang key='view_roadmap'}</a></li>

                                        {break}
                                    {/if}
                                {/foreach}
                            </ul>
                        </li>
                        <li><a href="https://github.com/intelliants/subrion/milestones" target="_blank" title="{lang key='view_roadmap'}"><i class="i-flow-branch"></i></a></li>
                        <li><a href="#" class="widget-toggle"><i class="i-chevron-up"></i></a></li>
                    </ul>
                </div>
                <div class="widget-content">
                    {foreach $changelog as $index => $items}
                        <div class="changelog-item" id="changelog-item-{$index}" style="display:none;">
                            {foreach $items as $class => $list}
                                {if !empty($list) && $class != 'title'}
                                    {assign classtext "changelog_{$class}"}
                                    {if 'added' == $class}
                                        <h5 class="text-success"><i class="i-fire"></i> {lang key=$classtext}</h5>
                                    {elseif 'modified' == $class}
                                        <h5 class="text-warning"><i class="i-lightning"></i> {lang key=$classtext}</h5>
                                    {else}
                                        <h5 class="text-danger"><i class="i-bug"></i> {lang key=$classtext}</h5>
                                    {/if}
                                    <ol>{$list}</ol>
                                {/if}
                            {/foreach}
                        </div>
                    {/foreach}
                </div>
            </div>
        {/if}

        {ia_hooker name='smartyDashboardContentRight'}
    </div>
</div>

{ia_hooker name='smartyDashboardAfterContent'}

{ia_add_media files='css: _IA_URL_js/jquery/plugins/scrollbars/jquery.mCustomScrollbar'}
{ia_print_js files='admin/index, jquery/plugins/jquery.sparkline.min, jquery/plugins/scrollbars/jquery.mCustomScrollbar.concat.min'}
{/if}