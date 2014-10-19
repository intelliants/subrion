<form method="post" enctype="multipart/form-data" class="ia-form">
	{preventCsrf}

	{if !empty($assignableGroups)}
		{capture append='fieldset_content_before' name='general'}
			<div class="control-group">
				<label class="control-label" for="input-group">{lang key='group'}</label>
				<div class="controls">
					<select name="usergroup_id" id="input-group">
						<option value="8">{lang key='default'}</option>
						{foreach $assignableGroups as $id => $title}
							<option value="{$id}"{if $id == $item.usergroup_id} selected{/if}>{$title}</option>
						{/foreach}
					</select>
				</div>
			</div>
		{/capture}
	{/if}

	{capture append='tabs_content' name='password'}
		<div class="fieldset-wrapper">
			<div class="fieldset">
				<h3 class="title">{lang key='change_password'}</h3>
				<div class="content">
					<div class="control-group">
						<label class="control-label" for="current">{lang key='current_password'}:</label>
						<div class="controls">
							<input type="password" name="current" id="current">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="new">{lang key='new_password'}:</label>
						<div class="controls">
							<input type="password" name="new" id="new">
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="confirm">{lang key='new_password2'}:</label>
						<div class="controls">
							<input type="password" name="confirm" id="confirm">
						</div>
					</div>
				</div>
				<div class="actions">
					<button type="submit" name="change_pass" class="btn btn-primary btn-plain">{lang key='change_password'}</button>
				</div>
			</div>
		</div>
	{/capture}

	{if $plans_count}
		{capture append='tabs_content' name='member_balance'}
			<div class="fieldset-wrapper">
				<div class="fieldset">
					{if $item.funds}
						<h3 class="title">{lang key='member_balance'}: {$item.funds|string_format:'%d'} {$config.currency}</h3>
					{else}
						<p class="alert alert-info">{lang key='no_funds'}</p>
					{/if}
					{preventCsrf}
					<div class="actions"><button type="button" class="btn btn-primary btn-plain" id="js-add-funds">{lang key='add_funds'}</button></div>
					{ia_add_media files='js:frontend/member-balance'}
				</div>
			</div>
		{/capture}
	{/if}

	{if $plans}
		{capture append='tabs_content' name='plans'}
			{include file='plans.tpl' item=$member}
			<div class="actions"><button type="submit" class="btn btn-primary btn-plain">{lang key='save'}</button></div>
		{/capture}
	{/if}

	{* use this to exclude tabs where you don't need capture named __all__ *}
	{append 'tabs_after' array('password', 'member_balance', 'plans') index='excludes'}

	{capture append='tabs_after' name='__all__'}
		<div class="actions"><button type="submit" name="change_info" class="btn btn-primary btn-plain">{lang key='save'}</button></div>
	{/capture}

	{ia_hooker name='frontEditProfile'}

	{include file='item-view-tabs.tpl'}
</form>