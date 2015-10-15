Ext.onReady(function()
{
	if ($('#js-grid-placeholder').length)
	{
		intelli.items = {
			columns: [
				'selection',
				{name: 'id', title: _t('id'), width: 35},
				{name: 'item', title: _t('item_name'), width: 1, editor: 'text'},
				{name: 'package', title: _t('package'), width: 120},
				{name: 'table_name', title: _t('table_name'), width: 180, editor: 'text'},
				{name: 'class_name', title: _t('class_name'), width: 180, editor: 'text'},
				'update',
				'delete'
			],
			frame: true,
			url: intelli.config.admin_url + '/items/'
		};

		intelli.items = new IntelliGrid(intelli.items, false);

		intelli.items.init();
	}
	else
	{
		$('input[name="browsable"]').change(function()
		{
			var obj = $('#url_alias_wrap');
			$(this).val() == 1 ? obj.show() : obj.hide();
		}).change();
	}
});