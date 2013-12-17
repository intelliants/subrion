{assign var='type' value=$variable.type}
{assign var='varname' value=$variable.name}
{assign var='name' value="field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}
{if isset($item.$varname) && $item.$varname}
	{if 'checkbox' == $type}
		{assign var='chosen' value=','|explode:$item.$varname}
	{elseif in_array($type, array('image', 'pictures', 'storage'))}
		{assign var='chosen' value=$item.$varname|unserialize}
	{else}
		{assign var='chosen' value=$item.$varname}
	{/if}
{else}
	{assign var='chosen' value=$variable.default}
{/if}

<div id="{$varname}_fieldzone" class="row {$variable.relation}">

	<label class="col col-lg-2 control-label">{lang key=$name} {if $variable.required}{lang key='field_required'}{/if}
		{assign var='annotation' value="{$name}_annotation"}
		{if isset($lang.$annotation)}<br><span class="help-block">{lang key=$annotation}</span>{/if}
	</label>

	{if 'textarea' != $type}
		<div class="col col-lg-4">
	{else}
		<div class="col col-lg-8">
	{/if}

	{switch $type}
		{case 'text' break}
			<input type="text" name="{$varname}" value="{if $chosen}{$chosen}{else}{$variable.empty_field}{/if}" id="{$name}">

		{case 'date' break}
			<div class="input-group">
				<input type="text" class="js-datetimepicker" name="{$varname}" id="{$name}" value="{if '0000-00-00' != $chosen}{$chosen}{/if}">
				<span class="input-group-addon js-datetimepicker-toggle"><i class="i-calendar"></i></span>
			</div>

			{ia_add_js}
			$(function()
			{
				$('.js-datetimepicker').datetimepicker({ format: 'yyyy-mm-dd', pickerPosition: 'top-left', autoclose: true, todayBtn: true, startView: 2, minView: 2, maxView: 4 });
				$('.js-datetimepicker-toggle').on('click', function()
				{
					$(this).prev().datetimepicker('show');
				});
			});
			{/ia_add_js}

		{case 'number' break}
			<input type="text" name="{$varname}" value="{if $chosen}{$chosen}{else}{$variable.empty_field}{/if}" id="{$name}">

		{case 'url' break}
			{if !is_array($chosen)}
				{assign var='chosen' value='|'|explode:$chosen}
			{/if}

			<div class="row control-group-inner">
				<div class="col col-lg-6">
					<label for="{$variable.name}[title]" class="control-label">{lang key='title'}:</label>
					<input type="text" name="{$variable.name}[title]" value="{if isset($chosen['title'])}{$chosen['title']}{elseif !empty($chosen[1])}{$chosen[1]}{/if}">
				</div>

				<div class="col col-lg-6">
					<label for="{$variable.name}[url]" class="control-label">{lang key='url'}:</label>
					<input type="text" name="{$variable.name}[url]" value="{if isset($chosen['url'])}{$chosen['url']}{elseif !empty($chosen[0])}{$chosen[0]}{else}http://{/if}">
				</div>
			</div>

		{case 'textarea' break}
			{if !$variable.use_editor}
				<textarea name="{$varname}" rows="8" id="{$name}">{$chosen}</textarea>

				{if $variable.length > 0}
					{ia_add_js}
						$(function($)
						{
							$('#{$name}').dodosTextCounter({$variable.length},
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
				{ia_wysiwyg value=$chosen name=$variable.name}
			{/if}

		{case 'image' break}
			{if $chosen}
				<div class="input-group thumbnail thumbnail-single with-actions">
					<a href="{printImage imgfile=$chosen.path url=true fullimage=true}" rel="ia_lightbox[{$varname}]">
						{printImage imgfile=$chosen.path}
					</a>

					<div class="caption">
						<a class="btn btn-small btn-danger" href="javascript:void(0);" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$chosen.path}', this, '{$variable.item}', '{$varname}', '{$item.id}')"><i class=" i-remove-sign"></i></a>
					</div>
				</div>

				{ia_html_file name="{$varname}[]" id=$name value=$chosen.path}
			{else}
				{ia_html_file name="{$varname}[]" id=$name}
			{/if}

		{case 'pictures' break}
			{if $chosen}
				<div class="thumbnails-grid">
					{foreach $chosen as $picture}
						<div class="input-group">
							<div class="thumbnail">
								<a href="{printImage imgfile=$picture.path url=true fullimage=true}" title="{$picture.title}" rel="ia_lightbox[{$variable.name}]">{printImage imgfile=$picture.path}</a>

								<div class="caption">
									<input type="text" name="{$varname}_title[]" value="{$picture.title}" class="js-edit-picture-title" id="{$varname}_{$picture@index}">
								</div>

								<div class="caption text-center">
									<a class="btn btn-small btn-success" href="javascript:void(0);" id="title_{$varname}_{$picture@index}" title="{lang key='save'}" onclick="return intelli.admin.updatePictureTitle(this, $('#{$varname}_{$picture@index}').val());" style="display: none;" data-field="{$varname}" data-item="{$variable.item}" data-picture-path="{$picture.path}" data-item-id="{$item.id}"><i class=" i-ok-sign"></i></a>
									<a class="btn btn-small btn-danger" href="javascript:void(0);" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$picture.path}', this, '{$variable.item}', '{$variable.name}', '{$item.id}')"><i class=" i-remove-sign"></i></a>
								</div>
							</div>
						</div>
					{/foreach}
				</div>

				{assign var='max_num' value=($variable.length - count($chosen))}
			{else}
				{assign var='max_num' value=$variable.length}
			{/if}

			{ia_html_file name=$varname id=$varname multiple=true max_num=$max_num title=true}

		{case 'storage' break}
			{if $chosen}
				<div class="file-uploaded-group">
					{foreach $chosen as $file}
						<div class="input-group">
							<div class="thumbnail">
								<div class="caption">
									{$file.path}
									<input type="text" name="{$varname}_title[]" value="{$file.title}" class="js-edit-picture-title" id="{$varname}_{$file@index}">
								</div>

								<div class="caption text-center">
									<a class="btn btn-primary" href="{$smarty.const.IA_CLEAR_URL}uploads/{$file.path}" title="{lang key='download'}"><i class="i-box-add"></i></a>
									<a class="btn btn-danger js-file-delete" href="#" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$file.path}', this, '{$variable.item}', '{$variable.name}', '{$item.id}')"><i class="i-remove-sign"></i></a>
								</div>
							</div>
						</div>
					{/foreach}
				</div>
				{assign var='max_num' value=($variable.length - count($chosen))}
			{else}
				{assign var='max_num' value=$variable.length}
			{/if}

			{ia_html_file name=$varname id=$varname multiple=true max_num=$max_num title=true}
	{/switch}

	{if $type == 'combo'}
		<select name="{$varname}" id="{$name}">
			<option value="">{lang key='_select_'}</option>
			{html_options options=$variable.values selected=$chosen}
		</select>

		{if $variable.relation == 'parent' && !empty($variable.children)}
			{ia_add_js order=5}
			$(function()
			{
				{foreach $variable.children as $_field => $field_list}
					{foreach $field_list as $child}
						$('#{$child}_fieldzone').addClass('hide_{$varname}');
					{/foreach}
				{/foreach}
				$('#{$name}').on('change', function()
				{
					var value = $(this).val();
					$('.hide_{$varname}').hide();
					{foreach $variable.children as $_field => $field_list}
					if (value == '{$_field}')
					{
						{foreach $field_list as $child}
							$('#{$child}_fieldzone').show();
						{/foreach}
					}
					{/foreach}
				}).change();
			});
			{/ia_add_js}
		{/if}

	{elseif $type == 'radio'}
		{if !empty($variable.values)}
			{html_radios assign='radios' name=$varname options=$variable.values selected=$chosen separator="</div>"}

			<div class="radio">{'<div class="radio">'|implode:$radios}
		{/if}

		{if $variable.relation == 'parent' && !empty($variable.children)}
			{ia_add_js order=5}
			$(function()
			{
				{foreach $variable.children as $_field => $field_list}
					{foreach $field_list as $child}
						$('#{$child}_fieldzone').addClass('hide_{$varname}');
					{/foreach}
				{/foreach}
				$('input[name="{$varname}"]').on('change', function()
				{
					var value = $(this).val();
					$('.hide_{$varname}').hide();
					{foreach $variable.children as $_field => $field_list}
					if (value == '{$_field}')
					{
						{foreach $field_list as $child}
							$('#{$child}_fieldzone').show();
						{/foreach}
					}
					{/foreach}
				}).change();
			});
			{/ia_add_js}
		{/if}

	{elseif $type == 'checkbox'}
		{html_checkboxes assign='checkboxes' name=$varname options=$variable.values selected=$chosen separator='</div>'}

		{if $variable.values}
			{html_checkboxes assign='checkboxes' name=$varname options=$variable.values selected=$chosen separator='</div>'}
			<div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
		{/if}

		{if $variable.relation == 'parent' && !empty($variable.children)}
			{ia_add_js order=5}
			$(function()
			{
				{foreach $variable.children as $_field => $field_list}
					{foreach $field_list as $child}
						$('#{$child}_fieldzone').addClass('hide_{$varname}');
					{/foreach}
				{/foreach}
				$('input[name="{$varname}[]"]').on('change', function()
				{
					$('.hide_{$varname}').hide();
					$('#type_fieldzone input[type="checkbox"]:checked').each(function()
					{
						var value = $(this).val();
						{foreach $variable.children as $_field => $field_list}
							if (value == '{$_field}')
							{
								{foreach $field_list as $child}
									$('#{$child}_fieldzone').show();
								{/foreach}
							}
						{/foreach}
					});
				}).change();
			});
			{/ia_add_js}
		{/if}

	{elseif ($type == 'checkbox' || $type == 'combo' || $type == 'radio') && $variable.show_as == 'radio'}
		<h3>{lang key=$name}</h3>
		<div>
			{html_radios name=$varname options=$variable.values separator='<br>' grouping=10}
		</div>

	{elseif ($type == 'checkbox' || $type == 'combo' || $type == 'radio') && $variable.show_as == 'combo'}
		<div>
			<h3><label for="{$varname}_domid"> {lang key=$name}: </label>
			<select name="{$varname}" id="{$varname}_domid">
				<option value="_na_">{lang key='all'}</option>
				{html_options options=$variable.values}
			</select></h3>
		</div>
	{/if}
	</div>
</div>
{if isset($field_after[$varname])}{$field_after.$varname}{/if}