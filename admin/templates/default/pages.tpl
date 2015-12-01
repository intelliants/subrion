<form method="post" id="page_form" class="sap-form form-horizontal">
	{preventCsrf}
	<input type="hidden" name="language" id="js-active-language">
	<input type="hidden" name="extras" value="{$item.extras|escape:'html'}">

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>
				<div class="col col-lg-4">
					<input type="text" name="name" value="{$item.name|escape:'html'}" id="input-name"{if iaCore::ACTION_EDIT == $pageAction} readonly{/if}>
					{if iaCore::ACTION_ADD == $pageAction}<p class="help-block">{lang key='unique_name'}</p>{/if}
				</div>
			</div>

			{if !$item.service && !$item.readonly}

				<div class="row js-local-url-field">
					<label class="col col-lg-2 control-label">{lang key='parent'}</label>

					<div class="col col-lg-4">
						<select name="parent_id" id="input-parent">
							<option value="0">{lang key='_no_parent_page_'}</option>
							{foreach $pagesGroup as $pageGroup}
								<optgroup label="{$pageGroup.title}">
									{foreach $pageGroup.children as $pageId => $pageTitle}
										<option value="{$pageId}"{if $parentPageId == $pageId} selected{/if}>{$pageTitle|escape:'html'}</option>
									{/foreach}
								</optgroup>
							{/foreach}
						</select>
					</div>
				</div>

				<div class="row" id="js-field-remote-url" style="display: none;">
					<label class="col col-lg-2 control-label">{lang key='page_external_url'}</label>
					<div class="col col-lg-4">
						<input type="text" name="custom_url" id="input-custom-url" value="{if isset($item.custom_url)}{$item.custom_url|escape:'html'}{/if}">
					</div>
				</div>

				<div class="row js-local-url-field">
					<label class="col col-lg-2 control-label">{lang key='custom_url'}</label>
					<div class="col col-lg-4">
						<div class="input-group">
							<input type="text" name="alias" id="input-alias" value="{$item.alias|escape:'html'}">
							<input type="hidden" name="extension" value="{if $item.extension}.{$item.extension}{else}/{/if}">
							<div class="input-group-btn">
								<button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown">
									{if empty($item.extension)}{lang key='no_extension'}{else}{$item.extension}{/if}
									<span class="caret"></span>
								</button>
								<ul id="js-page-extension-list" class="dropdown-menu pull-right">
									<li{if empty($item.extension)} class="active"{/if}><a href="#" data-extension="/">{lang key='no_extension'}</a></li>
									<li class="divider"></li>
									{foreach $extensions as $extension}
									<li{if $item.extension == $extension} class="active"{/if}><a href="#" data-extension=".{$extension}">{$extension}</a></li>
									{/foreach}
								</ul>
							</div>
						</div>
						<p id="js-alias-placeholder" class="help-block">{lang key='page_url_will_be'}: <span class="text-danger"></span></p>
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='external_url'}</label>
					<div class="col col-lg-4">
						{if isset($item.custom_url) && $item.custom_url != '' || isset($smarty.post.unique) && $smarty.post.unique == 1}
							{assign custom_url 1}
						{else}
							{assign custom_url 0}
						{/if}
						{html_radio_switcher value=$custom_url name='unique'}
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='no_follow_url'}</label>
					<div class="col col-lg-4">
						{html_radio_switcher value=$item.nofollow name='nofollow'}
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='open_in_new_tab'}</label>
					<div class="col col-lg-4">
						{html_radio_switcher value=$item.new_window name='new_window'}
					</div>
				</div>

				<div class="row js-local-url-field">
					<label class="col col-lg-2 control-label">{lang key='password'}</label>
					<div class="col col-lg-4">
						<input type="text" name="passw" value="{if isset($item.passw)}{$item.passw|escape:'html'}{elseif isset($smarty.post.passw)}{$smarty.post.passw|escape:"html"}{/if}">
					</div>
				</div>
			{else}
				<input type="hidden" value="1" name="service">
			{/if}

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='use_as_home_page'}</label>
				<div class="col col-lg-4">
					{if $isHomePage}
						<div class="alert alert-info">{lang key='already_home_page'}</div>
					{else}
						{html_radio_switcher value=$isHomePage name='home_page'}
						<p class="help-block">{lang key='current_home_page'}: <span class="text-danger">{lang key="page_title_{$core.config.home_page}"}</span></p>
					{/if}
				</div>
			</div>

			{access object='admin_pages' id='manage_menus' action=iaCore::ACTION_ADD}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='show_menus'}</label>

					<div class="col col-lg-4">
						<ul class="nav nav-tabs">
							{foreach $menus as $menu_list}
								{if $menu_list.list}
									<li{if $menu_list@iteration == 1} class="active"{/if}><a href="#tab-{$menu_list.title|replace:' ':''}" data-toggle="tab">{$menu_list.title}</a></li>
								{/if}
							{/foreach}
						</ul>

						<div class="tab-content">
							{foreach $menus as $menu_list}
								{if $menu_list.list}
									<div class="tab-pane{if $menu_list@iteration == 1} active{/if}" id="tab-{$menu_list.title|replace:' ':''}">
										{foreach $menu_list.list as $menu}
											<div class="checkbox">
												<label>
													<input type="checkbox" name="menus[]" value="{$menu.id}" id="p_{$menu.id}"{if in_array($menu.id, $selectedMenus)} checked{/if}> {$menu.title}
												</label>
											</div>
										{/foreach}
									</div>
								{/if}
							{/foreach}
						</div>
					</div>
				</div>
			{/access}

			<div class="js-local-url-field">
				{if 'page' == $item.filename}
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='custom_template'}</label>
						<div class="col col-lg-4">
							{html_radio_switcher value=$item.custom_tpl name='custom_tpl'}
						</div>
					</div>

					<div class="row" id="js-field-tpl-filename" style="display: none;">
						<label class="col col-lg-2 control-label">{lang key='custom_template_filename'}</label>
						<div class="col col-lg-4">
							<input type="text" name="template_filename" id="input-tpl-filename" value="{if isset($item.template_filename)}{$item.template_filename|escape:'html'}{/if}">
						</div>
					</div>
				{/if}
			</div>
		</div>

		<div class="wrap-group js-local-url-field">
			<div class="wrap-group-heading">
				<h4>{lang key='seo'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='meta_description'}</label>

				<div class="col col-lg-4">
					<textarea name="meta_description" rows="2">{$item.meta_description|escape:'html'}</textarea>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='meta_keywords'}</label>

				<div class="col col-lg-4">
					<input type="text" name="meta_keywords" value="{$item.meta_keywords|escape:'html'}">
				</div>
			</div>
		</div>

		<div class="wrap-group" id="js-content-fields">
			<div class="row">
				<ul class="nav nav-tabs">
					{foreach $core.languages as $code => $language}
						<li{if $language@iteration == 1} class="active"{/if}><a href="#tab-language-{$code}" data-toggle="tab" data-language="{$code}">{$language.title}</a></li>
					{/foreach}
				</ul>

				<div class="tab-content">
					{foreach $core.languages as $code => $language}
					<div class="tab-pane{if $language@first} active{/if}" id="tab-language-{$code}">
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='title'}</label>
							<div class="col col-lg-10">
								<input type="text" name="titles[{$code}]" value="{if isset($item.titles)}{$item.titles.$code|escape:'html'}{/if}">
							</div>
						</div>
						<div class="row js-local-url-field">
							<label class="col col-lg-2 control-label">{lang key='page_content'}</label>
							<div class="col col-lg-10">
								<textarea rows="30" name="contents[{$code}]">{if isset($item.contents.$code)}{$item.contents.$code|escape:'html'}{/if}</textarea>
							</div>
						</div>
					</div>
					{/foreach}
				</div>
			</div>
		</div>

		{include file='fields-system.tpl'}
	</div>
</form>
{ia_print_js files='ckeditor/ckeditor, admin/pages'}