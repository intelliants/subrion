{assign var='type' value=$variable.type}
{assign var='varname' value=$variable.name}
{assign var='name' value="field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}

{if isset($item.$varname)}
	{if 'checkbox' == $type}
		{assign var='chosen' value=','|explode:$item.$varname}
	{elseif in_array($type, array('image', 'pictures', 'storage'))}
		{* TODO: refactor the code below *}
		{if $item.$varname}
			{assign var='chosen' value=$item.$varname|unserialize}
		{else}
			{assign var='chosen' value=array()}
		{/if}
		{* *}
	{else}
		{assign var='chosen' value=$item.$varname}
	{/if}
{else}
	{assign var='chosen' value=$variable.default}
{/if}

{if isset($variable.disabled) && $variable.disabled}
	<input type="hidden" name="{$varname}" value="{$chosen}">
{/if}

<div class="control-group {if $type == 'textarea'}textarea{/if} {$variable.class} {$variable.relation}{if $variable.for_plan && !$variable.required} for_plan" style="display:none;{/if}" id="{$varname}_fieldzone">
	<label class="control-label" for="{$name}">
		{lang key=$name}:
		{if $variable.required}<span class="required">*</span>{/if}
	</label>

	<div class="controls">

	{switch $type}

		{case 'text' break}
			<input type="text" name="{$varname}" value="{if $chosen}{$chosen|escape:'html'}{else}{$variable.default}{/if}" id="{$name}" maxlength="{$variable.length}">

		{case 'number' break}
			<input type="text" class="js-filter-numeric" name="{$varname}" value="{if $chosen}{$chosen}{else}{$variable.default}{/if}" id="{$name}" maxlength="{$variable.length}">

		{case 'textarea' break}
			{if !$variable.use_editor}
				<textarea name="{$varname}" class="input-block-level" rows="8" id="{$name}">{$chosen}</textarea>
				{if $variable.length > 0}
					{ia_add_js}
						$(function()
						{
							$('#{$name}').dodosTextCounter({$variable.length},
							{
								counterDisplayElement: 'span',
								counterDisplayClass: 'textcounter_{$varname}'
							});
							$('.textcounter_{$varname}').addClass('textcounter').wrap('<p class="help-block text-right"></p>').before('{lang key='chars_left'} ');
						});
					{/ia_add_js}
					{ia_print_js files='jquery/plugins/jquery.textcounter'}
				{/if}
			{else}
				{ia_wysiwyg value=$chosen name=$variable.name}
			{/if}

		{case 'url' break}
			{if !is_array($chosen)}
				{assign var='chosen' value='|'|explode:$chosen}
			{/if}

			<div class="row-fluid">
				<div class="span6">
					<label for="{$variable.name}[title]" class="control-label">{lang key='title'}:</label>
					<div class="controls">
						<input type="text" name="{$variable.name}[title]" value="{if isset($chosen['title'])}{$chosen['title']}{elseif !empty($chosen[1])}{$chosen[1]}{/if}">
					</div>
				</div>
				<div class="span6">
					<label for="{$variable.name}[url]" class="control-label">{lang key='url'}:</label>
					<div class="controls">
						<input type="text" name="{$variable.name}[url]" value="{if isset($chosen['url'])}{$chosen['url']}{elseif !empty($chosen[0])}{$chosen[0]}{else}http://{/if}">
					</div>
				</div>
			</div>

		{case 'date' break}
			{assign var='default_date' value=($chosen && '0000-00-00' != $chosen) ? {$chosen|escape:'html'} : ''}

			<div class="input-append date" id="field_date_{$varname}">
				<input type="text" name="{$varname}" class="js-datetimepicker" id="{$name}" value="{$default_date}">
				<span class="add-on js-datetimepicker-toggle"><i class="icon-calendar"></i></span>
			</div>

			{ia_add_media files='datepicker'}
			{ia_add_js}
			$(function()
			{
				$('.js-datetimepicker').datetimepicker(
				{
					format: 'yyyy-mm-dd',
					pickerPosition: 'top-left',
					autoclose: true,
					todayBtn: true,
					startView: 2,
					minView: 2,
					maxView: 4
				});
				$('.js-datetimepicker-toggle').on('click', function()
				{
					$(this).prev().datetimepicker('show');
				});
			});
			{/ia_add_js}

		{case 'storage' break}
			{if $chosen}
				<div class="files-list">
					{foreach $chosen as $file}
						<div class="thumbnail">
							<code><a href="{$smarty.const.IA_CLEAR_URL}uploads/{$file.path}">{if $file.title}{$file.title}{else}{lang key='download'} {$file@iteration}{/if}</a></code>

							<div class="caption">
								<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$variable.name}" data-item-id="{$item.id}" data-picture-path="{$file.path}">{lang key='delete'}</button>
							</div>
						</div>
					{/foreach}
				</div>

				{assign var='max_num' value=($variable.length - count($chosen))}
			{else}
				{assign var='max_num' value=$variable.length}
			{/if}

			<div class="upload-gallery-wrap-outer" id="wrap_{$variable.name}" {if $max_num <= 0}style="display: none;"{/if}>
				<div class="upload-gallery-wrap clearfix">
					<div class="upload-wrap pull-left">
						<div class="input-append">
							<span class="span2 uneditable-input">{lang key='file_click_to_upload'}</span>
							<span class="add-on">{lang key='browse'}</span>
						</div>
						<input type="file" class="upload-hidden" name="{$variable.name}[]">
					</div>
					<input class="upload-title" type="text" placeholder="{lang key='title'}" name="{$variable.name}_title[]" maxlength="100">
					{if $max_num > 1}
						<button type="button" class="js-add-img btn btn-info"><i class="icon-plus"></i></button>
						<button type="button" class="js-remove-img btn btn-info"><i class="icon-minus"></i></button>
					{/if}
				</div>
			</div>
			<input type="hidden" value="{$max_num}" id="{$variable.name}">

		{case 'image' break}
			<div class="upload-wrap">
				<div class="input-append">
					<span class="span2 uneditable-input">{lang key='click_here_to_upload'}</span>
					<span class="add-on">{lang key='browse'}</span>
				</div>
				<input type="file" name="{$varname}[]" id="{$name}" class="upload-hidden">
			</div>

			{if $chosen}
				<div class="thumbnail" style="width: {$variable.thumb_width}px;">
					{if $variable.thumb_width == $variable.image_width && $variable.thumb_height == $variable.image_height}
						{printImage imgfile=$chosen.path width=$variable.thumb_width height=$variable.thumb_height title=$chosen.title thumbnail=1}
					{else}
						<a href="{printImage imgfile=$chosen.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]" style="max-width: {$variable.thumb_width}px;">
							{printImage imgfile=$chosen.path width=$variable.thumb_width height=$variable.thumb_height title=$chosen.title}
						</a>
					{/if}

					<div class="caption">
						<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id}" data-picture-path="{$chosen.path}">{lang key='delete'}</button>
					</div>
				</div>
			{/if}

		{case 'pictures' break}
			{ia_add_media files='js:bootstrap/js/bootstrap-editable.min, css:_IA_URL_js/bootstrap/css/bootstrap-editable' order=5}

			{if $chosen}
				<div class="thumbnails-grid">
					{foreach $chosen as $picture}
						<div class="thumbnail gallery">
							<a href="{printImage imgfile=$picture.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]" title="{$picture.title}" style="max-width: {$variable.thumb_width}px;">
								{printImage imgfile=$picture.path title=$picture.title}
							</a>

							<div class="caption">
								<a href="#" id="{$varname}_{$picture@index}" data-type="text" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id}" data-picture-path="{$picture.path}" data-pk="1" class="js-edit-picture-title editable editable-click">{$picture.title}</a>
							</div>

							<div class="caption">
								<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id}" data-picture-path="{$picture.path}">{lang key='delete'}</button>
							</div>
						</div>
					{/foreach}
				</div>

				{assign var='max_num' value=($variable.length - count($chosen))}
			{else}
				{assign var='max_num' value=$variable.length}
			{/if}

			<div class="upload-gallery-wrap-outer" id="wrap_{$variable.name}" {if $max_num <= 0}style="display: none;"{/if}>
				{lang key='image'}
				<div class="upload-gallery-wrap clearfix">
					<div class="upload-wrap pull-left">
						<div class="input-append">
							<span class="span2 uneditable-input">{lang key='image_click_to_upload'}</span>
							<span class="add-on">{lang key='browse'}</span>
						</div>
						<input type="file" class="upload-hidden" name="{$variable.name}[]">
					</div>
					<input class="upload-title" type="text" placeholder="{lang key='title'}" name="{$variable.name}_title[]" maxlength="100">

					{if $max_num > 1}
						<button type="button" class="js-add-img btn btn-info"><i class="icon-plus"></i></button>
						<button type="button" class="js-remove-img btn btn-info"><i class="icon-minus"></i></button>
					{/if}
				</div>

				<input type="hidden" value="{$max_num}" id="{$variable.name}">
				<p class="help-block">{lang key='click'}<strong> {lang key='browse'}... </strong>{lang key='choose_image_file'}</p>
			</div>

	{/switch}

		{if $type == 'combo'}
			<select name="{$varname}" class="text" id="{$name}"{if isset($variable.disabled) && $variable.disabled} disabled="disabled"{/if}>
				<option value="">{lang key='_select_'}</option>
				{if !empty($variable.values)}
					{html_options options=$variable.values selected=$chosen}
				{/if}
			</select>

			{if $variable.relation == 'parent' && $variable.children}
			{ia_add_js order=5}
			$(function()
			{
				$('{foreach $variable.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$variable.name}');
				$('#{$name}').on('change', function()
				{
					var value = $(this).val();
					$('.hide_{$variable.name}').hide();
					{foreach $variable.children as $_field => $_values}
					if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
					{/foreach}
					$('fieldset').show().each(function(index, item)
					{
						if ($('.fieldset-wrapper', item).length > 0)
						{
							$('.fieldset-wrapper div.fieldzone:visible, .fieldset-wrapper div.fieldzone.regular', item).length == 0
								? $(this).hide()
								: $(this).show();
						}
					});
				}).change();
			});
			{/ia_add_js}
			{/if}

		{elseif $type == 'radio'}
			<div class="radios-list">
				{if !empty($variable.values)}
					{html_radios assign='radios' name=$varname id=$name options=$variable.values selected=$chosen separator='</div>'}
					<div class="radio">{'<div class="radio">'|implode:$radios}
				{/if}
			</div>

			{if $variable.relation == 'parent' && $variable.children}
				{ia_add_js order=5}
				$(function()
				{
					$('{foreach $variable.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$variable.name}');
					$('input[name="{$varname}"]').on('change', function()
					{
						var value = $(this).val();
						$('.hide_{$variable.name}').hide();
						{foreach $variable.children as $_field => $_values}
						if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
						{/foreach}
						$('fieldset').show().each(function(index, item)
						{
							if ($('.fieldset-wrapper', item).length > 0)
							{
								if($('.fieldset-wrapper div.fieldzone:visible, .fieldset-wrapper div.fieldzone.regular', item).length == 0) $(this).hide();
								else $(this).show();
							}
						});
					}).change();
				});
				{/ia_add_js}
			{/if}

		{elseif $type == 'checkbox'}
			<div class="radios-list">
				{if !empty($variable.values)}
					{html_checkboxes assign='checkboxes' name=$varname id=$name options=$variable.values selected=$chosen separator="</div>"}
					<div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
				{/if}
			</div>

			{if $variable.relation == 'parent' && $variable.children}
			{ia_add_js order=5}
			$(function()
			{
				$('{foreach $variable.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$variable.name}');
				$('input[name="{$varname}[]"]').on('change', function()
				{
					$('.hide_{$variable.name}').hide();
					$('#type_fieldzone input[type="checkbox"]:checked').each(function()
					{
						var value = $(this).val();
						{foreach $variable.children as $_field => $_values}
						if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
						{/foreach}
					});
					$('fieldset').show().each(function(index, item)
					{
						if ($('.fieldset-wrapper', item).length > 0)
						{
							if($('.fieldset-wrapper div.fieldzone:visible, .fieldset-wrapper div.fieldzone.regular', item).length == 0) $(this).hide();
							else $(this).show();
						}
					});
				}).change();
			});
			{/ia_add_js}
			{/if}
		{/if}

		{assign var='annotation' value="{$name}_annotation"}
		{if isset($lang.$annotation)}<p class="annotation help-block">{lang key=$annotation}</p>{/if}
	</div>
</div>
{if isset($field_after[$varname])}{$field_after.$varname}{/if}