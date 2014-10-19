<div class="ia-items">
	{foreach $all_items as $oneitem}
		<div class="media ia-item ia-item-bordered">
			{assign item $oneitem}

			<div class="pull-left">
				{if $oneitem.avatar}
					{assign avatar $oneitem.avatar|unserialize}
					{if $avatar}
						{printImage imgfile=$avatar.path width=100 height=100 title=$oneitem.fullname|default:$oneitem.username class='media-object'}
					{/if}
				{/if}
			</div>
			
			<div class="media-body">
				{foreach $all_item_fields as $onefield}
					{if 'plan_id' != $onefield.name && 'avatar' != $onefield.name}
						{include file='field-type-content-view.tpl' variable=$onefield wrappedValues=true}
					{/if}
				{/foreach}
			</div>

			<div class="ia-item-panel">
				{ia_url item=$all_item_type data=$oneitem type='icon_text' icon='icon-user'}

				{if isset($member.id) && $member.id}
					{printFavorites item=$oneitem itemtype=$all_item_type}
					{accountActions item=$oneitem itemtype=$all_item_type}
				{/if}
			</div>
		</div>
	{/foreach}
</div>