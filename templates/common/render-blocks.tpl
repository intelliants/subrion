{if $movable && $manage}<div id="{$position}Blocks" class="groupWrapper">{/if}

{foreach $blocks as $block}
	{ia_block title=$block.title name=$block.name header=$block.header collapsible=$block.collapsible tpl=$block.tpl classname=$block.classname movable=$movable}
		{ia_block_view block=$block}
	{/ia_block}
{/foreach}

{if $movable && $manage}</div>{/if}