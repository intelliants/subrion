<div class="ia-item ia-item--view">
	<div class="ia-item__image">
		{printImage imgfile=$item.avatar title=$item.fullname|default:$item.username gravatar=true email=$item.email gravatar_width=200}

		{if $item.featured || $item.sponsored}
			<div class="ia-item__labels">
				{if $item.sponsored}<span class="label label-warning" title="{lang key='sponsored'}"><span class="fa fa-star"></span> {lang key='sponsored'}</span>{/if}
				{if $item.featured}<span class="label label-info" title="{lang key='featured'}"><span class="fa fa-star-o"></span> {lang key='featured'}</span>{/if}
			</div>
		{/if}
	</div>
	<div class="ia-item__content">
		<div class="ia-item__content__info">
			<span class="fa fa-eye"></span> {$item.views_num}
		</div>
		<div class="ia-item__additional">
			{if isset($item.facebook) && $item.facebook}
				<p><a class="ia-item__additional__icon" href="{$item.facebook}"><span class="fa fa-facebook"></span></a></p>
			{/if}
			{if isset($item.twitter) && $item.twitter}
				<p><a class="ia-item__additional__icon" href="{$item.twitter}"><span class="fa fa-twitter"></span></a></p>
			{/if}
			{if isset($item.gplus) && $item.gplus}
				<p><a class="ia-item__additional__icon" href="{$item.gplus}"><span class="fa fa-google-plus"></span></a></p>
			{/if}
			{if isset($item.linkedin) && $item.linkedin}
				<p><a class="ia-item__additional__icon" href="{$item.linkedin}"><span class="fa fa-linkedin"></span></a></p>
			{/if}
		</div>

		<table class="table ia-item__table">
			<tbody>
				{if isset($item.phone) && $item.phone}
					<tr>
						<td>{lang key='field_phone'}</td>
						<td>{$item.phone}</td>
					</tr>
				{/if}
				{if isset($item.website) && $item.website}
					<tr>
						<td>{lang key='field_website'}</td>
						<td>{$item.website|linkify}</td>
					</tr>
				{/if}
				{if isset($item.biography) && $item.biography}
					<tr>
						<td>{lang key='field_biography'}</td>
						<td>{$item.biography|escape}</td>
					</tr>
				{/if}
				<tr>
					<td>{lang key='member_since'}</td>
					<td>{$item.date_reg|date_format:$core.config.date_format}</td>
				</tr>
			</tbody>
		</table>
	</div>
</div>

{foreach $item.items as $itemName => $oneitem}
	{if $oneitem.items}
		{capture name=$itemName append='tabs_content'}
			{if !$oneitem.package}
				{include "search.{$itemName}.tpl" listings=$oneitem.items fields=$oneitem.fields}
			{else}
				{include "extra:{$oneitem.package}/search.{$itemName}" listings=$oneitem.items fields=$oneitem.fields}
			{/if}
		{/capture}
	{/if}
{/foreach}

{include file='item-view-tabs.tpl' isView=true exceptions=array('username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography')}

{if isset($groups['___empty___'])}
	{include file='field-type-content-fieldset.tpl' item_sections=$groups isView=true exceptions=array('username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography')}
{/if}

{ia_hooker name='smartyViewListingBeforeFooter'}