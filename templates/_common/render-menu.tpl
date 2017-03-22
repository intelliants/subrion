{if 'mainmenu' == $position}
    {ia_menu menus=$menu.contents class="nav navbar-nav navbar-right nav-main {$menu.classname}"}
{elseif 'inventory' == $position}
    {ia_menu menus=$menu.contents class="nav navbar-nav navbar-right nav-inventory {$menu.classname}"}
{elseif 'account' == $position}
    {if 'account' == $menu.name && $member && $core.config.members_enabled}
        <ul class="nav navbar-nav navbar-right nav-account">
            <li class="dropdown">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                    {ia_image file=$member.avatar title=$member.fullname|default:$member.username class='img-circle' gravatar=true email=$member.email}

                    {$member.fullname|default:$member.username}
                </a>
                <span class="navbar-nav__drop dropdown-toggle" data-toggle="dropdown"><span class="fa fa-angle-down"></span></span>
                {ia_hooker name='smartyFrontInsideAccountBox'}
                {ia_menu menus=$menu.contents class='dropdown-menu' loginout=true}
            </li>
        </ul>
    {else}
        <ul class="nav navbar-nav navbar-right">
            <li{if 'login' == $core.page.name} class="active"{/if}><a href="{$smarty.const.IA_URL}login/">{lang key='login'}</a></li>
            <li{if 'registration' == $core.page.name} class="active"{/if}><a href="{$smarty.const.IA_URL}registration/">{lang key='register'}</a></li>
        </ul>
    {/if}
{elseif in_array($position, ['left', 'right', 'user1', 'user2', 'top'])}
    {if !empty($menu.contents[0]) && 'account' != $menu.name}
        {ia_block header=$menu.header title=$menu.title movable=true id=$menu.id name=$menu.name collapsible=$menu.collapsible classname=$menu.classname}
            {ia_menu menus=$menu.contents class="list-group {$menu.classname}"}
        {/ia_block}
    {/if}
{elseif 'copyright' == $position}
    {ia_menu menus=$menu.contents class="nav nav-inline {$menu.classname}"}
{else}
    <!--__ms_{$menu.id}-->
    {if $menu.header || isset($manageMode)}
        <div class="nav-menu-header {$menu.classname}">{$menu.title}</div>
    {else}
        <div class="menu {$menu.classname}">
    {/if}

    <!--__ms_c_{$menu.id}-->
    {ia_menu menus=$menu.contents class='nav-menu'}
    <!--__me_c_{$menu.id}-->

    {if $menu.header || isset($manageMode)}
    {else}
        </div>
    {/if}
    <!--__me_{$menu.id}-->
{/if}