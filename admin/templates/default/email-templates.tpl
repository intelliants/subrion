<form method="post" id="js-email-template-form" class="sap-form form-horizontal">
    {preventCsrf}

    <div class="wrap-list">
        <div class="wrap-group">
            <div class="wrap-group-heading">{lang key='configuration'}</div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-name">{lang key='email'}</label>
                <div class="col col-lg-4">
                    <select id="input-name" name="name">
                        <option value="">{lang key='_select_'}</option>
                        {foreach $templates as $entry}
                            {if $entry.divider}
                                {if isset($previousGroup)}
                                    </optgroup>
                                {/if}
                                <optgroup label="{lang key="email_template_{$entry.name}"}">
                                {$previousGroup = $entry.name}
                            {else}
                                <option value="{$entry.name}">{lang key="email_template_{$entry.name}"}</option>
                            {/if}
                        {/foreach}
                        </optgroup>
                    </select>
                </div>
            </div>

            <div class="row" id="input-active" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='enable_template_sending'}</label>
                <div class="col col-lg-4">
                    {html_radio_switcher value=1 name='active'}
                </div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label" for="input-subject">{lang key='subject'}</label>
                <div class="col col-lg-4">
                    <input type="text" name="subject" id="input-subject" disabled>
                </div>
            </div>

            <div class="row" id="js-patterns" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='available_patterns'}</label>
                <div class="col col-lg-4"></div>
            </div>

            <div class="row">
                <label class="col col-lg-2 control-label">{lang key='body'}</label>
                <div class="col col-lg-8">
                    {ia_wysiwyg name='body'}
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="submit" class="btn btn-primary" disabled>{lang key='save'}</button>
    </div>
</form>

<div class="x-hidden template-tags" id="template-tags">
    <p class="help-block">{lang key='email_templates_tags_info'}</p>

    <h4>{lang key='common'}</h4>
    <ul class="js-tags">
        <li><a href="#">{literal}{$siteName}{/literal}</a> - <span>{$core.config.site}</span></li>
        <li><a href="#">{literal}{$siteUrl}{/literal}</a> - <span>{$smarty.const.IA_URL}</span></li>
        <li><a href="#">{literal}{$siteEmail}{/literal}</a> - <span>{$core.config.site_email}</span></li>
    </ul>
</div>
{ia_add_media files='js:admin/email-templates'}