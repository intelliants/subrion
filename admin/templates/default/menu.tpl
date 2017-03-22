{foreach $core.page.info.menu as $item}
    {if !empty($item.items)}
    <ul id="nav-sub-{$item.name}" class="nav-sub{if $core.page.info.group == $item.id} active{/if}">
        {foreach $item.items as $submenu}
            {if $submenu.name}
            <li class="{if $core.page.info.name == $submenu.name || $core.page.info.parent == $submenu.name
            || (isset($core.page.info.active_menu) && $core.page.info.active_menu == $submenu.name && !isset($submenu.config))}active{/if}{if isset($submenu.config) && isset($core.page.info.active_menu) && $submenu.config == $core.page.info.active_menu} active-setting{/if}">
                {if empty($submenu.url)}
                    <span>{$submenu.title}</span>
                {else}
                    <a href="{$submenu.url}"{if isset($submenu.attr)} {$submenu.attr}{/if}>{$submenu.title}</a>
                {/if}
                {if !empty($submenu.config)}
                    <a href="configuration/{$submenu.config}/" class="nav-sub__config{if isset($core.page.info.active_config) && $submenu.config == $core.page.info.active_config} active{/if}" title="{lang key='settings'}"><i class="i-cog"></i></a>
                {/if}
            </li>
            {else}
            <li class="heading">
                {$submenu.title}
                {if !empty($submenu.config)}
                    <a href="configuration/{$submenu.config}/" class="nav-sub__config{if isset($core.page.info.active_config) && $submenu.config == $core.page.info.active_config} active{/if}" title="{lang key='settings'}"><i class="i-cog"></i></a>
                {/if}
            </li>
            {/if}
        {/foreach}
    </ul>
    {/if}
{/foreach}