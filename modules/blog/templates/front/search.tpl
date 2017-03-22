<div class="ia-items blogroll">
    {foreach $entries as $entry}
        <div class="ia-item">
            {if $entry.image}
                <a href="{$smarty.const.IA_URL}blog/{$entry.id}-{$entry.alias}"
                   class="ia-item__image">{ia_image file=$entry.image title=$entry.title}</a>
            {/if}
            <div class="ia-item__content">
                <h4 class="ia-item__title">
                    <a href="{$smarty.const.IA_URL}blog/{$entry.id}-{$entry.alias}">{$entry.title|escape}</a>
                </h4>
                <div class="ia-item__additional">
                    <p>{lang key='posted_on'} {$entry.date_added|date_format:$core.config.date_format} {lang key='by'} {$entry.fullname}</p>
                </div>
                <div class="ia-item__body">{$entry.body|strip_tags|truncate:$core.config.blog_max:'...'}</div>
            </div>
        </div>
    {/foreach}
</div>