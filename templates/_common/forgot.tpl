{if 'confirm' == $form}
    <form action="{$smarty.const.IA_URL}forgot/" method="get" class="ia-form ia-form--bordered">
        {preventCsrf}
        <div class="form-group">
            <label>{lang key='email'}:</label>
            {if isset($smarty.post.email)}
                <input class="form-control" type="text" name="email" value="{$smarty.post.email|escape}">
            {elseif  isset($smarty.get.email)}
                <input class="form-control" type="text" name="email" value="{$smarty.get.email|escape}">
            {else}
                <input class="form-control" type="text" name="email">
            {/if}
        </div>
        <div class="form-group">
            <label>{lang key='code'}:</label>
            <input class="form-control" type="text" name="code"{if isset($smarty.get.code)} value="{$smarty.get.code|escape}"{/if}>
        </div>
        <div class="fieldset__actions">
            <button type="submit" class="btn btn-primary">{lang key='send'}</button>
        </div>
    </form>
{elseif 'request' == $form}
    <p>{lang key='forgot_tooltip'}</p>

    <form action="{$smarty.const.IA_URL}forgot/" method="post" class="ia-form ia-form--bordered">
        <div class="fieldset">
            <div class="fieldset__content">
                {preventCsrf}
                <div class="form-group">
                    <label>{lang key='email'}:</label>
                    <input class="form-control" type="text" name="email" value="{if isset($smarty.post.email)}{$smarty.post.email|escape}{/if}">
                </div>
            </div>
        </div>

        {include 'captcha.tpl'}

        <div class="fieldset__actions">
            <button type="submit" name="restore" class="btn btn-primary">{lang key='restore_password'}</button>
        </div>
    </form>
{/if}