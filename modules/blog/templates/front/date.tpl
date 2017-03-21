{if isset($show['years'])}
    <table class="table table-bordered archive">
        {foreach $years as $y => $year}
            {if $year@first}<tr>{/if}
            <td>
                <h3><a class="title" href="{$smarty.const.IA_SELF}{$y}/">{$y}</a></h3>
                <ul class="unstyled">
                    {foreach $year.months as $m => $month}
                        {if isset($month.blogs)}
                            <li><a href="{$smarty.const.IA_SELF}{$y}/{$m}/">{lang key=$month.name}</a></li>
                        {else}
                            <li>{lang key=$month.name}</li>
                        {/if}
                    {/foreach}
                </ul>
            </td>
            {if $year@iteration is div by 4}</tr><tr>{/if}
            {if $year@last}</tr>{/if}
        {/foreach}
    </table>
{/if}

{if isset($show['months'])}
    <table class="table table-bordered archive">
        {foreach $months as $m => $month}
            {if $month@first}<tr>{/if}
            <td>
                {if isset($month.blogs)}
                    <a href="{$smarty.const.IA_SELF}{$m}/">{lang key=$month.name}</a>
                {else}
                    {lang key=$month.name}
                {/if}
            </td>
            {if $month@iteration is div by 4}</tr><tr>{/if}
            {if $month@last}</tr>{/if}
        {/foreach}
    </table>
{/if}

{if isset($blogs)}
    <div class="ia-items blogroll">
        {foreach $blogs as $blog_entry}
            <div class="media ia-item">
                {if $blog_entry.image}
                    <a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}"
                       class="pull-left ia-item-thumbnail">{ia_image file=$blog_entry.image width=150 title=$blog_entry.title class='media-object'}</a>
                {/if}
                <div class="media-body">
                    <h4 class="media-heading">
                        <a href="{$smarty.const.IA_URL}blog/{$blog_entry.id}-{$blog_entry.alias}">{$blog_entry.title|escape}</a>
                    </h4>
                    <p class="ia-item-date">{lang key='posted_on'} {$blog_entry.date_added|date_format:$core.config.date_format} {lang key='by'} {$blog_entry.fullname}</p>
                    <div class="ia-item-body">{$blog_entry.body|strip_tags|truncate:$core.config.blog_max:'...'}</div>
                </div>
            </div>
        {/foreach}
    </div>

    {navigation aTotal=$pagination.total aTemplate=$pagination.template aItemsPerPage=$core.config.blog_number aNumPageItems=5}
{/if}
