<div class="modal-header">
	<button type="button" class="close" data-dismiss="modal" aria-label="{lang key='close'}"><span aria-hidden="true">&times;</span></button>
	<h4 class="modal-title">{lang key='my_saved_searches'}</h4>
</div>
<div class="modal-body">
	{if $searches}
		<table class="table table-condensed">
			{foreach $searches as $entry}
				<tr>
					<td width="100"><small>{$entry.date|date_format:$core.config.date_format}</small></td>
					<td><a href="{$smarty.const.IA_URL}{$entry.params}" title="{lang key='open_in_new_tab'}" target="_blank">{$entry.title|escape:'html'|default:"<em>{lang key='untitled'}</em>"}</a></td>
					<td width="60"><small>{lang key=$entry.item}</small></td>
				</tr>
			{/foreach}
		</table>
	{else}
		<p>{lang key='no_items'}</p>
	{/if}
</div>
<div class="modal-footer">
	<button type="button" class="btn btn-default" data-dismiss="modal">{lang key='close'}</button>
</div>
