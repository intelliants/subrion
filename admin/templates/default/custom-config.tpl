{ia_add_js order=0}
    intelli.pageName = '{$core.page.name}';
    {foreach $core.customConfig as $key => $value}
        intelli.config.{$key} = '{$value}';
    {/foreach}
    intelli.config.url = '{$smarty.const.IA_URL}';
    intelli.config.admin_url = '{$smarty.const.IA_URL}{$core.config.admin_page}';
    intelli.securityToken = '{$securityToken}';
{/ia_add_js}