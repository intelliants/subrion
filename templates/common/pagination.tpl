<div class="pagination">
	<ul>
		<li><span>{lang key='page'} {$_pagination.current_page} / {$_pagination.pages_count}</span></li>
		{if $_pagination.current_page > 1}
			<li><a href="{$_pagination.first_page}" title="{lang key='first'}">&#171;</a></li>
			<li><a href="{$_pagination.pages_range[$_pagination.current_page-1]}" title="{lang key='previous'}">&lt;</a></li>
		{/if}
		{foreach $_pagination.pages_range as $pageNumber => $url}
			{if $pageNumber != $_pagination.current_page}
				<li><a href="{$url}">{$pageNumber}</a></li>
			{else}
				<li class="active"><span>{$pageNumber}</span></li>
			{/if}
		{/foreach}
		{if $_pagination.current_page < $_pagination.pages_count}
			<li><a href="{$_pagination.pages_range[$_pagination.current_page+1]}" title="{lang key='next'}">&gt;</a></li>
			<li><a href="{$_pagination.last_page}" title="{lang key='last'}">&#187;</a></li>
		{/if}
	</ul>
</div>