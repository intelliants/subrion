<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-name">{lang key='name'}</label>

				<div class="col col-lg-4">
					<input type="text" name="name" id="input-name" value="{if isset($smarty.post.name)}{$smarty.post.name|escape:'html'}{/if}">
					<p class="help-block">{lang key='unique_name'}</p>
				</div>
			</div>

			{foreach $core.languages as $code => $language}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='title'}{if count($core.languages) > 1} <span class="label label-info">{$language.title}</span>{/if}</label>

					<div class="col col-lg-4">
						<input type="text" name="title[{$code}]" value="{if isset($smarty.post.title[$code]) && $smarty.post.title[$code]}{$smarty.post.title[$code]|escape:'html'}{/if}">
					</div>
				</div>
			{/foreach}

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-source">{lang key='copy_privileges_from'}</label>

				<div class="col col-lg-4">
					<select name="copy_from" id="input-source">
						{foreach $groups as $id => $name}
							<option value="{$id}"{if isset($smarty.post.copy_from) && $smarty.post.copy_from == $id} selected{/if}>{lang key="usergroup_{$name}"}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='assignable'} <a href="#" class="js-tooltip" title="{$tooltips.usergroup_assignable}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					{html_radio_switcher value=0 name='assignable'}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='visible_on_members'} <a href="#" class="js-tooltip" title="{$tooltips.usergroup_visible}"><i class="i-info"></i></a></label>

				<div class="col col-lg-4">
					{html_radio_switcher value=0 name='visible'}
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions inline">
		<input type="submit" name="save" value="{lang key='save'}" class="btn btn-primary">
		<input type="reset" value="{lang key='reset'}" class="btn btn-danger">
	</div>
</form>