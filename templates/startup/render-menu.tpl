{if 'mainmenu' == $position}
	{ia_menu menus=$menu.contents class="nav nav-pills nav-mainmenu {$menu.classname}"}
{elseif 'inventory' == $position}
	{ia_menu menus=$menu.contents class="nav-inventory {$menu.classname}" loginout=true}
{elseif 'account' == $position}
	{if 'account' == $menu.name && $member && $config.members_enabled}
		<div class="nav-account">
			<a class="dropdown-toggle" data-toggle="dropdown" href="#">
				<span class="nav-account__avatar">
					{if $member.avatar}
						{assign var='avatar' value=$member.avatar|unserialize}
						{if $avatar}
							{printImage imgfile=$avatar.path width=30 height=30 title=$member.fullname|default:$member.username}
						{else}
							<img src="{$img}no-avatar.png" alt="{$member.username}">
						{/if}
					{else}
						<img src="{$img}no-avatar.png" alt="{$member.username}">
					{/if}
				</span>
				<span class="nav-account__name">
					{$member.fullname|default:$member.username}
				</span>
				<span class="caret"></span>
			</a>
			<ul class="nav-account__menu dropdown-menu">
				{access object='admin_access'}
					<li><a rel="nofollow" href="{$smarty.const.IA_ADMIN_URL}" target="_blank" title="{lang key='su_admin_dashboard'}"><i class="icon-cog"></i> {lang key='su_admin_dashboard'}</a></li>
					<li class="divider"></li>
				{/access}
				<li class="account-box">
					{ia_hooker name='smartyFrontInsideAccountBox'}
					{ia_menu menus=$menu.contents class='nav nav-pills nav-stacked' loginout=true}
				</li>
			</ul>
		</div>
	{else}
		<ul class="nav-account">
			<li><a href="{$smarty.const.IA_URL}login/">{lang key='su_login'}</a></li>
			<li><a class="btn-account" href="{$smarty.const.IA_URL}registration/">{lang key='su_signup'}</a></li>
		</ul>
	{/if}
{elseif in_array($position, array('left', 'right', 'user1', 'user2', 'top', 'footer1', 'footer2', 'footer3', 'footer4'))}
	{if !empty($menu.contents[0])}
		{ia_block title=$menu.title movable=true id=$menu.id name=$menu.name collapsible=$menu.collapsible classname=$menu.classname}
			{if 'account' == $menu.name && $member && $config.members_enabled}
				<div class="account-panel">
					<div class="account-info">
						{if $member.avatar}
							{assign avatar $member.avatar|unserialize}
							{if $avatar}
								{printImage imgfile=$avatar.path width=100 height=100 title=$member.fullname|default:$member.username class='img-circle'}
							{else}
								<img src="{$img}no-avatar.png" class="img-circle" alt="{$member.username|escape:'html'}">
							{/if}
						{else}
							<img src="{$img}no-avatar.png" class="img-circle" alt="{$member.username|escape:'html'}">
						{/if}

						{lang key='welcome'}, {$member.fullname|default:$member.username|escape:'html'}
						{access object='admin_access'}
							<a rel="nofollow" href="{$smarty.const.IA_ADMIN_URL}" target="_blank" title="{lang key='su_admin_dashboard'}"><i class="icon-cog"></i></a>
						{/access}
					</div>
					{ia_hooker name='smartyFrontInsideAccountBox'}
				</div>
				{ia_menu menus=$menu.contents class='nav nav-pills nav-stacked' loginout=true}
			{elseif 'account' != $menu.name}
				{ia_menu menus=$menu.contents class="nav nav-pills nav-stacked {$menu.classname}"}
			{/if}
		{/ia_block}
	{/if}
{elseif in_array($position, array('bottom', 'copyright'))}
	{ia_menu menus=$menu.contents class="nav nav-inline nav-footer {$menu.classname}"}
{else}
	<!--__ms_{$menu.id}-->
	{if $menu.header || isset($manageMode)}
		<div class="menu_header {$menu.classname}">{$menu.title}</div>
	{else}
		<div class="menu {$menu.classname}">
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