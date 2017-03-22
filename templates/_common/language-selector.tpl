{if $core.config.language_switch && count($core.languages) > 1}
    <ul class="nav navbar-nav navbar-right nav-langs">
        <li class="dropdown">
            <a href="#" class="dropdown-toggle" data-toggle="dropdown">
                {$core.languages[$smarty.const.IA_LANGUAGE].title}
            </a>
            <span class="navbar-nav__drop dropdown-toggle" data-toggle="dropdown"><span class="fa fa-angle-down"></span></span>
            <ul class="dropdown-menu">
                {foreach $core.languages as $code => $language}
                    <li{if $smarty.const.IA_LANGUAGE == $code} class="active"{/if}><a href="{ia_page_url code=$code}" title="{$language.title}">{$language.title}</a></li>
                {/foreach}
            </ul>
        </li>
    </ul>
{/if}