{if isset($tree)}
    {$search = !isset($nosearch)}
    <div class="form-group js-tree-control">
        <input type="text" value="{$tree.title|escape}" disabled class="form-control js-category-label">
        <a href="#" class="categories-toggle js-tree-toggler">{lang key='open_close'}</a>
        {if $search}
            <div id="js-tree-search"{if iaCore::ACTION_EDIT == $pageAction} style="display:none"{/if}>
                <input class="form-control" type="text" placeholder="{lang key='start_typing_to_filter'}">
            </div>
        {/if}
        <div id="{$tree.selector|default:'js-tree'}" class="tree categories-tree">{lang key='loading'}</div>
        <input type="hidden" name="{$tree.value|default:'tree_id'}" id="{$tree.value|default:'input-tree'}" value="{$tree.id}">
        {ia_add_js}
$(function() {
    new IntelliTree({
        url: '{$tree.url}',
        nodeOpened: [{$tree.nodes}],
        nodeSelected: '{$tree.id}',
        {if isset($tree.selector)}selector: '#{$tree.selector}',{/if}
        {if isset($tree.value)}value: '#{$tree.value}',{/if}
        search: {if $search}true{else}false{/if}
    });
});
        {/ia_add_js}
        {ia_add_media files='tree'}
    </div>
{/if}