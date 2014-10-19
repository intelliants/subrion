{foreach $item_sections as $key => $section}
	{if !empty($section.fields) && isset($section.name) && !($section.name == 'plans' && $isView)}

		{if '___empty___' != $section.name}
			{assign grouptitle "fieldgroup_{$section.name}"}
		{else}
			{assign grouptitle 'other'}
		{/if}

		{capture name='field_text'}
			{if 'plans' != $section.name}
				{foreach $section.fields as $variable}
					{if !isset($exceptions) || !in_array($variable.name, $exceptions)}
						{include file="field-type-content-{(isset($isView)) ? 'view' : 'manage'}.tpl" wrappedValues=true}
					{/if}
				{/foreach}
			{else}
				{foreach $section.fields as $variable}
					<div class="field field-plan">
						<input type="radio" name="plan" value="{$variable.id}" id="plan_{$variable.id}"{if !$smarty.post && $variable@first} checked{elseif $smarty.post.plan == $variable.id} checked{/if}><label for="plan_{$variable.id}">{$variable.description}</label> - <b>${$variable.cost}</b>
					</div>
				{/foreach}

				{include file='gateways.tpl'}
			{/if}
		{/capture}

		{if trim($smarty.capture.field_text) != ''}
			<div class="fieldset {if isset($section.collapsed) && $section.collapsible} collapsible{if $section.collapsed} collapsed{/if}{/if}" id="{$grouptitle}">
				{if isset($fieldset_before[$section.name])}{$fieldset_before[$section.name]}{/if}

				{if isset($grouptitle)}
					<h3 class="title">{lang key=$grouptitle}</h3>
				{/if}

				<div class="fieldset-wrapper content">
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