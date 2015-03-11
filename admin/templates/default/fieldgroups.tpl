<form method="post" class="sap-form form-horizontal">
	{preventCsrf}
	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>

				<div class="col col-lg-4">
					{if iaCore::ACTION_ADD == $pageAction}
						<input type="text" name="name" id="input-name" value="{$item.name|escape:'html'}">
						<p class="help-block">{lang key='unique_name'}</p>
					{else}
						<input type="text" class="disabled" value="{$item.name|escape:'html'}" disabled>
						<input type="hidden" name="name" id="input-name" value="{$item.name|escape:'html'}">
					{/if}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='item'}</label>

				<div class="col col-lg-4">
					{if iaCore::ACTION_ADD == $pageAction}
						<select name="item" id="input-item">
							<option value="">{lang key='_select_'}</option>
							{foreach $items as $itemName}
								<option value="{$itemName}"{if isset($smarty.post.item) && $smarty.post.item == $itemName || isset($smarty.get.item) && $smarty.get.item == $itemName} selected{/if}>{lang key=$itemName default=$itemName}</option>
							{/foreach}
						</select>
					{else}
						<select class="disabled" disabled><option>{lang key=$item.item}</option></select>
						<input type="hidden" name="item" id="input-item" value="{$item.item}">
					{/if}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='view_as_tab'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.tabview|default:0 name='tabview'}
				</div>
			</div>

			<div class="row" id="js-tab-container">
				<label class="col col-lg-2 control-label">{lang key='tab_container'}</label>

				<div class="col col-lg-4">
					<input type="hidden" id="tabcontainer" value="{$item.tabcontainer|escape:'html'}">
					<select name="tabcontainer" id="js-fieldgroup-selectbox">
						<option value="">{lang key='_select_'}</option>
					</select>
				</div>
			</div>

			<div class="row" id="js-collapsible">
				<label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.collapsible|default:0 name='collapsible'}
				</div>
			</div>

			<div class="row" id="js-collapsed">
				<label class="col col-lg-2 control-label">{lang key='collapsed'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.collapsed|default:0 name='collapsed'}
				</div>
			</div>

			{foreach $core.languages as $code => $language}
				<div class="row">
					<label class="col col-lg-2 control-label">{$language.title} {lang key='title'}</label>

					<div class="col col-lg-4">
						<input type="text" name="titles[{$code}]" value="{if isset($smarty.post.titles.$code)}{$smarty.post.titles.$code|escape:'html'}{elseif isset($item.titles[$code])}{$item.titles[$code]}{/if}">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{$language.title} {lang key='description'}</label>

					<div class="col col-lg-4">
						<textarea id="description[{$code}]" rows="6" name="description[{$code}]">{if isset($smarty.post.description.$code)}{$smarty.post.description[$code]|escape:'html'}{elseif isset($item.description[$code])}{$item.description.$code}{/if}</textarea>
					</div>
				</div>
			{/foreach}
		</div>

		{include file='fields-system.tpl'}
	</div>
</form>

{ia_add_media files='js:admin/fieldgroups'}