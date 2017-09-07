{if $favorites}
    <ul class="nav nav-tabs m-b">
        <li><span>{lang key='sort_by'}</span></li>
        {foreach $favorites as $itemName => $data}
            <li{if $data@first} class="active"{/if}><a href="#tab-{$itemName}" data-toggle="tab"><span>{lang key=$itemName}</span></a></li>
        {/foreach}
    </ul>

    <div class="tab-content">
        {foreach $favorites as $itemName => $data}
            <div id="tab-{$itemName}" class="tab-pane{if $data@first} active{/if}">
                {include $data.tpl listings=$data.items fields=$data.fields}
            </div>
        {/foreach}
    </div>
{else}
    <div class="alert alert-info">{lang key='no_favorites'}</div>
{/if}