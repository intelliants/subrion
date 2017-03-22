{if isset($menus.$pid)}
<div{if $level > 0} style="display:none"{/if} class="menus {$class} {$class}{$level+1}{if $pid != 0} menu_{$pid}{/if}">
    {$text_before}
    {foreach $menus.$pid as $menu}
        <span class="{if isset($menus[$menu.el_id]) || isset($chidlren)}element{/if}{if $menu.active} active{/if}" rel="menu_{$menu.el_id}">
        <a href="{if $menu.url}{$menu.url}{else}{$smarty.const.IA_SELF}#" class="nolink"{/if}" {if $menu.newwindow} target="_blank"{/if} {if isset($menu.nofollow)}{$menu.nofollow}{/if}><span>{$menu.text}</span></a>
        {if isset($menus[$menu.el_id])}
            {include 'menu-ul.tpl' menus=$menus pid=$menu.el_id level=$menu.level+1 class=$class text_before='' text_after=''}
        {/if}
        </span>
        {if !$smarty.foreach.menu_name.last} | {/if}
    {/foreach}
    {$text_after}
</div>
{/if}