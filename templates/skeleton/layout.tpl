<!DOCTYPE html>
<html lang="en">
	<head>
		{ia_hooker name='smartyFrontBeforeHeadSection'}

		<title>{ia_print_title title=$gTitle|default:$pageTitle}</title>
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
			<script src="{$smarty.const.IA_CLEAR_URL}js/utils/shiv.js"></script>
		<![endif]-->

		<link rel="shortcut icon" href="{$smarty.const.IA_CLEAR_URL}favicon.ico">

		{ia_add_media files='jquery, subrion, bootstrap' order=0}
		{ia_print_js files='_IA_TPL_app' order=999}

		{ia_hooker name='smartyFrontAfterHeadSection'}

		{ia_print_css display='on'}

		{ia_add_js}
			{foreach $customConfig as $key => $item}
				intelli.config.{$key} = '{$item}';
			{/foreach}
		{/ia_add_js}
	</head>

	<body{if $config.sticky_navbar} class="sticky-navbar"{/if}>
		<header>

			<section class="section section-narrow inventory">
				<div class="container">
					{ia_blocks block='inventory'}

					{if $config.language_switch && count($languages) > 1}
						<ul class="nav nav-pills nav-langs">
							{foreach $languages as $code => $language}
								<li{if $smarty.const.IA_LANGUAGE == $code} class="active"{/if}><a href="{$smarty.const.IA_CLEAR_URL}{$code}/" title="{$language}">{$language}</a></li>
							{/foreach}
						</ul>
					{/if}
				</div>
			</section>

			<section class="section navigation">
				<div class="container">
					<div class="nav-bar">
						<a class="brand" href="{$smarty.const.IA_URL}">
							{if !empty($config.site_logo)}
								<img src="{$smarty.const.IA_CLEAR_URL}uploads/{$config.site_logo}" alt="{$config.site}">
							{else}
								<img src="{$img}logo.png" alt="{$gTitle}">
							{/if}
						</a>

						<a href="#" class="nav-toggle pull-left" data-toggle="collapse" data-target=".nav-bar-collapse"><i class="icon-reorder"></i></a>

						<div class="nav-bar-collapse">
							<form id="fast-search" method="post" action="{$smarty.const.IA_URL}search/" class="form-inline">
								<div class="control-group">
									<input type="text" name="q" placeholder="{lang key='search'}" class="span2">
									<button type="submit"><i class="icon-search"></i></button>
								</div>
							</form>

							{ia_blocks block='mainmenu'}

						</div>
					</div>
				</div>
			</section>

			{ia_blocks block='header'}

		</header>

		{if isset($iaBlocks.after_header_1) || 
			isset($iaBlocks.after_header_2) || 
			isset($iaBlocks.after_header_3)}
			<section id="after-header" class="section section-dark top-headlines">
				<div class="container">
					<div class="row">
						<div class="{width section='after-header' position='after_header_1' movable=true}">
							{ia_blocks block='after_header_1'}
						</div>
						<div class="{width section='after-header' position='after_header_2' movable=true}">
							{ia_blocks block='after_header_2'}
						</div>
						<div class="{width section='after-header' position='after_header_3' movable=true}">
							{ia_blocks block='after_header_3'}
						</div>
					</div>
				</div>
			</section>
		{/if}

		{if isset($iaBlocks.verytop)}
			<div class="section section-light">
				<div class="container">
					{ia_blocks block='verytop' movable=true}
				</div>
			</div>
		{/if}

		{ia_hooker name='smartyFrontBeforeBreadcrumb'}

		{include file='breadcrumb.tpl'}

		<section id="content" class="section">
			<div class="container">
				<div class="row">

					<div class="{width section='content' position='left' movable=true}">
						{ia_blocks block='left'}
					</div>

					<div class="{width section='content' position='center' movable=true}">
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
								<div class="row">
									<div class="{width section='user-blocks' position='user1' movable=true}">{ia_blocks block='user1'}</div>
									<div class="{width section='user-blocks' position='user2' movable=true}">{ia_blocks block='user2'}</div>
								</div>
							{/if}
						</div>
					</div>

					<div class="{width section='content' position='right' movable=true}">
						{ia_blocks block='right'}
					</div>

				</div>
			</div>
		</section>

		{if isset($iaBlocks.verybottom)}
			<div class="section section-light">
				<div class="container">{ia_blocks block='verybottom' movable=true}</div>
			</div>
		{/if}

		<section class="section section-dark">
			<div class="container">
				<div class="row">
					<div class="{width section='footer' position='footer1' movable=true}">{ia_blocks block='footer1'}</div>
					<div class="{width section='footer' position='footer2' movable=true}">{ia_blocks block='footer2'}</div>
					<div class="{width section='footer' position='footer3' movable=true}">{ia_blocks block='footer3'}</div>
					<div class="{width section='footer' position='footer4' movable=true}">{ia_blocks block='footer4'}</div>
				</div>
			</div>
		</section>

		<footer class="section section-dark">
			<div class="container">
				{ia_hooker name='smartyFrontBeforeFooterLinks'}
	
				<div class="row">
					<div class="span4">
						<p class="copyright">&copy; {$smarty.server.REQUEST_TIME|date_format:'%Y'} {lang key='powered_by_subrion'}</p>
					</div>
					<div class="span8">
						{ia_blocks block='copyright'}
					</div>
				</div>

				{ia_hooker name='smartyFrontAfterFooterLinks'}
			</div>
		</footer>

		<!-- SYSTEM STUFF -->

		{if $config.cron}
			<div style="display: none;">
				<img src="{$smarty.const.IA_CLEAR_URL}cron/?{randnum}" width="1" height="1" alt="">
			</div>
		{/if}

		{if isset($manageMode) || isset($previewMode) || $ie6}
			<div id="manage-mode">
				{if isset($manageMode)}
					<p><i class="icon-gears"></i>{lang key='youre_in_manage_mode'}</p>
				{/if}
				{if isset($previewMode)}<p>{lang key='youre_in_preview_mode'}</p>{/if}
				{if $ie6}<p>{lang key='youre_in_ie6_mode'}</p>{/if}
			</div>
		{/if}

		{ia_print_js display='on'}

	</body>
</html>