Ext.onReady(function()
{
	if ($('#js-grid-placeholder').length)
	{
		intelli.plans = new IntelliGrid(
		{
			columns: [
				'selection',
				{name: 'title', title: _t('title'), sortable: false, width: 1},
				{name: 'item', title: _t('item'), width: 100},
				{name: 'cost', title: _t('cost'), width: 100},
				{name: 'days', title: _t('days'), editor: 'text', width: 70},
				{name: 'order', title: _t('order'), editor: 'text', width: 70},
				'status',
				'update',
				'delete'
			],
			texts: {delete_single: _t('are_you_sure_to_delete_this_plan')},
			url: intelli.config.admin_url + '/plans/'
		});
	}
	else
	{
		var checkAll = true;
		$('input[name="fields[]"]').each(function()
		{
			if (!$(this).prop('checked')) checkAll = false;
		});

		$('#check_all_fields')
		.prop('checked', checkAll)
		.click(function()
		{
			var checked = $(this).prop('checked');
			$('input[name="fields[]"]').each(function()
			{
				$(this).prop('checked', checked);
			});
		});

		$('select[name="item"]').change(function()
		{
			$('.js-fields-list, .js-items-list').hide();
			$('#js-fields-empty').hide();

			var value = $(this).val();
			if ('' == value) value = 'empty';

			$('#js-fields-' + value).show();
			$('#js-item-' + value).show();
		}).change();

		$('textarea.cked').each(function()
		{
			intelli.ckeditor($(this).attr('id'), {toolbar: 'Simple', height: '200px'});
		});
	}
});