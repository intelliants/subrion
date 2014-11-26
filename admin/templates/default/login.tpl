<!DOCTYPE html>
<html lang="{$config.lang}">
<head>
	<meta charset="{$config.charset}">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="generator" content="Subrion CMS {$config.version}">

	<base href="{$url}">

	<title>{ia_print_title}</title>

	<!--[if lt IE 9]>
	<script src="../../../js/utils/shiv.js"></script>
	<script src="../../../js/utils/respond.min.js"></script>
	<![endif]-->

	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="{$img}ico/apple-touch-icon-144-precomposed.png">
	<link rel="apple-touch-icon-precomposed" sizes="114x114" href="{$img}ico/apple-touch-icon-114-precomposed.png">
	<link rel="apple-touch-icon-precomposed" sizes="72x72" href="{$img}ico/apple-touch-icon-72-precomposed.png">
	<link rel="apple-touch-icon-precomposed" href="{$img}ico/apple-touch-icon-57-precomposed.png">
	<link rel="shortcut icon" href="{$img}favicon.png">
	<link rel="shortcut icon" href="{$img}favicon.ico">

	{ia_print_css files='bootstrap' order=99}
	{ia_add_media files='jquery, subrion, js:admin/login' order=0}
	{ia_print_js files='_IA_TPL_bootstrap.min'}

	{ia_print_css display='on'}

	{ia_add_js order=0}
		{foreach $customConfig as $key => $item}
			intelli.config.{$key} = '{$item}';
		{/foreach}
	{/ia_add_js}
</head>
<body id="page-login">

	<div class="login-block effect-1">

		<div class="login-block-content">
			<div class="login-header clearfix">
				<a href="http://www.subrion.org/" class="logo"><img src="{$img}logo-symbol-150.png" alt="Subrion CMS" title="Subrion CMS"/></a>
				<h3>{lang key='welcome_to_admin_panel'}</h3>
			</div>
			<div class="login-body">
				<!-- <p class="help-block">{lang key='login_to_text'}</p> -->
				{if isset($access_denied) && $access_denied}
					<div class="alert alert-danger">{lang key='access_denied'}</div>
				{/if}
				{if isset($error_login) && $error_login}
					<div class="alert alert-danger">{lang key='error_login'}</div>
				{/if}
				{if isset($empty_login) && $empty_login}
					<div class="alert alert-danger">{lang key='empty_login'}</div>
				{/if}

				<form method="post" class="sap-form">
					{preventCsrf}
					<p>
						<input type="text" id="username" name="username" tabindex="1" value="{if isset($smarty.post.username)}{$smarty.post.username|escape:"html"}{/if}" autofocus placeholder="{lang key='login'}">
					</p>
					<p>
						<input type="password" id="dummy_password" name="password" tabindex="2" placeholder="{lang key='password'}">
					</p>
					{if count($languages) > 1}
					<p>
						<select name="_lang" id="_lang">
							{foreach $languages as $code => $language}
							<option value="{$code}"{if $code == $smarty.const.IA_LANGUAGE} selected{/if}>{$language}</option>
							{/foreach}
						</select>
					</p>
					{/if}
					<input type="submit" class="btn btn-primary" tabindex="3" value="{lang key='login'}">
					<a href="#" class="btn btn-link" id="js-forgot-dialog">{lang key='forgot_password'}</a>
				</form>
			</div>
			<div class="js-login-body-forgot-password">
				<form action="" method="post" class="sap-form">
					<div class="alert" style="display: none;">{lang key='error_email_incorrect'}</div>
					{preventCsrf}
					<p class="help-block">{lang key='restore_password'}</p>
					<p>
						<input type="text" id="email" name="email" tabindex="1" placeholder="{lang key='type_email_here'}">
					</p>
					<input id="js-forgot-submit" type="submit" class="btn btn-primary" tabindex="2" value="{lang key='go'}">
					<input  id="js-forgot-dialog-close" type="submit" class="btn btn-link" tabindex="3" value="{lang key='cancel'}">
				</form>
			</div>
			<p class="copyright">
				Powered by <a href="http://www.subrion.org/" title="Subrion CMS">Subrion CMS v{$config.version}</a><br>
				Copyright &copy; 2008-{$smarty.now|date_format:'%Y'} <a href="http://www.intelliants.com/" title="Intelligent Web Solutions">Intelliants LLC</a>
			</p>
			<a href="{$smarty.const.IA_URL}" class="back-to-home"><span>←</span> {lang key='back_to_homepage'}</a>
		</div>
	</div>

	{ia_hooker name='smartyAdminFooterBeforeJsDisplay'}
	{ia_print_js display='on'}
	{ia_hooker name='smartyAdminFooterAfterJsDisplay'}

</body>
</html>