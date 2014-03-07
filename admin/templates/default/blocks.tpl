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
					{if 'add' == $pageAction}
						<input type="text" name="name" value="{if isset($block.name)}{$block.name}{elseif isset($smarty.post.name)}{$smarty.post.name}{/if}">
						<p class="help-block">{lang key='unique_name'}</p>
					{else}
						<input type="text" disabled="disabled" value="{$block.name}">
					{/if}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='type'}</label>

				<div class="col col-lg-4">
					{if 'menu' != $block.type}
						<select name="type" id="block_type">
						{foreach $types as $key => $type}
							{if 'menu' != $type}
								{access object='admin_pages' id='blocks' action=$type}
								<option value="{$type}"{if isset($block.type) && $block.type == $type} selected="selected"{elseif isset($smarty.post.type) && $smarty.post.type == $type} selected="selected"{/if}>{$type}</option>
								{/access}
							{/if}
						{/foreach}
						</select>
					{else}
						{$block.type}
					{/if}
					<p class="help-block" id="type_tip_plain" style="display: none;">{lang key='block_type_tip_plain'}</p>
					<p class="help-block" id="type_tip_html" style="display: none;">{lang key='block_type_tip_html'}</p>
					<p class="help-block" id="type_tip_smarty" style="display: none;">{lang key='block_type_tip_smarty'}</p>
					<p class="help-block" id="type_tip_php" style="display: none;">{lang key='block_type_tip_php'}</p>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='position'}</label>

				<div class="col col-lg-4">
					<select name="position">
						{foreach $positions as $key => $position}
							<option value="{$position}" {if isset($block.position) && $block.position == $position}selected="selected"{elseif isset($smarty.post.position) && $smarty.post.position == $position}selected="selected"{/if}>{$position}</option>
						{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='status'}</label>

				<div class="col col-lg-4">
					<select name="status">
						<option value="active" {if isset($block.status) && $block.status == 'active'}selected="selected"{/if}>{lang key='active'}</option>
						<option value="inactive" {if isset($block.status) && $block.status == 'inactive'}selected="selected"{/if}>{lang key='inactive'}</option>
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='css_class_name'}</label>

				<div class="col col-lg-4">
					<input type="text" name="classname" value="{if isset($block.classname)}{$block.classname}{elseif isset($smarty.post.classname)}{$smarty.post.classname}{/if}">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='show_header'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$block.header name='header'}
				</div>
			</div>

			<div class="row" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='collapsible'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$block.collapsible name='collapsible'}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='multi_language'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$block.multi_language name='multi_language'}
				</div>
			</div>

			<div class="row" id="languages" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='language'}</label>

				<div class="col col-lg-4">
					<div class="checkbox">
						<label>
							<input type="checkbox" id="select_all_languages" name="select_all_languages" value="1" {if isset($smarty.post.select_all) && $smarty.post.select_all == '1'}checked="checked"{/if}> {lang key='select_all'}
						</label>
					</div>

					{foreach $languages as $code => $pre_lang}
						<div class="checkbox">
							<label>
								<input type="checkbox" class="block_languages" name="block_languages[]" value="{$code}" {if isset($block.block_languages) && in_array($code, $block.block_languages)}checked="checked"{elseif isset($smarty.post.block_languages) && in_array($code, $smarty.post.block_languges)}checked="checked"{/if}> {$pre_lang}
							</label>
						</div>
					{/foreach}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='sticky'}</label>

				<div class="col col-lg-4">
					{html_radio_switcher value=$block.sticky name='sticky'}
				</div>
			</div>

			<div class="row" id="acos" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='visible_on_pages'}</label>

				<div class="col col-lg-8">
				{if isset($pages_group) && !empty($pages_group)}
					{if isset($pages) && !empty($pages)}
						<ul class="nav nav-tabs">
							{foreach $pages_group as $group => $row}
								{assign var='classname' value='visible_'|cat:$row.name}
								<li{if $row@iteration == 1} class="active"{/if}><a href="#tab-{$classname}" data-toggle="tab">{$row.title}</a></li>
							{/foreach}
						</ul>

						<div class="tab-content">
							{foreach $pages_group as $group => $row}
								{assign var='post_key' value='select_all_'|cat:$row.name}
								{assign var='classname' value='visible_'|cat:$row.name}
								<div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
									<div class="checkbox checkbox-all">
										<label>
											<input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="select_all_{$classname}" id="select_all_{$classname}" {if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'}checked="checked"{/if}> {lang key='select_all_in_tab'}
										</label>
									</div>

									{foreach $pages as $key => $page}
										{if $page.group == $group}
											<div class="checkbox">
												<label>
													<input type="checkbox" name="visible_on_pages[]" class="{$classname}" value="{$page.name}" id="page_{$key}" {if in_array($page.name, $visibleOn, true)}checked="checked"{/if}> {$page.title}
												</label>
											</div>
											{if $page.suburl}
												<div class="subpages" style="display:none" rel="{$page.suburl}::{$key}">&nbsp;</div>
												<input type="hidden" name="subpages[{$page.name}]" value="{if isset($block.subpages[$page.name])}{$block.subpages[$page.name]}{elseif isset($smarty.post.subpages[$page.name])}{$smarty.post.subpages[$page.name]}{/if}" id="subpage_{$key}">
											{/if}
										{/if}
									{/foreach}
								</div>
							{/foreach}
						</div>

						<div class="checkbox checkbox-all">
							<label>
								<input type="checkbox" value="1" name="select_all" id="select_all" {if isset($smarty.post.select_all) && $smarty.post.select_all == '1'}checked="checked"{/if}> {lang key='select_all'}
							</label>
						</div>
					{/if}
				{/if}
				</div>
			</div>

			<div id="pages" class="row" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='pages_contains'}</label>

				<div class="col col-lg-4">
				{if isset($pages_group) && !empty($pages_group)}
					{if isset($pages) && !empty($pages)}
						<div class="checkbox">
							<label>
								<input type="checkbox" value="1" name="all_pages" id="all_pages" {if isset($smarty.post.all_pages) && $smarty.post.all_pages == '1'}checked="checked"{/if}> {lang key='select_all'}
							</label>
						</div>

						<ul class="nav nav-tabs">
							{foreach from=$pages_group key=group item=row}
								{assign var='classname' value='pages_'|cat:$row.name}
								<li{if $row@iteration == 1} class="active"{/if}><a href="#tab-{$classname}" data-toggle="tab">{$row.title}</a></li>
							{/foreach}
						</ul>

						<div class="tab-content">
							{foreach from=$pages_group key=group item=row}
								{assign var='post_key' value='all_pages_'|cat:$row.name}
								{assign var='classname' value='pages_'|cat:$row.name}
								<div class="tab-pane{if $row@iteration == 1} active{/if}" id="tab-{$classname}">
									<div class="checkbox">
										<label>
											<input type="checkbox" value="1" class="{$classname}" data-group="{$classname}" name="{$post_key}" id="{$post_key}" {if isset($smarty.post.$post_key) && $smarty.post.$post_key == '1'}checked="checked"{/if}>
										</label>
									</div>

									{foreach $pages as $key => $page}
										{if $page.group == $group}
										<div class="checkbox">
											<label>
												<input type="checkbox" name="pages[]" class="{$classname}" value="{$page.name}" {if in_array($page.name, $menuPages, true)}checked="checked"{/if}>
												{if empty($page.title)}{$page.name}{else}{$page.title}{/if}
											</label>
										</div>
										{/if}
									{/foreach}
								</div>
							{/foreach}
						</div>
					{/if}
				{/if}
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
				<div class="row" id="external_file_row">
					<label class="col col-lg-2 control-label">{lang key='external_file'}</label>

					<div class="col col-lg-4">
						{html_radio_switcher value=$block.external name='external'}
					</div>
				</div>

				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='title'}</label>

					<div class="col col-lg-4">
						<input type="text" name="multi_title" value="{if isset($block.title) && !is_array($block.title)}{$block.title|escape:'html'}{elseif isset($smarty.post.multi_title)}{$smarty.post.multi_title|escape:'html'}{/if}">
					</div>
				</div>

				<div class="row" id="multi_contents_row">
					<label class="col col-lg-2 control-label">{lang key='contents'}</label>

					<div class="col col-lg-8">
						<textarea name="multi_contents" id="multi_contents" rows="8" class="js-wysiwyg">{if isset($block.contents) && !is_array($block.contents)}{$block.contents}{elseif isset($smarty.post.multi_contents)}{$smarty.post.multi_contents}{/if}</textarea>
					</div>
				</div>

				<div class="row" id="external_filename">
					<label class="col col-lg-2 control-label">{lang key='filename'}</label>

					<div class="col col-lg-4">
						<input type="text" name="filename" value="{if isset($block.filename) && !empty($block.filename)}{$block.filename|escape:'html'}{elseif isset($smarty.post.filename)}{$smarty.post.filename|escape:'html'}{/if}">
						{if $pageAction == 'add'}
							<p class="help-block">{lang key='filename_notification'}</p>
						{/if}
					</div>
				</div>
			</div>

			<div class="wrap-row" id="blocks_contents_multi" style="display: none;">
				{foreach $languages as $code => $pre_lang}
				<div id="blocks_contents_{$code}" class="wrap-row">
					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='title'} <span class="label label-info">{$pre_lang}</span></label>

						<div class="col col-lg-4">
							<input type="text" name="title[{$code}]" value="{if isset($block.title) && is_array($block.title)}{if isset($block.title.$code)}{$block.title.$code|escape:'html'}{elseif isset($smarty.post.title.$code)}{$smarty.post.title.$code|escape:'html'}{/if}{/if}">
						</div>
					</div>

					<div class="row">
						<label class="col col-lg-2 control-label">{lang key='contents'} <span class="label label-info">{$pre_lang}</span></label>

						<div class="col col-lg-8">
							<textarea name="contents[{$code}]" id="contents_{$code}" rows="8" class="js-wysiwyg resizable">{if isset($block.contents) && is_array($block.contents)}{if isset($block.contents.$code)}{$block.contents.$code|escape:'html'}{elseif isset($smarty.post.contents.$code)}{$smarty.post.contents.$code|escape:'html'}{/if}{/if}</textarea>
						</div>
					</div>
				</div>
				{/foreach}
			</div>
		</div>
	</div>

	<div class="form-actions inline">
		<input type="hidden" name="do" value="{$pageAction}">
		<input type="hidden" name="id" value="{if isset($block.id)}{$block.id}{/if}">
		<input type="submit" name="data-block" class="btn btn-primary" value="{if $pageAction == 'add'}{lang key='add'}{else}{lang key='save_changes'}{/if}">

		{if $pageAction == 'add'}
			{include file='goto.tpl'}
		{/if}
	</div>
</form>

{ia_print_js files='utils/edit_area/edit_area, ckeditor/ckeditor, admin/blocks'}