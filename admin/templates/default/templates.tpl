<div class="cards">
	<div class="row">
		{foreach $templates as $template}
			<div class="col-md-4">
				<div class="card card--template{if isset($template.remote)} card--remote{/if}{if $template.name == $tmpl} card--active{/if}">
					<div class="card__item">
						<div class="card__item__actions">
							<a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown"><span class="fa fa-ellipsis-v"></span></a>
							<ul class="dropdown-menu dropdown-menu-right has-icons">
								<li><a href="{$template.url}" target="_blank"><span class="fa fa-info-circle"></span> {lang key='documentation'}</a></li>
								{if $template.buttons}
									{if $template.name == $tmpl && $template.config_groups}
										<li>
											<a href="{$smarty.const.IA_ADMIN_URL}configuration/{$template.config_groups['Template'].name}/"><span class="fa fa-cogs"></span> {lang key='go_to_config'}</a>
										</li>
									{/if}
									{if $template.name == $tmpl}
										<li>
											<form method="post" class="hidden">
												{preventCsrf}
												<input type="hidden" name="template" value="{$template.name}">
											</form>
											<a href="#" class="js-reinstall"><span class="fa fa-refresh"></span> {lang key='reinstall'}</a>
										</li>
									{/if}
								{/if}
							</ul>
						</div>
						<div class="card__item__image">
							<img src="{$template.logo}" alt="{$template.title}">
						</div>
						<div class="card__item__body">
							<h4>{$template.title}</h4>
							<p>{$template.description}</p>
							<div class="card__item__chips">
								{foreach $template.notes as $note}
									<span class="chip chip--danger js-tooltip" data-toggle="tooltip" title="{$note}"><span class="fa fa-warning"></span> {lang key='package_required'}</span>
								{/foreach}
								{if isset($template.remote) && $template.price > 0}
									<span class="chip chip--warning"><span class="fa fa-star"></span> Premium</span>
								{/if}
								<span class="chip chip--default">{lang key='compatibility'}: v{$template.compatibility}</span>
							</div>
						</div>
					</div>
					<div class="card__actions">
						<span class="card__actions__info">
							<span class="fa fa-tag"></span> <b>v{$template.version}</b> &middot; {$template.date|date_format:$core.config.date_format}
						</span>

						{if $template.buttons}
							{if $template.name != $tmpl}
								<form method="post" class="pull-right">
									{preventCsrf}
									<input type="hidden" name="template" value="{$template.name}">
									<button type="submit" name="install" class="btn btn-success btn-xs">{lang key='install'}</button>
								</form>
							{else}
								<span class="card__actions__status"><span class="fa fa-check"></span> {lang key='installed'}</span>
							{/if}
						{elseif isset($template.remote)}
							{if $template.price > 0}
								<a href="{$template.url}" target="_blank" class="btn btn-success btn-xs pull-right">{lang key='buy'} ${$template.price}</a>
								<a href="{$template.url}" target="_blank" class="btn btn-default btn-xs pull-right">{lang key='view'}</a>
							{else}
								<form method="post" class="pull-right">
									{preventCsrf}
									<a href="{$template.url}" class="btn btn-default btn-xs" target="_blank" title="{lang key='preview'}"><i class="i-eye"></i></a>
									<button type="submit" name="download" value="{$template.name}" class="btn btn-success btn-xs"><i class="i-box-add"></i> {lang key='download'}</button>
								</form>
							{/if}
						{else}
							<span class="card__actions__status card__actions__status--inactive"><span class="fa fa-info-circle"></span> {lang key='unable_to_install'}</span>
						{/if}
					</div>
				</div>
			</div>

			{if $template@iteration % 3 == 0}
				</div>
				<div class="row">
			{/if}
		{/foreach}
	</div>
</div>

{ia_print_js files='admin/templates'}