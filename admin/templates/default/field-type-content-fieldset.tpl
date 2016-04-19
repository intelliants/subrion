<div class="wrap-list">
	{foreach $item_sections as $key => $section}
		{if !empty($section.fields) && isset($section.name)}
			{if '___empty___' != $key}
				{assign grouptitle "fieldgroup_{$section.name}"}
			{else}
				{assign grouptitle 'other'}
			{/if}

			<div class="wrap-group" id="{$section.name}">
				<div class="wrap-group-heading">
					<h4>{lang key=$grouptitle}
						{if isset($section.description) && $section.description}
							<a href="#" class="js-tooltip" data-placement="right" title="{$section.description}"><i class="i-info"></i></a>
						{/if}
					</h4>
				</div>

				{if isset($fieldset_before[$section.name])}{$fieldset_before[$section.name]}{/if}

				{foreach $section.fields as $field}
					{if !isset($exceptions) || !in_array($field.name, $exceptions)}
						{include 'field-type-content-manage.tpl'}
					{/if}
				{/foreach}

				{if isset($fieldset_after[$section.name])}{$fieldset_after[$section.name]}{/if}
			</div>
		{/if}
	{/foreach}

	{if isset($isSystem) && $isSystem}
		{include 'fields-system.tpl'}
	{/if}
</div>
{ia_print_js files='jquery/plugins/jquery.numeric'}