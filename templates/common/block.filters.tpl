{if isset($filters.item)}
	<form class="ia-form ia-form-filters" id="js-item-filters-form" data-item="{$filters.item}" action="{$smarty.const.IA_CLEAR_URL}search/{$filters.item}.json">
		{if $member}
			<div class="ia-form-filters__actions">
				<a href="{$smarty.const.IA_URL}search/my/" class="btn btn-xs btn-success" data-loading-text="{lang key='loading'}" id="js-cmd-open-searches">{lang key='my_searches'}</a>
				<div class="modal fade" id="js-modal-searches" tabindex="-1" role="dialog">
					<div class="modal-dialog" role="document"><div class="modal-content"></div></div>
				</div>
				{if isset($regular)}
					<button type="button" class="btn btn-xs btn-default" id="js-cmd-save-search">{lang key='save_this_search'}</button>
				{/if}
			</div>
		{/if}

		{ia_hooker name='smartyFrontFiltersBeforeFields'}

		{foreach $filters.fields as $field}
			{if !empty($filters.params[$field.name])}
				{$selected = $filters.params[$field.name]}
			{else}
				{$selected = null}
			{/if}
			<div class="form-group">
				<label>{lang key="field_{$field.name}"}</label>
				{switch $field.type}
					{case iaField::CHECKBOX break}
						<div class="radios-list">
							{html_checkboxes assign='checkboxes' name=$field.name options=$field.values separator='</div>' selected=$selected}
							<div class="checkbox">{'<div class="checkbox">'|implode:$checkboxes}
						</div>
					{case iaField::COMBO break}
						<select class="form-control js-interactive" name="{$field.name}[]" multiple>
							{if !empty($field.values)}
								{html_options options=$field.values selected=$selected}
							{/if}
						</select>

					{case iaField::RADIO break}
						{if iaField::COMBO == $field.show_as}
							<select class="form-control js-interactive" name="{$field.name}[]" multiple>
								{if !empty($field.values)}
									{html_options options=$field.values selected=$selected}
								{/if}
							</select>
						{else}
							<div class="radios-list">
								{if !empty($field.values)}
									{html_radios assign='radios' name=$field.name id=$field.name options=$field.values separator='</div>'}
									<div class="radio">{'<div class="radio">'|implode:$radios}
								{/if}
							</div>
						{/if}
					{case iaField::STORAGE}
					{case iaField::IMAGE}
					{case iaField::PICTURES break}
						<input type="checkbox" name="{$field.name}" value="1"{if $selected} checked{/if}>

					{case iaField::NUMBER break}
						<div class="row">
							<div class="col-md-6">
								<label class="ia-form__label-mini">{lang key='from'}</label>
								<input class="form-control" type="text" name="{$field.name}[f]" maxlength="{$field.length}" placeholder="{$field.range[0]}"{if $selected} value="{$selected.f|escape:'html'}"{/if}>
							</div>
							<div class="col-md-6">
								<label class="ia-form__label-mini">{lang key='to'}</label>
								<input class="form-control" type="text" name="{$field.name}[t]" maxlength="{$field.length}" placeholder="{$field.range[1]}"{if $selected} value="{$selected.t|escape:'html'}"{/if}>
							</div>
						</div>

					{case iaField::TEXT}
					{case iaField::TEXTAREA break}
						<input class="form-control" type="text" name="{$field.name}"{if is_string($selected)} value="{$selected|escape:'html'}"{/if}>

					{case iaField::TREE}
						<select class="form-control" name="{$field.name}[]" multiple>
							<option value="">&lt;{lang key='any'}&gt;</option>
							{if !empty($field.values)}
								{html_options options=$field.values selected=$selected}
							{/if}
						</select>
				{/switch}
			</div>
		{/foreach}

		{ia_hooker name='smartyFrontFiltersAfterFields'}
	</form>
	{ia_add_media files='select2, js:intelli/intelli.search, js:frontend/search'}
{/if}