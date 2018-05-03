<div class="ia-item">
    <div class="ia-item__image">
        {ia_image file=$listing.avatar type='thumbnail' title=$listing.fullname|default:$listing.username gravatar=true email=$listing.email gravatar_width=200}
    </div>
    <div class="ia-item__content">
        <div class="ia-item__actions">
            {printFavorites item=$listing guests=true}
            {accountActions item=$listing}
            <a href="{$listing.link}">{lang key='view_profile'} <span class="fa fa-angle-double-right"></span></a>
        </div>

        <h4 class="ia-item__title">
            <a href="{$listing.link}">{$listing.fullname|default:$listing.username}</a>
        </h4>

        <div class="ia-item__additional">
            {if !empty($listing.phone)}
                <p><span class="fa fa-phone"></span> {$listing.phone|escape}</p>
            {/if}
            {if !empty($listing.website)}
                <p><span class="fa fa-globe"></span> <a href="{$listing.website|escape}">{lang key='field_member_website'}</a></p>
            {/if}
            {if !empty($listing.facebook)}
                <p><span class="fa fa-facebook"></span> <a href="{$listing.facebook|escape}">{lang key='field_member_facebook'}</a></p>
            {/if}
            {if !empty($listing.twitter)}
                <p><span class="fa fa-twitter"></span> <a href="{$listing.twitter|escape}">{lang key='field_member_twitter'}</a></p>
            {/if}
            {if !empty($listing.gplus)}
                <p><span class="fa fa-google-plus"></span> <a href="{$listing.gplus|escape}">{lang key='field_member_gplus'}</a></p>
            {/if}
            {if !empty($listing.linkedin)}
                <p><span class="fa fa-linkedin"></span> <a href="{$listing.linkedin|escape}">{lang key='field_member_linkedin'}</a></p>
            {/if}
        </div>

        {if !empty($listing.biography)}
            <p>{$listing.biography|escape|truncate:250:'...':true}</p>
        {/if}

        {foreach $fields as $field}
            {if !in_array($field.name, ['username', 'avatar', 'fullname', 'phone', 'website', 'facebook', 'twitter', 'gplus', 'linkedin', 'biography']) && 'plan_id' != $field.name}
                {include 'field-type-content-view.tpl' wrappedValues=true item=$listing}
            {/if}
        {/foreach}
    </div>
</div>