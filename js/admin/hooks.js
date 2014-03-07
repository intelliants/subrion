Ext.onReady(function()
{
	intelli.hooks = new IntelliGrid(
	{
		columns:
		[
			'numberer',
			{name: 'name', title: _t('name'), width: 1, editor: 'text'},
			{name: 'extras', title: _t('extras'), width: 130, editor: 'text'},
			{name: 'type', title: _t('type'), width: 100, editor: Ext.create('Ext.form.ComboBox',
			{
				typeAhead: true,
				editable: false,
				lazyRender: true,
				store: Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: [['php', 'PHP'],['smarty', 'Smarty'],['html', 'HTML'],['plain', 'Plain Text']]}),
				displayField: 'title',
				valueField: 'value'
			})},
			{name: 'order', title: _t('order'), width: 50, editor: 'text'},
			'status',
			{name: 'filename', title: _t('filename'), width: 120, editor: 'text'},
			{name: 'open', title: _t('edit'), icon: 'pencil', click: function(record, field)
			{
				$.post(intelli.config.admin_url + '/hooks.json', {action: 'get', hook: record.get('id')}, function(response)
				{
					$('.wrap-list').show();
					editAreaLoader.openFile('codeContainer', {id: record.get('id'), text: response.code, syntax: record.get('type'), title: record.get('name') + ' | ' + record.get('extras')});
				});
			}},
			'delete'
		],
		url: intelli.config.admin_url + '/hooks/'
	}, false);

	intelli.hooks.toolbar = Ext.create('Ext.Toolbar', {items:[
	{
		emptyText: _t('name'),
		xtype: 'textfield',
		name: 'name',
		listeners: intelli.gridHelper.listener.specialKey
	},{
		emptyText: _t('extras'),
		xtype: 'combo',
		typeAhead: true,
		editable: false,
		store: Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: intelli.config.extras}),
		displayField: 'title',
		name: 'item',
		valueField: 'value'
	},{
		emptyText: _t('type'),
		xtype: 'combo',
		typeAhead: true,
		editable: false,
		store: Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: [['php', 'PHP'],['smarty', 'Smarty'],['html', 'HTML'],['plain', 'Plain Text']]}),
		displayField: 'title',
		name: 'type',
		valueField: 'value'
	},{
		handler: function(){intelli.gridHelper.search(intelli.hooks)},
		id: 'fltBtn',
		text: '<i class="i-search"></i> ' + _t('search')
	},{
		handler: function(){intelli.gridHelper.search(intelli.hooks, true)},
		text: '<i class="i-close"></i> ' + _t('reset')
	}]});

	intelli.hooks.init();

	editAreaLoader.init(
	{
		id: 'codeContainer',
		syntax: 'php',
		start_highlight: true,
		allow_resize: 'yes',
		min_height: 300,
		toolbar: 'save, search, go_to_line, |, undo, redo',
		save_callback: 'saveHook',
		allow_toggle: false
	});

	$('#js-save-cmd').click(function()
	{
		var code = editAreaLoader.getValue('codeContainer');
		var save_hook = editAreaLoader.getCurrentFile('codeContainer').id;
		saveHook(save_hook, code);
	});

	$('#js-close-cmd').click(function()
	{
		var hooks = editAreaLoader.getAllFiles('codeContainer');
		if (hooks)
		{
			var hook;
			for (hook in hooks)
			{
				editAreaLoader.closeFile('codeContainer', hook);
			}
		}
	});
});

function saveHook(id, code)
{
	$.post(intelli.config.admin_url + '/hooks.json', {action: 'set', hook: editAreaLoader.getCurrentFile('codeContainer').id, code: code}, function()
	{
		intelli.notifFloatBox({msg: _t('saved'), type: 'notification', autohide: true});
	});
}