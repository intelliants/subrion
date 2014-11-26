{if isset($params)}
<form enctype="multipart/form-data" method="post" class="sap-form form-horizontal">
	{preventCsrf}
	<div class="wrap-list">
		<div class="wrap-group">
		{foreach $params as $key => $value}
			{if !empty($value.show)}
				{assign field_show explode('|', $value.show)}

				{capture assign='dependent_fields'}
					data-id="js-{$field_show[0]}-{$field_show[1]}" {if (!empty($field_show[0]) && $config.{$field_show[0]} != $field_show[1])} style="display: none;"{/if}
				{/capture}
			{else}
				{assign dependent_fields ''}
			{/if}

			{if 'divider' == $value.type}
				{if !$value@first}
					</div>
					<div class="wrap-group" {$dependent_fields}>
				{/if}
				<a name="{$value.name}"></a>
				<div class="wrap-group-heading" {$dependent_fields}>
					{$value.value|escape:'html'}

					{if isset($tooltips[$value.name])}
						<a href="#" class="js-tooltip" data-placement="right" title="{$tooltips[$value.name]}"><i class="i-info"></i></a>
					{/if}
				</div>
			{elseif 'hidden' != $value.type}
				<div class="row {if $pageAction == 'custom'}custom{/if}" {$dependent_fields}>
					<label class="col col-lg-2 control-label" for="{$value.name}">
						{$value.description|escape:'html'}
						{if isset($tooltips[$value.name])}
							<a href="#" class="js-tooltip" title="{$tooltips[$value.name]}"><i class="i-info"></i></a>
						{/if}
					</label>

					{if in_array($value.type, array('textarea', 'tpl'))}
						<div class="col col-lg-8">
					{else}
						<div class="col col-lg-4">
					{/if}

					{if 'custom' == $pageAction}
						<div class="pull-right">
							<span class="js-set-custom">{lang key='config_set_custom'}</span>
							<span class="js-set-default">{lang key='config_set_default'}</span>
						</div>
					{/if}

					<input type="hidden" class="chck" name="chck[{$value.name}]" value="{if $value.classname != 'custom'}1{else}0{/if}" />
					{if $value.type == 'password'}
						{if $pageAction == 'custom'}
							<div class="item_val">{if empty($value.default)}{lang key='config_empty_password'}{else}***********{/if}</div>
						{/if}
						<div class="item_input">
							<input type="password" class="js-input-password" name="param[{$value.name}]" id="{$value.name}" value="{$value.value|escape:"html"}" />
						</div>
					{elseif $value.type == 'text'}
						{if $value.name == 'expiration_action'}
							<select name="param[expiration_action]">
								<option value=""{if $value.value == ''} selected{/if}>{lang key='nothing'}</option>
								<option value="remove"{if $value.value == 'remove'} selected{/if}>{lang key='remove'}</option>
								<optgroup label="Status">
									<option value="approval"{if $value.value == 'approval'} selected{/if}>{lang key='approval'}</option>
									<option value="banned"{if $value.value == 'banned'} selected{/if}>{lang key='banned'}</option>
									<option value="suspended"{if $value.value == 'suspended'} selected{/if}>{lang key='suspended'}</option>
								</optgroup>
								<optgroup label="Type">
									<option value="regular"{if $value.value == 'regular'} selected{/if}>{lang key='regular'}</option>
									<option value="featured"{if $value.value == 'featured'} selected{/if}>{lang key='featured'}</option>
									<option value="partner"{if $value.value == 'partner'} selected{/if}>{lang key='partner'}</option>
								</optgroup>
							</select>
						{elseif $value.name == 'captcha_preview'}
							{captcha preview=true}
						{else}
							{if $pageAction == 'custom'}
								<div class="item_val">{if empty($value.default)}{lang key='config_empty_value'}{else}{$value.default|escape:'html'}{/if}</div>
							{/if}
							<div class="item_input">
								<input type="text" name="param[{$value.name}]" id="{$value.name}" value="{$value.value|escape:'html'}" />
							</div>
						{/if}
					{elseif $value.type == 'textarea'}
						{if $pageAction == 'custom'}
							<div class="item_val">{if empty($value.default)}{lang key='config_empty_value'}{else}{$value.default}{/if}</div>
						{/if}
						<div class="item_input">
							<textarea name="param[{$value.name}]" id="{$value.name}" class="{if $value.wysiwyg == '1'}js-wysiwyg {elseif $value.code_editor}js-code-editor {/if}common" cols="45" rows="7">{$value.value|escape:'html'}</textarea>
						</div>
					{elseif $value.type == 'image'}
						{if !is_writeable($smarty.const.IA_UPLOADS)}
							<div class="alert alert-info">{lang key='upload_writable_permission'}</div>
						{else}
							{if !empty($value.value) || $value.name == 'site_logo'}
								<div class="thumbnail">
									{if !empty($value.value)}
										<img src="{$nonProtocolUrl}uploads/{$value.value}">
									{elseif $value.name == 'site_logo'}
										<img src="{$nonProtocolUrl}templates/{$config.tmpl}/img/logo.png">
									{/if}
								</div>

								{if !empty($value.value)}
									<div class="checkbox">
										<label><input type="checkbox" name="delete[{$value.name}]"> {lang key='delete'}</label>
									</div>
								{/if}
							{/if}

							{ia_html_file name=$value.name value=$value.value}
						{/if}
					{elseif $value.type == 'checkbox'}
						<div class="item_input">
							<input type="checkbox" name="param[{$value.name}]" id="{$value.name}">
						</div>
					{elseif $value.type == 'radio'}
						{if $pageAction == 'custom'}
							<div class="item_val">{if $value.default == 1}ON{else}OFF{/if}</div>
						{/if}
						<div class="item_input">
							{html_radio_switcher value=$value.value name=$value.name conf=true}
						</div>
					{elseif $value.type == 'select'}
						{if $pageAction == 'custom'}
							<div class="item_val">{if $value.name == 'lang'}{$value.values[$value.default]}{else}{$value.default}{/if}</div>
						{/if}
						<div class="item_input">
							<select name="param[{$value.name}]" {if $value.values|@count == 1} disabled="disabled"{/if} id="{$value.name}">
								{foreach from=$value.values key=key item=value2}
									{if is_array($value2)}
										<optgroup label="{$key}">
											{foreach from=$value2 key=subkey item=subvalue}
												<option value="{$subkey}"{if $subkey == $value.value} selected{/if}>{$subvalue}</option>
											{/foreach}
										</optgroup>
									{else}
										<option value="{if $value.name == 'lang'}{$key}{else}{$value2|trim:"'"}{/if}"{if ($value.name == 'lang' && $key == $value.value) || $value2|trim:"'" == $value.value} selected{/if}>{$value2|trim:"'"}</option>
									{/if}
								{/foreach}
							</select>
						</div>
					{elseif $value.type == 'itemscheckbox' && $pageAction != 'custom'}
						{if isset($value.items)}
							<div class="item_input">
								<input type="hidden" name="param[{$value.name}][]">
								{foreach $value.items as $item name=items}
								<p>
									<input type="checkbox" id="icb_{$value.name}_{$smarty.foreach.items.iteration}" name="param[{$value.name}][]" value="{$item.name}"{if $item.checked} checked{/if}>
									<label for="icb_{$value.name}_{$smarty.foreach.items.iteration}">{$item.title}</label>
								</p>
								{/foreach}
							</div>
						{else}
							<div class="alert alert-info">{lang key='no_implemented_packages'}</div>
						{/if}
					{elseif $value.type == 'tpl'}
						{if file_exists($value.multiple_values)}
							{include file=$value.multiple_values}
						{else}
							{lang key='template_file_error'} {$value.multiple_values}
						{/if}
					{/if}
					</div>
				</div>
			{/if}
		{/foreach}
	</div>

	<div class="form-actions"><input type="submit" name="save" id="save" class="btn btn-primary" value="{lang key='save_changes'}"></div>
</form>
{/if}
{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/configuration'}