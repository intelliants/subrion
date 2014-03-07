<input type="text" class="common text" id="{$accordion_params.name}_category-label" size="50" disabled="disabled">
<div id="{$accordion_params.name}" class="tree">{lang key='loading'}</div>

<input id="{$accordion_params.name}_current-tree-category" type="hidden" value="{$accordion_params.category.id}">
<input type="hidden" name="{$accordion_params.name}_category_id" id="{$accordion_params.name}_category_id" value="{$accordion_params.category.id}">
<input type="hidden" id="tree_name" value="{$accordion_params.name}">
<input type="hidden" id="json_url" value="{$accordion_params.json_url}">

{ia_add_js order=1}
intelli.categories = [{if $accordion_params.category.parents}{$accordion_params.category.parents},{/if}{$accordion_params.category.id}];
intelli.category = {ldelim}parent: {if !isset($accordion_params.category.parent_id)}{$accordion_params.category.id_parent}{else}{$accordion_params.category.parent_id}{/if}, selected: {$accordion_params.category.id}{rdelim};
{/ia_add_js}
{ia_add_media files='jstree, js:intelli/intelli.accordion'}