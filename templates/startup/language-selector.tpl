{if $config.language_switch && count($languages) > 1}
	<ul class="nav-inventory nav-inventory--langs">
		{foreach $languages as $code => $language}
			<li{if $smarty.const.IA_LANGUAGE == $code} class="active"{/if}><a href="{ia_page_url code=$code}" title="{$language}">{$language}</a></li>
		{/foreach}
	</ul>
{/if}