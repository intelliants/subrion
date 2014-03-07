{assign var='type' value=$variable.type}
{assign var='varname' value=$variable.name}
{assign var='name' value="field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}

{if trim($item.$varname)}
	{capture assign='_field_text'}
		{* display as link to view details page *}
		{if trim($item.$varname) && $variable.link_to && isset($all_item_type) && in_array($type, array('text', 'number', 'combo', 'radio', 'date'))}
			<a href="{ia_url data=$oneitem item=$all_item_type attr='class="view-details-link"' type='url'}">
		{/if}

		{if 'text' == $type}
			{$item.$varname}

		{elseif 'textarea' == $type}
			{if $variable.use_editor}
				{$item.$varname}
			{else}
				{$item.$varname|nl2br}
			{/if}

		{elseif 'number' == $type}
			{$item.$varname}

		{elseif 'checkbox' == $type}
			{arrayToLang values=$item.$varname name=$varname}

		{elseif 'storage' == $type}
			{assign var='chosen' value=$item.$varname|unserialize}

			{if $chosen}
				{foreach $chosen as $file}
					<a href="{$smarty.const.IA_CLEAR_URL}uploads/{$file.path}">{if $file.title}{$file.title}{else}{lang key='download'} {$file@iteration}{/if}</a>{if !$file@last}, {/if}
				{/foreach}
			{/if}

		{elseif 'image' == $type}
			{assign var='chosen' value=$item.$varname|unserialize}
			<div class="thumbnail" style="width: {$variable.thumb_width}px;">
				{if $variable.link_to && isset($all_item_type)}
					<a href="{ia_url data=$oneitem item=$all_item_type type='url'}" title="{$chosen.title|default:''}">
						{printImage imgfile=$chosen.path|default:'' title=$chosen.title|default:''}
						{if !empty($chosen.title)}<span class="caption">{$chosen.title|default:''}</span>{/if}
					</a>
				{elseif $variable.thumb_width == $variable.image_width && $variable.thumb_height == $variable.image_height}
					{printImage imgfile=$chosen.path|default:'' title=$chosen.title|default:'' width=$variable.thumb_width height=$variable.thumb_height}
				{else}
					<a href="{printImage imgfile=$chosen.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$varname}]" title="{$chosen.title|default:''}">
						{printImage imgfile=$chosen.path|default:'' title=$chosen.title|default:''}
						{if !empty($chosen.title)}<span class="caption">{$chosen.title|default:''}</span>{/if}
					</a>
				{/if}
			</div>

		{elseif 'combo' == $type || 'radio' == $type}
			{assign var='field_combo' value="{$name}_{$item.$varname}"}

			{lang key=$field_combo default='&nbsp;'}

		{elseif 'date' == $type}
			{$item.$varname|date_format:$config.date_format}

		{elseif 'url' == $type}
			{assign var='chosen' value='|'|explode:$item.$varname}
			<a href="{$chosen[0]}"{if $variable.url_nofollow == '1'} rel="nofollow"{/if} target="_blank">{$chosen[1]}</a>

		{elseif 'pictures' == $type}
			{if $item.$varname}
				<ul id="{$varname}" class="thumbnails">
					{foreach $item.$varname|unserialize as $pic}
						<li class="thumbnail">
							<a href="{printImage imgfile=$pic.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$varname}]" title="{$pic.title}">
								{printImage imgfile=$pic.path|default:'' title=$pic.title}
								{if !empty($pic.title)}<div class="caption">{$pic.title}</div>{/if}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		{/if}

		{* display as link to view details page *}
		{if trim($item.$varname) && $variable.link_to && isset($all_item_type) && in_array($type, array('text', 'number', 'combo', 'radio', 'date'))}
			</a>
		{/if}
	{/capture}

	{if !isset($wrappedValues)}
		{$_field_text}
	{else}
		<div class="field field-{$type}" id="{$varname}_fieldzone">
			{if !isset($excludedTitles) || !in_array($varname, $excludedTitles)}
				<span>{lang key=$name}:</span>
			{/if}
			{$_field_text}
		</div>
	{/if}
{elseif !trim($item.$varname) && $variable.empty_field}
	{if !isset($wrappedValues)}
		<span class="empty_field">{$variable.empty_field}</span>
	{else}
		<div class="field field-{$type}" id="{$varname}_fieldzone">
			<span>{lang key=$name}:</span>
			<span class="empty_field">{$variable.empty_field}</span>
		</div>
	{/if}
{/if}

{if isset($field_after[$varname])}{$field_after.$varname}{/if}