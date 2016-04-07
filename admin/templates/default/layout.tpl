<!DOCTYPE html>
<html lang="{$core.language.iso}" dir="{$core.language.direction}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge">
		<title>{ia_print_title}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="generator" content="Subrion CMS &middot; {$core.config.version}">
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

		{if isset($core.config.sap_style)}
			{ia_print_css files="bootstrap-{$core.config.sap_style}" order=0}
		{else}
			{ia_print_css files='bootstrap' order=0}
		{/if}

		{ia_add_media files='jquery, extjs, subrion' order=0}

		{ia_print_css display='on'}

		{ia_add_js order=0}
			{foreach $core.customConfig as $key => $value}
				intelli.config.{$key} = '{$value}';
			{/foreach}
			intelli.config.admin_url = '{$smarty.const.IA_URL}{$core.config.admin_page}';
		{/ia_add_js}
	</head>
	<body id="page--{$core.page.name}">
		<div class="overall-wrapper">
			<div class="panels-wrapper">
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
								<a href="#"{if isset($entry.items) && $entry.items} data-toggle="nav-sub-{$entry.name}"{/if}><i class="i-cogs i-{$entry.name}"></i>{$entry.title}</a>
							</li>
						{/foreach}
					</ul>
					<div class="system-info">
						Subrion CMS
						<br>
						<span class="version">v {$core.config.version}</span>
					</div>
					<div class="social-links">
						<a href="https://twitter.com/IntelliantsLLC" target="_blank" class="social-links__twitter"><i class="i-twitter-2"></i></a>
						<a href="https://www.facebook.com/Intelliants" target="_blank" class="social-links__facebook"><i class="i-facebook-2"></i></a> 
						<a href="https://plus.google.com/102005294232479547608/posts" target="_blank" class="social-links__googleplus"><i class="i-googleplus"></i></a>
					</div>
				</section>

				<section id="panel-center" class="{if isset($smarty.cookies.panelHidden) && '1' == $smarty.cookies.panelHidden}is-hidden{/if}">
					{if isset($dashboard)}
						<ul id="nav-sub-dashboard" class="nav-sub{if 0 == $core.page.info.group} active{/if}">
							<li class="single">
								<ul class="list-unstyled quick-links clearfix">
									{foreach $dashboard as $item}
										<li><a href="{$item.url}"><span class="link-icon"><i class="i-{$item.icon}"></i></span>{$item.text}</a></li>
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
					<div class="navbar navbar-static-top navbar-inverse">
						<ul class="nav navbar-nav navbar-right">
							<li>
								<a href="{$smarty.const.IA_URL}" title="{lang key='site_home'}" target="_blank"><i class="i-screen"></i><span> {lang key='site_home'}</span></a>
							</li>
							<li>
								<a href="{$smarty.const.IA_ADMIN_URL}visual-mode/" title="{lang key='visual_manage'}" target="_blank"><i class="i-equalizer"></i><span> {lang key='visual_manage'}</span></a>
							</li>
							<li class="dropdown">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="{lang key='quick_access'}">
									<i class="i-fire"></i><span> {lang key='quick_access'}</span>
								</a>
								<ul class="dropdown-menu">
									{foreach $core.page.info.headerMenu as $entry}
										{if empty($entry.name)}
											<li class="divider"></li>
										{else}
											<li{if $core.page.info.name == $entry.name} class="active"{/if}><a href="{$entry.url}"{if $entry.attr} {$entry.attr}{/if}>{$entry.title}</a></li>
										{/if}
									{/foreach}
								</ul>
							</li>

							{if isset($core.notifications.system)}
								<li class="dropdown notifications alerts">
									<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="{lang key='system_notifications'}">
										<i class="i-flag"></i>
										<span class="label label-info">{$core.notifications.system|count}</span>
										<span> {lang key='system_notifications'}</span>
									</a>
									<ul class="dropdown-menu pull-right">
										<li class="dropdown-block">
											{foreach $core.notifications.system as $message}
												<div class="alert alert-danger">{$message}</div>
											{/foreach}
										</li>
									</ul>
								</li>
							{/if}

							<li class="dropdown">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#" title="Help and Support">
									<i class="i-support"></i>
									<span> Help and Support</span>
								</a>
								<ul class="dropdown-menu pull-right">
									{if !empty($core.config.display_feedbacks)}
										<li>
											<a data-toggle="modal" href="#feedback-modal">Submit feedback</a>
										</li>
									{/if}
									<li><a href="http://www.subrion.com/desk/" target="_blank">Helpdesk</a></li>
									<li><a href="http://www.subrion.org/forums/" target="_blank">User forums</a></li>
									<li><a href="http://dev.subrion.org/projects/subrion-cms/wiki" target="_blank">Wiki</a></li>
								</ul>
							</li>
							<li>
								<a href="{$smarty.const.IA_ADMIN_URL}logout/" title="{lang key='logout'}" id="user-logout">
									<i class="i-switch"></i>
									<span> {lang key='logout'}</span>
								</a>
							</li>
						</ul>
						<ul class="nav navbar-nav navbar-left">
							<li class="panel-toggle">
								<a href="#"><i class="{if isset($smarty.cookies.panelHidden) && '1' == $smarty.cookies.panelHidden}i-chevron-right{else}i-chevron-left{/if}"></i></a>
							</li>
							<li id="user-info">
								<a href="{$smarty.const.IA_ADMIN_URL}members/edit/{$member.id}/">
									{printImage imgfile=$member.avatar title=$member.fullname|default:$member.username gravatar=true email=$member.email}
									{$member.fullname|escape:'html'}
								</a>
							</li>

							{*
							KEEP THIS FOR FUTURE IMPLEMENTATION

							<li class="dropdown">
								<a class="dropdown-toggle" data-toggle="dropdown" href="#">{$member.fullname}</a>
								<ul class="dropdown-menu pull-right">
									<li><a href="{$url}members/edit/{$member.id}/">{lang key='edit'}</a></li>
								</ul>
							</li> 
							*}
						</ul>

						<form id="quick-search" class="navbar-form navbar-right" action="{$smarty.const.IA_ADMIN_URL}members/">
							<input type="text" name="q" style="width: 200px;" class="form-control" placeholder="{lang key='type_here_to_search'}"{if isset($smarty.get.q)} value="{$smarty.get.q|escape:'html'}"{/if}>
							<div class="btn-group">
								<button type="button" class="btn btn-primary dropdown-toggle" data-toggle="dropdown">
									{$quickSearch[$quickSearchItem].title} <span class="caret"></span>
								</button>
								<ul class="dropdown-menu pull-right">
									{foreach $quickSearch as $itemName => $entry}
										<li{if $quickSearchItem == $itemName} class="active"{/if}><a href="{$smarty.const.IA_ADMIN_URL}{$entry.url}">{$entry.title}</a></li>
									{/foreach}
									{if count($quickSearch) > 1}
										<li class="divider"></li>
										<li><a href="#" rel="reset">{lang key='reset'}</a></li>
									{/if}
								</ul>
							</div>
							<button type="submit" class="btn btn-primary"><i class="i-search"></i></button>
						</form>
					</div>

					<div class="content-wrapper">
						<div class="block">
							<div class="block-heading">
								<ul class="nav nav-pills pull-right">
									{if 'index' == $core.page.name}
										{if isset($customization_mode)}
											<li><a href="?reset"><i class="i-loop"></i> {lang key='reset'}</a></li>
											<li><a href="?save" id="js-cmd-save"><i class="i-checkmark"></i> {lang key='save'}</a></li>
											<li><a href=""><i class="i-close"></i> {lang key='discard'}</a></li>
										{else}
											<li><a href="?customize"><i class="i-equalizer"></i> {lang key='customize'}</a></li>
										{/if}
									{/if}

									{foreach $core.page.info.toolbarActions as $action}
										<li><a href="{$action.url}" {$action.attributes}>{if $action.icon}<i class="{$action.icon}"></i> {/if}{$action.title}</a></li>
									{/foreach}
								</ul>
								<h3>{$core.page.title|escape:'html'}</h3>

								{include 'breadcrumb.tpl'}
							</div>

							{include 'notification.tpl'}

							<div class="block-content">{$_content_}</div>
						</div>
					</div>
				</section>
			</div>

			<!-- Feedback modal -->
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