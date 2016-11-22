<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
	{preventCsrf}

	{capture 'email' append='field_after'}
		<div class="row">
			<label class="col col-lg-2 control-label" for="translatable">Translatable field</label>
			<div class="col col-lg-4">
				<div class="translate-group">
					<div class="translate-group__default">
						<div class="translate-group__item">
							<input type="text" name="translatable[en]" id="translatable" value="">
							<div class="translate-group__item__code">English</div>
						</div>
					</div>
					<div class="translate-group__langs">
						<div class="translate-group__item">
							<input type="text" name="translatable[ru]" id="translatable[ru]" value="">
							<span class="translate-group__item__code">Russian</span>
						</div>
						<div class="translate-group__item">
							<input type="text" name="translatable[es]" id="translatable[es]" value="">
							<span class="translate-group__item__code">Espaniol</span>
						</div>
					</div>
					<div class="translate-group__edit js-edit-lang-group"><span class="i-earth"></span></div>
				</div>
			</div>
		</div>

		<div class="row">
			<label class="col col-lg-2 control-label" for="translatable_textarea">Translatable textarea</label>
			<div class="col col-lg-4">
				<div class="translate-group">
					<div class="translate-group__default">
						<div class="translate-group__item">
							<textarea name="translatable_textarea[en]" id="translatable_textarea[en]" rows="5"></textarea>
							<div class="translate-group__item__code">English</div>
						</div>
					</div>
					<div class="translate-group__langs">
						<div class="translate-group__item">
							<textarea name="translatable_textarea[ru]" id="translatable_textarea[ru]" rows="5"></textarea>
							<span class="translate-group__item__code">Russian</span>
						</div>
						<div class="translate-group__item">
							<textarea name="translatable_textarea[es]" id="translatable_textarea[es]" rows="5"></textarea>
							<span class="translate-group__item__code">Espaniol</span>
						</div>
					</div>
					<div class="translate-group__edit js-edit-lang-group"><span class="i-earth"></span></div>
				</div>
			</div>
		</div>

		{ia_add_js}
$(function()
{
	$('.js-edit-lang-group').click(function() {
		var $this = $(this),
			$parent = $this.closest('.translate-group'),
			$group = $parent.find('.translate-group__langs');

		if (!$parent.hasClass('is-opened'))
		{
			$group.slideDown('fast');
			$parent.addClass('is-opened');
		}
		else
		{
			$group.slideUp('fast');
			$parent.removeClass('is-opened');
		}
	});
});
		{/ia_add_js}

		{access object='admin_pages' id='members' action='password'}
			<hr>

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

			<hr>
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
							{foreach $usergroups as $value => $name}
							<option{if $item.usergroup_id == $value} selected{/if} value="{$value}">{lang key="usergroup_{$name}"}</option>
							{/foreach}
						</select>
					{/if}
				</div>
			</div>
		{/access}
	{/capture}

	{ia_hooker name='smartyAdminSubmitItemBeforeFields'}

	{capture 'systems' append='fieldset_after'}
		{if isset($item.status) && iaUsers::STATUS_UNCONFIRMED == $item.status}
			<div class="row">
				<div class="col col-lg-2"></div>
				<div class="col col-lg-4">
					<button type="button" class="btn btn-sm btn-default btn-warning" id="js-cmd-send-reg-email">{lang key='resend_registration_email'}</button>
				</div>
			</div>
			{ia_add_js}
$(function()
{
	$('#js-cmd-send-reg-email').on('click', function()
	{
		var $btn = $(this);

		intelli.confirm(_t('are_you_sure_resend_registration_email'), null, function(result)
		{
			if (result)
			{
				$.post(intelli.config.admin_url + '/members/registration-email.json', { id: {$id} }, function(response)
				{
					intelli.notifFloatBox({ msg: response.message, type: response.result ? 'success' : 'error', autohide: true });
					$btn.prop('disabled', true);
				});
			}
		});
	});
});
			{/ia_add_js}
		{/if}
	{/capture}

	{include 'field-type-content-fieldset.tpl' isSystem=true}
</form>

{ia_hooker name='smartyAdminSubmitItemBeforeFooter'}