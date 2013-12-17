<form method="post" id="js-email-template-form" class="sap-form form-horizontal">
	<div class="wrap-list">
		<div class="wrap-group">
			<div class="wrap-group-heading">
				<h4>{lang key='configuration'}</h4>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label" for="tpl">{lang key='email'}</label>
				<div class="col col-lg-4">
					<select id="tpl" name="tpl">
						<option value="">{lang key='_select_'}</option>
						{foreach $templates as $item}
							{if 'divider' == $item.type}
								{if isset($previous_group)}
									</optgroup>
								{/if}
								<optgroup label="{$item.description}">
								{assign var='previous_group' value=$item.name}
							{else}
								<option value="{$item.name}">{$item.description}</option>
							{/if}
						{/foreach}
						</optgroup>
					</select>
				</div>
			</div>
			<div class="row" id="enable_sending" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='enable_template_sending'}</label>
				<div class="col col-lg-4">
					{html_radio_switcher value=1 name='enable_template'}
				</div>
			</div>
			<div class="row" id="use_signature" style="display: none;">
				<label class="col col-lg-2 control-label">{lang key='use_custom_signature'}</label>
				<div class="col col-lg-4">
					{html_radio_switcher value=1 name='enable_signature'}
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label" for="subject">{lang key='subject'}</label>
				<div class="col col-lg-4">
					<input type="text" name="subject" id="subject" disabled="disabled" />
				</div>
			</div>
			<div class="row">
				<label class="col col-lg-2 control-label">{lang key='body'}</label>
				<div class="col col-lg-8">
					{ia_wysiwyg value='' name='body'}
				</div>
			</div>
		</div>
	</div>
	<div class="form-actions">
		<button type="submit" class="btn btn-primary" disabled="disabled">{lang key='save'}</button>
	</div>
</form>

<div class="x-hidden template-tags" id="template-tags">
	<p class="help-block">{lang key='email_templates_tags_info'}</p>

	<h4>{lang key='common'}</h4>
	<ul class="js-tags">
		<li><a href="#">{literal}{site_title}{/literal}</a> - <span>{$config.site}</span></li>
		<li><a href="#">{literal}{site_url}{/literal}</a> - <span>{$smarty.const.IA_URL}</span></li>
		<li><a href="#">{literal}{site_email}{/literal}</a> - <span>{$config.site_email}</span></li>
	</ul>
</div>

{ia_add_media files='js:admin/email-templates'}