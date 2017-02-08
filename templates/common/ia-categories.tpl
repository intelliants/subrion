{if isset($categories) && $categories}
	{$num_columns = ((isset($num_columns)) ? $num_columns : 2)}
	{$class_names = ['col-md-12', 'col-md-6', 'col-md-4', 'col-md-3']}

	<div class="row ia-cats">
		{foreach $categories as $category}
			<div class="{$class_names[$num_columns - 1]}">
				<div class="ia-cat">
					{if isset($icons) && $icons}
						<span class="fa{if !empty($category.icon)} {$category.icon}{else} fa-folder-open{/if}"></span>
					{/if}

					{if isset($category.crossed) && $category.crossed}@&nbsp;{/if}<a href="{ia_url type='url' item=$item data=$category}">{$category.title|escape:'html'}</a>
					{if isset($show_amount) && $show_amount}
						&mdash; {$category.num|default:0}
					{/if}

					{if isset($category.subcategories) && $category.subcategories}
						<div class="ia-cat__sub">
							{foreach $category.subcategories as $subcategory}
								<a href="{ia_url type='url' item=$item data=$subcategory}">{$subcategory.title|escape:'html'}</a>{if !$subcategory@last}, {/if}
							{/foreach}
						</div>
					{/if}
				</div>
			</div>

			{if $category@iteration % $num_columns == 0 && !$category@last}
				</div>
				<div class="row ia-cats">
			{/if}
		{/foreach}
	</div>
{/if}