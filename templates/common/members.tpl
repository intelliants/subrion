{if $members}
	{include file='accounts-items.tpl' all_items=$members all_item_fields=$fields all_item_type='members'}
	{navigation aTotal=$pagination.total aTemplate=$pagination.url aItemsPerPage=$pagination.limit aNumPageItems=5 aTruncateParam=1}
{else}
	<div class="alert alert-info">{lang key='no_members'}</div>
{/if}