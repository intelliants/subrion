<div class="ia-item">
	<div class="ia-item__image">
		{printImage imgfile=$listing.avatar title=$listing.fullname|default:$listing.username gravatar=true email=$listing.email gravatar_width=200}
	</div>
	<div class="ia-item__content">
		<div class="ia-item__actions">
			{printFavorites item=$listing itemtype='members' guests=true}
			{accountActions item=$listing itemtype='members'}
			<a href="{ia_url item='members' data=$listing type='url'}">{lang key='view_profile'} <span class="fa fa-angle-double-right"></span></a>
		</div>

		<h4 class="ia-item__title">
			<a href="{ia_url item='members' data=$listing type='url'}">{$listing.fullname|default:$listing.username}</a>
		</h4>

		<div class="ia-item__additional">
			{if isset($listing.phone) && $listing.phone}
				<p><span class="fa fa-phone"></span> {$listing.phone}</p>
			{/if}
			{if isset($listing.website) && $listing.website}
				<p><span class="fa fa-globe"></span> <a href="{$listing.website}">{lang key='field_website'}</a></p>
			{/if}
			{if isset($listing.facebook) && $listing.facebook}
				<p><span class="fa fa-facebook"></span> <a href="{$listing.facebook}">{lang key='field_facebook'}</a></p>
			{/if}
			{if isset($listing.twitter) && $listing.twitter}
				<p><span class="fa fa-twitter"></span> <a href="{$listing.twitter}">{lang key='field_twitter'}</a></p>
			{/if}
			{if isset($listing.gplus) && $listing.gplus}
				<p><span class="fa fa-google-plus"></span> <a href="{$listing.gplus}">{lang key='field_gplus'}</a></p>
			{/if}
			{if isset($listing.linkedin) && $listing.linkedin}
				<p><span class="fa fa-linkedin"></span> <a href="{$listing.linkedin}">{lang key='field_linkedin'}</a></p>
			{/if}
		</div>

		{if isset($listing.biography) && $listing.biography}
			<p>{$listing.biography|escape|truncate:250:'...':true}</p>
		{/if}

		{foreach $fields as $field}
			{if !in_array($field.name, array('username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography')) && 'plan_id' != $field.name}
				{include file='field-type-content-view.tpl' wrappedValues=true item=$listing}
			{/if}
		{/foreach}
	</div>
</div>