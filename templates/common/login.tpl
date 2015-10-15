<form action="{$smarty.const.IA_URL}login/" method="post">
	{preventCsrf}

	<div class="form-group">
		<label for="field_login">{lang key='username_or_email'}:</label>
		<input class="form-control" type="text" tabindex="4" name="username" value="{if isset($smarty.post.username)}{$smarty.post.username|escape:'html'}{/if}">
	</div>

	<div class="form-group">
		<label for="field_password">{lang key='password'}:</label>
		<input class="form-control" type="password" tabindex="5" name="password">
	</div>

	<div class="form-group form-actions">
		<button class="btn btn-primary" type="submit" tabindex="6" name="login">{lang key='login'}</button>
		<a class="btn btn-link" href="{$smarty.const.IA_URL}forgot/">{lang key='forgot'}</a>
		<a class="btn btn-link" href="{$smarty.const.IA_URL}registration/" rel="nofollow">{lang key='registration'}</a>
	</div>
</form>