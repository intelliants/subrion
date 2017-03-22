<form method="post" enctype="multipart/form-data" class="ia-form">
    {preventCsrf}

    {if !empty($assignableGroups)}
        {capture append='fieldset_content_before' name='general'}
            <div class="control-group">
                <label class="control-label" for="input-group">{lang key='group'}</label>
                <div class="controls">
                    <select name="usergroup_id" id="input-group">
                        <option value="8">{lang key='default'}</option>
                        {foreach $assignableGroups as $id => $name}
                            <option value="{$id}"{if $id == $item.usergroup_id} selected{/if}>{lang key="usergroup_{$name}"}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        {/capture}
    {/if}

    {capture append='tabs_content' name='password'}
        <div class="fieldset">
            <div class="fieldset__header">{lang key='change_password'}</div>
            <div class="fieldset__content">
                <div class="form-group">
                    <label for="current">{lang key='current_password'}:</label>
                    <input class="form-control" type="password" name="current" id="current">
                </div>
                <div class="form-group">
                    <label for="new">{lang key='new_password'}:</label>
                    <input class="form-control" type="password" name="new" id="new">
                </div>
                <div class="form-group">
                    <label for="confirm">{lang key='new_password2'}:</label>
                    <input class="form-control" type="password" name="confirm" id="confirm">
                </div>
            </div>
            <div class="fieldset__actions">
                <button class="btn btn-primary" type="submit" name="change_pass">{lang key='change_password'}</button>
            </div>
        </div>
    {/capture}

    {if $plans_count}
        {capture append='tabs_content' name='funds'}
            <div class="fieldset">
                {if $item.funds > 0}
                    <div class="fieldset__header">{lang key='funds'}: {$item.funds|string_format:'%d'} {$core.config.currency}</div>
                {else}
                    <div class="alert alert-info">{lang key='no_funds'}</div>
                {/if}
                {preventCsrf}
                <div class="fieldset__actions">
                    <button class="btn btn-primary" type="button" id="js-add-funds">{lang key='add_funds'}</button>
                </div>
                {ia_add_media files='js:frontend/member-funds'}
            </div>
        {/capture}
    {/if}

    {if $plans}
        {capture append='tabs_content' name='plans'}
            {include 'plans.tpl' item=$member}
            <div class="fieldset__actions">
                <button class="btn btn-primary" type="submit">{lang key='save'}</button>
            </div>
        {/capture}
    {/if}

    {* use this to exclude tabs where you don't need capture named __all__ *}
    {append 'tabs_after' ['password', 'funds', 'plans'] index='excludes'}

    {capture append='tabs_after' name='__all__'}
        <div class="fieldset__actions">
            <button class="btn btn-primary" type="submit" name="change_info">{lang key='save'}</button>
        </div>
    {/capture}

    {ia_hooker name='frontEditProfile'}

    {include 'item-view-tabs.tpl'}
</form>