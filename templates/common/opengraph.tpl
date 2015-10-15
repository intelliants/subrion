{if isset($core.page.info.og)}
	{foreach $core.page.info.og as $key => $value}
		<meta property="og:{$key}" content="{if $key == 'description'}{$value|strip_tags|escape:'html'|truncate:200}{else}{$value|strip_tags|escape:'html'}{/if}">
		{if 'image' == $key}
			{$noImage=true}
		{/if}
	{/foreach}
{else}
	<meta property="og:title" content="{$core.page.title|escape:'html'}">
	<meta property="og:url" content="{$smarty.const.IA_SELF}">
	<meta property="og:description" content="{$core.config.opengraph_description|strip_tags|escape:'html'}">
{/if}

{if !isset($noImage) && $core.config.opengraph_image}
	<meta property="og:image" content="{$smarty.const.IA_URL}uploads/{$core.config.opengraph_image}">
{/if}