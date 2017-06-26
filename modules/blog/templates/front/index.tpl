{if isset($blog_entry)}
    <p class="text-i text-fade-50">{lang key='posted_on'} {$blog_entry.date_added|date_format} {lang key='by'} {$blog_entry.fullname}</p>
    {if $blog_entry.image}
        {ia_image file=$blog_entry.image type='large' title=$blog_entry.title class='img-responsive m-b'}
    {/if}

    {$blog_entry.body}
    <div class="tags">
        <span class="fa fa-tags"></span>
        {if $blog_tags}
            {lang key='tags'}:
            {foreach $blog_tags as $tag}
                <a href="{$smarty.const.IA_URL}tag/{$tag.alias}">{$tag.title|escape}</a>{if !$tag@last}, {/if}
            {/foreach}
        {else}
            {lang key='no_tags'}
        {/if}
    </div>
    <hr>
    <!-- AddThis Button BEGIN -->
    <div class="addthis_toolbox addthis_default_style">
        <a class="addthis_button_facebook_like" fb:like:layout="button_count"></a>
        <a class="addthis_button_tweet"></a>
        <a class="addthis_button_pinterest_pinit"></a>
        <a class="addthis_button_google_plusone" g:plusone:size="medium"></a>
        <a class="addthis_counter addthis_pill_style"></a>
    </div>
    <script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js#pubid=xa-5170da8b1f667e6d"></script>
    <!-- AddThis Button END -->
{else}
    {if $blog_entries}
        <div class="ia-items blogroll">
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
                            <p><span class="fa fa-tags"></span>
                                {if $blog_tags}
                                    {$tagsExist=0}
                                    {foreach $blog_tags as $tag}
                                        {if $blog_entry.id == $tag.blog_id}
                                            {$tagsExist = $tagsExist + 1}
                                        {/if}
                                    {/foreach}
                                    {if $tagsExist != 0}
                                        {foreach $blog_tags as $tag}
                                            {if $blog_entry.id == $tag.blog_id}
                                                <a href="{$smarty.const.IA_URL}tag/{$tag.alias}">{$tag.title|escape}</a>
                                            {/if}
                                        {/foreach}
                                    {else}
                                        {lang key='no_tags'}
                                    {/if}

                                {else}
                                    {lang key='no_tags'}
                                {/if}
                            </p>
                            <p>{lang key='posted_on'} {$blog_entry.date_added|date_format} {lang key='by'} {$blog_entry.fullname|escape}</p>
                        </div>
                        <div class="ia-item__body">{$blog_entry.body|strip_tags|truncate:$core.config.blog_max:'...'}</div>
                    </div>
                </div>
            {/foreach}
        </div>
        {navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$core.config.blog_number aNumPageItems=5}
    {else}
        <div class="alert alert-info">{lang key='no_blog_entries'}</div>
    {/if}
{/if}