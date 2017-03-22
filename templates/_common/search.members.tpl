{foreach $listings as $listing}
    {include 'list-members.tpl'}
{foreachelse}
    <div class="alert alert-info">{lang key='no_members'}</div>
{/foreach}