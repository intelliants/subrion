<!--__b_{$id}-->
{if $header}
    <div id="block_{$name}" class="box {$classname}{if !empty($collapsible)} collapsible{if !empty($collapsed)} collapsed{/if}{/if}"{if isset($manageMode)} vm-hidden="{$hidden}"{/if}>
        <h4 id="caption_{$name}" class="box__caption">{$title|escape}
            {if !empty($icons)}
                <span class="box__actions">
                    {foreach $icons as $icon}
                        <a href="{$icon.url}" {$icon.attributes} id="{$icon.name}_{$name}">{$icon.text}</a>
                    {/foreach}
                </span>
            {/if}
        </h4>
        <div id="content_{$name}" class="box__content"{if isset($display) && !$display} style="display: none;"{/if}>
{else}
    <div id="block_{$name}" class="box box--no-header {$classname}"{if isset($manageMode)} vm-hidden="{$hidden}"{/if}>
{/if}

<!--__b_c_{$id}-->
{$_block_content_}
<!--__e_c_{$id}-->

{if $header}
        </div>
    </div>
{else}
    </div>
{/if}
<!--__e_{$id}-->