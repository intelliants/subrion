{if isset($block_portfolio_entries) && $block_portfolio_entries}
	<div class="ia-items portfolio-entries">
		<div class="row">
			{foreach $block_portfolio_entries as $pf_entry}
				<div class="col-md-3">
					<div class="ia-item ia-item--card">
						{if $pf_entry.image}
							<a href="{$smarty.const.IA_URL}portfolio/{$pf_entry.id}-{$pf_entry.alias}" class="ia-item__image">{printImage imgfile=$pf_entry.image title=$pf_entry.title}<span class="fa fa-eye"></span></a>
						{/if}

						<div class="ia-item__content">
							<h4 class="ia-item__title text-center">
								<a href="{$smarty.const.IA_URL}portfolio/{$pf_entry.id}-{$pf_entry.alias}">{$pf_entry.title|escape: html}</a>
							</h4>
						</div>
					</div>
				</div>

				{if $pf_entry@iteration == $core.config.portfolio_block_count}
					{break}
				{/if}
			{/foreach}
		</div>

		<div class="m-t text-center">
			<a class="btn btn-primary text-uppercase" href="{$smarty.const.IA_URL}portfolio/">{lang key='pf_view_all'}</a>
		</div>
	</div>
{else}
	<div class="alert alert-info">{lang key='pf_no_entries'}</div>
{/if}

{ia_add_media files='css: _IA_URL_plugins/portfolio/templates/front/css/style'}