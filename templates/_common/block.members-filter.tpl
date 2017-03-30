{if !empty($letters)}
    <div class="members-filter">
        {include 'ia-alpha-sorting.tpl' letters=$letters url="{$smarty.const.IA_URL}members/"}
    </div>
{/if}

{if !empty($usergroups)}
    <hr>
    <div class="group-filter">
        <h4>{lang key='group_filter'}</h4>
        <ul class="nav nav-pills nav-stacked">
            <li{if !$activeGroup} class="active"{/if}><a href="{$smarty.const.IA_SELF}?group=all">{lang key='all_groups'}</a></li>
            {foreach $usergroups as $entryId => $name}
                <li{if $entryId == $activeGroup} class="active"{/if}><a href="{$smarty.const.IA_SELF}?group={$entryId}" rel="nofollow">{lang key="usergroup_{$name}"}</a></li>
            {/foreach}
        </ul>
    </div>
{/if}