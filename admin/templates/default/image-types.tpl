<form method="post" class="sap-form form-horizontal">
	{preventCsrf}

	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">{lang key='options'}</div>

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
				<label class="col col-lg-2 control-label">{lang key='image_width'} / {lang key='image_height'}</label>

				<div class="col col-lg-4">
					<div class="row">
						<div class="col col-lg-6">
							<input type="text" name="width" value="{if isset($item.width)}{$item.width|escape:'html'}{else}900{/if}">
						</div>
						<div class="col col-lg-6">
							<input type="text" name="height" value="{if isset($item.height)}{$item.height|escape:'html'}{else}600{/if}">
						</div>
					</div>
				</div>
			</div>

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='resize_mode'}</label>

				<div class="col col-lg-4">
					<select name="pic_resize_mode">
						<option value="crop"{if isset($item.pic_resize_mode) && iaPicture::CROP == $item.pic_resize_mode} selected{/if} data-annotation="{lang key='crop_tip'}">{lang key='crop'}</option>
						<option value="fit"{if isset($item.pic_resize_mode) && iaPicture::FIT == $item.pic_resize_mode} selected{/if} data-annotation="{lang key='fit_tip'}">{lang key='fit'}</option>
					</select>
					<p class="help-block"></p>
				</div>
			</div>

			{*<div class="row" id="use_cropper">*}
				{*<label class="col col-lg-2 control-label">{lang key='use_cropper'}</label>*}
				{*<div class="col col-lg-4">*}
					{*{html_radio_switcher value=0 name='cropper'}*}
				{*</div>*}
			{*</div>*}

			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='allowed_file_types'}</label>

				<div class="col col-lg-4">
					{foreach $imageTypes as $entry}
						<div class="checkbox">
							<label>
								<input type="checkbox" value="{$entry.id}"{if in_array($entry.id, $item.types)} checked{/if} name="imageTypes[{$entry.id}]">
								{$entry.extension}
							</label>
						</div>
					{/foreach}
				</div>
			</div>
		</div>
	</div>

	<div class="form-actions">
		<button type="submit" name="save" class="btn btn-primary">{lang key='save'}</button>
	</div>
</form>