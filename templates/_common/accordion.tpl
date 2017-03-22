{assign accordionName $accordion_params.name}
<div id="{$accordionName}" class="accordion">{lang key='loading'}</div>
<input type="hidden" class="tree_name" value="{$accordionName}">
<input type="hidden" id="{$accordionName}_json_url" value="{$accordion_params.json_url}">
{ia_add_js order=1}
intelli.{$accordionName}_category =
{
    parents: {if isset($smarty.cookies.$accordionName.parents)}'{$smarty.cookies.$accordionName.parents}'{else}'0'{/if},
    selected: {if isset($smarty.cookies.$accordionName.id)}'{$smarty.cookies.$accordionName.id}'{else}'0'{/if}
};
{/ia_add_js}
{ia_add_media files='tree, js:intelli/intelli.accordion'}