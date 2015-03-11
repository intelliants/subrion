<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='name'}</label>

				<div class="col col-lg-4">
					{if iaCore::ACTION_ADD == $pageAction}
						<input type="text" name="name" value="{if isset($item.name)}{$item.name|escape:'html'}{/if}">
						<p class="help-block">{lang key='unique_name'}</p>
					{else}
						<input type="text" value="{$item.name|escape:'html'}" disabled>
					{/if}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='type'}</label>

				<div class="col col-lg-4">
					<select name="type" id="input-block-type">
					{foreach $types as $key => $type}
						{if iaBlock::TYPE_MENU != $type}
							{access object='admin_pages' id='blocks' action=$type}
							<option value="{$type}"{if $type == $item.type} selected{/if}>{$type}</option>
							{/access}
						{/if}
					{/foreach}
					</select>
					<p class="help-block">
						<span data-type="plain" style="display: none;">{lang key='block_type_tip_plain'}</span>
						<span data-type="html" style="display: none;">{lang key='block_type_tip_html'}</span>
						<span data-type="smarty" style="display: none;">{lang key='block_type_tip_smarty'}</span>
						<span data-type="php" style="display: none;">{lang key='block_type_tip_php'}</span>
					</p>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='position'}</label>

				<div class="col col-lg-4">
					<select name="position">
						{foreach $positions as $position}
							<option value="{$position.name}"{if isset($item.position) && $item.position == $position.name} selected{/if}>{$position.name}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='css_class_name'}</label>

				<div class="col col-lg-4">
					<input type="text" name="classname" value="{if isset($item.classname)}{$item.classname}{/if}">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='show_header'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.header name='header'}
				</div>
			</div>

			<div class="row" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.collapsible name='collapsible'}
				</div>
			</div>

			<div class="row" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='collapsed'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.collapsed name='collapsed'}
				</div>
			</div>

			<div class="row" id="js-multi-language-row">
				<label class="col col-lg-2 control-label">{lang key='multilingual'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.multilingual name='multilingual'}
				</div>
			</div>

			<div class="row" id="languages" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='language'}</label>

				<div class="col col-lg-4">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="js-check-all-lngs" value="1"{if isset($smarty.post.select_all) && $smarty.post.select_all == '1'} checked{/if}> {lang key='select_all'}
						</label>
					</div>

					{foreach $core.languages as $code => $language}
						<div class="checkbox">
							<label>
								<input type="checkbox" class="js-language-check" name="languages[]" value="{$code}"{if isset($item.languages) && in_array($code, $item.languages)} checked{/if}> {$language.title}
							</label>
						</div>
					{/foreach}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='block_visible_everywhere'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$item.sticky name='sticky'}
					<p class="js-visibility-visible help-block">{lang key='block_visibility_exceptions_visible'}</p>
					<p class="js-visibility-hidden help-block">{lang key='block_visibility_exceptions_hidden'}</p>
				</div>
			</div>

			<div class="row" id="js-pages-list">
				<label class="col col-lg-2 control-label"></label>

				<div class="col col-lg-8">
					<ul class="nav nav-tabs">
						{foreach $pagesGroup as $group => $row}
							<li{if $row@iteration == 1} class="active"{/if}><a href="#tab-visible_{$row.name}" data-toggle="tab">{$row.title}</a></li>
						{/foreach}
					</ul>

					<div class="tab-content">
						{foreach $pagesGroup as $group => $row}
							{assign post_key "select_all_{$row.name}"}
							{assign classname "visible_{$row.name}"}
							<div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
								<div class="checkbox checkbox-all">
									<label>
										<input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="select_all_{$classname}" id="select_all_{$classname}"{if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'} checked{/if}> {lang key='select_all_in_tab'}
									</label>
								</div>

								{foreach $pages as $key => $page}
									{if $page.group == $group}
										<div class="checkbox">
											<label>
												<input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}" id="page_{$key}"{if in_array($page.name, $item.pages)} checked{/if}> {$page.title|escape:'html'}
											</label>
										</div>
										{if $page.suburl}
											<div class="subpages" style="display:none" rel="{$page.suburl}::{$key}">&nbsp;</div>
											<input type="hidden" name="subpages[{$page.name}]" value="{if isset($item.subpages[$page.name])}{$item.subpages[$page.name]}{elseif isset($smarty.post.subpages[$page.name])}{$smarty.post.subpages[$page.name]}{/if}" id="subpage_{$key}">
										{/if}
									{/if}
								{/foreach}
							</div>
						{/foreach}
					</div>

					<div class="checkbox checkbox-all">
						<label>
							<input type="checkbox" value="1" name="select_all" id="js-pages-select-all"{if isset($smarty.post.select_all) && $smarty.post.select_all == '1'} checked{/if}> {lang key='select_all'}
						</label>
					</div>
				</div>
			</div>

			<div id="pages" class="row" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='pages_contains'}</label>

				<div class="col col-lg-4">
					<div class="checkbox">
						<label>
							<input type="checkbox" value="1" name="all_pages" id="all_pages"{if isset($smarty.post.all_pages) && $smarty.post.all_pages == '1'} checked{/if}> {lang key='select_all'}
						</label>
					</div>

					<ul class="nav nav-tabs">
						{foreach $pagesGroup as $group => $row}
							<li{if $row@iteration == 1} class="active"{/if}><a href="#tab-pages_{$row.name}" data-toggle="tab">{$row.title}</a></li>
						{/foreach}
					</ul>

					<div class="tab-content">
						{foreach $pagesGroup as $group => $row}
							{assign post_key "all_pages_{$row.name}"}
							{assign classname "pages_{$row.name}"}
							<div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
								<div class="checkbox">
									<label>
										<input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="{$post_key}" id="{$post_key}"{if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'} checked{/if}>
									</label>
								</div>

								{foreach $pages as $key => $page}
									{if $page.group == $group}
									<div class="checkbox">
										<label>
											<input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}"{if in_array($page.name, $menuPages, true)} checked{/if}>
											{if empty($page.title)}{$page.name}{else}{$page.title}{/if}
										</label>
									</div>
									{/if}
								{/foreach}
							</div>
						{/foreach}
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='block_contents'}</h4>
			</div>

			<div class="wrap-row" id="blocks_contents" style="display: none;">
				<div class="row" id="js-external-row">
					<label class="col col-lg-2 control-label">{lang key='external_file'}</label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$item.external name='external'}
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='title'}</label>

					<div class="col col-lg-4">
						<input type="text" name="title" value="{if !is_array($item.title)}{$item.title|escape:'html'}{/if}">
					</div>
				</div>

				<div class="row" id="js-multilingual-content-row">
					<label class="col col-lg-2 control-label">{lang key='contents'}</label>

					<div class="col col-lg-8">
						<textarea name="content" id="multi_contents" rows="8" class="js-ckeditor">{$item.content|escape:'html'}</textarea>
					</div>
				</div>

				<div class="row" id="external_filename">
					<label class="col col-lg-2 control-label">{lang key='filename'}</label>

					<div class="col col-lg-4">
						<input type="text" name="filename" value="{if isset($item.filename) && !empty($item.filename)}{$item.filename|escape:'html'}{elseif isset($smarty.post.filename)}{$smarty.post.filename|escape:'html'}{/if}">
						{if iaCore::ACTION_ADD == $pageAction}
							<p class="help-block">{lang key='filename_notification'}</p>
						{/if}
					</div>
				</div>
			</div>

			<div class="wrap-row" id="blocks_contents_multi" style="display: none;">
				{foreach $core.languages as $code => $language}
					<div id="blocks_contents_{$code}" class="wrap-row">
						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='title'} <span class="label label-info">{$language.title}</span></label>

							<div class="col col-lg-4">
								<input type="text" name="titles[{$code}]" value="{if isset($item.titles.$code)}{$item.titles.$code|escape:'html'}{/if}">
							</div>
						</div>

						<div class="row">
							<label class="col col-lg-2 control-label">{lang key='contents'} <span class="label label-info">{$language.title}</span></label>

							<div class="col col-lg-8">
								<textarea name="contents[{$code}]" id="contents_{$code}" rows="8" class="js-ckeditor resizable">{if isset($item.contents.$code)}{$item.contents.$code|escape:'html'}{/if}</textarea>
							</div>
						</div>
					</div>
				{/foreach}
			</div>
		</div>

		{include file='fields-system.tpl'}
	</div>
</form>
{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/blocks'}