{if $regular}
	<form class="ia-form">
		<div class="input-group">
			<input type="text" class="form-control" name="q" id="input-search-query" placeholder="{lang key='search_for'}" value="{$query|escape:'html'}">
			<span class="input-group-btn">
				<button class="btn btn-primary" type="submit">{lang key='search'}</button>
			</span>
		</div>
	</form>
{else}
	<div class="js-search-sorting-header">
		{ia_hooker name="smartyFrontSearchSorting{$itemName|ucfirst}"}
	</div>
{/if}

<div id="js-search-results-container">
	{if $results}
		{if $regular}
			{foreach $results as $item => $data}
				{if $data[0]}
					<div class="search-results">
						<h3 class="title">{lang key=$item}</h3>
						{$data[1]}
					</div>
				{/if}
			{/foreach}
		{else}
			{$results[1]}
		{/if}
	{elseif !$regular}
		<div class="message alert">{lang key='nothing_found'}</div>
	{/if}
</div>

<div id="js-search-results-pagination">
	{navigation aTotal=$pagination.total aTemplate=$pagination.url aItemsPerPage=$pagination.limit aNumPageItems=5}
</div>

{ia_print_js files='frontend/search'}