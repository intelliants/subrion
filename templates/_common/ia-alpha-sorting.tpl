<div class="alpha-sorting">
    {foreach $letters.all as $letter}
        {if $letter == $letters.active || !in_array($letter, $letters.existing)}
            <a class="btn btn-sm {if $letter == $letters.active} btn-success{else} btn-default{/if} disabled">{$letter}</a>
        {else}
            <a href="{$url}{$letter}/" class="btn btn-sm btn-default">{$letter}</a>
        {/if}
    {/foreach}
    <a href="{$url}" class="btn btn-sm btn-warning" title="{lang key='reset'}"><span class="fa fa-remove"></span></a>
</div>