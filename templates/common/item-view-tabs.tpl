{function content_hook}
	{if isset($value[$key])}
		{$value.$key}
	{elseif isset($value['__all__']) && isset($value['excludes']) && !in_array($key, $value['excludes'])}
		{$value.__all__}
	{elseif isset($value['__all__']) && !isset($value['excludes'])}
		{$value.__all__}
	{/if}
{/function}

{if isset($sections) && $sections}
	<div class="tabbable">
		<ul class="nav nav-tabs">
			{foreach $sections as $section_name => $section}
				{if $section || isset($tabs_content.$section_name)}
					{* define active tab *}
					{if !isset($active)}
						{assign var='active' value=$section_name}
					{/if}

					<li{if $active == $section_name} class="active"{/if}><a data-toggle="tab" href="#tab-{$section_name}"><span>{lang key=$section_name}</span></a></li>
				{/if}
			{/foreach}
		</ul>

		<div class="tab-content ia-form">
			{foreach $sections as $section_name => $section}
				<div id="tab-{$section_name}" class="tab-pane{if $active == $section_name} active{/if}">
					{content_hook value=$tabs_before key=$section_name}

					{if $section}
						{include file='field-type-content-fieldset.tpl' item_sections=$section}
					{elseif isset($tabs_content[$section_name])}
						{$tabs_content[$section_name]}
					{/if}

					{content_hook value=$tabs_after key=$section_name}
				</div>
			{/foreach}
		</div>
	</div>
{/if}
