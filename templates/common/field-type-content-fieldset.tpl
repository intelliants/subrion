{foreach $item_sections as $key => $section}
	{if !empty($section.fields) && isset($section.name) && !($section.name == 'plans' && $isView)}

		{if '___empty___' != $section.name}
			{$grouptitle = "fieldgroup_{$section.name}"}
		{else}
			{$grouptitle = 'other'}
		{/if}

		{capture name='field_text'}
			{if 'plans' != $section.name}
				{*--
					Checking for fields relations
					TODO: to be rewritten along with the iaField method
				 --*}
				{$relations = []}
				{if isset($isView)}
					{foreach $section.fields as $field}
						{if iaField::RELATION_PARENT == $field.relation && $field.children}
							{foreach $field.children as $dependentField => $relation}
								{$relations[$dependentField] = array($field.name, $relation[0])}
							{/foreach}
						{/if}
					{/foreach}
				{/if}
				{*-- END --*}
				{foreach $section.fields as $field}
					{if (!isset($exceptions) || !in_array($field.name, $exceptions))
						&& (!isset($relations[$field.name])
							|| (isset($relations[$field.name]) && $relations[$field.name][1] == $item[$relations[$field.name][0]]))}
						{include file="field-type-content-{(isset($isView)) ? 'view' : 'manage'}.tpl" wrappedValues=true}
					{/if}
				{/foreach}
			{else}
				{foreach $section.fields as $field}
					<div class="field field-plan">
						<input type="radio" name="plan" value="{$field.id}" id="plan_{$field.id}"{if !$smarty.post && $field@first} checked{elseif $smarty.post.plan == $field.id} checked{/if}><label for="plan_{$field.id}">{$field.description}</label> - <b>${$field.cost}</b>
					</div>
				{/foreach}

				{include file='gateways.tpl'}
			{/if}
		{/capture}

		{if trim($smarty.capture.field_text) != ''}
			<div class="fieldset {if isset($section.collapsible) && $section.collapsible} is-collapsible{if $section.collapsed} is-collapsed{/if}{/if}" id="{$grouptitle}">
				{if isset($fieldset_before[$section.name])}{$fieldset_before[$section.name]}{/if}

				{if isset($grouptitle)}
					<div class="fieldset__header">{lang key=$grouptitle}</div>
				{/if}

				<div class="fieldset__content">
					{if isset($section.description) && $section.description}
						<p class="help-block fields-description">{$section.description}</p>
						<hr>
					{/if}

					{if isset($fieldset_content_before[$section.name])}{$fieldset_content_before[$section.name]}{/if}

					{$smarty.capture.field_text}

					{if isset($fieldset_content_after[$section.name])}{$fieldset_content_after[$section.name]}{/if}
				</div>

				{if isset($fieldset_after[$section.name])}{$fieldset_after[$section.name]}{/if}
			</div>
		{/if}
	{/if}
{/foreach}