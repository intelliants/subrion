{if $favorites}
    <ul class="nav nav-tabs m-b">
        <li><span>{lang key='sort_by'}</span></li>
        {foreach $favorites as $itemName => $data}
            {if $data.items}
                <li{if $data@first} class="active"{/if}><a href="#tab-{$itemName}" data-toggle="tab"><span>{lang key=$itemName}</span></a></li>
            {/if}
        {/foreach}
    </ul>

    <div class="tab-content">
        {foreach $favorites as $itemName => $data}
            {if $data.items}
                <div id="tab-{$itemName}" class="tab-pane{if $data@first} active{/if}">
                    {if !$data.package}
                        {include "search.{$itemName}.tpl" listings=$data.items fields=$data.fields}
                    {else}
                        {include "extra:{$data.package}/search.{$itemName}" listings=$data.items fields=$data.fields}
                    {/if}
                </div>
            {/if}
        {/foreach}
    </div>
{else}
    <div class="alert alert-info">{lang key='no_favorites'}</div>
{/if}