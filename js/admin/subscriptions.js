Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		var grid = new IntelliGrid(
		{
			columns: [
				'selection',
				{name: 'reference_id', title: _t('reference_id'), width: 1},
				{name: 'user', title: _t('member'), width: 150},
				{name: 'plan', title: _t('plan'), width: 150},
				'status'
			],
			statuses: ['active','pending','canceled','failed']
		}, false);

		grid.toolbar = new Ext.Toolbar({items:[
		{
			xtype: 'textfield',
			name: 'reference_id',
			emptyText: _t('reference_id'),
			listeners: intelli.gridHelper.listener.specialKey
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('status'),
			name: 'status',
			store: grid.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			handler: function(){intelli.gridHelper.search(grid)},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(grid, true)},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		grid.init();
	}
});