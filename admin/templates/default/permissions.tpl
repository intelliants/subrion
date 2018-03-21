{if !isset($permissions)}
    <div class="row">
        <div class="col col-lg-6">
            <div class="widget widget-large" id="widget-usergroups">
                <div class="widget-header"><i class="i-users"></i> {lang key='usergroups'}</div>
                <div class="widget-content">
                    <table class="table table-light table-hover">
                        <thead>
                            <tr>
                                <th width="30">#</th>
                                <th>{lang key='name'}</th>
                                <th>{lang key='admin_panel'}</th>
                                <th width="100">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $usergroups as $entry}
                                <tr>
                                    <td>{$entry@iteration}</td>
                                    <td>{lang key="usergroup_{$entry.name}"|escape}</td>
                                    <td>
                                        {if $entry.admin_access}
                                            <span class="text-success">{lang key='allowed'}</span>
                                        {else}
                                            <span class="text-danger">{lang key='not_allowed'}</span>
                                        {/if}
                                    </td>
                                    <td class="text-right"><a class="btn btn-xs btn-default" href="{$smarty.const.ADMIN_URL}permissions/?group={$entry.id}"><i class="i-lock"></i> {lang key='edit'}</a></td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col col-lg-6">
            <div class="widget widget-large" id="widget-members">
                <div class="widget-header">
                    <i class="i-user"></i> {lang key='members_with_custom_perms'}
                    <form action="#" class="special-search special-search--right" id="js-form-users-search">
                        <i class="i-search"></i>
                        <input type="text" name="user-search" id="input-user-search" placeholder="{lang key='username'}">
                    </form>
                </div>
                <div class="widget-content">
                    <table class="table table-light table-hover" id="js-table-users">
                        <thead>
                            <tr>
                                <th width="30">#</th>
                                <th>{lang key='name'}</th>
                                <th>{lang key='group'}</th>
                                <th>{lang key='admin_panel'}</th>
                                <th width="100">&nbsp;</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $members as $entry}
                                <tr data-name="{$entry.fullname|escape}">
                                    <td>{$entry@iteration}</td>
                                    <td><a href="{$smarty.const.ADMIN_URL}members/edit/{$entry.id}/">{$entry.fullname|escape}</a></td>
                                    <td>{lang key="usergroup_{$entry.usergroup}"}</td>
                                    <td>
                                        {if $entry.admin_access}
                                            <span class="text-success">{lang key='allowed'}</span>
                                        {else}
                                            <span class="text-danger">{lang key='not_allowed'}</span>
                                        {/if}
                                    </td>
                                    <td class="text-right">
                                        <a class="btn btn-xs btn-default" href="{$smarty.const.ADMIN_URL}permissions/?user={$entry.id}"><i class="i-lock"></i> {lang key='edit'}</a>
                                    </td>
                                </tr>
                            {/foreach}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {ia_add_media files='css: _IA_URL_js/jquery/plugins/scrollbars/jquery.mCustomScrollbar'}
    {ia_print_js files='jquery/plugins/scrollbars/jquery.mCustomScrollbar.concat.min'}

    {ia_add_js}
