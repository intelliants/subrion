{assign type $variable.type}
{assign varname $variable.name}
{assign name "field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}

{if isset($item.$varname)}
	{if 'checkbox' == $type}
		{assign value ','|explode:$item.$varname}
	{elseif in_array($type, array('image', 'pictures', 'storage'))}
		{* TODO: refactor the code below *}
		{if $item.$varname}
			{assign value $item.$varname|unserialize}
		{else}
			{assign value array()}
		{/if}
		{* *}
	{else}
		{assign value $item.$varname}
	{/if}
{else}
	{assign value $variable.default}
{/if}

{if isset($variable.disabled) && $variable.disabled}
	<input type="hidden" name="{$varname}" value="{$value}">
{/if}

<div class="control-group{if $type == 'textarea'} textarea{/if} {$variable.class} {$variable.relation}{if $variable.for_plan && !$variable.required} for_plan" style="display:none;{/if}" id="{$varname}_fieldzone">
	<label class="control-label" for="{$name}">
		{lang key=$name}:
		{if $variable.required}<span class="required">*</span>{/if}
	</label>

	<div class="controls">

	{switch $type}

		{case 'text' break}
			<input type="text" name="{$varname}" value="{if $value}{$value|escape:'html'}{else}{$variable.default}{/if}" id="{$name}" maxlength="{$variable.length}">

		{case 'number' break}
			<input type="text" class="js-filter-numeric" name="{$varname}" value="{if $value}{$value|escape:'html'}{else}{$variable.default}{/if}" id="{$name}" maxlength="{$variable.length}">

		{case 'textarea' break}
			{if !$variable.use_editor}
				<textarea name="{$varname}" class="input-block-level" rows="8" id="{$name}">{$value|escape:'html'}</textarea>
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
				{ia_wysiwyg value=$value name=$variable.name}
			{/if}

		{case 'url' break}
			{if !is_array($value)}
				{assign value '|'|explode:$value}
			{/if}

			<div class="row-fluid">
				<div class="span6">
					<label for="{$variable.name}[title]" class="control-label">{lang key='title'}:</label>
					<div class="controls">
						<input type="text" name="{$variable.name}[title]" value="{if isset($value['title'])}{$value['title']|escape:'html'}{elseif !empty($value[1])}{$value[1]|escape:'html'}{/if}">
					</div>
				</div>
				<div class="span6">
					<label for="{$variable.name}[url]" class="control-label">{lang key='url'}:</label>
					<div class="controls">
						<input type="text" name="{$variable.name}[url]" value="{if isset($value['url'])}{$value['url']}{elseif !empty($value[0])}{$value[0]}{else}http://{/if}">
					</div>
				</div>
			</div>

		{case 'date' break}
			{assign var='default_date' value=($value && '0000-00-00' != $value) ? {$value|escape:'html'} : ''}

			<div class="input-append date" id="field_date_{$varname}">
				<input type="text" name="{$varname}" class="js-datepicker" id="{$name}" value="{$default_date}">
				<span class="add-on js-datepicker-toggle"><i class="icon-calendar"></i></span>
			</div>

			{ia_add_media files='datepicker'}

		{case 'storage' break}
			{if $value}
				<div class="files-list">
					{foreach $value as $entry}
						<div class="thumbnail">
							<code><a href="{$nonProtocolUrl}uploads/{$entry.path}">{if $entry.title}{$entry.title|escape:'html'}{else}{lang key='download'} {$entry@iteration}{/if}</a></code>

							<div class="caption">
								<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$variable.name}" data-item-id="{$item.id|default:''}" data-picture-path="{$entry.path}">{lang key='delete'}</button>
							</div>
						</div>
					{/foreach}
				</div>

				{assign var='max_num' value=($variable.length - count($value))}
			{else}
				{assign max_num $variable.length}
			{/if}

			<div class="upload-gallery-wrap-outer" id="wrap_{$variable.name}"{if $max_num <= 0} style="display: none;"{/if}>
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

			{if $value}
				<div class="thumbnail" style="width: {$variable.thumb_width}px;">
					{if $variable.thumb_width == $variable.image_width && $variable.thumb_height == $variable.image_height}
						{printImage imgfile=$value.path width=$variable.thumb_width height=$variable.thumb_height title=$value.title thumbnail=1}
					{else}
						<a href="{printImage imgfile=$value.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]" style="max-width: {$variable.thumb_width}px;">
							{printImage imgfile=$value.path width=$variable.thumb_width height=$variable.thumb_height title=$value.title}
						</a>
					{/if}

					<div class="caption">
						<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id|default:''}" data-picture-path="{$value.path}">{lang key='delete'}</button>
					</div>
				</div>
			{/if}

		{case 'pictures' break}
			{ia_add_media files='js:bootstrap/js/bootstrap-editable.min, css:_IA_URL_js/bootstrap/css/bootstrap-editable' order=5}

			{if $value}
				<div class="thumbnails-grid">
					{foreach $value as $entry}
						<div class="thumbnail gallery">
							<a href="{printImage imgfile=$entry.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]" title="{$entry.title|escape:'html'}" style="max-width: {$variable.thumb_width}px;">
								{printImage imgfile=$entry.path title=$entry.title}
							</a>

							<div class="caption">
								<a href="#" id="{$varname}_{$entry@index}" data-type="text" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id}" data-picture-path="{$entry.path}" data-pk="1" class="js-edit-picture-title editable editable-click">{$entry.title|escape:'html'}</a>
							</div>

							{if empty($item.id)}
								<input type="hidden" name="{$varname}[{$entry@index}][title]" value="{$entry.title|escape:'html'}">
								<input type="hidden" name="{$varname}[{$entry@index}][path]" value="{$entry.path}">
							{/if}

							<div class="caption">
								<button class="btn btn-mini btn-danger js-delete-file" data-item="{$variable.item}" data-field="{$varname}" data-item-id="{$item.id|default:''}" data-picture-path="{$entry.path}">{lang key='delete'}</button>
							</div>
						</div>
					{/foreach}
				</div>

				{assign var='max_num' value=($variable.length - count($value))}
			{else}
				{assign max_num $variable.length}
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
			<select name="{$varname}" class="text" id="{$name}"{if isset($variable.disabled) && $variable.disabled} disabled{/if}>
				<option value="">{lang key='_select_'}</option>
				{if !empty($variable.values)}
					{html_options options=$variable.values selected=$value}
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
					{html_radios assign='radios' name=$varname id=$name options=$variable.values selected=$value separator='</div>'}
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
					{html_checkboxes assign='checkboxes' name=$varname id=$name options=$variable.values selected=$value separator="</div>"}
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
		$('input[type="checkbox"]:checked', '#type_fieldzone').each(function()
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

		{assign annotation "{$name}_annotation"}
		{if isset($lang.$annotation)}<p class="annotation help-block">{lang key=$annotation}</p>{/if}
	</div>
</div>
{if isset($field_after[$varname])}{$field_after.$varname}{/if}