<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
	{preventCsrf}

	{capture name='email' append='field_after'}
		{access object='admin_pages' id='members' action='password'}
		<div class="row">
			<label class="col col-lg-2 control-label" for="input-password">{lang key='password'}</label>

			<div class="col col-lg-4">
				<input type="password" class="js-input-password" name="_password" id="input-password" value="{if isset($smarty.post._password)}{$smarty.post._password|escape:'html'}{/if}">
			</div>
		</div>

		<div class="row">
			<label class="col col-lg-2 control-label" for="input-password-confirmation">{lang key='password_confirm'}</label>

			<div class="col col-lg-4">
				<input type="password" name="_password2" id="input-password-confirmation" value="{if isset($smarty.post._password2)}{$smarty.post._password2|escape:'html'}{/if}">
			</div>
		</div>
		{/access}

		{access object='admin_pages' id='members' action='usergroup'}
		<div class="row">
			<label class="col col-lg-2 control-label" for="input-usergroup">{lang key='usergroup'}</label>

			<div class="col col-lg-4">
			{if isset($admin_count) && $admin_count == 1 && $item.usergroup_id == 1}
				<div class="alert alert-info">{lang key='usergroup_disabled'}</div>
				<input type="hidden" name="usergroup_id" value="1">
			{else}
				<select name="usergroup_id" id="input-usergroup">
					{foreach $usergroups as $value => $usergroup}
					<option{if $item.usergroup_id == $value} selected{/if} value="{$value}">{$usergroup}</option>
					{/foreach}
				</select>
			{/if}
			</div>
		</div>
		{/access}
	{/capture}

	{include file='field-type-content-fieldset.tpl' isSystem=true}
</form>