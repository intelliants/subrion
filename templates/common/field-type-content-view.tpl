{$type = $field.type}
{$name = $field.name}
{$fieldName = "field_{$field.item}_{$field.name}"}

{if isset($field_before[$name])}{$field_before.$name}{/if}

{if trim($item.$name)}
	{capture assign='_field_text'}
		{switch $type}
		{case iaField::TEXT break}
			{$item.$name|escape:'html'}

		{case iaField::TEXTAREA break}
			{if $field.use_editor}
				{$item.$name}
			{else}
				{$item.$name|escape:'html'|nl2br}
			{/if}

		{case iaField::NUMBER break}
			{$item.$name|escape:'html'}

		{case iaField::CHECKBOX break}
			{arrayToLang values=$item.$name item=$field.item name=$name}

		{case iaField::STORAGE break}
			{$value = $item.$name}

			{if $value}
				{foreach $value as $entry}
					<a href="{$core.page.nonProtocolUrl}uploads/{$entry.path}">{if $entry.title}{$entry.title|escape:'html'}{else}{lang key='download'} {$entry@iteration}{/if}</a>{if !$entry@last}, {/if}
				{/foreach}
			{/if}

		{case iaField::IMAGE break}
			{$entry = $item.$name}
			<div class="thumbnail" style="width: {$field.thumb_width}px;">
				{if $field.thumb_width == $field.image_width && $field.thumb_height == $field.image_height}
					{printImage imgfile=$entry.path|default:'' title=$entry.title|default:'' width=$field.thumb_width height=$field.thumb_height class='img-responsive'}
				{else}
					<a class="thumbnail__image" href="{printImage imgfile=$entry.path|default:'' url=true type='full'}" rel="ia_lightbox[{$name}]" title="{$entry.title|default:''}">
						{printImage imgfile=$entry.path|default:'' title=$entry.title|default:''}
					</a>
					{if !empty($entry.title)}<div class="caption"><h5>{$entry.title|default:''}</h5></div>{/if}
				{/if}
			</div>

		{case iaField::COMBO}
		{case iaField::RADIO break}
			{lang key="{$fieldName}+{$item.$name}" default='&nbsp;'}

		{case iaField::DATE break}
			{if $field.timepicker}
				{$item.$name|date_format:"{$core.config.date_format} %H:%M:%S"}
			{else}
				{$item.$name|date_format:$core.config.date_format}
			{/if}
		{case iaField::URL break}
			{$value = '|'|explode:$item.$name}
			<a href="{$value[0]}"{if $field.url_nofollow} rel="nofollow"{/if} target="_blank">{$value[1]|escape:'html'}</a>

		{case iaField::PICTURES break}
			{if $item.$name}
				{ia_add_media files='fotorama'}
				<div id="{$name}" class="ia-gallery">
					<div class="fotorama" 
						 data-nav="thumbs"
						 data-width="100%"
						 data-ratio="800/400"
						 data-allowfullscreen="true"
						 data-fit="cover">
						{foreach $item.$name as $entry}
							<a class="ia-gallery__item" {if !empty($entry.title)}data-caption="{$entry.title|escape:'html'}"{/if} href="{printImage imgfile=$entry.path|default:'' url=true type='full'}">{printImage imgfile=$entry.path|default:'' title=$entry.title}</a>
						{/foreach}
					</div>
				</div>
			{/if}
		{case iaField::TREE}
			{displayTreeNodes ids=$item.$name nodes=$field.values}
		{/switch}
	{/capture}

	{if !isset($wrappedValues)}
		{$_field_text}
	{else}
		<div class="field field-{$type}" id="{$name}_fieldzone">
			{if !isset($excludedTitles) || !in_array($name, $excludedTitles)}
				<div class="field__header">{lang key=$fieldName}</div>
			{/if}
			<div class="field__content">
				{$_field_text}
			</div>
		</div>
	{/if}
{elseif !trim($item.$name) && $field.empty_field}
	{if !isset($wrappedValues)}
		<span class="empty_field">{$field.empty_field|escape:'html'}</span>
	{else}
		<div class="field field-{$type}" id="{$name}_fieldzone">
			<span>{lang key=$fieldName}:</span>
			<span class="empty_field">{$field.empty_field|escape:'html'}</span>
		</div>
	{/if}
{/if}

{if isset($field_after[$name])}{$field_after.$name}{/if}