<ul class="pagination">
    <li><span>{lang key='page'} {$_pagination.current_page} / {$_pagination.pages_count}</span></li>
    {if $_pagination.current_page > 1}
        <li><a href="{$_pagination.first_page}" title="{lang key='first'}" rel="first" data-page="1"><span class="fa fa-angle-double-left"></span></a></li>
        <li><a href="{$_pagination.pages_range[$_pagination.current_page-1]}" title="{lang key='previous'}" rel="prev" data-page="{$_pagination.current_page-1}"><span class="fa fa-angle-left"></span></a></li>
    {/if}
    {foreach $_pagination.pages_range as $pageNumber => $url}
        {if $pageNumber != $_pagination.current_page}
            <li><a href="{$url}" data-page="{$pageNumber}">{$pageNumber}</a></li>
        {else}
            <li class="active"><span>{$pageNumber}</span></li>
        {/if}
    {/foreach}
    {if $_pagination.current_page < $_pagination.pages_count}
        <li><a href="{$_pagination.pages_range[$_pagination.current_page+1]}" rel="next" title="{lang key='next'}" data-page="{$_pagination.current_page+1}"><span class="fa fa-angle-right"></span></a></li>
        <li><a href="{$_pagination.last_page}" title="{lang key='last'}" rel="last" data-page="{$_pagination.pages_count}"><span class="fa fa-angle-double-right"></span></a></li>
    {/if}
</ul>
