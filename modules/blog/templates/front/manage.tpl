<div class="tabbable">
    <ul class="nav nav-tabs">
        <li class="active"><a data-toggle="tab" href="#tab-common"><span>{lang key='common'}</span></a></li>
    </ul>
    <div class="tab-content ia-form">
        <div id="tab-common" class="tab-pane active">
            <form method="post" enctype="multipart/form-data" class="ia-form">
                {preventCsrf}

                <div class="fieldset">
                    <div class="fieldset__header">{lang key='general'}</div>
                    <div class="fieldset__content">
                        <div class="form-group">
                            <label for="input-title">{lang key='title'}</label>
                            <input class="form-control" type="text" name="title" value="{$item.title|escape}" id="input-title">
                        </div>

                        <div class="form-group">
                            <label for="input-alias">{lang key='title_alias'}</label>
                            <input class="form-control" type="text" name="alias" id="input-alias"
                                   value="{if isset($item.alias)}{$item.alias}{/if}">
                            <p class="help-block text-break-word" id="title_box"
                               style="display: none;">{lang key='page_url_will_be'}: <span id="title_url" class="text-danger">{$smarty.const.IA_URL}</span>
                            </p>
                        </div>

                        <div class="form-group">
                            <label for="body">{lang key='body'}</label>
                            {ia_wysiwyg name='body' value=$item.body}
                        </div>

                        <div class="form-group">
                            <label for="input-tag">{lang key='tags'}</label>
                            <input class="form-control" type="text" name="tags" value="{$blog_entry_tags|escape}" id="input-tag">
                            <p class="help-block text-break-word" id="title_box">{lang key='separate_with_comma_or_enter'}</p>
                        </div>

                        <div class="form-group">
                            <label for="input-image">{lang key='image'}</label>

                            {if !empty($item.image)}
                                <div class="thumbnail">
                                    <div class="thumbnail__actions">
                                        <button class="btn btn-danger btn-sm js-delete-file" data-field="image"
                                                data-item="blog_entries" data-item-id="{$item.id|default:''}"
                                                data-file="{$item.image|escape}" title="{lang key='delete'}"><span
                                                    class="fa fa-times"></span></button>
                                    </div>

                                    <a href="{ia_image file=$item.image type='large' url=true}"
                                       rel="ia_lightbox[image]">
                                        {ia_image file=$item.image}
                                    </a>

                                    <input type="hidden" name="image[path]" value="{$item.image|escape}">
                                </div>
                            {/if}

                            <div class="input-group js-files">
                                <span class="input-group-btn">
                                    <span class="btn btn-primary btn-file">
                                        {lang key='browse'} <input type="file" name="image" id="field_image">
                                    </span>
                                </span>
                                <input type="text" class="form-control js-file-name" readonly{if $item.image} value="{$item.image|escape}{/if}">
                            </div>
                        </div>
                    </div>
                </div>

                {include 'captcha.tpl'}

                <div class="fieldset__actions">
                    <button type="submit" name="data-blog-entry" class="btn btn-primary">{lang key='save'}</button>
                </div>
            </form>
        </div>
    </div>
</div>
{ia_add_media files='tagsinput, js:_IA_URL_modules/blog/js/manage'}