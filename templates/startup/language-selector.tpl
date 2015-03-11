{if $config.language_switch && count($core.languages) > 1}
	<ul class="nav-inventory nav-inventory--langs">
		{foreach $core.languages as $code => $language}
			<li{if $smarty.const.IA_LANGUAGE == $code} class="active"{/if}><a href="{ia_page_url code=$code}" title="{$language.title}">{$language.title}</a></li>
		{/foreach}
	</ul>
{/if}