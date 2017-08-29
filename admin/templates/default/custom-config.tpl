{ia_add_js order=0}
    {foreach $core.customConfig as $key => $value}
        intelli.config.{$key} = '{$value}';
    {/foreach}
    intelli.config.admin_url = '{$smarty.const.IA_URL}{$core.config.admin_page}';
    intelli.securityToken = '{$securityToken}';
{/ia_add_js}