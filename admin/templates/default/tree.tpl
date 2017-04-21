{if isset($tree)}
    {$search = !isset($nosearch)}
    <div class="row js-tree-control">
        <label class="col col-lg-2 control-label">
            {lang key=$tree.label|default:'category'} {lang key='field_required'}<br>
            <a href="#" class="categories-toggle js-tree-toggler">{lang key='open_close'}</a>
        </label>
        <div class="col col-lg-4">
            <input type="text" class="js-category-label" value="{$tree.title|escape}" disabled>
            {if $search}
                <div class="input-group tree-filter" id="js-tree-search"{if iaCore::ACTION_EDIT == $pageAction} style="display:none"{/if}>
                    <span class="input-group-addon"><i class="i-filter"></i></span>
                    <input type="text" placeholder="{lang key='start_typing_to_filter'}">
                </div>
            {/if}
            <div id="{$tree.selector|default:'js-tree'}" class="tree categories-tree"{if iaCore::ACTION_EDIT == $pageAction} style="display:none"{/if}></div>
            <input type="hidden" name="{$tree.value|default:'tree_id'}" id="{$tree.value|default:'input-tree'}" value="{$tree.id}">
            {ia_add_js}
$(function() {
    new IntelliTree({
        url: '{$tree.url}',
        onchange: intelli.fillUrlBox,
        nodeSelected: $('#{$tree.value|default:'input-tree'}').val(),
        nodeOpened: [{$tree.nodes}],
        {if isset($tree.selector)}selector: '#{$tree.selector}',{/if}
        {if isset($tree.value)}value: '#{$tree.value}',{/if}
        search: {if $search}true{else}false{/if}
    });
});
            {/ia_add_js}
            {ia_add_media files='tree'}
        </div>
    </div>
{/if}