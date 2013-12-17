intelli.members = {
	columns: [
		'selection',
		{name: 'id', title: _t('id'), width: 35},
		{name: 'username', title: _t('username'), width: 90, editor: 'text'},
		{name: 'fullname', title: _t('fullname'), width: 1, editor: 'text'},
		{name: 'usergroup', title: _t('usergroup'), width: 150, renderer: function(value, metadata, record)
		{
			switch (record.get('usergroup_id'))
			{
				case '4':
				case '8':
					return '<span style="color: grey;">' + value + '</span>';
				case '1':
				case '2':
					return '<span style="color: green;">' + value + '</span>';
				default:
					return value;
			}
		}},
		{name: 'email', title: _t('email'), width: 180, editor: 'text'},
		'status',
		{name: 'date_reg', title: _t('date'), width: 120},
		/*
		{name: 'permissions', title: _t('permissions'), href: intelli.config.admin_url + '/permissions/?user={id}', icon: 'folder'},
		{name: 'config', title: _t('go_to_config'), href: intelli.config.admin_url + '/configuration/?user={id}', icon: 'cogs'},
		*/
		'update',
		'delete'
	],
	fields: ['usergroup_id'],
	statuses: ['active', 'approval', 'suspended', 'unconfirmed'],
	texts: {
		delete_single: _t('are_you_sure_to_delete_this_member'),
		delete_multiple: _t('are_you_sure_to_delete_selected_members')
	},
	url: intelli.config.admin_url + '/members/'
};

Ext.onReady(function()
{
	var searchParam = intelli.urlVal('q');
	if (searchParam)
	{
		intelli.members.storeParams = {name: searchParam};
	}
	searchParam = intelli.urlVal('status');
	if (searchParam)
	{
		intelli.members.storeParams = {status: searchParam};
	}

	intelli.members = new IntelliGrid(intelli.members, false);
	intelli.members.toolbar = new Ext.Toolbar({items:[
	{
		allowDecimals: false,
		allowNegative: false,
		emptyText: _t('id'),
		name: 'id',
		listeners: intelli.gridHelper.listener.specialKey,
		width: 90,
		xtype: 'numberfield'
	},{
		emptyText: 'Username, Fullname, or Email',
		id: 'fltName',
		name: 'name',
		listeners: intelli.gridHelper.listener.specialKey,
		width: 220,
		xtype: 'textfield'
	},{
		name: 'status',
		emptyText:  _t('status'),
		id: 'fltStatus',
		xtype: 'combo',
		typeAhead: true,
		editable: false,
		store: intelli.members.stores.statuses,
		width: 140,
		displayField: 'title',
		valueField: 'value'
	},{
		displayField: 'title',
		editable: false,
		emptyText: _t('usergroup'),
		name: 'usergroup_id',
		store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/usergroups/store.json'),
		typeAhead: true,
		valueField: 'value',
		width: 150,
		xtype: 'combo'
	},{
		handler: function(){intelli.gridHelper.search(intelli.members)},
		id: 'fltBtn',
		text: '<i class="i-search"></i> ' + _t('search')
	},{
		handler: function(){intelli.gridHelper.search(intelli.members, true)},
		text: '<i class="i-close"></i> ' + _t('reset')
	}]});

	if (searchParam)
	{
		Ext.getCmp('fltStatus').setValue(searchParam);
	}
	searchParam = intelli.urlVal('q');
	if (searchParam)
	{
		Ext.getCmp('fltName').setValue(searchParam);
	}

	intelli.members.init();
});