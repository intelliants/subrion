<form action="{$smarty.const.IA_SELF}{if 'edit' == $pageAction}?id={$group.id}{/if}" method="post" class="sap-form form-horizontal">
	{preventCsrf}
	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			{if 'add' == $pageAction}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='name'}</label>

					<div class="col col-lg-4">
						<input type="text" name="name" id="group_name" value="{if isset($smarty.post.name)}{$smarty.post.name|escape:'html'}{/if}">
						<p class="help-block">{lang key='unique_name'}</p>
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='item'}</label>

					<div class="col col-lg-4">
						<select name="item" id="field_item">
							<option value="">{lang key='_select_'}</option>
							{foreach $items as $item}
								<option value="{$item}"{if isset($smarty.post.item) && $smarty.post.item == $item || isset($smarty.get.item) && $smarty.get.item == $item} selected="selected"{/if}>{lang key=$item default=$item}</option>
							{/foreach}
						</select>
					</div>
				</div>
			{else}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='name'}</label>

					<div class="col col-lg-4">
						<b>{$group.name}</b>
						<input type="hidden" name="name" id="group_name" value="{$group.name}">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='item'}</label>

					<div class="col col-lg-4">
						<b>{lang key=$group.item}</b>
						<input type="hidden" id="field_item" value="{$group.item}">
					</div>
				</div>
			{/if}

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='view_as_tab'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$group.tabview|default:0 name='tabview'}
				</div>
			</div>

			<div class="row" id="js-tab-container">
				<label class="col col-lg-2 control-label">{lang key='tab_container'}</label>

				<div class="col col-lg-4">
					<input type="hidden" id="tabcontainer" value="{$group.tabcontainer}">
					<select name="tabcontainer" id="js-fieldgroup-selectbox">
						<option value="">{lang key='_select_'}</option>
					</select>
				</div>
			</div>

			<div class="row" id="js-collapsible">
				<label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$group.collapsible|default:0 name='collapsible'}
				</div>
			</div>

			<div class="row" id="js-collapsed">
				<label class="col col-lg-2 control-label">{lang key='collapsed'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$group.collapsed|default:0 name='collapsed'}
				</div>
			</div>

			{foreach $languages as $code => $pre_lang}
				<div class="row">
					<label class="col col-lg-2 control-label">{$pre_lang} {lang key='title'}</label>

					<div class="col col-lg-4">
						<input type="text" name="titles[{$code}]" value="{if isset($smarty.post.titles.$code)}{$smarty.post.titles.$code|escape:'html'}{elseif isset($group.titles)}{$group.titles.$code}{/if}" />
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{$pre_lang} {lang key='description'}</label>

					<div class="col col-lg-4">
						<textarea id="description[{$code}]" rows="6" name="description[{$code}]">{if isset($smarty.post.description.$code)}{$smarty.post.description.$code|escape:'html'}{elseif isset($group.description.$code)}{$group.description.$code}{/if}</textarea>
					</div>
				</div>
			{/foreach}
		</div>
	</div>

	<div class="form-actions inline">
		<input type="submit" value="{lang key='save'}" class="btn btn-primary">
		{include file='goto.tpl'}
	</div>
</form>

{ia_add_media files='js:admin/fieldgroups'}