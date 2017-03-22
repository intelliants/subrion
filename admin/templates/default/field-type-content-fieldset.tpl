<div class="wrap-list">
    {foreach $item_sections as $key => $section}
        {if !empty($section.fields) && isset($section.name)}
            <div class="wrap-group" id="{$section.name}">
                <div class="wrap-group-heading">
                    {$section.title|escape}
                    {if $section.description}
                        <a href="#" class="js-tooltip" data-placement="right" title="{$section.description|escape}"><span class="fa fa-info-circle"></span></a>
                    {/if}
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

    {if !empty($isSystem)}
        {include 'fields-system.tpl'}
    {/if}
</div>
{ia_print_js files='jquery/plugins/jquery.numeric'}