{foreach $groups as $page_type => $list}
    {if $page_type == 'pages'}
        {assign var='collapsed' value=1}
    {else}
        {assign var='collapsed' value=0}
    {/if}

	{if $page_type == 'admin_pages'}
		<table>
			<tr class="object-all-admin_login">
				<td width="450">{$admin_login.title}</td>
				<td width="150">{html_radio_switcher value=$admin_login.access name='all--admin_login--read' on='YES' off='NO'}</td>
			</tr>
		</table>
		<div id="div-input-all--admin_login--read" style="display: none">
	{else}
		<div>
	{/if}

	{foreach $list as $key => $group}
		<fieldset class="{$page_type}-{$key} list" style="padding: 10px; width: 600px;">
		<legend>&nbsp;<strong>{if isset($titles.$key)}{$titles.$key}{else}{$titles[1]}{/if}
			[ <span class="hide_btn" rel="{$page_type}-{$key}" style="display:none;">{lang key='hide'}</span> 
			<span class="show_btn" rel="{$page_type}-{$key}">{lang key='show'}</span> ]
		</strong>&nbsp;</legend>
		<div class="{$page_type}-{$key}" style="display: none">
			{foreach $group as $object => $acts}
			<div class="p_hover{if $acts.modified} modified{/if}" id="p_cont-{$page_type}-{$object}">
			<table class="striped" width="100%" cellspacing="0" cellpadding="0">
				<tr>
					<td colspan="3">
						<div class="p_links">
                            <div class="set_all_to">&nbsp;&nbsp;{lang key='set_all_to'}:&nbsp;&nbsp;</div>
							<div class="allow">&nbsp;</div>
							<div class="disallow">&nbsp;</div>
							<div class="save">&nbsp;</div>
                            <div class="empty">&nbsp;</div>
							<div class="set_default">&nbsp;</div>
						</div>
						<div>
							<span class="p_title">
								<strong>{$acts.title}</strong>
							</span>
						</div>
					</td>
				</tr>
				<tr class="light object-{$page_type}-{$object}">
					<td colspan="3" class="p_info">
                        <div style="min-height: 22px;">
						{foreach $acts.info as $action => $info}
							&nbsp;<span class="{$info.classname}" id="{$page_type}--{$object}--{$action}">{$info.title}</span>&nbsp;
						{/foreach}
						</div>
					</td>
				</tr>
				{foreach $acts.list as $action => $objects}
				<tr class="light object-{$page_type}-{$object}" style="display: none;">
					<td width="30">&nbsp;</td>
					<td width="370">{$objects.title}</td>
					<td width="200">
						{html_radio_switcher value=$objects.access name=$page_type|cat:'--'|cat:$object|cat:'--'|cat:$action on='YES' off='NO'}
						<input type="hidden" value="{if $objects.default}1{else}0{/if}" id="default-{$page_type}--{$object}--{$action}" />
					</td>
				</tr>
				{/foreach}
			</table>
			<div class="p_collapse" rel="{$page_type}-{$object}">&nbsp;</div>
			</div>
			{/foreach}
		</div>
		</fieldset>	
	{/foreach}
	</div>
{/foreach}

{ia_print_js files='admin/permissions'}