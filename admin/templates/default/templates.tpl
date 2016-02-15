<div class="plates plates--templates">
	<div class="row">
		{foreach $templates as $template}
			<div class="col col-lg-3">
				<div class="media plate{if isset($template.remote)} plate--remote{/if}{if $template.name == $tmpl} plate--active{/if}">
					<div class="plate__image">
						{if !isset($template.remote)}
							<a href="{$template.logo}" title="{$template.title}" rel="ia_lightbox[{$template.name}]">
								<img src="{$template.logo}" title="{$template.title}" alt="{$template.title}">
							</a>
						{else}
							<a href="{$template.url}" title="{$template.title}" target="_blank">
								<img src="{$template.logo}" title="{$template.title}" alt="{$template.title}">
							</a>
						{/if}
						{if isset($template.screenshots) && $template.screenshots}
							<div class="screenshots hidden">
								{foreach $template.screenshots as $screenshot}
									<a href="{$core.page.nonProtocolUrl}templates/{$template.name}/docs/img/{$screenshot.name}" rel="ia_lightbox[{$template.name}]" title="{$screenshot.title}"></a>
								{/foreach}
							</div>
						{/if}

						{if isset($template.remote) && $template.price > 0}
							<div class="plate__badge plate__badge--premium">Premium &mdash; ${$template.price}</div>
						{/if}

						{foreach $template.notes as $note}
							<div class="plate__note">{$note}</div>
						{/foreach}
					</div>
					<div class="media-body">
						<div class="plate__heading">
							<h4>{$template.title} <small>{$template.version}</small></h4>
						</div>
						<p class="plate__info">
							{lang key='date'}: {$template.date|date_format:$core.config.date_format}<br>
							{lang key='compatibility'}: {$template.compatibility}
						</p>

						{if $template.buttons}
							<form method="post" class="clearfix">
								{preventCsrf}
								<input type="hidden" name="template" value="{$template.name}">
								{if $template.name != $tmpl}
									<button type="submit" name="install" class="btn btn-success btn-sm"><i class="i-checkmark"></i> {lang key='set_as_default_template'}</button>
								{else}
									<button type="submit" name="reinstall" class="btn btn-warning btn-sm"><i class="i-loop"></i></button>
									{if $template.config_groups}
										<a href="{$smarty.const.IA_ADMIN_URL}configuration/{$template.config_groups['Template'].name}/" class="btn btn-sm btn-default" title="{lang key='go_to_config'}"><i class="i-cog"></i></a>
									{/if}
								{/if}
								<a href="#" rel="{$template.name}" class="btn btn-sm btn-default js-cmd-info" title="{lang key='details'}"><i class="i-info"></i></a>
								{if $template.name != $tmpl}
									<a href="{$smarty.const.IA_URL}index/?preview={$template.name}" class="btn btn-sm btn-default" title="{lang key='preview'}" target="_blank"><i class="i-eye"></i></a>
								{/if}
							</form>
						{elseif isset($template.remote)}
							{if $template.price > 0}
								<a href="{$template.url}" class="btn btn-default btn-sm" target="_blank" title="{lang key='view'}"><i class="i-eye"></i> {lang key='view'}</a>
							{else}
								<form method="post" class="clearfix">
									{preventCsrf}
									<button type="submit" name="download" value="{$template.name}" class="btn btn-success btn-sm"><i class="i-box-add"></i> {lang key='download'}</button>
									<a href="{$template.url}" class="btn btn-default btn-sm" target="_blank" title="{lang key='preview'}"><i class="i-eye"></i></a>
								</form>
							{/if}
						{else}
							<a href="#" rel="{$template.name}" class="btn btn-default btn-sm js-cmd-info" title="{lang key='details'}"><i class="i-info"></i></a>
						{/if}
					</div>
				</div>
			</div>

			{if $template@iteration % 4 == 0}
				</div>
				<div class="row">
			{/if}
		{/foreach}
	</div>
</div>

{ia_add_js}
$('.preview').on('click', function(e)
{
	e.preventDefault();
	$(this).parent().find('.screenshots a:first').trigger('click');
});
{/ia_add_js}

{ia_print_js files='admin/templates'}