<form method="post" class="form-inline ia-form page__search">
	{preventCsrf}

	<div class="search-bar">
		<input class="input-block-level" type="text" name="q" value="{$search.query|escape:'html'}">
		<button class="btn btn-primary" type="submit">{lang key='search'}</button>
		{if !$adv}
			<a href="{$smarty.const.IA_URL}advsearch/{if isset($search.id) && $search.id}?id={$search.id}{/if}" class="btn btn-link">{lang key='switch_to_advanced_search'}</a>
		{else}
			<a href="{$smarty.const.IA_URL}search/{if isset($search.id) && $search.id}?id={$search.id}{/if}" class="btn btn-link">{lang key='switch_to_regular_search'}</a>
		{/if}
	</div>

	{if $adv}
		<div class="search-items">
			<div class="row-fluid">
				{foreach $items as $item}
					<div class="span4">
						<label class="checkbox">
							<input type="checkbox" name="items[]" value="{$item}"{if $search.terms.items && $item|array_key_exists:$search.terms.items} checked{/if}>
							{lang key=$item default=$item}
						</label>
					</div>
			
					{if $item@iteration % 3 == 0}
						</div>
						<div class="row-fluid">
					{/if}
				{/foreach}
			</div>
		</div>

	    <div class="search-pane">
			{foreach $fields as $k => $item_fields}
				<div class="search-pane-fieldset" id="{$k}_fields">
					<h3 class="title">{lang key=$k default=$k}</h3>
					<div class="content">
						{foreach $item_fields as $f}
							{assign title "field_{$f.name}"}
							<div class="control-group">
								<label class="control-label">{lang key=$title}</label>
								<div class="controls">
									<div class="row-fluid">
										{assign fieldType $f.type}
										<div class="span4">
											<select class="input-block-level" name="cond[{$f.item}][{$f.name}]">
												{foreach $conditions.$fieldType as $value => $cond}
													<option value="{$value}"{if isset($f.cond) && $cond == $f.cond} selected{/if}>{$cond}</option>
												{/foreach}
											</select>
										</div>
										<div class="span8">
											{if 'combo' == $fieldType}
												<select class="input-block-level" name="f[{$f.item}][{$f.name}][]" multiple="multiple">
													{foreach $f.values as $key => $val}
														<option value="{$key}"{if isset($f.val) && is_array($f.val) && ($key|in_array:$f.val || isset($val) && $val|in_array:$f.val)} selected{/if}>{$val}</option>
													{/foreach}
												</select>
											{elseif 'radio' == $fieldType}
												{foreach $f.values as $key => $val}
													<label class="radio horizontal">
														<input type="radio" name="f[{$f.item}][{$f.name}]" value="{$key}"{if isset($f.val) && ($key == $f.val || isset($val) && $val == $f.val)} checked{/if}>
														{$val}
													</label>
												{/foreach}
											{elseif 'checkbox' == $fieldType}
												{foreach $f.values as $key => $val}
													<label class="checkbox horizontal">
														<input type="checkbox" name="f[{$f.item}][{$f.name}][]" value="{$key}"{if isset($f.val) && is_array($f.val) && ($key|in_array:$f.val || isset($val) && $val|in_array:$f.val)} checked{/if}>
														{$val}
													</label>
												{/foreach}
											{elseif 'image' == $fieldType || 'storage' == $fieldType}
												<input type="hidden" name="f[{$f.item}][{$f.name}]" value="">
											{else}
												<input class="input-block-level" type="text" name="f[{$f.item}][{$f.name}]" value="{if isset($f.val)}{$f.val}{/if}">
											{/if}
										</div>
									</div>
								</div>
							</div>
						{/foreach}
					</div>
				</div>
			{/foreach}
			<div class="actions">
				<button class="btn btn-primary" type="submit">{lang key='search'}</button> 
			</div>
		</div>
	{/if}
</form>

{if $search && $results}
	{foreach $results as $key => $item}
		<div class="search-results">
			<h3 class="title">{lang key=$key}</h3>
			{$item}
		</div>
	{/foreach}

	{navigation aTotal=$atotal aTemplate=$atemplate aItemsPerPage=$limit aNumPageItems=5}

	{ia_add_js}
$(function()
{
	var search = '{$search.query|escape:'html'}';
	if (search.length > 0)
	{
		var patt = new RegExp('('+search+')', 'mgi');

		$('.search-results :not(:has(div,span,p,td,table,a,img)):not(legend):visible:not(br)')
		.filter('div,p,td,span,a')
		.each(function()
		{
			var text = $(this).text();
			if (patt.exec(text))
			{
				text = text.replace(patt, '<span class="highlight">$1</span>');
				$(this).html(text); 
			}
		});
	}
});
	{/ia_add_js}
{elseif $search}
	<div class="message alert">{lang key='nothing_found'}</div>
{/if}

{ia_print_js files='frontend/search'}