{function content_hook}
	{if isset($value[$key])}
		{$value.$key}
	{elseif isset($value['__all__']) && isset($value['excludes']) && !in_array($key, $value['excludes'])}
		{$value.__all__}
	{elseif isset($value['__all__']) && !isset($value['excludes'])}
		{$value.__all__}
	{/if}
{/function}

{ia_hooker name='smartyItemViewBeforeTabs'}

{if (isset($sections) && $sections) || (isset($tabs_content) && $tabs_content)}
	<div class="tabbable">
		<ul class="nav nav-tabs">
			{if isset($sections)}
				{foreach $sections as $section_name => $section}
					{if $section || isset($tabs_content.$section_name)}
						{* define active tab *}
						{if !isset($active)}
							{assign active $section_name}
						{/if}

						<li{if $active == $section_name} class="active"{/if}><a data-toggle="tab" href="#tab-{$section_name}"><span>{lang key=$section_name}</span></a></li>
					{/if}
				{/foreach}
			{/if}

			{if isset($tabs_content)}
				{foreach $tabs_content as $section_name => $section}
					{if $section || isset($tabs_content.$section_name)}
						{* define active tab *}
						{if !isset($active)}
							{assign active $section_name}
						{/if}

						<li{if $active == $section_name} class="active"{/if}><a data-toggle="tab" href="#tab-{$section_name}"><span>{lang key=$section_name}</span></a></li>
					{/if}
				{/foreach}
			{/if}
		</ul>

		<div class="tab-content ia-form">
			{if isset($sections)}
				{foreach $sections as $section_name => $section}
					<div id="tab-{$section_name}" class="tab-pane{if $active == $section_name} active{/if}">
						{content_hook key=$section_name value=$tabs_before}

						{if $section}
							{include file='field-type-content-fieldset.tpl' item_sections=$section}
						{/if}

						{content_hook key=$section_name value=$tabs_after}
					</div>
				{/foreach}
			{/if}

			{if isset($tabs_content)}
				{foreach $tabs_content as $section_name => $section}
					<div id="tab-{$section_name}" class="tab-pane{if $active == $section_name} active{/if}">
						{content_hook key=$section_name value=$tabs_before}

						{if isset($tabs_content[$section_name])}
							{$tabs_content[$section_name]}
						{/if}

						{content_hook key=$section_name value=$tabs_after}
					</div>
				{/foreach}
			{/if}
		</div>
	</div>
{/if}