{if $config.captcha && !$member}
<div class="fieldset-wrapper">
	<div class="fieldset">
		<h3 class="title">{lang key='safety'}</h3>
		<div class="content">
			<div class="captcha">{captcha}</div>
		</div>
	</div>
</div>
{/if}