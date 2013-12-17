{if isset($notifications) && !empty($notifications)}
	{foreach from=$notifications key='name' item='notification'}
		{if !empty($notification)}
			<div {if isset($notification.type) && !empty($notification.type)}id="{$notification.type}"{else}id="notification"{/if}>
				{if isset($notification.message) && !empty($notification.message)}
					<div class="alert alert-block alert-{$notification.type}">
						<ul class="unstyled">
							{foreach from=$notification.message item='message'}
								<li>{$message}</li>
							{/foreach}
						</ul>
					</div>
				{/if}
			</div>
		{/if}
	{/foreach}
{else}
	<div id="notification" class="alert alert-info" style="display: none;"></div>
{/if}