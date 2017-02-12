<div class="cards">
	<div class="row">
		{foreach $modules as $module}
			<div class="col-md-4">
				<div class="card card--template{if !empty($module.remote)} card--remote{/if} card--{$module.status}">
					<div class="card__item">
						<div class="card__item__actions">
							<a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown"><span class="fa fa-ellipsis-v"></span></a>
							<ul class="dropdown-menu dropdown-menu-right has-icons">
								{if !empty($module.buttons.docs)}
									<li><a href="{$module.buttons.docs}" target="_blank"><span class="fa fa-info-circle"></span> {lang key='documentation'}</a></li>
								{/if}
								{if !empty($module.buttons.reinstall)}
									<li>
										<a href="{$smarty.const.IA_ADMIN_URL}modules/templates/{$module.name}/reinstall/" class="js-reinstall"><span class="fa fa-refresh"></span> {lang key='reinstall'}</a>
									</li>
								{/if}
							</ul>
						</div>
						<div class="card__item__image">
							<img src="{$module.logo}" alt="{$module.title}">
						</div>
						<div class="card__item__body">
							<h4>{$module.title}</h4>
							<p>{$module.summary}</p>
							<div class="card__item__chips">
								{foreach $module.notes as $note}
									<span class="chip chip--danger js-tooltip" data-toggle="tooltip" title="{$note}"><span class="fa fa-warning"></span> {lang key='package_required'}</span>
								{/foreach}
								{if isset($module.remote) && $module.price > 0}
									<span class="chip chip--warning"><span class="fa fa-star"></span> Premium</span>
								{/if}
								<span class="chip chip--default">{lang key='compatibility'}: v{$module.compatibility}</span>
							</div>
						</div>
					</div>
					<div class="card__actions">
						<span class="card__actions__info">
							<span class="fa fa-tag"></span> <b>v{$module.version}</b> &middot; {$module.date|date_format:$core.config.date_format}
						</span>

						{if !empty($module.buttons.install)}
							<a href="{$smarty.const.IA_ADMIN_URL}modules/templates/{$module.name}/install/" class="btn btn-success btn-xs pull-right">{lang key='install'}</a>
						{elseif !empty($module.buttons.reinstall)}
							<span class="card__actions__status"><span class="fa fa-check"></span> {lang key='installed'}</span>
						{elseif empty($module.buttons.download)}
							<span class="card__actions__status card__actions__status--inactive"><span class="fa fa-info-circle"></span> {lang key='unable_to_install'}</span>
						{elseif $module.price > 0}
							<a href="{$module.url}" target="_blank" class="btn btn-success btn-xs pull-right">{lang key='buy'} ${$module.price}</a>
							<a href="{$module.url}" target="_blank" class="btn btn-default btn-xs pull-right">{lang key='view'}</a>
						{elseif !empty($module.buttons.download)}
							<a href="{$smarty.const.IA_ADMIN_URL}modules/templates/{$module.name}/download/" class="btn btn-primary btn-xs pull-right"><i class="i-box-add"></i> {lang key='download'}</a>
						{/if}
					</div>
				</div>
			</div>

			{if $module@iteration % 3 == 0}
				</div>
				<div class="row">
			{/if}
		{/foreach}
	</div>
</div>

{ia_print_js files='admin/templates'}