{function name=menu pid=0}
	{if isset($data.$pid)}
		<ul class="{$class}{if $pid} menu_{$pid}{/if}">

			{foreach $data.$pid as $menu}

				{if 'mainmenu' == $position && $menu@iteration > $config.max_top_menu_items|default:5 && $menu.level < 1}{capture append=dropdown name=$menu.page_name}{/if}
				
				<li class="m_{$menu.page_name}
				    {if isset($data[$menu.el_id]) || isset($menu_children)} dropdown{/if}
				    {if $menu.active} active{/if}
				    {if $menu.level >= 1 && (isset($data[$menu.el_id]) || isset($menu_children))} dropdown-submenu{/if}
				    {if $menu.level >= 0 && (isset($data[$menu.el_id]) || isset($menu_children)) && $position == 'left'} dropdown-submenu{/if}
				    ">

					<a href="{if $menu.url}{$menu.url}{else}{$smarty.const.IA_SELF}#{/if}"
							{if (isset($data[$menu.el_id]) || isset($menu_children)) && $menu.level == 0} class="dropdown-toggle" data-toggle="dropdown" data-target="#"{/if}
							{if $menu.nofollow} rel="nofollow"{/if}
							{if $menu.new_window} target="_blank"{/if}
					>
						{$menu.text}{if (isset($data[$menu.el_id]) || isset($menu_children)) && $menu.level == 0  && $position != 'left'} <b class="caret"></b>{/if}
					</a>
					{if isset($data[$menu.el_id])}
						{if in_array($position, array('inventory', 'right', 'copyright'))}
							{menu data=$data pid=$menu.el_id class='dropdown-menu pull-right'}
						{else}
							{menu data=$data pid=$menu.el_id class='dropdown-menu'}
						{/if}
					{/if}
				</li>

				{if 'mainmenu' == $position && $menu@iteration > $config.max_top_menu_items|default:5 && $menu.level < 1}{/capture}{/if}

			{/foreach}

			<!-- MORE menu dropdown -->
			{if isset($dropdown) && $menu.level < 1}
				<li class="dropdown dropdown-more">
					<a href="#" class="dropdown-toggle" data-toggle="dropdown">
						{lang key='more'}
						<b class="caret"></b>
					</a>
					
					<ul class="dropdown-menu pull-right" role="menu">
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