$(function()
{
    $('.widget-content').mCustomScrollbar({ theme: 'dark-thin' });

    $('#js-form-users-search').on('submit', function(e)
    {
        e.preventDefault();
    });

    $('#input-user-search').on('keyup', function(e)
    {
        var text = $(this).val(),
            $collection = $('tr', '#js-table-users').not(':first');
        if (text != '')
        {
            var rx = RegExp(text, 'i')
            $collection.each(function()
            {
                var name = $(this).data('name');
                (name.match(rx) !== null) ? $(this).show() : $(this).hide();
            });
        }
        else {
            $collection.show();
        }
    });
});
    {/ia_add_js}
{else}
    <ul class="nav nav-tabs">
        {foreach $permissions as $pageType => $list}
            <li{if $list@first} class="active"{/if}><a href="#tab-{$pageType}" data-toggle="tab">{lang key="{$pageType}s"}</a></li>
        {/foreach}
    </ul>
    <div class="tab-content">
        {foreach $permissions as $pageType => $list}
        <div class="tab-pane{if $list@first} active{/if}" id="tab-{$pageType}">
            {if iaAcl::OBJECT_ADMIN_PAGE == $pageType}
            <div class="p-list">
                <div class="p-list__content">
                    <table class="p-table">
                    <tbody>
                        <tr class="p-table__row{if $adminAccess.modified} p-table__row--modified{/if}">
                            <td class="p-table__label"><p>{$adminAccess.title}</p></td>
                            <td class="p-table__actions js-toggler" data-default-access="{$adminAccess.default}" data-page-type="admin_access" data-action="read">
                                <span class="label label-{if $adminAccess.access}success{else}default{/if}" data-access="1"><i class="i-checkmark"></i> Yes</span>
                                <span class="label label-{if !$adminAccess.access}danger{else}default{/if}" data-access="0"><i class="i-close"></i> No</span>
                            </td>
                            <td class="p-table__more js-togglers-group">
                                <a href="#" class="p-table__more__reset" rel="reset"{if !$adminAccess.modified} style="display: none;"{/if}><i class="i-loop"></i> Reset to default</a>
                            </td>
                        </tr>
                    </tbody>
                    </table>
                </div>
            </div>
            {/if}

            {foreach $list as $key => $group}
            <div class="p-list{if iaAcl::OBJECT_ADMIN_PAGE == $pageType} js-dashboard-action{if !$adminAccess.access}" style="display: none;{/if}{/if}">
                <div class="p-list__title">
                    {*<div class="p-list__actions">
                        <a href="#" class=""><i class="i-chevron-up"></i></a>
                    </div>*}
                    <h4>
                        {lang key="pages_group_{$pageGroupTitles.$key}"}
                        {*<small>
                            <a href="#"><i class="i-checkmark"></i> Allow all</a>
                            <a href="#"><i class="i-close"></i> Deny all</a>
                            <a href="#"><i class="i-loop"></i> Reset all to default</a>
                        </small>*}
                    </h4>
                </div>

                <div class="p-list__content">
                    {foreach $group as $object => $actionsGroup}
                    <table class="p-table">
                        <thead>
                        <tr>
                            <th>{$actionsGroup.title}</th>
                            <th colspan="2" class="js-togglers-group">
                                <small>
                                    <a href="#" rel="allow-all" data-access="1"><i class="i-checkmark"></i> Allow all</a>
                                    <a href="#" rel="deny-all" data-access="0"><i class="i-close"></i> Deny all</a>
                                    <a href="#" rel="reset-all"><i class="i-loop"></i> Reset all to default</a>
                                </small>
                            </th>
                        </tr>
                        </thead>
                        <tbody>
                        {foreach $actionsGroup.list as $action => $data}
                        <tr class="p-table__row{if $data.modified} p-table__row--modified{/if}">
                            <td class="p-table__label">
                                <p>{$data.title}</p>
                            </td>
                            <td class="p-table__actions js-toggler" data-default-access="{$data.default}" data-page-type="{$pageType}" data-object="{$object}" data-action="{$action}">
                                <span class="label label-{if $data.access}success{else}default{/if}" data-access="1"><i class="i-checkmark"></i> Yes</span>
                                <span class="label label-{if !$data.access}danger{else}default{/if}" data-access="0"><i class="i-close"></i> No</span>
                            </td>
                            <td class="p-table__more js-togglers-group">
                                <a href="#" class="p-table__more__reset" rel="reset"{if !$data.modified} style="display: none;"{/if}><i class="i-loop"></i> Reset to default</a>
                            </td>
                        </tr>
                        {/foreach}
                        </tbody>
                    </table>
                    {/foreach}
                </div>
            </div>
            {/foreach}
        </div>
        {/foreach}
    </div>
    {ia_add_media files='js:admin/permissions'}
{/if}