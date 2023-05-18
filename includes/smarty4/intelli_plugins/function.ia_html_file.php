<?php

function smarty_function_ia_html_file($params, &$smarty)
{
    $browse = iaLanguage::get('browse');
    $title = iaLanguage::get('title');
    $placeholder = isset($params['value']) && $params['value'] ? $params['value'] : iaLanguage::get('file_click_to_upload');

    $id = isset($params['id']) ? $params['id'] : $params['name'];
    $multiple = isset($params['multiple']) ? $params['multiple'] : false;

    if (!$multiple) {
        $result = <<<OUT
<div class="upload-group">
	<div class="input-group file-upload">
		<input type="hidden" name="v[{$params['name']}]">
		<input type="file" name="{$params['name']}" id="{$id}">
		<input type="text" class="disabled" placeholder="{$placeholder}" disabled>
		<span class="input-group-btn">
			<a class="btn btn-primary js-file-browse" href="#">{$browse}</a>
		</span>
	</div>
</div>
OUT;
    } else {
        $max_num  = isset($params['max_num']) ? $params['max_num'] : 0;
        $hidden = ($max_num < 1) ? ' style="display: none;"' : '';

        $title_html = '';
        if (isset($params['title'])) {
            $title_html = <<<TITLE
<div class="input-group">
	<span class="input-group-addon">{$title}:</span>
	<input type="text" name="{$params['name']}_title[]" class="file-title">
</div>
TITLE;
        }

        $result = <<<OUT
<div class="upload-group" id="upload-group-{$id}">
	<div class="file-upload"{$hidden}>
		{$title_html}
		<div class="input-group">
			<input type="file" name="{$params['name']}[]">
			<input type="text" class="disabled" disabled="disabled" placeholder="{$placeholder}">
			<span class="input-group-btn">
				<a class="btn btn-primary js-file-browse" href="#">{$browse}</a>
				<a class="btn btn-primary js-file-add" href="#"><i class="i-plus-alt"></i></a>
				<a class="btn btn-primary js-file-remove" href="#"><i class="i-minus-alt"></i></a>
			</span>
		</div>
	</div>
	<input type="hidden" value="{$max_num}" id="{$id}">
</div>
OUT;
    }

    return $result;
}
