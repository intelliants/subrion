<?php

function smarty_function_html_radio_switcher(array $params)
{
    $name = $params['name'];
    $value = $params['value'];
    $id = empty($params['id']) ? $name : $params['id'];

    if (isset($params['conf'])) {
        $name = 'v[' . $name . ']';
    }

    $attr = $value ? ' checked' : '';
    if (isset($params['disabled'])) {
        $attr .= ' disabled';
    }

    echo <<<OUT
<input type="hidden" value="0" name="{$name}">
<div class="js-input-switch make-switch" id="switch-{$id}" data-animated="false" data-on="success" data-off="danger" data-on-label="&lt;i class='i-checkmark'&gt;&lt;/i&gt;" data-off-label="&lt;i class='i-close'&gt;&lt;/i&gt;">
	<input type="checkbox" name="{$name}" id="{$id}" value="{$value}"{$attr}>
</div>
OUT;
}
