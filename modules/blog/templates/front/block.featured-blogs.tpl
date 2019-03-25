{if isset($blog_blocks_data.featured)}
    <div class="new-blog-posts">
        {foreach $blog_blocks_data.featured as $one_blog_entry}
            <div class="media">
                {if !empty($one_blog_entry.image)}
                    <a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}"
                       class="media-object pull-left">{ia_image file=$one_blog_entry.image width=60 title=$one_blog_entry.title}</a>
                {/if}
                <div class="media-body">
                    <h5 class="media-heading">
                        <a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{$one_blog_entry.title|escape}</a>
                    </h5>
                    <p class="text-fade-50">{$one_blog_entry.date_added|date_format} {lang key='by'} {$one_blog_entry.fullname|escape}</p>
                    <p>{$one_blog_entry.body|strip_tags|truncate:50:'...'}</p>
                </div>
            </div>
            {if $one_blog_entry@iteration == $core.config.blog_number_new_block}
                {break}
            {/if}
        {/foreach}
    </div>
    <p>
        <a href="{$smarty.const.IA_URL}blog/">{lang key='view_all_blog_entries'} &rarr;</a>
    </p>
{else}
    <div class="alert alert-info">{lang key='no_featured_blog_entries'}</div>
{/if}

