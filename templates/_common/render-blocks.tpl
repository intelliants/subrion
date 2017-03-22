{if isset($iaPositions) && !$iaPositions[$position]['menu']}<div id="{$position}Blocks" class="groupWrapper{if $iaPositions[$position]['movable']} groupWrapper--movable{/if}{if $iaPositions[$position]['hidden']} groupWrapper--hidden{/if}">{/if}

{foreach $blocks as $block}
    {ia_block title=$block.title name=$block.name header=$block.header collapsible=$block.collapsible collapsed=$block.collapsed tpl=$block.tpl classname=$block.classname hidden=$block.hidden}
        {ia_block_view block=$block}
    {/ia_block}
{/foreach}

{if isset($iaPositions) && !$iaPositions[$position]['menu']}</div>{/if}