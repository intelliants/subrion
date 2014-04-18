<form method="post" enctype="multipart/form-data" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='options'}</h4>
			</div>
			
			<div class="row">
				<label class="col col-lg-2 control-label" for="input-language">{lang key='language'}</label>
				<div class="col col-lg-4">
					<select name="lang" id="input-language"{if $languages|@count == 1} disabled="disabled"{/if}>
					{foreach $languages as $code => $language}
						<option value="{$code}"{if $entry.lang == $code} selected="selected"{/if}>{$language}</option>
					{/foreach}
					</select>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-title">{lang key='title'}</label>
				<div class="col col-lg-4">
					<input type="text" name="title" value="{$entry.title|escape:'html'}" id="input-title">
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-alias">{lang key='title_alias'}</label>
				<div class="col col-lg-4">
					<input type="text" name="alias" id="input-alias" value="{if isset($entry.alias)}{$entry.alias}{/if}">
					<p class="help-block text-break-word" id="title_box" style="display: none;">{lang key='page_url_will_be'}: <span id="title_url" class="text-danger">{$smarty.const.IA_URL}</span></p>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="body">{lang key='body'}</label>
				<div class="col col-lg-8">
					{ia_wysiwyg name='body' value=$entry.body}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-image">{lang key='image'}</label>
				<div class="col col-lg-4">
					{if isset($entry.image) && $entry.image}
					<div class="input-group thumbnail thumbnail-single with-actions">
						<a href="{printImage imgfile=$entry.image fullimage=true url=true}" rel="ia_lightbox">
							{printImage imgfile=$entry.image}
						</a>

						<div class="caption">
							<a class="btn btn-small btn-danger" href="javascript:void(0);" title="{lang key='delete'}" onclick="return intelli.admin.removeFile('{$entry.image}', this, 'blog_entries', 'image', '{$entry.id}')"><i class=" i-remove-sign"></i></a>
						</div>
					</div>
					{/if}

					{ia_html_file name='image' id='input-image'}
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-date">{lang key='date'}</label>
				<div class="col col-lg-4">
					<div class="input-group">
						<input type="text" class="js-date-field" name="date" id="input-date" value="{$entry.date}">
						<span class="input-group-addon js-datetimepicker-toggle"><i class="i-calendar"></i></span>
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label" for="input-status">{lang key='status'}</label>
				<div class="col col-lg-4">
					<select name="status" id="input-status">
						<option value="active"{if 'active' == $entry.status} selected="selected"{/if}>{lang key='active'}</option>
						<option value="inactive"{if 'inactive' == $entry.status} selected="selected"{/if}>{lang key='inactive'}</option>
					</select>
				</div>
			</div>
		</div>

		<div class="form-actions inline">
			<button type="submit" name="save" class="btn btn-primary">{if $pageAction == 'edit'}{lang key='save_changes'}{else}{lang key='add'}{/if}</button>
			{include file='goto.tpl'}
		</div>
	</div>
</form>

{ia_add_media files='datepicker' order=2}
{ia_add_media files='js:_IA_URL_plugins/personal_blog/js/admin/index'}