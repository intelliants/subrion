<!DOCTYPE html>
<html lang="{$config.lang}">
	<head>
		<title>{ia_print_title title=$pageTitle}</title>
		<meta http-equiv="Content-Type" content="text/html;charset={$config.charset}">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="generator" content="Subrion CMS {$config.version}">

		<link rel="shortcut icon" href="{$nonProtocolUrl}favicon.ico">

		<link href="{$nonProtocolUrl}templates/{$config.tmpl}/css/iabootstrap{if isset($smarty.cookies.template_color_scheme) && isset($config.template_color_scheme)}-{$smarty.cookies.template_color_scheme}{elseif isset($config.template_color_scheme)}-{$config.template_color_scheme}{/if}.css" rel="stylesheet">
		<link href="{$nonProtocolUrl}templates/{$config.tmpl}/css/iabootstrap-responsive.css" rel="stylesheet">
	</head>

	<body>
		{ia_hooker name='smartyRedirectPage'}

		<div class="redirect-block">
		{if isset($redir)}
			<h3>{$redir.caption}</h3>
			<div class="alert alert-info">
				{$redir.msg}
			</div>
			<p class="muted text-small help-block">{lang key='wait_redirect'}<br><a href="{$redir.url}">{lang key='dont_wait_redir'}</a></p>
			<hr>
			<p class="muted text-small help-block"><a href="{$smarty.const.IA_URL}">{lang key='click_here'}</a> {lang key='redirected_to_home'}</p>
		{else}
			<p class="muted text-small help-block"><a href="{$smarty.const.IA_URL}">{lang key='click_here'}</a> {lang key='redirected_to_home'}</p>
		{/if}
		</div>

		{ia_add_js}
$(function()
{
	setTimeout("location.href = '{$redirect_url}'", {$config.redirect_time|default:4000});
});
		{/ia_add_js}

		{ia_print_js files='jquery/jquery' order='0' display='on'}

	</body>
</html>