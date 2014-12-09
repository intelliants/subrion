<h2 class="page-header">{lang key='blogroll'}</h2>

{if isset($block_blog_entries) && $block_blog_entries}
	{if $config.blog_display_grid}
	<div class="ia-items blogroll-grid">
		<div class="row-fluid">
		{foreach $block_blog_entries as $one_blog_entry}
			<div class="span6">
				<div class="ia-item-grid">
					{if $one_blog_entry.image}
						<a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{printImage imgfile=$one_blog_entry.image alt=$one_blog_entry.title}</a>
						<div class="info">
							<h3><a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{$one_blog_entry.title|strip_tags|truncate:25:'...'}</a></h3>
							<p class="date">{$one_blog_entry.date_added|date_format:$config.date_format}</p>
						</div>
					{else}
						<div class="info">
							<h3><a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{$one_blog_entry.title|strip_tags|truncate:25:'...'}</a></h3>
							<p class="date">{$one_blog_entry.date_added|date_format:$config.date_format}</p>
							<p Class="body">{$one_blog_entry.body|strip_tags|strip_tags|truncate:400:'...'}</p>
						</div>
					{/if}
				</div>
			</div>

			{if $one_blog_entry@iteration % 2 == 0}
				</div>
				<div class="row-fluid">
			{/if}
		{/foreach}
		</div>
		<div class="panel panel--clean">
			<a href="{$smarty.const.IA_URL}blog/" class="btn btn-primary">{lang key='view_all_blog_entries'}</a>
		</div>
	</div>
	{else}
		<div class="ia-items blogroll">
			{foreach $block_blog_entries as $one_blog_entry}
				<div class="media ia-item">
					{if $one_blog_entry.image}
						<a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}" class="pull-left ia-item-thumbnail">{printImage imgfile=$one_blog_entry.image width='150' class='media-object' title=$one_blog_entry.title}</a>
					{/if}

					<div class="media-body">
						<h4 class="media-heading">
							<a href="{$smarty.const.IA_URL}blog/{$one_blog_entry.id}-{$one_blog_entry.alias}">{$one_blog_entry.title}</a>
						</h4>
						<p class="ia-item-date">{$one_blog_entry.date_added|date_format:$config.date_format}</p>
						<div class="ia-item-body">{$one_blog_entry.body|strip_tags|truncate:$config.blog_max_block:'...'}</div>
					</div>
				</div>
			{/foreach}
		</div>
		<div class="panel panel--clean">
			<a href="{$smarty.const.IA_URL}blog/" class="btn btn-primary">{lang key='view_all_blog_entries'}</a>
		</div>
	{/if}
{else}
	<div class="alert alert-info">{lang key='no_blog_entries'}</div>
{/if}