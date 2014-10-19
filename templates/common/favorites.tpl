{if $favorites}
	<ul class="nav nav-tabs">
		{foreach $favorites as $itemName => $entry}
			{if $entry.items}
				<li{if $entry@first} class="active"{/if}><a href="#tab-{$itemName}" data-toggle="tab"><span>{lang key=$itemName}</span></a></li>
			{/if}
		{/foreach}
	</ul>

	<div class="tab-content">
		{foreach $favorites as $itemName => $entry}
			{if $entry.items}
				<div id="tab-{$itemName}" class="tab-pane{if $entry@first} active{/if}">
					{include file='all-items-page.tpl' all_items=$entry.items all_item_fields=$entry.fields all_item_type=$itemName}
				</div>
			{/if}
		{/foreach}
	</div>
{else}
	<div class="alert alert-info">{lang key='no_favorites'}</div>
{/if}