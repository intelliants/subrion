{if !$protect}
    {if isset($page_protect)}
        <div class="alert alert-warning">{$page_protect}</div>
    {/if}

    {$content}
{else}
    <div class="alert alert-warning">{lang key='password_protected_page'}</div>

    <form action="{$smarty.const.IA_SELF}" method="post" class="form-inline">
        {preventCsrf}
        <label>{lang key='password'}:
            <input type="password" name="password" value="">
            <button type="submit" name="login" value="" class="btn btn-primary">{lang key='view'}</button>
        </label>
    </form>
{/if}