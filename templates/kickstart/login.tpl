<div class="ia-form-system">
	<form action="{$smarty.const.IA_URL}login/" method="post">
		{preventCsrf}

		<div class="form-group">
			<input class="form-control" type="text" tabindex="4" name="username" value="{if isset($smarty.post.username)}{$smarty.post.username|escape:'html'}{/if}" placeholder="{lang key='username_or_email'}">
		</div>

		<div class="form-group">
			<input class="form-control" type="password" tabindex="5" name="password" placeholder="{lang key='password'}">
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
			<button class="btn btn-primary btn-block" type="submit" tabindex="6" name="login">{lang key='login'}</button>
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
{*
<div class="row">
	<div class="col-md-{if $core.providers}8{else}12{/if}">
		
	</div>
	{if $core.providers}
		<div class="col-md-4">
			<div class="social-providers">
				<p>{lang key='login_with_social_network'}:</p>
				{foreach $core.providers as $name => $provider}
					<a class="btn btn-block btn-social btn-{$name|lower}" href="{$smarty.const.IA_URL}login/{$name|lower}/"><span class="fa fa-{$name|lower}"></span> {$name}</a>
				{/foreach}
			</div>
		</div>
	{/if}
</div>
*}