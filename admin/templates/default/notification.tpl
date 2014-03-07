<div id="notification" style="display: none;"></div>
{if isset($notifications)}
	{foreach $notifications as $type => $entries}
		{if 'system' != $type}
		<div class="alert alert-{$type}">
			<ul>
				{foreach $entries as $message}
					<li>{$message}</li>
				{/foreach}
			</ul>
		</div>
		{/if}
	{/foreach}
{/if}