<?php

function smarty_function_html_radio_switcher(array $params)
{
	$name = $params['name'];
	$value = $params['value'];
	$id = isset($params['id']) ? $params['id'] : $name;

	if (isset($params['conf']))
	{
		$name = 'param[' . $name . ']';
	}

	$attr = $value ? 'checked="checked"' : '';
	if (isset($params['disabled']))
	{
		$attr .= ' disabled="disabled"';
	}

	echo <<<OUT
<input type="hidden" value="0" name="{$name}">
<div class="js-input-switch make-switch" id="switch-{$id}" data-animated="false" data-on="success" data-off="danger" data-on-label="<i class='i-checkmark'></i>" data-off-label="<i class='i-close'></i>">
	<input type="checkbox" name="{$name}" id="{$id}" value="{$value}" {$attr}>
</div>
OUT;
}