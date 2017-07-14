{if $core.config.currency_switch && count($core.currencies) > 1}
    <ul class="nav navbar-nav navbar-right nav-langs" id="js-currencies-list">
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                {$core.currency.title|escape}
            </a>
            <span class="navbar-nav__drop dropdown-toggle" data-toggle="dropdown"><span class="fa fa-angle-down"></span></span>
            <ul class="dropdown-menu">
                {foreach $core.currencies as $code => $entry}
                    <li{if $core.currency.code == $code} class="active"{/if}><a href="#" title="{$entry.code}" data-code="{$entry.code}">{$entry.title|escape}</a></li>
                {/foreach}
            </ul>
        </li>
    </ul>
{/if}