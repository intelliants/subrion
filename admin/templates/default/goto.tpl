<label>{lang key='and_then'}</label>
<select name="goto" class="goto-actions">
    {foreach $goto as $action => $name}
        <option value="{$action}"{if isset($smarty.post.goto) && $smarty.post.goto == $action} selected{/if}>{lang key=$name default=$name}</option>
    {/foreach}
</select>