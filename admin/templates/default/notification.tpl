<div id="notification" style="display: none;"></div>

{if isset($notifications)}
	{foreach $notifications as $entries}
		<div class="alert alert-{$entries.type}">
			<ul>
				{foreach $entries.message as $message}
					<li>{$message}</li>
				{/foreach}
			</ul>
		</div>
	{/foreach}
{/if}