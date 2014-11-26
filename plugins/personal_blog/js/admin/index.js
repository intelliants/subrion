Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		var grid = new IntelliGrid(
		{
			columns: [
				'selection',
				{name: 'title', title: _t('title'), width: 2, editor: 'text'},
				{name: 'alias', title: _t('title_alias'), width: 220},
				'status',
				{name: 'date_added', title: _t('date'), width: 120, editor: 'date'},
				'update',
				'delete'
			],
			sorters: [{property: 'date_added', direction: 'DESC'}]
		}, false);

		grid.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			emptyText: _t('text'),
			name: 'text',
			listeners: intelli.gridHelper.listener.specialKey,
			width: 275,
			xtype: 'textfield'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('status'),
			id: 'fltStatus',
			name: 'status',
			store: grid.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			handler: function(){intelli.gridHelper.search(grid);},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(grid, true);},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});
		grid.init();

		var searchStatus = intelli.urlVal('status');
		if (searchStatus)
		{
			Ext.getCmp('fltStatus').setValue(searchStatus);
			intelli.gridHelper.search(grid);
		}
	}
	else
	{
		$('#input-title, #input-alias').on('blur', function()
		{
			var alias = $('#input-alias').val();
			var title = alias != '' ? alias : $('#input-title').val();

			if ('' != title)
			{
				$.get(intelli.config.admin_url + '/blog/read.json', {get: 'alias', title: title}, function(data)
				{
					if ('' != data.url)
					{
						$('#title_url').text(data.url);
						$('#title_box').fadeIn();
					}
					else
					{
						$('#title_box').hide();
					}
				});
			}
			else
			{
				$('#title_box').hide();
			}
		});
	}
});