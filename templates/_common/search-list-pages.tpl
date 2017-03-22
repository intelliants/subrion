<div class="ia-items">
    {foreach $pages as $pageName => $entry}
        <div class="media ia-item ia-item-bordered">
            <a href="{$entry.url}">{$entry.title}</a>
            {if isset($entry.content)}<p>{$entry.content}</p>{/if}
            {if isset($entry.extraItems)}
                {foreach $entry.extraItems as $extraEntry}
                    <p>
                    {if $extraEntry.title}<em>{$extraEntry.title}</em> // {/if}
                    <span>{$extraEntry.content}</span></p>
                {/foreach}
            {/if}
        </div>
    {/foreach}
</div>