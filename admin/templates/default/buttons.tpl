{if isset($actions) && count($actions) > 0}
<div class="buttons">
	{foreach $actions as $action}
	<a href="{if isset($action.url) && $action.url}{$action.url}{else}#{/if}" {if isset($action.attributes) && $action.attributes}{$action.attributes}{/if}>
		<img src="{$action.icon_url}" title="{$action.label}" alt="{$action.label}" />
	</a>
	{/foreach}
</div>
{/if}