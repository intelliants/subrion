{if !$protect}
    {if isset($page_protect)}
        <div class="alert alert-warning">{$page_protect}</div>
    {/if}

    {if isset($manageMode)}
        <div id="Blocks" class="groupWrapper">
            <div class="box-visual">
                <div id="page-content__{$core.page.name}"
                     class="visual-page-content--wrapper">{$content}</div>
                <div class="box-visual__actions">
                    <a class="js-page-inline-edit box-visual__actions__item box-visual__actions__item--edit"
                       data-type="pages" data-name="{$core.page.name}" href="#"
                       data-action="edit"><span class="v-icon v-icon--pencil"></span></a>
                </div>
            </div>
        </div>
    {else}
        {$content}
    {/if}
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