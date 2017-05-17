{$search = !isset($nosearch)}
<div class="form-group">
    <input type="text" id="js-category-label" value="{if isset($category.title)}{$category.title|escape}{else}{lang key='select_category_from_list'}{/if}" disabled class="form-control">
    <a href="#" class="categories-toggle" id="js-tree-toggler">{lang key='open_close'}</a>
    {if $search}
        <div id="js-tree-search"{if iaCore::ACTION_EDIT == $pageAction} style="display:none"{/if}>
            <input class="form-control" type="text" placeholder="{lang key='start_typing_to_filter'}">
        </div>
    {/if}
    <div id="js-tree" class="tree categories-tree">{lang key='loading'}</div>
    <input type="hidden" name="tree_id" id="input-tree" value="{$item.category_id}">
    {ia_add_js}
$(function()
{
    new IntelliTree({ url: '{$url}', nodeOpened: [{$category._parents}], nodeSelected: '{$item.category_id}', search: {if $search}true{else}false{/if} });
});
    {/ia_add_js}
    {ia_add_media files='tree'}
</div>