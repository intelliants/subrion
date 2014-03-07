<div class="alpha-sorting">
	{foreach $letters.all as $letter}
		{if $letter == $letters.active || !in_array($letter, $letters.existing)}
			<span class="btn btn-mini{if $letter == $letters.active} btn-success{/if} disabled">{$letter}</span>
		{else}
			<a href="{$url}{$letter}/" class="btn btn-mini">{$letter}</a>
		{/if}
	{/foreach}
	<a href="{$url}" class="btn btn-mini btn-warning" title="{lang key='reset'}"><i class="icon-remove"></i></a>
</div>