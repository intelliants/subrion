{if $packages}
	<input type="hidden" id="js-default-package-value" value="{$core.config.default_package}">
	<div class="cards cards--templates">
		<div class="row">
			{foreach $packages as $package}
				<div class="col-md-4">
					<div class="card card--module{if $package.remote} card--remote{/if}{if iaCore::STATUS_ACTIVE == $package.status} card--active{/if}">
						<div class="card__item">
							{if $package.buttons}
								<div class="card__item__actions">
									<a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown"><span class="fa fa-ellipsis-v"></span></a>
									<ul class="dropdown-menu dropdown-menu-right has-icons" aria-labelledby="dropdownMenu2">
										{if $package.items.readme}
											<li><a href="javascript:;" onclick="readme('{$package.name}')"><span class="fa fa-info-circle"></span> {lang key='documentation'}</a></li>
										{/if}
										{if $package.items.config}
											<li><a href="{$smarty.const.IA_ADMIN_URL}configuration/{$package.items.config.url}/#{$package.items.config.anchor}"><span class="fa fa-cog"></span> {lang key='go_to_config'}</a></li>
										{/if}
										{if $package.items.manage}
											<li><a href="{$smarty.const.IA_ADMIN_URL}{$package.items.manage}"><span class="fa fa-list"></span> {lang key='manage'}</a></li>
										{/if}
										{if $package.items.import}
											<li><a href="{$smarty.const.IA_ADMIN_URL}database/import/"><span class="fa fa-database"></span> {lang key='import'}</a></li>
										{/if}
										{if $package.items.set_default}
											{access object='admin_page' id='packages' action='set_default'}
												{if $core.config.default_package != $package.name}
													<li><a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/set_default/" href="javascript:;" onclick="setDefault(this)"><span class="fa fa-refresh"></span> {lang key='set_as_default_package'}</a></li>
												{else}
													<li><a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/reset/" href="javascript:;" onclick="resetUrl(this,'{$package.name}')"><span class="fa fa-refresh"></span> {lang key='reset_default'}</a></li>
												{/if}
											{/access}
										{/if}
										{if $package.items.upgrade}
											{access object='admin_page' id='packages' action='upgrade'}
												<li><a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/upgrade/"><span class="fa fa-arrow-circle-o-up"></span> {lang key='upgrade'}</a></li>
											{/access}
										{/if}
										{if $package.items.deactivate}
											{access object='admin_page' id='packages' action='activate'}
												<li><a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/deactivate/"><span class="fa fa-power-off"></span> {lang key='deactivate'}</a></li>
											{/access}
										{/if}
										{if $package.items.activate}
											{access object='admin_page' id='packages' action='activate'}
												<li><a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/activate/"><span class="fa fa-check-circle"></span> {lang key='activate'}</a></li>
											{/access}
										{/if}
										{if $package.items.uninstall}
											{access object='admin_page' id='packages' action='uninstall'}
												<li><a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/uninstall/" class="js-uninstall"><span class="fa fa-remove"></span> {lang key='uninstall'}</a></li>
											{/access}
										{/if}
									</ul>
								</div>
							{/if}
							<div class="card__item__image">
								<img src="{$package.logo}" alt="{$package.title}">
							</div>
							<div class="card__item__body">
								<h4>{$package.title}</h4>
								<p>{$package.summary}</p>
								<div class="card__item__chips">
									{if isset($package.remote) && $package.price > 0}
										<span class="chip chip--sm chip--accent"><span class="fa fa-star"></span> Premium</span>
									{/if}
									<span class="chip chip--sm chip--default">{lang key='compatibility'}: v{$package.compatibility}</span>
								</div>
							</div>
						</div>

						<div class="card__actions">
							<span class="card__actions__info"><span class="fa fa-tag"></span> <b>v{$package.version}</b> &middot; {$package.date|date_format:$core.config.date_format}</span>

							{if $package.buttons}
								{if $package.items.activate}
									<span class="card__actions__status card__actions__status--inactive"><span class="fa fa-info-circle"></span> {lang key='deactivated'}</span>
								{/if}
								{if $package.items.install}
									{access object='admin_page' id='packages' action='install'}
										<a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/install/" href="javascript:;" onclick="installPackage(this,'{$package.name}')" title="{lang key='install'}" class="btn btn-success btn-xs pull-right">{lang key='install'}</a>
									{/access}
								{else}
									<span class="card__actions__status"><span class="fa fa-check"></span> {lang key='installed'}</span>
								{/if}
							{elseif $package.remote}
								<a href="{$package.url}" target="_blank" class="btn btn-success btn-xs pull-right">{lang key='buy'} ${$package.price}</a>
								<a href="{$package.url}" target="_blank" class="btn btn-default btn-xs pull-right">{lang key='view'}</a>
							{/if}
						</div>
					</div>

					{if $package@iteration % 3 == 0}
						</div>
						<div class="row">
					{/if}
				</div>
			{/foreach}
		</div>
	</div>
	{ia_print_js files='admin/packages'}
{else}
	<div class="alert alert-info">{lang key='no_packages'}</div>
{/if}