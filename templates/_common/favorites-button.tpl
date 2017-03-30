<a href="#" class="js-favorites {$replace.class}"
    data-item="{$replace.item}"
    data-id="{$replace.id}"
    data-action="{$replace.action}"
    data-guests="{$replace.guests}"
    data-text-add="<span class='fa fa-heart-o'></span>"
    data-text-delete="<span class='fa fa-heart'></span>"
    rel="nofollow" title="{lang key=$replace.text}">
    {if 'add' == $replace.action}<span class='fa fa-heart-o'></span>{else}<span class='fa fa-heart'></span>{/if}</a>