<div id="js-add-phrase-dialog-placeholder" style="margin:0px;height:0px;overflow:hidden;">{preventCsrf}</div>

{if 'list' == $action}
	<div class="widget widget-default">
		<div class="widget-content">
			<table cellspacing="0" cellpadding="0" class="table table-light table-hover">
				<thead>
					<tr>
						<th>{lang key='language'}</th>
						<th>{lang key='iso_code'}</th>
						<th>{lang key='default'}</th>
						<th>{lang key='edit_phrases'}</th>
						<th>{lang key='download'}</th>
						<th>{lang key='delete'}</th>
					</tr>
				</thead>
				<tbody>
					{foreach $languages as $code => $language}
						<tr>
							<td>{$language}</td>
							<td>{$code}</td>
							<td>
								{if $code != $config.lang}
									<a class="btn btn-success btn-xs" href="{$url}languages/default/{$code}/"><i class='i-checkmark'></i></a>
								{else}
									<span class="btn btn-xs disabled"><i class='i-checkmark'></i></span>
								{/if}
							</td>
							<td>
								<a class="btn btn-default btn-xs" href="{$url}languages/phrases/?language={$code}"><i class="i-pencil"></i> {lang key='edit'}</a>
							</td>
							<td>
								<a class="btn btn-default btn-xs" href="{$url}languages/download/{$code}/"><i class="i-box-add"></i> {lang key='download'}</a>
							</td>
							<td>
								{if $languages|count != 1 && $code != $config.lang}
								<a class="btn btn-danger btn-xs js-remove-lang-cmd" href="{$url}languages/rm/{$code}/"><i class='i-close'></i> {lang key='delete'}</a>
								{/if}
							</td>
						</tr>
					{/foreach}
				</tbody>
			</table>
		</div>
	</div>
{elseif 'phrases' == $action}
	{include file='grid.tpl'}
{elseif 'download' == $action}
	<form method="post" class="sap-form form-horizontal">
		{preventCsrf}
		<div class="wrap-list">
			<div class="wrap-group">
				<div class="wrap-group-heading"><h4>{lang key='download'}</h4></div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='language'}</label>
					<div class="col col-lg-4">
						<select name="lang">
						{foreach $languages as $code => $pre_lang}
							<option value="{$code}">{$pre_lang}</option>
						{/foreach}
						</select>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='file_format'}</label>
					<div class="col col-lg-4">
						<select name="file_format">
							<option value="csv"{if isset($smarty.post.file_format) && $smarty.post.file_format == 'csv'} selected{/if}>{lang key='csv_format'}</option>
							<option value="sql"{if isset($smarty.post.file_format) && $smarty.post.file_format == 'sql'} selected{/if}>{lang key='sql_format'}</option>
						</select>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='filename'}</label>
					<div class="col col-lg-4">
						<input type="text" name="filename" value="{if isset($smarty.post.filename) && $smarty.post.filename}{$smarty.post.filename|escape:'html'}{else}subrion_{$smarty.const.IA_VERSION}_{$smarty.const.IA_LANGUAGE}{/if}">
					</div>
				</div>
			</div>
		</div>
		<div class="form-actions">
			<input type="submit" class="btn btn-success" value="{lang key='download'}">
		</div>
	</form>

	<form action="{$smarty.const.IA_ADMIN_URL}languages/import/" method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
		{preventCsrf}
		<div class="wrap-list">
			<div class="wrap-group">
				<div class="wrap-group-heading"><h4>{lang key='import'}</h4></div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='file_format'}</label>
					<div class="col col-lg-4">
						<select name="format">
							<option value="csv"{if isset($smarty.post.format) && $smarty.post.format == 'csv'} selected="selected"{/if}>{lang key='csv_format'}</option>
							<option value="sql"{if isset($smarty.post.format) && $smarty.post.format == 'sql'} selected="selected"{/if}>{lang key='sql_format'}</option>
						</select>
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='title'}</label>
					<div class="col col-lg-4">
						<input type="text" name="title">
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='import_from_pc'}</label>
					<div class="col col-lg-4">
						{ia_html_file name='language_file'}
					</div>
				</div>
				<div class="row">
					<label class="col col-lg-2 control-label">{lang key='import_from_server'}</label>
					<div class="col col-lg-4">
						<input type="text" name="language_file2" value="../updates/">
					</div>
				</div>
			</div>
		</div>
		<div class="form-actions">
			<input type="submit" class="btn btn-success" value="{lang key='import'}" name="form-import">
		</div>
	</form>
{elseif 'copy' == $action}
	<form method="post" class="form-inline">
		{preventCsrf}
		<div class="wrap-list">
			<div class="wrap-group">
				<div class="wrap-group-heading"><h4>{lang key='copy_default_language_to' lang=$languages[$config.lang]}</h4></div>
				
				<div class="row">
					<div class="form-group col-md-2">
						<label for="input-code">{lang key='iso_code'}</label>
						<input id="input-code" size="2" class="form-control" maxlength="2" type="text" name="code"value="{if isset($smarty.post.code)}{$smarty.post.code|escape:'html'}{/if}">
					</div>

					<div class="form-group col-md-3">
						<label for="input-title">{lang key='title'}</label>
						<input id="input-title" size="10" class="form-control" maxlength="40" type="text" name="title" value="{if isset($smarty.post.title)}{$smarty.post.title|escape:'html'}{/if}">
					</div>

					<div class="form-group col-md-2">
						<label>&nbsp;</label>
						<button type="submit" class="btn btn-success btn-block" name="form-copy">{lang key='copy_language'}</button>
					</div>
				</div>
			</div>
		</div>

		<div class="widget widget-default">
			<div class="widget-content">
				<table cellspacing="0" cellpadding="0" class="table table-light table-hover">
					<thead>
						<tr>
							<th width="15%">{lang key='language'}</th>
							<th width="80%">{lang key='iso_code'}</th>
							<th width="100px" class="text-right">{lang key='default'}</th>
						</tr>
					</thead>
					<tbody>
						{foreach $languages as $code => $language}
							<tr>
								<td>{$language}</td>
								<td>{$code}</td>
								<td class="text-right">
									{if $code == $config.lang}
										<span class="btn btn-xs disabled"><i class='i-checkmark'></i></span>
									{/if}
								</td>
							</tr>
						{/foreach}
					</tbody>
				</table>
			</div>
		</div>
	</form>
{elseif 'comparison' == $action}
	{if count($languages) > 1}
		{ia_add_media files='css:grid-extra'}
		<div id="js-legend-panel" style="display: none;">
			<p><span style="background-color: #e0f2f7; display: inline-block; height: 20px; width: 40px;"></span> Identical phrases pair</p>
			<p><span style="background-color: #eaeaea; display: inline-block; height: 20px; width: 40px;"></span> Incomplete phrases pair</p>
		</div>
		<div id="js-comparison-grid"></div>
	{/if}
{/if}