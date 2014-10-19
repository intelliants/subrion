<form action="{$smarty.const.IA_URL}login/" method="post" id="login_form" class="ia-form">
	{preventCsrf}

	<div class="control-group">
		<label class="control-label" for="field_login">{lang key='username_or_email'}:</label>
		<div class="controls">
			<input type="text" tabindex="4" name="username" value="{if isset($smarty.post.username)}{$smarty.post.username|escape:'html'}{/if}" id="field_login">
		</div>
	</div>
	<div class="control-group">
		<label class="control-label" for="field_password">{lang key='password'}:</label>
		<div class="controls">
			<input type="password" tabindex="5" name="password" id="field_password">
		</div>
	</div>

	<div class="form-actions">
		<button type="submit" tabindex="6" name="login" class="btn btn-primary btn-plain">{lang key='login'}</button>
		<a href="{$smarty.const.IA_URL}forgot/" class="btn btn-info btn-plain">{lang key='forgot'}</a>
		<a href="{$smarty.const.IA_URL}registration/" class="btn btn-success btn-plain pull-right" rel="nofollow">{lang key='registration'}</a>
	</div>
</form>