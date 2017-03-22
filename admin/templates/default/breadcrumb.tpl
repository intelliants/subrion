<ul class="breadcrumb">
    {if 'index' == $core.page.name}
        <li class="active">{lang key='welcome_to_admin_board'}</li>
    {else}
        {foreach $core.page.breadcrumb as $item}
            {if $item.url && !$item@last}
                <li><a href="{$item.url}">{$item.caption}</a></li>
            {else}
                <li class="active">{$item.caption}</li>
            {/if}
        {/foreach}
    {/if}
</ul>