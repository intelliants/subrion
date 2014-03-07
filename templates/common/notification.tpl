{if isset($notifications) && $notifications}
	{foreach $notifications as $type => $entries}
		<div class="alert alert-block alert-{$type}">
			<ul class="unstyled">
				{foreach $entries as $message}
					<li>{$message}</li>
				{/foreach}
			</ul>
		</div>
	{/foreach}
{else}
	<div id="notification" class="alert alert-info" style="display: none;"></div>
{/if}