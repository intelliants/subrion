{ia_print_js files='admin/menus'}
<form method="post" id="js-form-menus" class="sap-form form-horizontal">
	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>
			
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>

				<div class="col col-lg-4">
				{if $pageAction == 'add'}
					<input type="text" value="{$form.name}" id="name" name="name">
					<p class="help-block">{lang key='unique_name'}</p>
				{else}
					<input type="text" value="{$form.name}" id="name" name="name" class="disabled" disabled="disabled">
					<input type="hidden" value="{$form.name}" id="name" name="name">
				{/if}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='config_menu'}</label>

				<div class="col col-lg-4">
					<div class="row">
						<div class="col col-lg-6">
							<h4>{lang key='menus'}</h4>
							<div id="js-placeholder-menus" class="box-simple box-simple-small" style="height: 240px;"></div>
						</div>
						<div class="col col-lg-6">
							<h4>{lang key='pages'}</h4>
							<div id="js-placeholder-pages" class="box-simple box-simple-small" style="height: 240px;"></div>
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='title'}</label>

				<div class="col col-lg-4">
					<input type="text" id="title" name="title" value="{$form.title}">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='place'}</label>

				<div class="col col-lg-4">
					<select class="common" id="position" name="position">
					{foreach $positions as $position}
						<option value="{$position}"{if $form.position == $position} selected{/if}>{$position}</option>
					{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='sticky'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$form.sticky name='sticky' onchange="if(this.value == 0) $('#acos').show();else $('#acos').hide();"}
				</div>
			</div>

			<div id="acos" class="row">
				<label class="col col-lg-2 control-label">{lang key='visible_on_pages'}</label>

				<div class="col col-lg-8">
					{if isset($pages_group) && !empty($pages_group)}
						{if isset($pages) && !empty($pages)}
							<ul class="nav nav-tabs">
								{foreach $pages_group as $group => $row}
									{assign var='classname' value='pages_'|cat:$row.name}
									<li{if $row@iteration == 1} class="active"{/if}><a href="#tab-{$classname}" data-toggle="tab">{$row.title}</a></li>
								{/foreach}
							</ul>

							<div class="tab-content">
								{foreach $pages_group as $group => $row}
									{assign var='post_key' value='all_pages_'|cat:$row.name}
									{assign var='classname' value='pages_'|cat:$row.name}
									<div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
										<div class="checkbox checkbox-all">
											<label>
												<input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="{$post_key}" id="{$post_key}" {if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'}checked="checked"{/if}> {lang key='select_all_in_tab'}
											</label>
										</div>

										{foreach $pages as $key => $page}
											{if $page.group == $group}
											<div class="checkbox">
												<label>
													<input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}" {if in_array($page.name, $visibleOn, true)}checked="checked"{/if}>
													{if empty($page.title)}{$page.name}{else}{$page.title}{/if}

													{if $page.suburl}
														<div class="subpages" style="display: none" rel="{$page.suburl}::{$key}">&nbsp;</div>
														<input type="hidden" name="subpages[{$page.name}]" value="{if isset($block.subpages[$page.name])}{$block.subpages[$page.name]}{elseif isset($smarty.post.subpages[$page.name])}{$smarty.post.subpages[$page.name]}{/if}" id="subpage_{$key}"/>
													{/if}
												</label>
											</div>
											{/if}
										{/foreach}
									</div>
								{/foreach}
							</div>

							<div class="checkbox checkbox-all">
								<label>
									<input type="checkbox" value="1" name="all_pages" id="js-select-all-pages" {if isset($smarty.post.all_pages) && $smarty.post.all_pages == '1'}checked="checked"{/if}> {lang key='select_all'}
								</label>
							</div>
						{/if}
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions inline">
		<input type="hidden" id="js-menu-data" name="menus">
		<input type="hidden" id="action" value="{$pageAction}">
		<input type="button" class="btn btn-primary" onclick="menusSave()" value="{if $pageAction == 'add'}{lang key='create'}{else}{lang key='save'}{/if}" />
		{include file='goto.tpl'}
	</div>
</form>