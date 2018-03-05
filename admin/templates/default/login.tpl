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

        {ia_print_css files="bootstrap-{$core.config.sap_style}" order=0}

        {ia_add_media files='jquery, subrion, js:admin/login' order=0}
        {ia_print_js files='_IA_TPL_bootstrap.min'}

        {ia_print_css display='on'}

        {include 'custom-config.tpl'}
    </head>

    <body class="page-login">
        <div class="page-login__content">
            <div class="b-login">
                <div class="b-login__img">
                    <a href="https://subrion.org/"><img src="{$img}subrion-logo-lines.png" alt="Subrion CMS" title="Subrion CMS"/></a>
                    <h1>{lang key='welcome_to_admin_panel'}</h1>
                </div>
                <div class="b-login__form">
                    <div class="login-body">
                        {if !empty($access_denied)}
                            <div class="alert alert-danger">{lang key='access_denied'}</div>
                        {/if}
                        {if !empty($error_login)}
                            <div class="alert alert-danger">{lang key='error_login'}</div>
                        {/if}
                        {if !empty($empty_login)}
                            <div class="alert alert-danger">{lang key='empty_login'}</div>
                        {/if}

                        <form method="post" class="sap-form">
                            {preventCsrf}
                            <p>
                                <input type="text" id="username" name="username" value="{if isset($smarty.post.username)}{$smarty.post.username|escape:"html"}{/if}" autofocus placeholder="{lang key='login'}">
                            </p>
                            <p>
                                <input type="password" id="dummy_password" name="password" placeholder="{lang key='password'}">
                            </p>
                            <div class="checkbox">
                                <label><input type="checkbox" name="remember"{if isset($smarty.post.remember)} checked{/if}> {lang key='remember_me'}</label>
                            </div>
                            {if count($core.languages) > 1}
                            <p>
                                <select name="_lang" id="_lang">
                                    {foreach $core.languages as $code => $language}
                                        <option value="{$code}"{if $code == $smarty.const.IA_LANGUAGE} selected{/if}>{$language.title}</option>
                                    {/foreach}
                                </select>
                            </p>
                            {/if}
                            <input type="submit" class="btn btn-primary" value="{lang key='login'}">
                            <a href="#" class="btn btn-link" id="js-forgot-dialog">{lang key='forgot_password'}</a>
                        </form>
                    </div>
                    <div class="js-login-body-forgot-password" style="display: none;">
                        <form method="post" class="sap-form">
                            <div class="alert" style="display: none;">{lang key='error_email_incorrect'}</div>
                            {preventCsrf}
                            <p class="help-block">{lang key='restore_password'}</p>
                            <p>
                                <input type="text" id="email" name="email" placeholder="{lang key='type_email_here'}">
                            </p>
                            <input id="js-forgot-submit" type="submit" class="btn btn-primary" value="{lang key='go'}">
                            <input  id="js-forgot-dialog-close" type="submit" class="btn btn-link" value="{lang key='cancel'}">
                        </form>
                    </div>
                    <div class="copyright">
                        <p>
                            Powered by <a href="https://subrion.org/" title="Subrion CMS">Subrion CMS v{$core.config.version}</a><br>
                            Copyright &copy; 2008-{$smarty.now|date_format:'%Y'} <a href="https://intelliants.com/" title="Intelligent Web Solutions">Intelliants LLC</a>
                        </p>
                        <p>
                            <a href="{$smarty.const.IA_URL}" class="back-to-home"><span>←</span> {lang key='back_to_homepage'}</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {ia_hooker name='smartyAdminFooterBeforeJsDisplay'}
        {ia_print_js display='on'}
        {ia_hooker name='smartyAdminFooterAfterJsDisplay'}
    </body>
</html>