<div class="ia-form-system">
    <form action="{$smarty.const.IA_URL}login/" method="post">
        {preventCsrf}

        <div class="form-group">
            <input class="form-control input-lg" type="text" name="username" value="{if isset($smarty.post.username)}{$smarty.post.username|escape}{/if}" placeholder="{lang key='username_or_email'}">
        </div>

        <div class="form-group">
            <input class="form-control input-lg" type="password" name="password" placeholder="{lang key='password'}">
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-6">
                    <div class="checkbox-inline">
                        <label><input type="checkbox" name="remember"> {lang key='remember_me'}</label>
                    </div>
                </div>
                <div class="col-md-6 text-right">
                    <a href="{$smarty.const.IA_URL}forgot/">{lang key='forgot'}</a>
                </div>
            </div>
        </div>

        <div class="form-group">
            <button class="btn btn-success btn-block btn-lg" type="submit" name="login">{lang key='login'}</button>
        </div>

        <p class="text-center  m-b-0">
            <a href="{$smarty.const.IA_URL}registration/" rel="nofollow">{lang key='registration'}</a>
        </p>
        {if $core.providers}
            <div class="social-providers">
                <p>{lang key='login_with_social_network'}:</p>
                {foreach $core.providers as $name => $provider}
                    <a class="btn btn-block btn-social btn-{$name|lower}" href="{$smarty.const.IA_URL}login/{$name|lower}/"><span class="fa fa-{$name|lower}"></span> {$name}</a>
                {/foreach}
            </div>
        {/if}
    </form>
</div>
