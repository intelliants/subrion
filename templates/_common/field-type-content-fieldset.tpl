{foreach $item_sections as $key => $section}
    {if !empty($section.fields) && isset($section.name) && !($section.name == 'plans' && $isView)}
        {capture name='field_text'}
            {if 'plans' != $section.name}
                {foreach $section.fields as $field}
                    {if (!isset($exceptions) || !in_array($field.name, $exceptions))}
                        {include "field-type-content-{(isset($isView)) ? 'view' : 'manage'}.tpl" wrappedValues=true}
                    {/if}
                {/foreach}
            {else}
                {foreach $section.fields as $field}
                    <div class="field field-plan">
                        <input type="radio" name="plan" value="{$field.id}" id="plan_{$field.id}"{if !$smarty.post && $field@first} checked{elseif $smarty.post.plan == $field.id} checked{/if}><label for="plan_{$field.id}">{$field.description}</label> - <b>${$field.cost}</b>
                    </div>
                {/foreach}

                {include 'gateways.tpl'}
            {/if}
        {/capture}

        {if trim($smarty.capture.field_text) != ''}
            <div class="fieldset {if isset($section.collapsible) && $section.collapsible} is-collapsible{if $section.collapsed} is-collapsed{/if}{/if}" id="fieldgroup_{$section.name}">
                {if isset($fieldset_before[$section.name])}{$fieldset_before[$section.name]}{/if}
                <div class="fieldset__header">{$section.title|escape}</div>
                <div class="fieldset__content">
                    {if $section.description}
                        <p class="help-block fields-description">{$section.description|escape}</p>
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