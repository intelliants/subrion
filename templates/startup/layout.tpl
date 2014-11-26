<!DOCTYPE html>
<html lang="en">
	<head>
		{ia_hooker name='smartyFrontBeforeHeadSection'}

		<title>{ia_print_title}</title>
		<meta http-equiv="Content-Type" content="text/html;charset={$config.charset}">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="generator" content="Subrion CMS {$config.version} - Open Source Content Management System">
		<meta name="description" content="{$description}">
		<meta name="keywords" content="{$keywords}">
		<meta name="robots" content="index">
		<meta name="robots" content="follow">
		<meta name="revisit-after" content="1 day">
		<base href="{$smarty.const.IA_URL}">

		<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
			<script src="{$nonProtocolUrl}js/utils/shiv.js"></script>
		<![endif]-->

		<link rel="shortcut icon" href="{$nonProtocolUrl}favicon.ico">

		{ia_add_media files='jquery, subrion, bootstrap' order=0}
		{ia_print_js files='_IA_TPL_app' order=999}

		{ia_hooker name='smartyFrontAfterHeadSection'}

		{ia_print_css display='on'}

		{ia_add_js}
			intelli.pageName = '{$pageName}';

			{foreach $customConfig as $key => $value}
				intelli.config.{$key} = '{$value}';
			{/foreach}
		{/ia_add_js}

		{if 'PT Sans' == $config.startup_font}
			<link href='http://fonts.googleapis.com/css?family=PT+Sans:400,700,400italic{if $config.startup_font_subset}&subset=latin,cyrillic{/if}' rel='stylesheet' type='text/css'>
			<style type="text/css">
				body, select, textarea { font-family: 'PT Sans', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
			</style>
		{elseif 'PT Serif' == $config.startup_font}
			<link href='http://fonts.googleapis.com/css?family=PT+Serif:400,700,400italic{if $config.startup_font_subset}&subset=latin,cyrillic{/if}' rel='stylesheet' type='text/css'>
			<style type="text/css">
				body, select, textarea { font-family: 'PT Serif', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
			</style>
		{elseif 'Roboto' == $config.startup_font}
			<link href='http://fonts.googleapis.com/css?family=Roboto:400,700,400italic{if $config.startup_font_subset}&subset=latin,cyrillic{/if}' rel='stylesheet' type='text/css'>
			<style type="text/css">
				body, select, textarea { font-family: 'Roboto', 'Helvetica Neue', Helvetica, Arial, sans-serif; }
			</style>
		{/if}
	</head>

	<body class="page-{$pageName}{if $config.sticky_navbar} sticky-navbar{/if}">
		<header class="header">
			<div class="inventory">
				<div class="container">
					<form id="fast-search" method="post" action="{$smarty.const.IA_URL}search/" class="form-inline nav-search">
						<div class="control-group">
							<input type="text" name="q" placeholder="{lang key='search'}" class="span2">
							<button type="submit"><i class="icon-search"></i></button>
						</div>
					</form>
					
					{ia_blocks block='inventory'}

					{include file='language-selector.tpl'}
				</div>
			</div>
			<nav class="navigation">
				<div class="container">
					<a class="brand" href="{$smarty.const.IA_URL}">
						{if !empty($config.site_logo)}
							<img src="{$nonProtocolUrl}uploads/{$config.site_logo}" alt="{$config.site}">
						{else}
							<img src="{$img}logo.png" alt="{$config.site}">
						{/if}
					</a>

					{if $config.startup_social}
						<ul class="social">
							{if $config.startup_social_t}<li><a href="{$config.startup_social_t}" class="twitter"><i class="icon-twitter"></i></a></li>{/if}
							{if $config.startup_social_f}<li><a href="{$config.startup_social_f}" class="facebook"><i class="icon-facebook"></i></a></li>{/if}
							{if $config.startup_social_g}<li><a href="{$config.startup_social_g}" class="google-plus"><i class="icon-google-plus"></i></a></li>{/if}
						</ul>
					{/if}

					<a href="#" class="nav-toggle" data-toggle="collapse" data-target=".nav-bar-collapse"><i class="icon-reorder"></i> {lang key='su_menu'}</a>
					
					<div class="nav-bar-collapse">
						{ia_blocks block='account'}
					
						{ia_blocks block='mainmenu'}
					</div>
				</div>
			</nav>
			
			{if isset($iaBlocks.header)}
				<div class="container">
					{ia_blocks block='header'}
				</div>
			{/if}
		</header>

		{if isset($iaBlocks.header1) || isset($iaBlocks.header2) || isset($iaBlocks.header3) || isset($iaBlocks.header4)}
			<div class="header-blocks">
				<div class="container">
					<div class="row">
						<div class="{width section='header' position='header1'}">{ia_blocks block='header1'}</div>
						<div class="{width section='header' position='header2'}">{ia_blocks block='header2'}</div>
						<div class="{width section='header' position='header3'}">{ia_blocks block='header3'}</div>
						<div class="{width section='header' position='header4'}">{ia_blocks block='header4'}</div>
					</div>
				</div>
			</div>
		{/if}

		{ia_hooker name='smartyFrontBeforeBreadcrumb'}

		{include file='breadcrumb.tpl'}

		{if isset($iaBlocks.verytop)}
			<div class="verytop">
				<div class="container">
					{ia_blocks block='verytop'}
				</div>
			</div>
		{/if}

		<div class="main-content">
			<div class="container">
				<div class="row">

					<div class="{width section='content' position='left'}">
						{ia_blocks block='left'}
					</div>

					<div class="{width section='content' position='center'}">
						<div class="content-wrap">

							{ia_blocks block='top'}

							<h1 class="page-header">{$pageTitle}</h1>

							{ia_hooker name='smartyFrontBeforeNotifications'}
							{include file='notification.tpl'}

							{ia_hooker name='smartyFrontBeforeMainContent'}

							{$_content_}

							{ia_hooker name='smartyFrontAfterMainContent'}

							{ia_blocks block='bottom'}

							{if isset($iaBlocks.user1) || isset($iaBlocks.user2)}
								<div class="row-fluid">
									<div class="{width section='user-blocks' position='user1'}">{ia_blocks block='user1'}</div>
									<div class="{width section='user-blocks' position='user2'}">{ia_blocks block='user2'}</div>
								</div>
							{/if}
						</div>
					</div>

					<div class="{width section='content' position='right'}">
						{ia_blocks block='right'}
					</div>

				</div>
			</div>
		</div>

		{if isset($iaBlocks.verybottom)}
			<div class="verybottom">
				<div class="container">{ia_blocks block='verybottom'}</div>
			</div>
		{/if}

		{if isset($iaBlocks.footer1) || isset($iaBlocks.footer2) || isset($iaBlocks.footer3) || isset($iaBlocks.footer4)}
			<div class="footer-blocks">
				<div class="container">
					<div class="row">
						<div class="{width section='footer' position='footer1'}">{ia_blocks block='footer1'}</div>
						<div class="{width section='footer' position='footer2'}">{ia_blocks block='footer2'}</div>
						<div class="{width section='footer' position='footer3'}">{ia_blocks block='footer3'}</div>
						<div class="{width section='footer' position='footer4'}">{ia_blocks block='footer4'}</div>
					</div>
				</div>
			</div>
		{/if}

		<footer class="footer">
			<div class="container">
				{ia_hooker name='smartyFrontBeforeFooterLinks'}

				{if $config.startup_social}
					<ul class="social social--dark">
						{if $config.startup_social_t}<li><a href="{$config.startup_social_t}" class="twitter"><i class="icon-twitter"></i></a></li>{/if}
						{if $config.startup_social_f}<li><a href="{$config.startup_social_f}" class="facebook"><i class="icon-facebook"></i></a></li>{/if}
						{if $config.startup_social_g}<li><a href="{$config.startup_social_g}" class="google-plus"><i class="icon-google-plus"></i></a></li>{/if}
					</ul>
				{/if}

				{ia_blocks block='copyright'}
				<p class="copyright">&copy; {$smarty.server.REQUEST_TIME|date_format:'%Y'} {lang key='powered_by_subrion'}</p>

				{ia_hooker name='smartyFrontAfterFooterLinks'}
			</div>
		</footer>

		<!-- SYSTEM STUFF -->

		{if $config.cron}
			<div style="display: none;">
				<img src="{$nonProtocolUrl}cron/?{randnum}" width="1" height="1" alt="">
			</div>
		{/if}

		{if isset($manageMode)}
			{include file='visual-mode.tpl'}
		{/if}

		{if isset($previewMode)}
			<p>{lang key='youre_in_preview_mode'}</p>
		{/if}

		{ia_print_js display='on'}

		{ia_hooker name='smartyFrontFinalize'}
	</body>
</html>