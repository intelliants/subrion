{foreach $menu as $item}
	{if isset($item.items) && $item.items}
	<ul id="nav-sub-{$item.name}" class="nav-sub{if $page.group == $item.id} active{/if}">
		{foreach $item.items as $submenu}
			{if $submenu.name}
			<li class="{if $page.name == $submenu.name || $page.parent == $submenu.name || (isset($page.active_menu) && $page.active_menu == $submenu.name) && !isset($submenu.config)}active{/if}{if isset($submenu.config) && isset($page.active_menu) && $submenu.config == $page.active_menu} active-setting{/if}">
				{if empty($submenu.url)}
					<span>{$submenu.title}</span>
				{else}
					<a href="{$submenu.url}"{if isset($submenu.attr)} {$submenu.attr}{/if}>{$submenu.title}</a>
				{/if}
				{if isset($submenu.config) && $submenu.config}
					<a href="{$smarty.const.IA_ADMIN_URL}configuration/{$submenu.config}/" class="nav-sub__config{if isset($page.active_menu) && $submenu.config == $page.active_menu} active{/if}" title="{lang key='settings'}"><i class="i-cog"></i></a>
				{/if}
			</li>
			{else}
			<li class="heading">
				{$submenu.title}
				{if isset($submenu.config) && $submenu.config}
					<a href="{$smarty.const.IA_ADMIN_URL}configuration/{$submenu.config}/" class="nav-sub__config{if isset($page.active_menu) && $submenu.config == $page.active_menu} active{/if}" title="{lang key='settings'}"><i class="i-cog"></i></a>
				{/if}
			</li>
			{/if}
		{/foreach}
	</ul>
	{/if}
{/foreach}