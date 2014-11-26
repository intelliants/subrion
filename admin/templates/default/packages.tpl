{if $packages}
	<input type="hidden" id="js-default-package-value" value="{$config.default_package}">
	<div class="plates-list">
	{foreach $packages as $package}
		<div class="media">
			{if isset($package.preview) && count($package.preview) > 0}
				{foreach $package.preview as $preview}
					<a class="pull-left" href="{$smarty.const.IA_CLEAR_URL}packages/{$package.name}/docs/img/{$preview.name}" rel="ia_lightbox[{$package.name}]" title="{$package.title}">
						<img title="{$preview.title}" class="media-object" src="{$nonProtocolUrl}packages/{$package.name}/docs/img/icon.png">
					</a>
				{/foreach}
				<div class="screenshots hidden">
					{foreach $package.screenshots as $screenshot}
						<a rel="ia_lightbox[{$package.name}]" title="{$package.title}. {$screenshot.title}" href="{$smarty.const.IA_CLEAR_URL}packages/{$package.name}/docs/img/{$screenshot.name}">
							<img width="150" height="150" src="{$nonProtocolUrl}packages/{$package.name}/docs/img/{$screenshot.name}">
						</a>
					{/foreach}
				</div>
			{/if}
			<div class="media-body">
				<h4 class="media-heading">{$package.title}</h4>
				<p>
					{lang key='compatibility'}: <strong>{$package.compatibility}</strong><br>
					{lang key='package_version'}: <strong>{$package.version}</strong><br>
					{if $package.date_updated}
						{lang key='last_updated'}: <strong>{$package.date_updated}</strong><br />
					{/if}
				</p>

				<p class="text-muted">{$package.summary}</p>

				{if $package.buttons}
					<div class="plate-actions clearfix">
						{if $package.items.install}
							{access object='admin_pages' id='packages' action='install'}
							<a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/install/" href="javascript:;" onclick="installPackage(this,'{$package.name}')" title="{lang key='install'}" class="btn btn-success btn-small"><i class="i-plus-alt"></i></a>
							{/access}
						{/if}
						{if $package.items.readme}
							<a href="javascript:;" title="{lang key='documentation'}" onclick="readme('{$package.name}')" class="btn btn-primary btn-small"><i class="i-info"></i></a>
						{/if}
						{if $package.items.set_default}
							{access object='admin_pages' id='packages' action='set_default'}
								{if $config.default_package != $package.name}
								<a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/set_default/" href="javascript:;" onclick="setDefault(this)" class="btn btn-primary btn-small" title="{lang key='set_as_default_package'}"><i class="i-loop"></i></a>
								{else}
								<a data-url="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/reset/" href="javascript:;" onclick="resetUrl(this,'{$package.name}')" class="btn btn-primary btn-small" title="{lang key='reset_default'}"><i class="i-loop"></i></a>
								{/if}
							{/access}
						{/if}
						{if $package.items.upgrade}
							{access object='admin_pages' id='packages' action='upgrade'}
							<a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/upgrade/" class="btn btn-success btn-small" title="{lang key='upgrade'}"><i class="i-box-remove"></i></a>
							{/access}
						{/if}
						{if $package.items.config}
						<a href="{$smarty.const.IA_ADMIN_URL}configuration/{$package.items.config.url}/#{$package.items.config.anchor}" class="btn btn-primary btn-small" title="{lang key='go_to_config'}"><i class="i-cog"></i></a>
						{/if}
						{if $package.items.manage}
							<a href="{$smarty.const.IA_ADMIN_URL}{$package.items.manage}" class="btn btn-primary btn-small" title="{lang key='manage'}"><i class="i-equalizer"></i></a>
						{/if}
						{if $package.items.import}
							<a href="{$smarty.const.IA_ADMIN_URL}database/import/" class="btn btn-primary btn-small" title="{lang key='import'}"><i class="i-database"></i></a>
						{/if}
						{if $package.items.deactivate}
							{access object='admin_pages' id='packages' action='activate'}
							<a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/deactivate/" class="btn btn-danger btn-small" title="{lang key='deactivate'}"><i class="i-switch"></i></a>
							{/access}
						{/if}
						{if $package.items.activate}
							{access object='admin_pages' id='packages' action='activate'}
							<a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/activate/" class="btn btn-success btn-small" title="{lang key='activate'}"><i class="i-switch"></i></a>
							{/access}
						{/if}
						{if $package.items.uninstall}
							{access object='admin_pages' id='packages' action='uninstall'}
							<a href="{$smarty.const.IA_ADMIN_URL}packages/{$package.name}/uninstall/" class="btn btn-danger btn-small" title="{lang key='uninstall'}"><i class="i-remove-sign"></i></a>
							{/access}
						{/if}
					</div>
				{/if}
			</div>
		</div>
	{/foreach}
	</div>
	{ia_print_js files='admin/packages'}
{else}
	<div class="alert alert-info">{lang key='no_packages'}</div>
{/if}