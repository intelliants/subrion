{assign var='type' value=$variable.type}
{assign var='varname' value=$variable.name}
{assign var='name' value="field_{$varname}"}

{if isset($field_before[$varname])}{$field_before.$varname}{/if}

{if trim($item.$varname)}
	{capture assign='_field_text'}
		{* display as a link to view details page *}
		{if trim($item.$varname) && $variable.link_to && isset($all_item_type) && in_array($type, array('text', 'number', 'combo', 'radio', 'date'))}
		<a href="{ia_url data=$oneitem item=$all_item_type attr='class="view-details-link"' type='url'}">
		{/if}

		{switch $type}
		{case 'text' break}
			{$item.$varname|escape:'html'}

		{case 'textarea' break}
			{if $variable.use_editor}
				{$item.$varname}
			{else}
				{$item.$varname|escape:'html'|nl2br}
			{/if}

		{case 'number' break}
			{$item.$varname|escape:'html'}

		{case 'checkbox' break}
			{arrayToLang values=$item.$varname name=$varname}

		{case 'storage' break}
			{assign value $item.$varname|unserialize}

			{if $value}
				{foreach $value as $entry}
					<a href="{$nonProtocolUrl}uploads/{$entry.path}">{if $entry.title}{$entry.title|escape:'html'}{else}{lang key='download'} {$entry@iteration}{/if}</a>{if !$entry@last}, {/if}
				{/foreach}
			{/if}

		{case 'image' break}
			{assign var='entry' value=$item.$varname|unserialize}
			<div class="thumbnail" style="width: {$variable.thumb_width}px;">
				{if $variable.link_to && isset($all_item_type)}
					<a href="{ia_url data=$oneitem item=$all_item_type type='url'}" title="{$entry.title|default:''}">
						{printImage imgfile=$entry.path|default:'' title=$entry.title|default:''}
						{if !empty($entry.title)}<span class="caption">{$entry.title|default:''}</span>{/if}
					</a>
				{elseif $variable.thumb_width == $variable.image_width && $variable.thumb_height == $variable.image_height}
					{printImage imgfile=$entry.path|default:'' title=$entry.title|default:'' width=$variable.thumb_width height=$variable.thumb_height}
				{else}
					<a href="{printImage imgfile=$entry.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$varname}]" title="{$entry.title|default:''}">
						{printImage imgfile=$entry.path|default:'' title=$entry.title|default:''}
						{if !empty($entry.title)}<span class="caption">{$entry.title|default:''}</span>{/if}
					</a>
				{/if}
			</div>

		{case 'combo'}
		{case 'radio' break}
			{assign field_combo "{$name}_{$item.$varname}"}
			{lang key=$field_combo default='&nbsp;'}

		{case 'date' break}
			{$item.$varname|date_format:$config.date_format}

		{case 'url' break}
			{assign value '|'|explode:$item.$varname}
			<a href="{$value[0]}"{if $variable.url_nofollow} rel="nofollow"{/if} target="_blank">{$value[1]|escape:'html'}</a>

		{case 'pictures' break}
			{if $item.$varname}
				<ul id="{$varname}" class="thumbnails">
					{foreach $item.$varname|unserialize as $entry}
						<li class="thumbnail">
							<a href="{printImage imgfile=$entry.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$varname}]" title="{$entry.title|escape:'html'}">
								{printImage imgfile=$entry.path|default:'' title=$entry.title}
								{if !empty($entry.title)}<div class="caption">{$entry.title|escape:'html'}</div>{/if}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		{/switch}

		{* display as a link to view details page *}
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