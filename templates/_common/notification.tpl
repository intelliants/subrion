{if $core.notifications}
	{foreach $core.notifications as $type => $entries}
		<div class="alert alert-{$type}">
			<ul class="list-unstyled">
				{foreach $entries as $message}
					<li>{$message}</li>
				{/foreach}
			</ul>
		</div>
	{/foreach}
{else}
	<div id="notification" class="alert alert-info" style="display: none;"></div>
{/if}