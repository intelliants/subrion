{if isset($blogs_archive)}
    {if $blogs_archive}
        <div class="list-group">
            {foreach $blogs_archive as $item}
                {$month="month{$item.month}"}
                <a class="list-group-item{if (isset($curr_year) && isset($curr_month)) && ($curr_year == $item.year && $curr_month == $item.month)} active{/if}"
                   href="{$item.url}">{lang key=$month} {$item.year}</a>
            {/foreach}
        </div>
    {else}
        <div class="alert alert-info">{lang key='no_blog_entries'}</div>
    {/if}
{/if}