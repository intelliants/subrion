<form method="post" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='options'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-name">{lang key='name'} {lang key='field_required'}</label>

                <div class="col col-lg-4">
                    <input type="text" name="name" id="input-name" value="{if isset($smarty.post.name)}{$smarty.post.name|escape}{/if}">
                    <p class="help-block">{lang key='unique_name'}</p>
                </div>
            </div>

            <div class="row">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                        <div class="btn-group btn-group-xs translate-group-actions">
                            <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-title"><span class="i-earth"></span></button>
                            <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-title"><span class="i-copy"></span></button>
                        </div>
                    {/if}
                    <label class="control-label">{lang key='title'} {lang key='field_required'}</label>
                </div>
                <div class="col col-lg-4">
                    {if count($core.languages) > 1}
                        <div class="translate-group" id="language-group-title">
                            <div class="translate-group__default">
                                <div class="translate-group__item">
                                    <input type="text" name="title[{$core.language.iso}]"{if isset($title[$core.language.iso])} value="{$title[$core.language.iso]|escape}"{/if}>
                                    <div class="translate-group__item__code">{$core.language.title|escape}</div>
                                </div>
                            </div>
                            <div class="translate-group__langs">
                                {foreach $core.languages as $iso => $language}
                                    {if $iso != $core.language.iso}
                                        <div class="translate-group__item">
                                            <input type="text" name="title[{$iso}]"{if isset($smarty.post.title.$iso)} value="{$smarty.post.title.$iso|escape}"{/if}>
                                            <span class="translate-group__item__code">{$language.title|escape}</span>
                                        </div>
                                    {/if}
                                {/foreach}
                            </div>
                        </div>
                    {else}
                        <input type="text" name="title[{$core.language.iso}]"{if isset($smarty.post.title[$core.language.iso])} value="{$smarty.post.title[$core.language.iso]|escape}"{/if}>
                    {/if}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-source">{lang key='copy_privileges_from'} {lang key='field_required'}</label>

                <div class="col col-lg-4">
                    <select name="copy_from" id="input-source">
                        {foreach $groups as $id => $name}
                            <option value="{$id}"{if (isset($smarty.post.copy_from) && $smarty.post.copy_from == $id) || (!isset($smarty.post.copy_from) && iaUsers::MEMBERSHIP_REGULAR == $id)} selected{/if}>{lang key="usergroup_{$name}"}</option>
                        {/foreach}
                    </select>
                </div>
            </div>

            <hr>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='assignable'} <a href="#" class="js-tooltip" title="{$tooltips.usergroup_assignable}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=0 name='assignable'}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='visible_on_members'} <a href="#" class="js-tooltip" title="{$tooltips.usergroup_visible}"><i class="i-info"></i></a></label>

                <div class="col col-lg-4">
                    {html_radio_switcher value=0 name='visible'}
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions inline">
        <input type="submit" name="save" value="{lang key='save'}" class="btn btn-primary">
        <input type="reset" value="{lang key='reset'}" class="btn btn-danger">
    </div>
</form>