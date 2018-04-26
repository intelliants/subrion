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

            <div class="row" id="js-patterns" style="display: none;">
                <label class="col col-lg-2 control-label">{lang key='available_patterns'}</label>
                <div class="col col-lg-4"></div>
            </div>

            <div class="row" id="row-subject">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                    <div class="btn-group btn-group-xs translate-group-actions">
                        <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-subject"><span class="i-earth"></span></button>
                        <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-subject"><span class="i-copy"></span></button>
                    </div>
                    {/if}
                    <label class="control-label">{lang key='subject'}</label>
                </div>
                <div class="col col-lg-4">
                    <div class="translate-group" id="language-group-subject">
                        <div class="translate-group__default">
                            <div class="translate-group__item">
                                <input type="text" name="subject_{$core.masterLanguage.iso}" id="input-subject-{$core.masterLanguage.iso}" disabled>
                                {if count($core.languages) > 1}<div class="translate-group__item__code">{$core.masterLanguage.title|escape}</div>{/if}
                            </div>
                        </div>
                        <div class="translate-group__langs">
                            {foreach $core.languages as $iso => $language}
                                {if $iso != $core.masterLanguage.code}
                                    <div class="translate-group__item">
                                        <input type="text" name="subject_{$iso}" id="input-subject-{$iso}" disabled>
                                        <span class="translate-group__item__code">{$language.title|escape}</span>
                                    </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col col-lg-2">
                    {if count($core.languages) > 1}
                    <div class="btn-group btn-group-xs translate-group-actions">
                        <button type="button" class="btn btn-default js-edit-lang-group" data-group="#language-group-body"><span class="i-earth"></span></button>
                        <button type="button" class="btn btn-default js-copy-lang-group" data-group="#language-group-body"><span class="i-copy"></span></button>
                    </div>
                    {/if}
                    <label class="control-label">{lang key='body'}</label>
                </div>
                <div class="col col-lg-8">
                    <div class="translate-group" id="language-group-body">
                        <div class="translate-group__default">
                            <div class="translate-group__item">
                                {ia_wysiwyg name="body_{$core.masterLanguage.iso}" source=true entities=false basicEntities=false}
                                {if count($core.languages) > 1}<div class="translate-group__item__code">{$core.masterLanguage.title|escape}</div>{/if}
                        </div>
                        <div class="translate-group__langs">
                            {foreach $core.languages as $iso => $language}
                                {if $iso != $core.masterLanguage.iso}
                                <div class="translate-group__item">
                                    {ia_wysiwyg name="body_{$iso}" source=true entities=false basicEntities=false}
                                    <span class="translate-group__item__code">{$language.title|escape}</span>
                                </div>
                                {/if}
                            {/foreach}
                        </div>
                    </div>
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
        <li><strong>{literal}{$siteName}{/literal}</strong> - <span>{$core.config.site}</span></li>
        <li><strong>{literal}{$siteUrl}{/literal}</strong> - <span>{$smarty.const.IA_URL}</span></li>
        <li><strong>{literal}{$siteEmail}{/literal}</strong> - <span>{$core.config.site_email}</span></li>
    </ul>
</div>
{ia_add_media files='js:admin/email-templates'}