{function name=menu pid=0}
    {if isset($data.$pid)}
        <ul class="{$class}{if $pid} menu_{$pid}{/if}">

            {foreach $data.$pid as $menu}

                {if 'mainmenu' == $position && $menu@iteration > $core.config.max_top_menu_items|default:5 && $menu.level < 1}{capture append=dropdown name=$menu.page_name}{/if}

                {if in_array($position, ['left', 'right', 'user1', 'user2', 'top'])}
                    <a class="list-group-item{if $menu.active} active{/if}" href="{if $menu.url}{$menu.url}{else}{$smarty.const.IA_SELF}#{/if}"{if $menu.nofollow} rel="nofollow"{/if}{if $menu.new_window} target="_blank"{/if}>{$menu.text}</a>
                {else}
                    <li class="m_{$menu.page_name}
                        {if isset($data[$menu.el_id]) || isset($menu_children)} dropdown{/if}
                        {if $menu.active} active{/if}
                        {if $menu.level >= 1 && (isset($data[$menu.el_id]) || isset($menu_children))} dropdown-submenu{/if}
                        {if $menu.level >= 0 && (isset($data[$menu.el_id]) || isset($menu_children)) && $position == 'left'} dropdown-submenu{/if}
                        ">

                        <a href="{if $menu.url}{$menu.url}{else}{$smarty.const.IA_SELF}#{/if}"
                                {if $menu.nofollow} rel="nofollow"{/if}
                                {if $menu.new_window} target="_blank"{/if}
                        >
                            {$menu.text}
                        </a>
                        {if (isset($data[$menu.el_id]) || isset($menu_children)) && $menu.level == 0  && $position != 'left'}<span class="navbar-nav__drop dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="fa fa-angle-down"></span></span>{/if}
                        {if isset($data[$menu.el_id])}
                            {if in_array($position, ['inventory', 'right', 'copyright'])}
                                {menu data=$data pid=$menu.el_id class='dropdown-menu pull-right'}
                            {else}
                                {menu data=$data pid=$menu.el_id class='dropdown-menu'}
                            {/if}
                        {/if}
                    </li>
                {/if}

                {if 'mainmenu' == $position && $menu@iteration > $core.config.max_top_menu_items|default:5 && $menu.level < 1}{/capture}{/if}

            {/foreach}

            <!-- MORE menu dropdown -->
            {if isset($dropdown) && $menu.level < 1}
                <li class="dropdown dropdown-more">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">{lang key='more'}</a>
                    <span class="navbar-nav__drop dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false"><span class="fa fa-angle-down"></span></span>

                    <ul class="dropdown-menu">
                        {foreach $dropdown as $menu}
                            {$menu}
                        {/foreach}
                    </ul>
                </li>
            {/if}
        </ul>
    {/if}
{/function}

{menu data=$menus class=$menu_class}