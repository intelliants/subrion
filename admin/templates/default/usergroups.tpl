<form action="{$url}usergroups/{$pageAction}/" method="post" class="sap-form form-horizontal">
	{preventCsrf}

	{if 'add' == $pageAction}
		<div class="wrap-list">
			<div class="wrap-group">
				<div class="wrap-group-heading">
					<h4>{lang key='options'}</h4>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label" for="input-title">{lang key='group'}</label>

					<div class="col col-lg-4">
						<input type="text" name="title" id="input-title" value="{if isset($smarty.post.title)}{$smarty.post.title}{/if}">
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label" for="input-source">{lang key='copy_privileges_from'}</label>

					<div class="col col-lg-4">
						<select name="copy_from" id="input-source">
							{foreach $groups as $id => $group}
								<option value="{$id}"{if isset($smarty.post.copy_from) && $smarty.post.copy_from == $id} selected="selected"{/if}>{$group}</option>
							{/foreach}
						</select>
					</div>
				</div>
			</div>
		</div>
	{/if}

	<div class="form-actions inline">
		<input type="hidden" name="action" value="{$pageAction}">
		<input type="hidden" name="params" id="all_acts">
		<input type="submit" value="{lang key='save'}" class="btn btn-primary">
		<input type="reset" value="{lang key='reset'}" class="btn btn-danger" id="acts_reset">
	</div>
</form>