<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">
                <h4>{lang key='general'}</h4>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label"
                       for="input-title">{lang key='title'} {lang key='field_required'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="title" value="{$item.title|escape}" id="input-title">
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-alias">{lang key='title_alias'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="alias" id="input-alias" value="{if isset($item.alias)}{$item.alias}{/if}">
                    <p class="help-block text-break-word" id="title_box"
                       style="display: none;">{lang key='page_url_will_be'}: <span id="title_url" class="text-danger">{$smarty.const.IA_URL}</span>
                    </p>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label"
                       for="body">{lang key='body'} {lang key='field_required'}</label>
                <div class="col col-lg-8">
                    {ia_wysiwyg name='body' value=$item.body}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-tag">{lang key='tags'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="tags" value="{$item.tags|escape}" id="input-tag">
                    <p class="help-block text-break-word" id="title_box">{lang key='separate_with_comma_or_enter'}</p>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-image">{lang key='image'}</label>
                <div class="col col-lg-4">
                    {if !empty($item.image)}
                        <div class="input-group thumbnail thumbnail-single with-actions">
                            <a href="{ia_image file=$item.image type='large' url=true}" rel="ia_lightbox">
                                {ia_image file=$item.image}
                            </a>

                            <div class="caption">
                                <a class="btn btn-small btn-danger js-cmd-delete-file" href="#"
                                   title="{lang key='delete'}" data-file="{$item.image}" data-item="blog_entries"
                                   data-field="image" data-id="{$id}"><i class="i-remove-sign"></i></a>
                            </div>
                        </div>
                    {/if}

                    {ia_html_file name='image' id='input-image'}
                </div>
            </div>
        </div>

        {capture name='systems' append='fieldset_before'}
            <div class="row">
                <label class="col col-lg-2 control-label" for="input-language">{lang key='language'}</label>
                <div class="col col-lg-4">
                    <select name="lang" id="input-language"{if count($core.languages) == 1} disabled{/if}>
                        {foreach $core.languages as $code => $language}
                            <option value="{$code}"{if $item.lang == $code} selected{/if}>{$language.title}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/capture}

        {include 'fields-system.tpl' datetime=true}
    </div>
</form>
{ia_add_media files='tagsinput, js:_IA_URL_modules/blog/js/manage'}