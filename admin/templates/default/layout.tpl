<!DOCTYPE html>
<html lang="{$core.language.iso}" dir="{$core.language.direction}">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=Edge">
        <title>{ia_print_title}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="generator" content="Subrion CMS - Open Source Content Management System">
        <meta name="robots" content="noindex">
        <base href="{$smarty.const.IA_ADMIN_URL}">

        <!--[if lt IE 9]>
            <script src="../../../js/utils/shiv.js"></script>
            <script src="../../../js/utils/respond.min.js"></script>
        <![endif]-->

        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="{$img}ico/apple-touch-icon-144-precomposed.png">
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="{$img}ico/apple-touch-icon-114-precomposed.png">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="{$img}ico/apple-touch-icon-72-precomposed.png">
        <link rel="apple-touch-icon-precomposed" href="{$img}ico/apple-touch-icon-57-precomposed.png">
        <link rel="shortcut icon" href="{$img}ico/favicon.ico">

        {ia_hooker name='smartyAdminAfterHeadSection'}

        {ia_print_css files="bootstrap-{$core.config.sap_style}" order=0}

        {ia_add_media files='jquery, extjs, subrion' order=0}
        {ia_print_js files='_IA_TPL_enquire.min, _IA_TPL_app'}

        {ia_print_css display='on'}

        {include 'custom-config.tpl'}
    </head>
    <body id="page--{$core.page.name}" class="ss-{$core.config.sap_style}">
        <div class="overall-wrapper">
            <div class="panels-wrapper">
                <div class="m-header">
                    <a class="m-header__brand" href="{$smarty.const.IA_ADMIN_URL}">
                        <img src="{$img}logo.png" alt="Subrion CMS &middot; {$core.config.version}">
                    </a>
                    <a href="#" class="m-header__toggle"><span class="fa fa-bars"></span></a>
                </div>
                <section id="panel-left">
                    <a class="brand" href="{$smarty.const.IA_ADMIN_URL}">
                        <img src="{$img}logo.png" alt="Subrion CMS &middot; {$core.config.version}">
                    </a>
                    <ul class="nav-main">
                        <li{if 0 == $core.page.info.group} class="current active"{/if}>
                            <a href="{$smarty.const.IA_ADMIN_URL}" class="dashboard" data-toggle="nav-sub-dashboard"><i class="i-gauge"></i>{lang key='dashboard'}</a>
                        </li>
                        {foreach $core.page.info.menu as $entry}
                            <li{if $core.page.info.group == $entry.id} class="current active"{/if} id="menu-section-{$entry.name}">
                                <a href="#"{if !empty($entry.items)} data-toggle="nav-sub-{$entry.name}"{/if}><i class="i-cogs i-{$entry.name}"></i>{$entry.title}</a>
                            </li>
                        {/foreach}
                    </ul>
                    <div class="system-info">
                        Subrion CMS
                        <br>
                        <span class="version">v {$core.config.version}</span>
                    </div>
                    <div class="social-links">
                        <a href="https://twitter.com/IntelliantsLLC" target="_blank" class="social-links__twitter"><span class="fa fa-twitter"></span></a>
                        <a href="https://github.com/intelliants/subrion" target="_blank" class="social-links__github"><span class="fa fa-github"></span></a>
                    </div>
                </section>

                <section id="panel-center" class="{if isset($smarty.cookies.panelHidden) && '1' == $smarty.cookies.panelHidden}is-hidden{/if}">
                    {if isset($dashboard)}
                        <ul id="nav-sub-dashboard" class="nav-sub{if 0 == $core.page.info.group} active{/if}">
                            <li class="single">
                                <ul class="list-unstyled quick-links clearfix">
                                    {foreach $dashboard as $item}
                                        <li><a href="{$item.url}"><span class="link-icon"><i class="i-{$item.icon}"></i></span>{lang key=$item.name default=$item.text}</a></li>
                                    {/foreach}
                                    <li class="link-add">
                                        <a href="#" id="js-cmd-add-quicklink">
                                            <span class="link-icon"><i class="i-plus"></i></span>{lang key='add_quick_link'}
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    {/if}
                    {include 'menu.tpl'}
                </section>

                <section id="panel-content">
                    <div class="navbar">
                        <ul class="nav navbar-nav navbar-right">
                            <li>
                                <a href="{$smarty.const.IA_URL}" title="{lang key='site_home'}" target="_blank"><i class="fa fa-desktop"></i><span> {lang key='site_home'}</span></a>
                            </li>
                            <li class="dropdown">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="#" title="">
                                    <i class="fa fa-eye"></i><span> {lang key='mode'}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    {$manageMode = (isset($smarty.session.manageMode) && 'mode' == $smarty.session.manageMode)}
                                    <li>
                                        <a href="{if $manageMode}{$smarty.const.IA_URL}?manage_exit=y{else}{$smarty.const.IA_ADMIN_URL}visual-mode/{/if}" target="_blank"><i class="fa fa-sliders"></i> {lang key='visual_mode'} {if $manageMode} <span class="label label-warning">{lang key='active'}</span>{/if}</a>
                                    </li>
                                    <li><a href="{$smarty.const.IA_ADMIN_URL}debug-mode/" title=""><i class="fa fa-bug"></i> {lang key='debug_mode'}{if $smarty.const.INTELLI_DEBUG}<span class="label label-warning">{lang key='global'}</span>{elseif $smarty.const.INTELLI_QDEBUG}<span class="label label-warning">{lang key='active'}</span>{/if}</a></li>

                                    {if count($core.languages) > 1}
                                        <li class="divider"></li>
                                        <li class="dropdown-header">{lang key="language"}</li>
                                        {foreach $core.languages as $code => $language}
                                            {$language_url = str_replace("/{$smarty.const.IA_LANGUAGE}/", "/{$code}/", $smarty.const.IA_SELF)}

                                            <li><a href="{$language_url}">{$language.title}{if $code == $smarty.const.IA_LANGUAGE} <span class="label label-success">{lang key='active'}</span>{/if}</a></li>
                                        {/foreach}
                                    {/if}
                                </ul>
                            </li>
                            <li class="dropdown">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="#" title="{lang key='quick_access'}">
                                    <i class="fa fa-bolt"></i><span> {lang key='quick_access'}</span>
                                </a>
                                <ul class="dropdown-menu">
                                    {foreach $core.page.info.headerMenu as $entry}
                                        {if empty($entry.name)}
                                            <li class="divider"></li>
                                        {else}
                                            <li{if $core.page.info.name == $entry.name} class="active"{/if}><a href="{$entry.url}"{if $entry.attr} {$entry.attr}{/if}><span class="fa fa-{$entry.name}"></span> {$entry.title}</a></li>
                                        {/if}
                                    {/foreach}
                                </ul>
                            </li>

                            {if isset($core.notifications.system)}
                                <li class="dropdown navbar-nav__notifications">
                                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" title="{lang key='system_notifications'}">
                                        <i class="fa fa-bell"></i>
                                        <span class="label label-info">{$core.notifications.system|count}</span>
                                        <span> {lang key='system_notifications'}</span>
                                    </a>
                                    <ul class="dropdown-menu pull-right">
                                        <li class="navbar-nav__notifications__alerts">
                                            {foreach $core.notifications.system as $message}
                                                <div class="alert alert-danger">{$message}</div>
                                            {/foreach}
                                        </li>
                                    </ul>
                                </li>
                            {/if}

                            <li class="dropdown">
                                <a class="dropdown-toggle" data-toggle="dropdown" href="#" title="Help and Support">
                                    <i class="fa fa-support"></i>
                                    <span> Help and Support</span>
                                </a>
                                <ul class="dropdown-menu pull-right">
                                    {if !empty($core.config.display_feedbacks)}
                                        <li><a data-toggle="modal" href="#feedback-modal"><span class="fa fa-commenting-o"></span> Submit feedback</a></li>
                                    {/if}
                                    <li><a href="https://subrion.org/desk/" target="_blank"><span class="fa fa-support"></span> Helpdesk</a></li>
                                    <li><a href="https://subrion.org/forums/" target="_blank"><span class="fa fa-comments-o"></span> User forums</a></li>
                                    <li><a href="https://github.com/intelliants/subrion" target="_blank"><span class="fa fa-github"></span> Github</a></li>
                                    <li><a href="https://dev.subrion.org/projects/subrion-cms/wiki" target="_blank"><span class="fa fa-wikipedia-w"></span> Wiki</a></li>
                                </ul>
                            </li>
                            <li class="navbar-nav__user">
                                <a href="{$smarty.const.IA_ADMIN_URL}members/edit/{$member.id}/" title="{lang key='edit'}">
                                    {ia_image file=$member.avatar type='large' alt=$member.fullname|default:$member.username gravatar=true email=$member.email}
                                </a>
                            </li>
                            <li><a href="{$smarty.const.IA_ADMIN_URL}logout/" title="{lang key='logout'}" id="user-logout"><i class="fa fa-sign-out"></i> <span>{lang key='logout'}</span></a></li>
                        </ul>
                        <ul class="nav navbar-nav navbar-left hidden-xs hidden-sm">
                            <li class="panel-toggle">
                                <a href="#"><i class="fa{if isset($smarty.cookies.panelHidden) && '1' == $smarty.cookies.panelHidden} fa-angle-right{else} fa-angle-left{/if}"></i></a>
                            </li>
                        </ul>
                    </div>

                    <div class="page">
                        <div class="page__heading">
                            <div class="page__heading__title">
                                <h1>{$core.page.title|escape}</h1>
                                {include 'breadcrumb.tpl'}
                            </div>

                            {if in_array($core.page.name, ['templates', 'plugins', 'packages'])}
                                <div class="sap-form filter-toolbar">
                                    <input type="text" class="form-control js-filter-modules-text" placeholder="Start typing...">
                                    <div class="dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">{lang key='filter'} <span class="fa fa-angle-down"></span></button>
                                        <ul class="dropdown-menu dropdown-menu-right">
                                            <li class="dropdown-header">Show only</li>
                                            <li><a class="js-filter-modules" data-type="local" data-filtered="no" href="#"><span class="fa fa-check"></span> {lang key='local'}</a></li>
                                            <li><a class="js-filter-modules" data-type="remote" data-filtered="no" href="#"><span class="fa fa-check"></span> {lang key='remote'}</a></li>
                                            <li><a class="js-filter-modules" data-type="installed" data-filtered="no" href="#"><span class="fa fa-check"></span> {lang key='installed'}</a></li>
                                            <li class="divider"></li>
                                            <li><a class="js-filter-modules-reset" href="#"><span class="fa fa-times"></span> {lang key='reset'}</a></li>
                                        </ul>
                                    </div>
                                </div>
                            {/if}

                            <ul class="page__heading__actions">
                                {if 'index' == $core.page.name}
                                    {if isset($customization_mode)}
                                        <li><a href="?reset"><span class="fa fa-refresh"></span> {lang key='reset'}</a></li>
                                        <li><a href="?save" id="js-cmd-save"><span class="fa fa-check-circle"></span> {lang key='save'}</a></li>
                                        <li><a href=""><span class="fa fa-times-circle"></span> {lang key='discard'}</a></li>
                                    {else}
                                        <li><a href="?customize"><span class="fa fa-magic"></span> {lang key='customize'}</a></li>
                                    {/if}
                                {/if}

                                {foreach $core.page.info.toolbarActions as $action}
                                    <li><a href="{$action.url}" {$action.attributes}>{if $action.icon}<i class="{$action.icon}"></i> {/if}{$action.title}</a></li>
                                {/foreach}
                            </ul>
                        </div>

                        {include 'notification.tpl'}

                        <div class="page__content">
                            {$_content_}
                        </div>
                    </div>
                </section>
            </div>

            <!-- Feedback modal -->
            {if !empty($core.config.display_feedbacks)}
                <div class="modal fade" id="feedback-modal">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="post" class="sap-form form-horizontal">
                                {preventCsrf}
                                <input type="hidden" name="action" value="request">
                                <div class="modal-header">
                                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                                    <h4 class="modal-title"><i class="i-bubbles-2"></i> {lang key='submit_feedback'}</h4>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col col-lg-12">
                                            <p class="text-muted">{lang key='feedback_terms'}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col col-lg-6">
                                            <label>{lang key='fullname'}</label>
                                            <input type="text" name="fullname" data-def="{$member.fullname}" value="{$member.fullname}">
                                        </div>
                                        <div class="col col-lg-6">
                                            <label>{lang key='email'}</label>
                                            <input type="text" name="email" data-def="{$member.email}" value="{$member.email}">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col col-lg-12">
                                            <label id="feedback_subject_label">{lang key='subject'}</label>
                                            <select name="subject">
                                                <option value="">{lang key='_select_'}</option>
                                                <option data-icon="bug">{lang key='bug_report'}</option>
                                                <option data-icon="lightning">{lang key='feature_request'}</option>
                                                <option data-icon="fire">{lang key='custom_modification'}</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col col-lg-12">
                                            <label>{lang key='message_body'}</label>
                                            <textarea name="body" cols="30" rows="5"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-default" data-dismiss="modal">{lang key='close'}</button>
                                    <button type="button" id="clearFeedback" class="btn btn-default">{lang key='clear'}</button>
                                    <button type="submit" class="btn btn-primary">{lang key='send'}</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        {/if}

        {if !isset($smarty.cookies.loader) || 'loaded' != $smarty.cookies.loader}
            <div id="js-ajax-loader">
                <div class="spinner"><i class="i-spinner"></i></div>
                <p id="js-ajax-loader-status"></p>
            </div>
        {/if}

        {ia_hooker name='smartyAdminFooterBeforeJsDisplay'}
        {ia_print_js display='on'}
        {ia_hooker name='smartyAdminFooterAfterJsDisplay'}
    </body>
</html>