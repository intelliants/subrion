<form action="{$url}pages/{$pageAction}/{if $pageAction == 'edit'}?id={$entry.id}{/if}" method="post" id="page_form" class="sap-form form-horizontal">
	{if $pageAction == 'edit'}
	<input type="hidden" name="extras" class="common" value="{$entry.extras}">
	{/if}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>
				<div class="col col-lg-4">
					<input type="text" name="name" value="{if isset($entry.name)}{$entry.name}{elseif isset($smarty.post.name)}{$smarty.post.name|escape:'html'}{/if}"{if $pageAction == 'edit'} readonly="readonly"{/if}>
					{if 'add' == $pageAction}<p class="help-block">{lang key='unique_name'}</p>{/if}
				</div>
			</div>

			{foreach $languages as $code => $language}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='title'} <span class="label label-info">{$language}</span></label>
				<div class="col col-lg-4">
					<input type="text" name="titles[{$code}]" value="{if isset($entry.titles)}{$entry.titles.$code|escape:'html'}{elseif isset($smarty.post.titles.$code)}{$smarty.post.titles.$code|escape:'html'}{/if}">
				</div>
			</div>
			{/foreach}

			{access object='admin_pages' id='manage_menus' action='add'}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='show_menus'}</label>

				<div class="col col-lg-4">
					<ul class="nav nav-tabs">
					{foreach $show_in_menus as $menu_list}
						{if $menu_list.list}
							<li{if $menu_list@iteration == 1} class="active"{/if}><a href="#tab-{$menu_list.title|replace:' ':''}" data-toggle="tab">{$menu_list.title}</a></li>
						{/if}
					{/foreach}
					</ul>

					<div class="tab-content">
					{foreach $show_in_menus as $menu_list}
						{if $menu_list.list}
						<div class="tab-pane{if $menu_list@iteration == 1} active{/if}" id="tab-{$menu_list.title|replace:' ':''}">
							{foreach $menu_list.list as $menu}
								<div class="checkbox">
									<label>
										<input type="checkbox" name="menus[]" value="{$menu.name}" id="p_{$menu.id}"{if in_array($menu.name, $menus)} checked="checked"{/if}> {$menu.title}
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

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='parent'}</label>

				<div class="col col-lg-4">
					<select name="parent_id" id="js-field-parent">
						<option value="0">{lang key='_no_parent_page_'}</option>
						{foreach $pages_group as $page_group}
							<optgroup label="{$page_group.title}">
								{foreach $page_group.children as $pageId => $pageTitle}
									<option value="{$pageId}"{if $parent_page == $pageId} selected="selected"{/if}>{$pageTitle}</option>
								{/foreach}
							</optgroup>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='status'}</label>
				<div class="col col-lg-4">
					<select name="status">
						<option value="active"{if isset($entry.status) && $entry.status == 'active'} selected="selected"{/if}>{lang key='active'}</option>
						<option value="inactive"{if isset($entry.status) && $entry.status == 'inactive'} selected="selected"{/if}>{lang key='inactive'}</option>
					</select>
				</div>
			</div>

			{if $home_page == 1}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='use_as_home_page'}</label>
				<div class="col col-lg-4">
					<div class="alert alert-info">{lang key='already_home_page'}</div>
				</div>
			</div>
			{else}
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='use_as_home_page'}</label>
				<div class="col col-lg-4">
					{html_radio_switcher value=$home_page name='home_page'}
					<p class="help-block">{lang key='current_home_page'}: <span class="text-danger">{lang key='page_title_'|cat:$config.home_page}</span></p>
				</div>
			</div>
			{/if}

			{if !isset($entry) || $entry === false || !isset($entry.service) && !isset($entry.readonly) || $entry.service == 0 && $entry.readonly == '0'}

				{if isset($entry.nofollow)}
					{assign var='nofollow' value=$entry.nofollow}
				{else}
					{assign var='nofollow' value=0}
				{/if}

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='no_follow_url'}</label>
					<div class="col col-lg-4">
						{html_radio_switcher value=$nofollow name='nofollow'}
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='external_url'}</label>
					<div class="col col-lg-4">
						{if isset($entry.custom_url) && $entry.custom_url != '' || isset($smarty.post.unique) && $smarty.post.unique == 1}
							{assign var='custom_url' value=1}
						{else}
							{assign var='custom_url' value=0}
						{/if}
						{html_radio_switcher value=$custom_url name='unique'}
					</div>
				</div>

				<div id="url_field" style="display: none;" class="row">
					<label class="col col-lg-2 control-label">{lang key='page_external_url'}</label>
					<div class="col col-lg-4">
						<input type="text" name="custom_url" id="custom_url" value="{if isset($entry.custom_url)}{$entry.custom_url}{elseif isset($smarty.post.custom_url)}{$smarty.post.custom_url|escape:'html'}{/if}">
					</div>
				</div>

				<div id="page_options" style="display: none;" class="row-stack">
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='password'}</label>
						<div class="col col-lg-4">
							<input type="text" name="passw" value="{if isset($entry.passw)}{$entry.passw|escape:"html"}{elseif isset($smarty.post.passw)}{$smarty.post.passw|escape:"html"}{/if}">
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='custom_url'}</label>

						<div class="col col-lg-4">
							<input type="text" name="alias" value="{if isset($entry.alias)}{$entry.alias}{elseif isset($smarty.post.alias)}{$smarty.post.alias|escape:'html'}{/if}">
							<p id="js-alias-placeholder" class="help-block">{lang key='page_url_will_be'}: <span class="text-danger"></span></p>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='meta_description'}</label>

						<div class="col col-lg-4">
							<textarea name="meta_description" rows="2">{if isset($entry.meta_description)}{$entry.meta_description}{elseif isset($smarty.post.meta_description)}{$smarty.post.meta_description|escape:'html'}{/if}</textarea>
						</div>
					</div>
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='meta_keywords'}</label>

						<div class="col col-lg-4">
							<input type="text" name="meta_keywords" value="{if isset($entry.meta_keywords)}{$entry.meta_keywords|escape:"html"}{elseif isset($smarty.post.meta_keywords)}{$smarty.post.meta_keywords|escape:'html'}{/if}">
						</div>
					</div>

					<div id="ckeditor" class="row">
						<label class="col col-lg-2 control-label">{lang key='page_content'}</label>

						<div class="col col-lg-10">
							<ul class="nav nav-tabs">
								{foreach $languages as $code => $language}
									<li{if $language@iteration == 1} class="active"{/if}><a href="#tab-language-{$code}" data-toggle="tab" data-language="{$code}">{$language}</a></li>
								{/foreach}
							</ul>

							<div class="tab-content">
								{foreach $languages as $code => $language}
									<div class="tab-pane{if $language@iteration == 1} active{/if}" id="tab-language-{$code}">
										<textarea id="contents[{$language}]" rows="30" name="contents[{$code}]" class="ckeditor_textarea">{if isset($entry.contents.$code)}{$entry.contents.$code}{elseif isset($smarty.post.contents.$code)}{$smarty.post.contents.$code}{/if}</textarea>
									</div>
								{/foreach}
							</div>
						</div>
					</div>
				</div>
			{else}
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='meta_description'}</label>
					<div class="col col-lg-4">
						<textarea name="meta_description" rows="2">{if isset($entry.meta_description)}{$entry.meta_description}{elseif isset($smarty.post.meta_description)}{$smarty.post.meta_description|escape:'html'}{/if}</textarea>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='meta_keywords'}</label>
					<div class="col col-lg-4">
						<input type="text" name="meta_keywords" value="{if isset($entry.meta_keywords)}{$entry.meta_keywords|escape:"html"}{elseif isset($smarty.post.meta_keywords)}{$smarty.post.meta_keywords|escape:'html'}{/if}">
					</div>
				</div>
				<input type="hidden" value="1" name="service">
			{/if}
		</div>
	</div>

	<div class="form-actions inline">
		<input type="hidden" name="do" value="{$pageAction}">
		<input type="hidden" name="old_name" value="{if isset($entry.name)}{$entry.name}{/if}">
		<input type="hidden" name="old_alias" value="{if isset($entry.alias)}{$entry.alias}{/if}">
		<input type="hidden" name="id" value="{if isset($entry.id)}{$entry.id}{/if}">
		<input type="hidden" name="prevent_csrf" id="js-csrf-protection-code">
		<input type="hidden" name="language" id="js-active-language">
		<input type="submit" name="save" class="btn btn-primary" value="{if $pageAction == 'add'}{lang key='add'}{else}{lang key='save_changes'}{/if}">
		{if !isset($entry) || $entry.readonly == 0}
		<input type="submit" value="{lang key='preview'} {lang key='page'}" class="btn btn-success" name="preview">
		{/if}
		{include file='goto.tpl'}
	</div>
</form>
<div style="display:none;" id="csrf_for_save">{preventCsrf}</div>
<div style="display:none;" id="csrf_for_preview">{preventCsrf}</div>