{ia_add_media files='jstree, js:intelli/intelli.accordion'}
<div id="{$accordion_params.name}" class="accordion">{lang key='loading'}</div>

{assign accordion_name $accordion_params.name}

<input type="hidden" class="tree_name" value="{$accordion_name}">
<input type="hidden" id="{$accordion_name}_json_url" value="{$accordion_params.json_url}">
{ia_add_js order=1}
	intelli.{$accordion_name}_category =
	{
		parents: {if isset($smarty.cookies.$accordion_name.parents)}'{$smarty.cookies.$accordion_name.parents}'{else}'0'{/if},
		selected: {if isset($smarty.cookies.$accordion_name.id)}'{$smarty.cookies.$accordion_name.id}'{else}'0'{/if}
	};
{/ia_add_js}