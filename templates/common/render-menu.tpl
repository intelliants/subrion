{if 'mainmenu' == $position}
	{ia_menu class='nav nav-pills nav-mainmenu' menus=$menu.contents}
{elseif 'inventory' == $position}
	{ia_menu class='nav nav-pills nav-inventory' menus=$menu.contents loginout=true}
{elseif in_array($position, array('left', 'right', 'user1', 'user2', 'top'))}
	{if !empty($menu.contents[0])}
		{ia_block title=$menu.title movable=true id=$menu.id name=$menu.name collapsible=$menu.collapsible}
			{if 'account' == $menu.name && $member && $config.members_enabled}
				<!-- account panel display start -->
				<div class="account-panel">
					<div class="account-info">
						{if $member.avatar}
							{assign var='avatar' value=$member.avatar|unserialize}
							{if $avatar}
								{printImage imgfile=$avatar.path width=100 height=100 title=$member.fullname|default:$member.username class='img-circle'}
							{else}
								<img src="{$img}no-avatar.png" class="img-circle" alt="{$member.username}">
							{/if}
						{else}
							<img src="{$img}no-avatar.png" class="img-circle" alt="{$member.username}">
						{/if}

						{lang key='welcome'}, {$member.fullname|default:$member.username}
						{access object='admin_login'}
							<a rel="nofollow" href="{$smarty.const.IA_ADMIN_URL}" target="_blank" title="{lang key='admin_panel'}"><i class="icon-cog"></i></a>
						{/access}
					</div>
					{ia_hooker name='smartyFrontInsideAccountBox'}
				</div>
				<!-- account panel display end -->
				{ia_menu menus=$menu.contents class='nav nav-pills nav-stacked' loginout=true}
			{elseif 'account' != $menu.name}
				{ia_menu menus=$menu.contents class='nav nav-pills nav-stacked'}
			{/if}
		{/ia_block}
	{/if}
{elseif in_array($position, array('bottom', 'copyright'))}
	{ia_menu menus=$menu.contents class='nav nav-pills pull-right'}
{else}
	<!--__ms_{$menu.id}-->
	{if $menu.header || isset($manageMode)}
		<div class="menu_header">{$menu.title}</div>
	{else}
		<div class="menu">
	{/if}

	<!--__ms_c_{$menu.id}-->
	{ia_menu menus=$menu.contents class='span'}
	<!--__me_c_{$menu.id}-->

	{if $menu.header || isset($manageMode)}
	{else}
		</div>
	{/if}
	<!--__me_{$menu.id}-->
{/if}