<div class="ia-items">
    {foreach $all_items as $oneitem}
        <div class="media ia-item ia-item-bordered">
            {assign item $oneitem}

            <div class="pull-left">
                {if $oneitem.avatar}
                    {ia_image file=$oneitem.avatar type='thumbnail' width=100 height=100 alt=$oneitem.fullname|default:$oneitem.username class='media-object'}
                {/if}
            </div>

            <div class="media-body">
                {foreach $all_item_fields as $onefield}
                    {if 'plan_id' != $onefield.name && 'avatar' != $onefield.name}
                        {include 'field-type-content-view.tpl' variable=$onefield wrappedValues=true}
                    {/if}
                {/foreach}
            </div>

            <div class="ia-item-panel">
                {ia_url item=$all_item_type data=$oneitem type='icon_text' icon='icon-user'}

                {if !empty($member.id)}
                    {printFavorites item=$oneitem itemtype=$all_item_type}
                    {accountActions item=$oneitem itemtype=$all_item_type}
                {/if}
            </div>
        </div>
    {/foreach}
</div>