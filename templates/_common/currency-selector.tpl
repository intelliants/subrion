{if $core.config.currency_switch && count($core.currencies) > 1}
    <ul class="nav-inventory pull-right" id="js-currencies-list">
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                {$core.currency.title|escape} <span class="caret"></span>
            </a>
            <ul class="dropdown-menu pull-right">
                {foreach $core.currencies as $code => $entry}
                    <li{if $core.currency.code == $code} class="active"{/if}><a href="#" title="{$entry.code}" data-code="{$entry.code}">{$entry.title|escape}</a></li>
                {/foreach}
            </ul>
        </li>
    </ul>
{/if}
