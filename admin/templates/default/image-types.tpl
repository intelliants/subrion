<form method="post" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='name'}</label>

                <div class="col col-lg-4">
                    {if iaCore::ACTION_ADD == $pageAction}
                        <input type="text" name="name" value="{$item.name|escape}">
                        <p class="help-block">{lang key='unique_name'}</p>
                    {else}
                        <input type="text" value="{$item.name|escape}" disabled>
                    {/if}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

                <div class="col col-lg-4">
                    <div class="row">
                        <div class="col col-lg-6">
                            <input type="text" name="width" value="{$item.width|intval}">
                        </div>
                        <div class="col col-lg-6">
                            <input type="text" name="height" value="{$item.height|intval}">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

                <div class="col col-lg-4">
                    <select name="resize_mode">
                        <option value="crop" data-tooltip="{lang key='crop_tip'}"{if iaPicture::CROP == $item.resize_mode} selected{/if}>{lang key='crop'}</option>
                        <option value="fit" data-tooltip="{lang key='fit_tip'}"{if iaPicture::FIT == $item.resize_mode} selected{/if}>{lang key='fit'}</option>
                    </select>
                    <p class="help-block"></p>
                </div>
            </div>

            {*<div class="row" id="use_cropper">*}
                {*<label class="col col-lg-2 control-label">{lang key='use_cropper'}</label>*}
                {*<div class="col col-lg-4">*}
                    {*{html_radio_switcher value=0 name='cropper'}*}
                {*</div>*}
            {*</div>*}

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='allowed_file_types'}</label>

                <div class="col col-lg-4">
                    {foreach $fileTypes as $entry}
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" value="{$entry.id}"{if in_array($entry.id, $assignedFileTypes)} checked{/if} name="fileTypes[{$entry.id}]">
                                {$entry.extension}
                            </label>
                        </div>
                    {/foreach}
                </div>
            </div>
        </div>

        {include 'fields-system.tpl' noSystemFields=true}
    </div>
</form>

{ia_print_js files='admin/image-types'}