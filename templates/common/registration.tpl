<p>{lang key='registration_annotation'}</p>

<form method="post" action="{$smarty.const.IA_SELF}" enctype="multipart/form-data" class="ia-form ia-form--bordered">
	{preventCsrf}

	{include file='plans.tpl' item=$tmp}

	{include file='field-type-content-fieldset.tpl' item_sections=$sections item=$tmp}

	<div class="fieldset">
		<div class="fieldset__header">{lang key='password'}</div>
		<div class="fieldset__content">
			<div class="checkbox">
				<label>
					<input type="checkbox" name="disable_fields" id="disable_fields" value="1"{if isset($smarty.post.disable_fields) && $smarty.post.disable_fields} checked{/if}> {lang key='auto_generate_password'}
				</label>
			</div>

			<div id="pass_fieldset" {if isset($smarty.post.disable_fields) && 1 == $smarty.post.disable_fields}style="display: none;"{/if}>
				<div class="form-group">
					<label for="pass1">{lang key='your_password'}:</label>
					<input class="form-control" type="password" name="password" id="pass1" value="{if isset($tmp.password)}{$tmp.password}{/if}">
				</div>
				<div class="form-group">
					<label for="pass2">{lang key='your_password_confirm'}:</label>
					<input class="form-control" type="password" name="password2" id="pass2" value="{if isset($tmp.password)}{$tmp.password}{/if}">
				</div>
			</div>
		</div>
	</div>

	{include file='captcha.tpl'}

	<div class="fieldset__actions">
		<button type="submit" name="register" class="btn btn-success">{lang key='registration'}</button>
	</div>
</form>

{ia_print_js files='frontend/registration'}

{if $core.providers}
	{foreach $core.providers as $name => $provider}
		<a class="btn btn-{$name|lower}" href="{$smarty.const.IA_URL}login/{$name|lower}/"><i class="icon-{$name|lower}"></i> {$name}</a>
	{/foreach}
{/if}