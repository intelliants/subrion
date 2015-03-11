{if isset($core.page.info.og)}
	{foreach $core.page.info.og as $key => $value}
		<meta property="og:{$key}" content="{$value}">
	{/foreach}
{/if}