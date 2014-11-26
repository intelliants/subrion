{assign type $variable.type}
{assign name $variable.name}
{assign fieldName "field_{$name}"}

{if isset($field_before[$name])}{$field_before.$name}{/if}

{if trim($item.$name)}
	{capture assign='_field_text'}
		{* display as a link to view details page *}
		{if trim($item.$name) && $variable.link_to && isset($all_item_type) && in_array($type, array('text', 'number', 'combo', 'radio', 'date'))}
		<a href="{ia_url data=$oneitem item=$all_item_type attr='class="view-details-link"' type='url'}">
		{/if}

		{switch $type}
		{case 'text' break}
			{$item.$name|escape:'html'}

		{case 'textarea' break}
			{if $variable.use_editor}
				{$item.$name}
			{else}
				{$item.$name|escape:'html'|nl2br}
			{/if}

		{case 'number' break}
			{$item.$name|escape:'html'}

		{case 'checkbox' break}
			{arrayToLang values=$item.$name name=$name}

		{case 'storage' break}
			{assign value $item.$name|unserialize}

			{if $value}
				{foreach $value as $entry}
					<a href="{$nonProtocolUrl}uploads/{$entry.path}">{if $entry.title}{$entry.title|escape:'html'}{else}{lang key='download'} {$entry@iteration}{/if}</a>{if !$entry@last}, {/if}
				{/foreach}
			{/if}

		{case 'image' break}
			{assign entry $item.$name|unserialize}
			<div class="thumbnail" style="width: {$variable.thumb_width}px;">
				{if $variable.link_to && isset($all_item_type)}
					<a href="{ia_url data=$oneitem item=$all_item_type type='url'}" title="{$entry.title|default:''}">
						{printImage imgfile=$entry.path|default:'' title=$entry.title|default:''}
						{if !empty($entry.title)}<span class="caption">{$entry.title|default:''}</span>{/if}
					</a>
				{elseif $variable.thumb_width == $variable.image_width && $variable.thumb_height == $variable.image_height}
					{printImage imgfile=$entry.path|default:'' title=$entry.title|default:'' width=$variable.thumb_width height=$variable.thumb_height}
				{else}
					<a href="{printImage imgfile=$entry.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$name}]" title="{$entry.title|default:''}">
						{printImage imgfile=$entry.path|default:'' title=$entry.title|default:''}
						{if !empty($entry.title)}<span class="caption">{$entry.title|default:''}</span>{/if}
					</a>
				{/if}
			</div>

		{case 'combo'}
		{case 'radio' break}
			{assign field_combo "{$fieldName}_{$item.$name}"}
			{lang key=$field_combo default='&nbsp;'}

		{case 'date' break}
			{$item.$name|date_format:$config.date_format}

		{case 'url' break}
			{assign value '|'|explode:$item.$name}
			<a href="{$value[0]}"{if $variable.url_nofollow} rel="nofollow"{/if} target="_blank">{$value[1]|escape:'html'}</a>

		{case 'pictures' break}
			{if $item.$name}
				<ul id="{$name}" class="thumbnails">
					{foreach $item.$name|unserialize as $entry}
						<li class="thumbnail">
							<a href="{printImage imgfile=$entry.path|default:'' url=true fullimage=true}" class="gallery" rel="ia_lightbox[{$name}]" title="{$entry.title|escape:'html'}">
								{printImage imgfile=$entry.path|default:'' title=$entry.title}
								{if !empty($entry.title)}<div class="caption">{$entry.title|escape:'html'}</div>{/if}
							</a>
						</li>
					{/foreach}
				</ul>
			{/if}
		{/switch}

		{* display as a link to view details page *}
		{if trim($item.$name) && $variable.link_to && isset($all_item_type) && in_array($type, array('text', 'number', 'combo', 'radio', 'date'))}
			</a>
		{/if}

	{/capture}

	{if !isset($wrappedValues)}
		{$_field_text}
	{else}
		<div class="field field-{$type}" id="{$name}_fieldzone">
			{if !isset($excludedTitles) || !in_array($name, $excludedTitles)}
				<span>{lang key=$fieldName}:</span>
			{/if}
			{$_field_text}
		</div>
	{/if}
{elseif !trim($item.$name) && $variable.empty_field}
	{if !isset($wrappedValues)}
		<span class="empty_field">{$variable.empty_field}</span>
	{else}
		<div class="field field-{$type}" id="{$name}_fieldzone">
			<span>{lang key=$fieldName}:</span>
			<span class="empty_field">{$variable.empty_field}</span>
		</div>
	{/if}
{/if}

{if isset($field_after[$name])}{$field_after.$name}{/if}