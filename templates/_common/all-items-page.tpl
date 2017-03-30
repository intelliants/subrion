<div class="ia-items">
    {foreach $all_items as $item}
        <div class="media ia-item ia-item-bordered">
            {if !empty($item.title)}
                {ia_url item=$all_item_type data=$item type='link' text=$item.title}
            {/if}

            {foreach $all_item_fields as $field}
                {if 'plan_id' != $field.name}
                    {include 'field-type-content-view.tpl' wrappedValues=true}
                {/if}
            {/foreach}

            <div class="ia-item-panel">
                {ia_url item=$all_item_type data=$item type='icon' classname='btn-info'}

                {if $member}
                    {printFavorites item=$item itemtype=$all_item_type classname='btn-info'}
                    {accountActions item=$item itemtype=$all_item_type classname='btn-info'}
                {/if}
            </div>
        </div>
    {/foreach}
</div>