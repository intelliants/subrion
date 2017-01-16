<!DOCTYPE html>
<html lang="{$core.language.iso}" dir="{$core.language.direction}">
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=Edge">
		<title>{ia_print_title}</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="generator" content="Subrion CMS {$core.config.version}">

<link rel="shortcut icon" href="{if !empty($core.config.site_favicon)}{$core.page.nonProtocolUrl}uploads/{$core.config.site_favicon}{else}{$core.page.nonProtocolUrl}favicon.ico{/if}">

		<link href="{$core.page.nonProtocolUrl}templates/{$core.config.tmpl}/css/iabootstrap{if isset($smarty.cookies.template_color_scheme)}-{$smarty.cookies.template_color_scheme}{elseif isset($core.config.template_color_scheme)}-{$core.config.template_color_scheme}{/if}.css" rel="stylesheet">
	</head>

	<body class="page-redirect">
		{ia_hooker name='smartyRedirectPage'}

		<div class="redirect-block">
			{if isset($redir)}
				<h3>{$redir.caption}</h3>
				<div class="alert alert-info">{$redir.msg}</div>

				{include $redir.form}
			{/if}
		</div>

		{ia_add_js}
$(function()
{
	setTimeout("$('form').submit();", {$core.config.redirect_time|default:5000});
});
		{/ia_add_js}

		{ia_print_js files='jquery/jquery' order=0 display='on'}
	</body>
</html>