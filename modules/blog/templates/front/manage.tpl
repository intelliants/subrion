<form method="post" enctype="multipart/form-data" action="{$smarty.const.IA_SELF}" class="ia-form">
    {preventCsrf}

    {include 'item-view-tabs.tpl'}

    <div class="ia-form__after-tabs">
        {include 'captcha.tpl'}

        <div class="fieldset__actions">
            <button type="submit" name="data-blog-entry" class="btn btn-primary">{lang key='save'}</button>
        </div>
    </div>

</form>

{ia_add_media files='tagsinput, js:_IA_URL_modules/blog/js/manage'}
