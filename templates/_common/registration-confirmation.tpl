{if isset($success)}
    <form method="get" action="{$smarty.const.IA_SELF}">
        {preventCsrf}
        <label>{lang key='username_or_email'}:</label>
        <input type="text" name="email" value="{$smarty.get.email|escape}">
        <p>{lang key='enter_confirmation_code'}:</p>
        <label>{lang key='key'}:</label>
        <p class="form-horizontal">
            <input type="text" name="key" value="{$smarty.get.key|escape}">
            <button type="submit" class="btn btn-primary">{lang key='confirm'}</button>
        </p>
    </form>
{else}
    {lang key='thankyou_head'}
    <p class="text-center"><b>{$email}</b></p>
    {lang key='thankyou_tail'}
    <p><a href="{$smarty.const.IA_URL}">{lang key='click_here'}</a> {lang key='thank_text'}</p>
{/if}