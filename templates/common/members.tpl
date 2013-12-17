{if $letters}
	<div id="account-letters">
		<div class="accounts-sorting block movable">
			<div class="user-type">
				<form method="get" id="sort_form" class="form-inline">
					<select name="account_by" onchange="$('#sort_form').submit()">
						<option value="fullname"{if $filter == 'fullname'} selected="selected"{/if}>{lang key='by_fullname'}</option>
						<option value="username"{if $filter == 'username'} selected="selected"{/if}>{lang key='by_username'}</option>
					</select>
					&nbsp;<b>{lang key='starts_with'}</b>
				</form>
			</div>

			{include file='ia-alpha-sorting.tpl' letters=$letters url="{$smarty.const.IA_URL}members/"}
		</div>
	</div>
{/if}

{if $members}
	{include file='accounts-items.tpl' all_items=$members all_item_fields=$fields all_item_type='members'}

	{navigation aTotal=$pagination.total aTemplate=$pagination.url aItemsPerPage=$pagination.limit aNumPageItems=5 aTruncateParam=1}
{else}
	<div class="alert alert-info">{lang key='no_members'}</div>
{/if}