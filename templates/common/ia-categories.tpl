{if isset($categories) && $categories}
	{assign var='num_columns' value=((isset($num_columns)) ? $num_columns : 2)}
	{assign class_names ['span12', 'span6', 'span4', 'span3']}

	<div class="row-fluid cats">
		{foreach $categories as $category}
			<div class="{$class_names[$num_columns - 1]}">
				<div class="cat-wrap">
					{if isset($icons) && $icons}
						{if isset($category.icon) && $category.icon}
							<img src="{$nonProtocolUrl}uploads/{$category.icon.path}" alt="{$category.icon.title}">
						{else}
							<i class="icon-folder-open"></i>
						{/if}
					{/if}

					{if isset($category.crossed) && $category.crossed}@&nbsp;{/if}<a href="{ia_url type='url' item=$item data=$category}">{$category.title|escape:'html'}</a>
					{if isset($show_amount) && $show_amount}
						&mdash; {$category.num|default:0}
					{/if}

					{if isset($category.subcategories) && $category.subcategories}
						<div class="subcat-wrap">
							{foreach $category.subcategories as $subcategory}
								<a href="{ia_url type='url' item=$item data=$subcategory}">{$subcategory.title}</a>{if !$subcategory@last}, {/if}
							{/foreach}
						</div>
					{/if}
				</div>
			</div>

			{if $category@iteration % $num_columns == 0}
				</div>
				<div class="row-fluid cats">
			{/if}
		{/foreach}
	</div>
{/if}