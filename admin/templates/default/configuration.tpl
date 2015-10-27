{if isset($params)}
<form enctype="multipart/form-data" method="post" class="sap-form form-horizontal">
	{preventCsrf}
	<div class="wrap-list">
		<div class="wrap-group">
		{foreach $params as $entry}
			{if !empty($entry.show)}
				{assign field_show explode('|', $entry.show)}

				{capture assign='dependent_fields'}
					data-id="js-{$field_show[0]}-{$field_show[1]}" {if (!empty($field_show[0]) && $core.config.{$field_show[0]} != $field_show[1])} style="display: none;"{/if}
				{/capture}
			{else}
				{assign dependent_fields ''}
			{/if}

			{if 'divider' == $entry.type}
				{if !$entry@first}
					</div>
					<div class="wrap-group" {$dependent_fields}>
				{/if}
				<a name="{$entry.name}"></a>
				<div class="wrap-group-heading" {$dependent_fields}>
					{$entry.value|escape:'html'}

					{if isset($tooltips[$entry.name])}
						<a href="#" class="js-tooltip" data-placement="right" title="{$tooltips[$entry.name]}"><i class="i-info"></i></a>
					{/if}
				</div>
			{elseif 'hidden' != $entry.type}
				<div class="row {$entry.class}" {$dependent_fields}>
					<label class="col col-lg-2 control-label" for="{$entry.name}">
						{$entry.description|escape:'html'}
						{if isset($tooltips[$entry.name])}
							<a href="#" class="js-tooltip" title="{$tooltips[$entry.name]}"><i class="i-info"></i></a>
						{/if}
					</label>

					{if in_array($entry.type, array('textarea', 'tpl'))}
						<div class="col col-lg-8">
					{else}
						<div class="col col-lg-5">
					{/if}

					<input type="hidden" class="chck" name="c[{$entry.name}]" value="{if 'custom' != $entry.class}1{else}0{/if}" />
					{if 'password' == $entry.type}
						{if $custom}
							<div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_password'}{else}***********{/if}</div>
						{/if}

						<div class="item-input">
							<input type="password" class="js-input-password" name="v[{$entry.name}]" id="{$entry.name}" value="{$entry.value|escape:"html"}" />
						</div>
					{elseif 'text' == $entry.type}
						{if 'captcha_preview' == $entry.name}
							{captcha preview=true}
						{else}
							{if $custom}
								<div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_value'}{else}{$entry.default|escape:'html'}{/if}</div>
							{/if}

							<div class="item-input">
								<input type="text" name="v[{$entry.name}]" id="{$entry.name}" value="{$entry.value|escape:'html'}" />
							</div>
						{/if}
					{elseif 'textarea' == $entry.type}
						{if $custom}
							<div class="form-control disabled item-val">{if empty($entry.default)}{lang key='config_empty_value'}{else}{$entry.default}{/if}</div>
						{/if}

						<div class="item-input">
							<textarea name="v[{$entry.name}]" id="{$entry.name}" class="{if $entry.wysiwyg == 1}js-wysiwyg {elseif $entry.code_editor}js-code-editor {/if}common" cols="45" rows="7">{$entry.value|escape:'html'}</textarea>
						</div>
					{elseif 'image' == $entry.type}
						{if !is_writeable($smarty.const.IA_UPLOADS)}
							<div class="alert alert-info">{lang key='upload_writable_permission'}</div>
						{else}
							{if !empty($entry.value) || $entry.name == 'site_logo'}
								<div class="thumbnail">
									{if !empty($entry.value)}
										<img src="{$core.page.nonProtocolUrl}uploads/{$entry.value}">
									{elseif $entry.name == 'site_logo'}
										<img src="{$core.page.nonProtocolUrl}templates/{$core.config.tmpl}/img/logo.png">
									{/if}
								</div>

								{if !empty($entry.value)}
									<div class="checkbox">
										<label><input type="checkbox" name="delete[{$entry.name}]"> {lang key='delete'}</label>
									</div>
								{/if}
							{/if}

							{ia_html_file name=$entry.name value=$entry.value}
						{/if}
					{elseif 'checkbox' == $entry.type}
						<div class="item-input">
							<input type="checkbox" name="v[{$entry.name}]" id="{$entry.name}">
						</div>
					{elseif 'radio' == $entry.type}
						{if $custom}
							<div class="form-control disabled item-val">{if $entry.default == 1}ON{else}OFF{/if}</div>
						{/if}

						<div class="item-input">
							{html_radio_switcher value=$entry.value name=$entry.name conf=true}
						</div>
					{elseif 'select' == $entry.type}
						{if $custom}
							<div class="form-control disabled item-val">{if $entry.name == 'lang'}{$entry.values[$entry.default].title|escape:'html'}{else}{$entry.default}{/if}</div>
						{/if}

						<div class="item-input">
							<select name="v[{$entry.name}]" {if count($entry.values) == 1} disabled="disabled"{/if} id="{$entry.name}">
								{foreach $entry.values as $key => $value2}
									{if 'lang' == $entry.name}
										<option value="{$key}"{if $key == $entry.value || $value2 == $entry.value} selected{/if}>{$value2.title}</option>
									{elseif is_array($value2)}
										<optgroup label="{$key}">
											{foreach $value2 as $subkey => $subvalue}
												<option value="{$subkey}"{if $subkey == $entry.value} selected{/if}>{$subvalue}</option>
											{/foreach}
										</optgroup>
									{else}
										<option value="{$value2|trim:"'"}"{if $value2|trim:"'" == $entry.value} selected{/if}>{$value2|trim:"'"}</option>
									{/if}
								{/foreach}
							</select>
						</div>
					{elseif $entry.type == 'itemscheckbox' && !$custom}
						{if isset($entry.items)}
							<div class="item-input">
								<input type="hidden" name="v[{$entry.name}][]">
								{foreach $entry.items as $item name=items}
									<p>
										<input type="checkbox" id="icb_{$entry.name}_{$smarty.foreach.items.iteration}" name="v[{$entry.name}][]" value="{$item.name}"{if $item.checked} checked{/if}>
										<label for="icb_{$entry.name}_{$smarty.foreach.items.iteration}">{$item.title}</label>
									</p>
								{/foreach}
							</div>
						{else}
							<div class="alert alert-info">{lang key='no_implemented_packages'}</div>
						{/if}
					{elseif 'tpl' == $entry.type}
						{if file_exists($entry.multiple_values)}
							{include $entry.multiple_values}
						{else}
							{lang key='template_file_error' file=$entry.multiple_values}
						{/if}
					{/if}
					</div> <!-- /.col -->
					{if $custom}
						<div class="col col-lg-2">
							<span class="btn btn-default set-custom" data-value="1">{lang key='config_set_custom'}</span>
							<span class="btn btn-default set-default" data-value="0">{lang key='config_set_default'}</span>
						</div>
					{/if}
				</div><!-- /.row -->
			{/if}
		{/foreach}
	</div>

	<div class="form-actions">
		<input type="submit" name="save" id="save" class="btn btn-primary" value="{lang key='save_changes'}">
	</div>
</form>
{/if}

{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/configuration'}