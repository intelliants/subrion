{assign type $field.type}
{assign varname $field.name}
{assign name "field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}
{if isset($item.$varname) && $item.$varname}
	{if 'checkbox' == $type}
		{assign value ','|explode:$item.$varname}
	{elseif in_array($type, array('image', 'pictures', 'storage'))}
		{assign value $item.$varname|unserialize}
	{else}
		{assign value $item.$varname}
	{/if}
{else}
	{assign value $field.default}
{/if}

<div id="{$varname}_fieldzone" class="row {$field.relation}">

	<label class="col col-lg-2 control-label">{lang key=$name} {if $field.required}{lang key='field_required'}{/if}
		{assign annotation {lang key="{$name}_annotation" default=''}}
		{if $annotation}<br><span class="help-block">{$annotation}</span>{/if}
	</label>

	{if 'textarea' != $type}
		<div class="col col-lg-4">
	{else}
		<div class="col col-lg-8">
	{/if}

	{switch $type}
		{case 'text' break}
			<input type="text" name="{$varname}" value="{if $value}{$value|escape:'html'}{else}{$field.empty_field}{/if}" id="{$name}" maxlength="{$field.length}">

		{case 'date' break}
			{assign var='default_date' value=($value && !in_array($value, array('0000-00-00', '0000-00-00 00:00:00'))) ? {$value|escape:'html'} : ''}

			<div class="input-group date" id="field_date_{$varname}">
				<input type="text" class="js-datepicker" name="{$varname}" id="{$name}" value="{$default_date}" {if $field.timepicker} data-date-show-time="true" data-date-format="yyyy-mm-dd H:i:s"{else}data-date-format="yyyy-mm-dd"{/if}>
				<span class="input-group-addon js-datepicker-toggle"><i class="i-calendar"></i></span>
			</div>

		{case 'number' break}
			<input type="text" name="{$varname}" value="{if $value}{$value|escape:'html'}{else}{$field.empty_field}{/if}" id="{$name}" maxlength="{$field.length}">

		{case 'url' break}
			{if !is_array($value)}
				{assign value '|'|explode:$value}
			{/if}
			<div class="row control-group-inner">
				<div class="col col-lg-6">
					<label for="{$field.name}[url]" class="control-label">{lang key='url'}:</label>
					<input type="text" name="{$field.name}[url]" value="{if isset($value['url'])}{$value['url']}{elseif !empty($value[0])}{$value[0]}{else}http://{/if}">
				</div>

				<div class="col col-lg-6">
					<label for="{$field.name}[title]" class="control-label">{lang key='title'}:</label>
					<input type="text" name="{$field.name}[title]" value="{if isset($value['title'])}{$value['title']|escape:'html'}{elseif !empty($value[1])}{$value[1]|escape:'html'}{/if}">
					<p class="help-block">({lang key='optional'})</p>
				</div>
			</div>

		{case 'textarea' break}
			{if !$field.use_editor}
				<textarea name="{$varname}" rows="8" id="{$name}">{$value|escape:'html'}</textarea>
				{if $field.length > 0}
					{ia_add_js}
$(function($)
{
$('#{$name}').dodosTextCounter({$field.length},
{
	counterDisplayElement: 'span',
	counterDisplayClass: 'textcounter_{$varname}',
	addLineBreak: false
});

$('.textcounter_{$varname}').wrap('<p class="help-block text-right">').addClass('textcounter').after(' ' + _t('chars_left'));
});
					{/ia_add_js}

					{ia_print_js files='jquery/plugins/jquery.textcounter'}
				{/if}
			{else}
				{ia_wysiwyg value=$value name=$field.name}
			{/if}

		{case 'image' break}
			{if $value}
				<div class="input-group thumbnail thumbnail-single with-actions">
					<a href="{printImage imgfile=$value.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]">
						{printImage imgfile=$value.path}
					</a>

					<input type="hidden" name="{$varname}[path]" value="{$value.path}">

					<div class="caption">
						<a class="btn btn-small btn-danger" href="javascript:void(0);" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$value.path}',this,'{$field.item}','{$varname}','{$id}')"><i class="i-remove-sign"></i></a>
					</div>
				</div>

				{ia_html_file name="{$varname}[]" id=$name value=$value.path}
			{else}
				{ia_html_file name="{$varname}[]" id=$name}
			{/if}

		{case 'pictures'}
		{case 'storage' break}
			{if $value}
				<div class="uploads-list" id="{$varname}_upload_list">
					{foreach $value as $i => $entry}
						<div class="uploads-list-item">
							{if 'pictures' == $type}
								<a class="uploads-list-item__thumb" href="{printImage imgfile=$entry.path url=true fullimage=true}" title="{$entry.title|escape:'html'}" rel="ia_lightbox[{$field.name}]">{printImage imgfile=$entry.path}</a>
							{else}
								<span class="uploads-list-item__thumb uploads-list-item__thumb--file"><i class="i-file-2"></i></span>
							{/if}
							<div class="uploads-list-item__body">
								<div class="input-group">
									<input type="text" name="{$varname}[{$i}][title]" value="{$entry.title|escape:'html'}" id="{$varname}_{$entry@index}">
									<input type="hidden" name="{$varname}[{$i}][path]" value="{$entry.path}">

									<span class="input-group-btn">
										{if 'pictures' == $type}
											<a class="btn btn-danger" href="javascript:void(0);" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$entry.path}', this, '{$field.item}', '{$field.name}', '{$id|default:''}')"><i class=" i-remove-sign"></i></a>
										{else}
											<a class="btn btn-success uploads-list-item__img" href="{$core.page.nonProtocolUrl}uploads/{$entry.path}" title="{$entry.title|escape:'html'}"><i class="i-box-add"></i></a>
											<a class="btn btn-danger js-file-delete" href="#" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$entry.path}', this, '{$field.item}', '{$field.name}', '{$id|default:''}')"><i class="i-remove-sign"></i></a>
										{/if}
										<span class="btn btn-default uploads-list-item__drag-handle"><i class="i-list-2"></i></span>
									</span>
								</div>
							</div>
						</div>
					{/foreach}
				</div>

				{ia_add_js}
