{if $modules}
    <input type="hidden" id="js-default-package-value" value="{$core.config.default_package}">

    <div class="cards">
        {foreach $modules as $module}
            <div class="card card--module{if $module.remote} card--remote{elseif !$module.installed} card--local{elseif $module.installed} card--installed{/if} card--{$module.status}">
                <div class="card__item">
                    {if $module.buttons}
                        <div class="card__item__actions">
                            <a href="#" class="dropdown-toggle" type="button" data-toggle="dropdown"><span class="fa fa-ellipsis-v"></span></a>
                            <ul class="dropdown-menu dropdown-menu-right has-icons">
                                {if !empty($module.buttons.docs)}
                                    <li><a href="{$module.buttons.docs}" target="_blank"><span class="fa fa-info-circle"></span> {lang key='details'}</a></li>
                                {/if}

                                {if !empty($module.buttons.readme)}
                                    <li><a href="#" class="js-readme" data-module="{$module.name}"><span class="fa fa-book"></span> {lang key='documentation'}</a></li>
                                {/if}

                                {if !empty($module.buttons.reinstall)}
                                    <li>
                                        <a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/reinstall/" class="js-reinstall" data-module="{$module.name}" data-type="{$core.page.name}"><span class="fa fa-refresh"></span> {lang key='reinstall'}</a>
                                    </li>
                                {/if}

                                {if !empty($module.buttons.config)}
                                    <li><a href="{$smarty.const.IA_ADMIN_URL}configuration/{$module.buttons.config.url}/#{$module.buttons.config.anchor}"><span class="fa fa-cog"></span> {lang key='go_to_config'}</a></li>
                                {/if}

                                {if !empty($module.buttons.manage)}
                                    <li><a href="{$smarty.const.IA_ADMIN_URL}{$module.buttons.manage}"><span class="fa fa-list"></span> {lang key='manage'}</a></li>
                                {/if}

                                {if !empty($module.buttons.import)}
                                    <li><a href="{$smarty.const.IA_ADMIN_URL}database/import/"><span class="fa fa-database"></span> {lang key='import'}</a></li>
                                {/if}

                                {if !empty($module.buttons.set_default)}
                                    {access object='admin_page' id=$core.page.name action='set_default'}
                                        {if $core.config.default_package != $module.name}
                                            <li><a data-url="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/set_default/" href="javascript:;" onclick="setDefault(this)"><span class="fa fa-refresh"></span> {lang key='set_as_default_package'}</a></li>
                                        {else}
                                            <li><a data-url="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/reset/" href="javascript:;" onclick="resetUrl(this,'{$module.name}')"><span class="fa fa-refresh"></span> {lang key='reset_default'}</a></li>
                                        {/if}
                                    {/access}
                                {/if}

                                {if !empty($module.buttons.upgrade)}
                                    {access object='admin_page' id=$core.page.name action='upgrade'}
                                        <li><a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/upgrade/"><span class="fa fa-arrow-circle-o-up"></span> {lang key='upgrade'}</a></li>
                                    {/access}
                                {/if}

                                {if !empty($module.buttons.deactivate)}
                                    {access object='admin_page' id=$core.page.name action='activate'}
                                        <li><a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/deactivate/"><span class="fa fa-power-off"></span> {lang key='deactivate'}</a></li>
                                    {/access}
                                {/if}

                                {if !empty($module.buttons.activate)}
                                    {access object='admin_page' id=$core.page.name action='activate'}
                                        <li><a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/activate/"><span class="fa fa-check-circle"></span> {lang key='activate'}</a></li>
                                    {/access}
                                {/if}

                                {if !empty($module.buttons.uninstall)}
                                    {access object='admin_page' id=$core.page.name action='uninstall'}
                                        <li><a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/uninstall/" class="js-uninstall" data-module="{$module.name}" data-type="{$core.page.name}"><span class="fa fa-remove"></span> {lang key='uninstall'}</a></li>
                                    {/access}
                                {/if}
                            </ul>
                        </div>
                    {/if}
                    <div class="card__item__image">
                        <img src="{$module.logo}" alt="{$module.title}">
                    </div>
                    <div class="card__item__body">
                        <h4>{$module.title}</h4>
                        <p>{$module.summary}</p>
                        <div class="card__item__chips">
                            {if !empty($module.buttons.upgrade)}
                                <span class="chip chip--success"><span class="fa fa-arrow-circle-o-up"></span> {lang key='update_available'}</span>
                            {/if}

                            {if !empty($module.notes)}
                                {foreach $module.notes as $note}
                                    <span class="chip chip--danger js-tooltip" data-toggle="tooltip" title="{$note}"><span class="fa fa-warning"></span> {lang key='package_required'}</span>
                                {/foreach}
                            {/if}

                            {if !empty($module.remote) && $module.price > 0}
                                <span class="chip chip--warning"><span class="fa fa-star"></span> Premium ${$module.price}</span>
                            {/if}
                            <span class="chip chip--default">{lang key='compatibility'}: {$module.compatibility}</span>
                        </div>
                    </div>
                </div>

                <div class="card__actions">
                    <span class="card__actions__info">
                        <span class="fa fa-tag"></span> <b>{$module.version}</b> &middot; {$module.date|date_format:$core.config.date_format}
                    </span>

                    {if !empty($module.buttons.install)}
                        {access object='admin_page' id=$core.page.name action='install'}
                            <a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/install/" class="btn btn-success btn-xs pull-right js-install" data-module="{$module.name}" data-type="{$core.page.name}" data-remote="{$module.remote}">{lang key='install'}</a>
                        {/access}
                    {elseif !empty($module.buttons.reinstall)}
                        <span class="card__actions__status"><span class="fa fa-check"></span> {lang key='installed'}</span>
                    {elseif !empty($module.buttons.activate)}
                        <span class="card__actions__status card__actions__status--inactive"><span class="fa fa-info-circle"></span> {lang key='deactivated'}</span>
                    {elseif !empty($module.buttons.download)}
                        <a href="{$smarty.const.IA_ADMIN_URL}modules/{$core.page.name}/{$module.name}/download/" class="btn btn-primary btn-xs pull-right"><i class="i-box-add"></i> {lang key='download'}</a>
                    {elseif $module.price > 0}
                        <a href="{$module.url}" target="_blank" class="btn btn-default btn-xs pull-right">{lang key='view'}</a>
                    {elseif empty($module.buttons.download)}
                        <span class="card__actions__status card__actions__status--inactive"><span class="fa fa-info-circle"></span> {lang key='unable_to_install'}</span>
                    {/if}
                </div>
            </div>
        {/foreach}
    </div>

    {ia_print_js files='admin/modules'}
{else}
    <div class="alert alert-info">{lang key='no_modules'}</div>
{/if}