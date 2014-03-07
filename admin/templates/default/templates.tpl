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
									<a href="{$smarty.const.IA_CLEAR_URL|cat:'templates/'|cat:$template.name|cat:'/info/screenshots/'|cat:$screenshot.name}" rel="ia_lightbox[{$template.name}]" title="{$screenshot.title}"></a>
								{/foreach}
							</div>
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
							{lang key='date'}: {$template.date}<br>
							{lang key='compatibility'}: {$template.compatibility}
						</p>

						{if $template.buttons}
							<form method="post" class="clearfix">
								{preventCsrf}
								<input type="hidden" name="template" value="{$template.name}">
								{if $template.name != $tmpl}
									<button type="submit" name="set_template" class="btn btn-success btn-sm"><i class="i-checkmark"></i> {lang key='set_as_default_template'}</button>
								{else}
									<!-- <a href="#" class="btn btn-warning btn-sm disabled"><i class="i-checkmark"></i> {lang key='active'}</a> -->
									{if $template.config}
										<a href="{$smarty.const.IA_ADMIN_URL}configuration/template_{$tmpl}/" class="btn btn-sm btn-default" title="{lang key='go_to_config'}"><i class="i-cog"></i></a>
									{/if}
								{/if}
								<a href="#" rel="{$template.name}" class="btn btn-sm btn-default js-cmd-info" title="{lang key='details'}"><i class="i-info"></i></a>
								<a href="{$smarty.const.IA_URL}index/?preview={$template.name}" class="btn btn-sm btn-default" title="{lang key='preview'}" target="_blank"><i class="i-eye"></i></a>
							</form>
						{elseif isset($template.remote)}
							<form method="post" class="clearfix">
								<button type="submit" name="download_template" value="{$template.name}" class="btn btn-success btn-sm"><i class="i-box-add"></i> {lang key='download'}</button>
								<a href="{$template.url}" class="btn btn-default btn-sm" target="_blank" title="{lang key='preview'}"><i class="i-eye"></i></a>
							</form>
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
$('.preview').click(function(e)
{
	e.preventDefault();
	$(this).parent().find('.screenshots a:first').trigger('click');
});
{/ia_add_js}

{ia_print_js files='admin/templates'}