$(function()
{
	intelli.sortable('{$varname}_upload_list', '.uploads-list-item__drag-handle');
});
				{/ia_add_js}

				{assign var='max_num' value=($field.length - count($value))}
			{else}
				{assign max_num $field.length}
			{/if}

			{ia_html_file name=$varname id=$varname multiple=true max_num=$max_num title=true}
	{/switch}

	{if $type == 'combo'}
		<select name="{$varname}" id="{$name}">
			<option value="">{lang key='_select_'}</option>
			{html_options options=$field.values selected=$value}
		</select>

		{if 'parent' == $field.relation && $field.children}
			{ia_add_js order=5}
$(function()
{
	$('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
	$('#{$name}').on('change', function()
	{
		var value = $(this).val();
		$('.hide_{$field.name}').hide();
		{foreach $field.children as $_field => $_values}
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
		{if !empty($field.values)}
			{html_radios assign='radios' name=$varname options=$field.values selected=$value separator="</div>"}

			<div class="radio">{'<div class="radio">'|implode:$radios}
		{/if}

		{if $field.relation == 'parent' && $field.children}
			{ia_add_js order=5}
$(function()
{
	$('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
	$('input[name="{$varname}"]').on('change', function()
	{
		var $this = $(this),
			value = $this.val();

		if ($this.is(':checked'))
		{
			{foreach $field.children as $_field => $_values}
				if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1)
				{
					$('#{$_field}_fieldzone').show();
				}
				else
				{
					$('#{$_field}_fieldzone').hide();
				}
			{/foreach}
		}
	}).change();
});
			{/ia_add_js}
		{/if}

	{elseif $type == 'checkbox'}
		{html_checkboxes assign='checkboxes' name=$varname options=$field.values selected=$value separator='</div>'}

		{if $field.values}
			{html_checkboxes assign='checkboxes' name=$varname options=$field.values selected=$value separator='</div>'}
			<div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
		{/if}

		{if $field.relation == 'parent' && $field.children}
			{ia_add_js order=5}
$(function()
{
	$('{foreach $field.children as $_field => $_values}#{$_field}_fieldzone{if !$_values@last}, {/if}{/foreach}').addClass('hide_{$field.name}');
	$('input[name="{$varname}[]"]').on('change', function()
	{
		$('.hide_{$field.name}').hide();

		$('input[type="checkbox"]:checked', '#type_fieldzone').each(function()
		{
			var value = $(this).val();

			{foreach $field.children as $_field => $_values}
				if ($.inArray(value, [{foreach $_values as $_value}'{$_value}'{if !$_value@last},{/if}{/foreach}])!=-1) $('#{$_field}_fieldzone').show();
			{/foreach}
		});
	}).change();
});
			{/ia_add_js}
		{/if}

	{elseif ($type == 'checkbox' || $type == 'combo' || $type == 'radio') && $field.show_as == 'radio'}
		<h3>{lang key=$name}</h3>
		<div>
			{html_radios name=$varname options=$field.values separator='<br>' grouping=10}
		</div>

	{elseif ($type == 'checkbox' || $type == 'combo' || $type == 'radio') && $field.show_as == 'combo'}
		<div>
			<h3><label for="{$varname}_domid"> {lang key=$name}: </label>
			<select name="{$varname}" id="{$varname}_domid">
				<option value="_na_">{lang key='all'}</option>
				{html_options options=$field.values}
			</select></h3>
		</div>
	{/if}
	</div>
</div>
{if isset($field_after[$varname])}{$field_after.$varname}{/if}