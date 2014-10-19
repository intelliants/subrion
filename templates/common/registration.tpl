<p>{lang key='registration_annotation'}</p>

<form method="post" action="{$smarty.const.IA_SELF}" enctype="multipart/form-data" class="ia-form ia-form-bordered">
	{preventCsrf}

	{include file='plans.tpl' item=$tmp}

	{include file='field-type-content-fieldset.tpl' item_sections=$sections item=$tmp}

	<div class="fieldset password">
		<h3 class="title">{lang key='password'}</h3>
		<div class="content">
			<div class="control-group">
				<div class="controls">
					<label class="checkbox" for="disable_fields">
						<input type="checkbox" id="disable_fields" name="disable_fields" value="1"{if isset($smarty.post.disable_fields) && $smarty.post.disable_fields} checked{/if}> {lang key='auto_generate_password'}
					</label>
				</div>
			</div>

			<div id="pass_fieldset" {if isset($smarty.post.disable_fields) && 1 == $smarty.post.disable_fields}style="display: none;"{/if}>
				<div class="control-group">
					<label class="control-label" for="pass1">{lang key='your_password'}:</label>
					<div class="controls">
						<input type="password" name="password" id="pass1" value="{if isset($tmp.password)}{$tmp.password}{/if}">
					</div>
				</div>
				<div class="control-group">
					<label class="control-label" for="pass2">{lang key='your_password_confirm'}:</label>
					<div class="controls">
						<input type="password" name="password2" id="pass2" value="{if isset($tmp.password)}{$tmp.password}{/if}">
					</div>
				</div>
			</div>

			{include file='captcha.tpl'}
		</div>

		<div class="actions">
			<button type="submit" name="register" class="btn btn-success btn-plain">{lang key='registration'}</button>
		</div>
	</div>
</form>

{ia_print_js files='frontend/registration'}