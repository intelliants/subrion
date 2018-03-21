{if !isset($smarty.get.edit)}
    <div class="row">
        <div class="col-md-3">
            <div class="ia-item-author">
                <a href="{$smarty.const.IA_URL}profile/?edit" class="btn btn-default btn-sm ia-item-author__edit" title="{lang key='edit'}"><span class="fa fa-pencil"></span></a>
                <a class="ia-item-author__image" href="{ia_url type='url' item='member' data=$member}">
                    {ia_image file=$member.avatar type='thumbnail' width=120 alt=$member.fullname|default:$member.username gravatar=true email=$member.email}
                </a>
                <div class="ia-item-author__content">
                    <h4 class="ia-item__title"><a href="{ia_url type='url' item='member' data=$member}">{$member.fullname|escape}</a></h4>
                    {if $member.biography}
                        <p class="text-center text-fade-50">{$member.biography|strip_tags|truncate:100:'...':true}</p>
                    {/if}
                    {if $member.phone}
                    <div class="ia-item__additional">
                        <p><span class="fa fa-phone"></span> {lang key='field_member_phone'}: {$member.phone|escape}</p>
                    </div>
                    {/if}
                </div>
                {if $member.facebook || $member.twitter || $member.gplus || $member.linkedin}
                    <p class="text-center">
                        {if !empty($member.facebook)}
                            <a href="{$member.facebook|escape:'url'}" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-facebook fa-stack-1x fa-inverse"></i></a>
                        {/if}
                        {if !empty($member.twitter)}
                            <a href="{$member.twitter|escape:'url'}" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-twitter fa-stack-1x fa-inverse"></i></a>
                        {/if}
                        {if !empty($member.gplus)}
                            <a href="{$member.gplus|escape:'url'}" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-google-plus fa-stack-1x fa-inverse"></i></a>
                        {/if}
                        {if !empty($member.linkedin)}
                            <a href="{$member.linkedin|escape:'url'}" class="fa-stack fa-lg"><i class="fa fa-circle fa-stack-2x"></i><i class="fa fa-linkedin fa-stack-1x fa-inverse"></i></a>
                        {/if}
                    </p>
                {/if}
            </div>

            <div class="box box--border">
                <h4 class="box__caption">{lang key='funds'}</h4>
                <div class="box__content">
                    <p>{lang key='current_assets'}: <b>{$member.funds}</b></p>

                    <form method="post" action="{$smarty.const.IA_URL}profile/funds/">
                        {preventCsrf}

                        <div class="form-group">
                            <label>{lang key='amount_to_add'}</label>
                            <div class="input-group">
                                <input class="form-control" type="text" name="amount" id="amount" placeholder="{$core.config.funds_min_deposit}">
                                <span class="input-group-btn">
                                    <button class="btn btn-primary" type="submit"><span class="fa fa-plus"></span> {lang key='add'}</button>
                                </span>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-9">
            <div class="alert alert-info">No activities at the moment</div>
        </div>
    </div>
{else}
    <p class="clearfix"><a href="{$smarty.const.IA_URL}profile/" class="btn btn-default pull-right">&larr; {lang key='back_to_profile'}</a></p>
    <form method="post" enctype="multipart/form-data" class="ia-form" action="{$smarty.const.IA_SELF}">
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
{/if}