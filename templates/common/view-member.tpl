<div class="media ia-item view-account">
	{if $item.avatar}
		{assign avatar $item.avatar|unserialize}
		{if $avatar}
			<div class="media-object pull-left">
				{printImage imgfile=$avatar.path title=$item.fullname|default:$item.username class='img-circle'}
			</div>
		{/if}
	{/if}

	<div class="media-body">
		{if $item.featured || $item.sponsored}
			<p>
				{if $item.featured}<span class="label label-info">{lang key='featured'}<span>{/if}
				{if $item.sponsored}<span class="label label-warning">{lang key='sponsored'}<span>{/if}
			</p>
		{/if}

		<div>
			<p><span class="muted">{lang key='field_username'}:</span> {$item.username}</p>
			<p><span class="muted">{lang key='field_fullname'}:</span> {$item.fullname}</p>
		</div>
	</div>
</div>

{foreach $item.items as $itemkey => $oneitem}
	{if $oneitem.items}
		{capture name=$itemkey append='tabs_content'}
			{include file='all-items-page.tpl' all_items=$oneitem.items all_item_fields=$oneitem.fields all_item_type=$itemkey}
		{/capture}
	{/if}
{/foreach}

{include file='item-view-tabs.tpl' isView=true exceptions=array('username', 'avatar', 'fullname')}

{ia_hooker name='smartyViewListingBeforeFooter'}