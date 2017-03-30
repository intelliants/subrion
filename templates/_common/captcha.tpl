{if $core.config.captcha && !$member}
    <div class="fieldset">
        <div class="fieldset__header">{lang key='safety'}</div>
        <div class="fieldset__content">
            <div class="captcha">{captcha}</div>
        </div>
    </div>
{/if}