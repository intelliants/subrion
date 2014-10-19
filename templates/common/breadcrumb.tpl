{if isset($breadcrumb) && $breadcrumb|count}
	<div class="section section-light section-narrow ia-breadcrumb--wrapper">
		<div class="container" xmlns:v="http://rdf.data-vocabulary.org/#">
			<ul class="ia-breadcrumb pull-left">
				{foreach $breadcrumb as $entry}
					{if $entry.url && !$entry@last}
						<li typeof="v:Breadcrumb">
							<a href="{$entry.url}"{if isset($entry.no_follow) && $entry.no_follow} rel="nofollow"{/if} rel="v:url" property="v:title">{$entry.caption}</a>
							<span class="divider">/</span>
						</li>
					{else}
						<li class="active">{$entry.caption}</li>
					{/if}
				{/foreach}
			</ul>

			{if isset($page_actions)}
				<div class="action-buttons pull-right">
					{section action $page_actions max=2}
						<a href="{$page_actions[action].url}" class="btn btn-mini {if isset($page_actions[action].classes)} {$page_actions[action].classes}{else}btn-success{/if}"><i class="{$page_actions[action].icon}"></i> {$page_actions[action].title}</a> 
					{/section}

					{if count($page_actions) > 2}
						<a class="btn btn-mini dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="caret"></span>
						</a>
						<ul class="dropdown-menu pull-right">
							{section action $page_actions start=2}
								{if isset($page_actions[action].divider) && $page_actions[action].divider == '1'}
									<li class="divider"></li>
								{/if}
								<li>
									<a href="{$page_actions[action].url}"{if isset($page_actions[action].classes)} class="{$page_actions[action].classes}{/if}"><i class="{$page_actions[action].icon}"></i> {$page_actions[action].title}</a>
								</li>
							{/section}
						</ul>
					{/if}
				</div>
			{/if}
		</div>
	</div>
{/if}