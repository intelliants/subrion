{if isset($blog_entries)}
    {foreach $blog_entries as $blog_entry}
        <div class="ia-item">
            {if $blog_entry.image}
                <a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}"
                   class="ia-item__image">{ia_image file=$blog_entry.image title=$blog_entry.title}</a>
            {/if}
            <div class="ia-item__content">
                <h4 class="ia-item__title">
                    <a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}">{$blog_entry.title|escape}</a>
                </h4>
                <div class="ia-item__additional">
                    <p>{lang key='posted_on'} {$blog_entry.date_added|date_format:$core.config.date_format} {lang key='by'} {$blog_entry.fullname|escape}</p>
                </div>
                <div class="ia-item__body">{$blog_entry.body|strip_tags|truncate:$core.config.blog_max:'...'}</div>
            </div>
        </div>
    {/foreach}
    {navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$core.config.blog_number aNumPageItems=5}
{else}
    {if $blog_tags}
        {foreach $blog_tags as $tag}
            {if $tag != ''}
                <div class="media ia-item">
                    <div class="media-body">
                        <h4 class="media-heading">
                            <a href="{$smarty.const.IA_URL}tag/{$tag.alias}">#{$tag.title|escape}</a>
                        </h4>
                    </div>
                </div>
            {/if}
        {/foreach}

        {navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$core.config.blog_tag_number aNumPageItems=5}
    {else}
        <div class="alert alert-info">{lang key='no_tags'}</div>
    {/if}
{/if}