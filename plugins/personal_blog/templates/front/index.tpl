{if isset($blog_entry)}
	<div class="media ia-item blog-entry">
		<p class="ia-item-date">{lang key='posted_on'} {$blog_entry.date_added|date_format:$config.date_format}</p>

		{if $blog_entry.image}
			<div class="ia-item-image">{printImage imgfile=$blog_entry.image fullimage=true title=$blog_entry.title}</div>
		{/if}

		<div class="ia-item-body">{$blog_entry.body}</div>

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
	</div>
{else}
	{if $blog_entries}
		<div class="ia-items blogroll">
			{foreach $blog_entries as $blog_entry}
			<div class="media ia-item">
				{if $blog_entry.image}
					<a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}" class="pull-left ia-item-thumbnail">{printImage imgfile=$blog_entry.image width='150' title=$blog_entry.title class='media-object'}</a>
				{/if}
				<div class="media-body">
					<h4 class="media-heading">
						<a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}">{$blog_entry.title}</a>
					</h4>
					<p class="ia-item-date">{lang key='posted_on'} {$blog_entry.date_added|date_format:$config.date_format}</p>
					<div class="ia-item-body">{$blog_entry.body|strip_tags|truncate:$config.blog_max:'...'}</div>
				</div>
			</div>
			{/foreach}
		</div>

		{navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$config.blog_number aNumPageItems=5}
	{else}
		<div class="alert alert-info">{lang key='no_blog_entries'}</div>
	{/if}
{/if}