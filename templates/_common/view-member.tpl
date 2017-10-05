<div class="ia-item ia-item--view">
    <div class="ia-item__image">
        {ia_image file=$item.avatar type='thumbnail' title=$item.fullname|default:$item.username gravatar=true email=$item.email gravatar_width=200}

        {if $item.featured || $item.sponsored}
            <div class="ia-item__labels">
                {if $item.sponsored}<span class="label label-warning" title="{lang key='sponsored'}"><span class="fa fa-star"></span> {lang key='sponsored'}</span>{/if}
                {if $item.featured}<span class="label label-info" title="{lang key='featured'}"><span class="fa fa-star-o"></span> {lang key='featured'}</span>{/if}
            </div>
        {/if}
    </div>
    <div class="ia-item__content">
        <div class="ia-item__content__info">
            <span class="fa fa-eye"></span> {$item.views_num}
        </div>
        <div class="ia-item__additional">
            {if !empty($item.facebook)}
                <p><a class="ia-item__additional__icon" href="{$item.facebook|escape:'url'}"><span class="fa fa-facebook"></span></a></p>
            {/if}
            {if !empty($item.twitter)}
                <p><a class="ia-item__additional__icon" href="{$item.twitter|escape:'url'}"><span class="fa fa-twitter"></span></a></p>
            {/if}
            {if !empty($item.gplus)}
                <p><a class="ia-item__additional__icon" href="{$item.gplus|escape:'url'}"><span class="fa fa-google-plus"></span></a></p>
            {/if}
            {if !empty($item.linkedin)}
                <p><a class="ia-item__additional__icon" href="{$item.linkedin|escape:'url'}"><span class="fa fa-linkedin"></span></a></p>
            {/if}
        </div>

        <table class="table ia-item__table">
            <tbody>
                {if !empty($item.phone)}
                    <tr>
                        <td>{lang key='field_member_phone'}</td>
                        <td>{$item.phone}</td>
                    </tr>
                {/if}
                {if !empty($item.website)}
                    <tr>
                        <td>{lang key='field_member_website'}</td>
                        <td>{$item.website|linkify}</td>
                    </tr>
                {/if}
                {if !empty($item.biography)}
                    <tr>
                        <td>{lang key='field_member_biography'}</td>
                        <td>{$item.biography|escape}</td>
                    </tr>
                {/if}
                <tr>
                    <td>{lang key='member_since'}</td>
                    <td>{$item.date_reg|date_format:$core.config.date_format}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

{foreach $item.items as $itemName => $data}
    {if $data.items}
        {capture name=$itemName append='tabs_content'}
            {include $data.tpl listings=$data.items fields=$data.fields}
        {/capture}
    {/if}
{/foreach}

{include 'item-view-tabs.tpl' isView=true exceptions=['username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography']}

{if isset($groups['___empty___'])}
    {include 'field-type-content-fieldset.tpl' item_sections=$groups isView=true exceptions=['username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography']}
{/if}

{ia_hooker name='smartyViewListingBeforeFooter'}