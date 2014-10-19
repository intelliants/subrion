{if 'confirm' == $form}
	<form action="{$smarty.const.IA_URL}forgot/" method="get" class="ia-form">
		{preventCsrf}
		<label>{lang key='email'}:</label>
		{if isset($smarty.post.email)}
			<input type="text" name="email" value="{$smarty.post.email|escape:'html'}">
		{elseif  isset($smarty.get.email)}
			<input type="text" name="email" value="{$smarty.get.email|escape:'html'}">
		{else}
			<input type="text" name="email">
		{/if}
		<label>{lang key='code'}:</label>
		<p class="form-horizontal">
			<input type="text" name="code"{if isset($smarty.get.code)} value="{$smarty.get.code|escape:'html'}"{/if}>
			<button type="submit" class="btn btn-primary">{lang key='send'}</button>
		</p>
	</form>
{elseif 'request' == $form}
	<p>{lang key='forgot_annotation'}</p>

	<form action="{$smarty.const.IA_URL}forgot/" method="post" class="ia-form bordered">
		<div class="fieldset">
			<div class="content">
				{preventCsrf}
				<label>{lang key='email'}:</label>
				<input type="text" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|escape:'html'}{/if}">
				{include file='captcha.tpl'}
			</div>
		</div>
		<div class="actions">
			<button type="submit" name="restore" class="btn btn-primary btn-plain">{lang key='restore_password'}</button>
		</div>
	</form>
{/if}