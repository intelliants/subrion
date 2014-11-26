<!DOCTYPE html>
<html lang="{$config.lang}">
	<head>
		<title>{ia_print_title title=$pageTitle}</title>
		<meta http-equiv="Content-Type" content="text/html;charset={$config.charset}">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="generator" content="Subrion CMS {$config.version}">

		<link rel="shortcut icon" href="{$nonProtocolUrl}favicon.ico">

		<link href="{$nonProtocolUrl}templates/{$config.tmpl}/css/iabootstrap{if isset($smarty.cookies.template_color_scheme)}-{$smarty.cookies.template_color_scheme}{elseif isset($config.template_color_scheme)}-{$config.template_color_scheme}{/if}.css" rel="stylesheet">
		<link href="{$nonProtocolUrl}templates/{$config.tmpl}/css/iabootstrap-responsive.css" rel="stylesheet">
	</head>

	<body>
		{ia_hooker name='smartyRedirectPage'}

		<div class="redirect-block">
			{if isset($redir)}
				<h3>{$redir.caption}</h3>
				<div class="alert alert-info">{$redir.msg}</div>

				{include file=$redir.form}
			{/if}
		</div>

		{ia_add_js}
$(function()
{
	setTimeout("$('form').submit();", {$config.redirect_time|default:5000});
});
		{/ia_add_js}

		{ia_print_js files='jquery/jquery' order=0 display='on'}
	</body>
</html>