{if !empty($block_blog_entries)}
    <div class="blogroll">
        <div class="row">
            {foreach $block_blog_entries as $one_blog_entry}
                <div class="col-md-3">
                    <div class="blog-item">
                        <div class="blog-item__date">{$one_blog_entry.date_added|date_format:$core.config.date_format}</div>
                        <h4 class="blog-item__title"><a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{$one_blog_entry.title|escape}</a></h4>

                        <div class="blog-item__intro">
                            {$one_blog_entry.body|strip_tags|truncate:$core.config.blog_max_block:'...'}
                        </div>
                        <div class="blog-item__author">{$one_blog_entry.fullname|escape}</div>
                    </div>
                </div>

                {if $one_blog_entry@iteration % 4 == 0 && !$one_blog_entry@last}
                    </div>
                    <div class="row">
                {/if}
            {/foreach}
        </div>
    </div>
    <p class="m-t text-center"><a href="{$smarty.const.IA_URL}blog/" class="btn btn-primary text-uppercase">{lang key='view_all_blog_entries'}</a></p>
{else}
    <div class="alert alert-info">{lang key='no_blog_entries'}</div>
{/if}