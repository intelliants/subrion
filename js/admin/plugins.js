intelli.plugins = {
	failure: function()
	{
		intelli.notifFloatBox({msg: _t('error_saving_changes'), type: 'error', autohide: true});
	},
	markers: {installed: false, available: false},
	url: intelli.config.admin_url + '/plugins/',
	refresh: function(response)
	{
		intelli.notifFloatBox({msg: response.message, type: response.result ? 'notif' : 'error', autohide: true});

		if (response.result)
		{
			$('#js-grid-installed:visible').length
				? intelli.installed.store.reload()
				: intelli.plugins.markers.installed = true;

			if (intelli.available.config)
			{
				$('#js-grid-available:visible').length
					? intelli.available.store.reload()
					: intelli.plugins.markers.available = true;
			}

			intelli.admin.synchronizeAdminMenu();
		}
	}
};

var installClick = function(record, field)
{
	if (record.get('notes'))
	{
		Ext.Msg.show({title: _t('invalid_plugin_dependencies'), msg: record.get('notes'), buttons: Ext.Msg.OK, icon: Ext.Msg.WARNING});
		return;
	}

	Ext.Msg.show(
	{
		title: _t('confirm'),
		msg: _t('are_you_sure_' + field + '_plugin'),
		buttons: Ext.Msg.YESNO,
		icon: Ext.Msg.QUESTION,
		fn: function(btn)
		{
			if ('yes' != btn)
			{
				return;
			}

			var params = {action: field, name: record.get('file')};
			if (Ext.getCmp('modeFilter'))
			{
				if ('remote' == Ext.getCmp('modeFilter').getValue())
				{
					params.mode = 'remote';
				}
			}

			$.ajax(
			{
				data: params,
				dataType: 'json',
				failure: intelli.plugins.failure,
				type: 'POST',
				url: intelli.plugins.url + 'read.json',
				success: intelli.plugins.refresh
			});
		}
	});
};
/*
var installRequest = function()
{
	if (intelli.install_data.length >= 1)
	{
		var params = intelli.install_data.shift();

		Ext.Ajax.request(
		{
			url: intelli.plugins.url + 'read.json',
			method: 'POST',
			params: params,
			failure: function()
			{
				var msg = _t('error_install_plugin');

				Ext.MessageBox.alert(msg.replace(/:plugin/, params.extra));
				installRequest();
			},
			success: function(data)
			{
				var response = Ext.decode(data.responseText);
				var type = response.error ? 'error' : 'notif';
				var msg = '';
				if (intelli.install_data.length > 0)
				{
					msg = _t('plugins_left');
					msg = msg.replace(/:count/, intelli.install_data.length);
				}
				intelli.admin.notifBox({msg: response.msg + '<br />' + msg, type: type, autohide: false});	
				installRequest();
			}
		});
	}
	else
	{
		intelli.plugins.refresh();
	}
};

var install_click_multiple = function()
{
	var rows = intelli.available.grid.getSelectionModel().getSelections();
	intelli.install_data = [];
	for (var i = 0; i < rows.length; i++)
	{
		intelli.install_data.push({action:'install', extra: rows[i].json.file});
	}

	installRequest();
};
*/
var helpClick = function(record, field)
{
	Ext.Ajax.request(
	{
		url: intelli.plugins.url + 'read.json',
		method: 'GET',
		params: {get: 'info', name: (record.get('file') ? record.get('file') : record.get('name'))},
		failure: intelli.plugins.failure,
		success: function(data)
		{
			var response = Ext.decode(data.responseText);
			if (response.tabs)
			{
				var win = new Ext.Window(
				{
					title: _t('extra_documentation'),
					closable: true,
					width: 800,
					height: 550,
					border: false,
					plain: true,
					layout: 'border',
					items:
					[
						new Ext.TabPanel(
						{
							region: 'center',
							bodyStyle: 'padding: 5px;',
							activeTab: 0,
							defaults: {autoScroll: true},
							items: response.tabs
						}),
						new Ext.Panel(
						{
							region: 'east',
							split: true,
							minWidth: 200,
							collapsible: true,
							html: response.info,
							bodyStyle: 'padding: 5px;'
						})
					]
				});

				win.show();
			}
			else
			{
				intelli.notifFloatBox({msg: _t('doc_extra_not_available'), type: 'error', autohide: true});
			}
		}
	});
};

var upgradeClick = function(record, field)
{
	$.ajax(
	{
		data: {action: 'install', name: record.get('file')},
		dataType: 'json',
		failure: intelli.plugins.failure,
		type: 'POST',
		url: intelli.plugins.url + 'read.json',
		success: intelli.plugins.refresh
	});
};

var uninstallClick = function(record, field)
{
	Ext.Msg.show(
	{
		title: _t('confirm'),
		msg: _t('are_you_sure_to_uninstall_selected_plugin'),
		buttons: Ext.Msg.YESNO,
		icon: Ext.Msg.QUESTION,
		fn: function(btn)
		{
			if ('yes' != btn)
			{
				return;
			}

			$.ajax(
			{
				data: {action: 'uninstall', name: record.get('file')},
				dataType: 'json',
				failure: intelli.plugins.failure,
				type: 'POST',
				url: intelli.plugins.url + 'read.json',
				success: intelli.plugins.refresh
			});
		}
	});
};

intelli.available = {
	columns: [
		'numberer',
		'selection',
		'expander',
		{name: 'title', title: _t('title'), width: 1},
		{name: 'version', title: _t('version'), width: 80, sortable: false},
		{name: 'compatibility', title: _t('compatibility'), width: 80, renderer: function(value, metadata, record)
		{
			return '<span style="color:' + (record.get('install') ? 'green' : 'red') + ';">' + value + '</span>';
		}},
		{name: 'author', title: _t('author'), width: 130},
		{name: 'date', title: _t('date'), width: 130},
		{name: 'info', title: _t('documentation'), icon: 'info', click: helpClick},
		{name: 'install', title: _t('install'), icon: 'box-add', click: installClick}
	],
	expanderTemplate: '{description}',
	fields: ['file', 'description'],
	resizer: false,
/*	rowselect: function(that)
	{
		var mode = Ext.getCmp('modeFilter').getValue();
		if ('local' == mode)
		{
			Ext.getCmp('multi_install_btn').enable();
		}
	},
	rowdeselect: function(that)
	{
		var rows = that.grid.getSelectionModel().getSelections();
		if (rows.length == 0) Ext.getCmp('multi_install_btn').disable();
	},*/
	sort: 'date',
	sortDir: 'DESC',
	statusBar: false,
	storeParams: {type: 'available', mode: 'local'},
	target: 'js-grid-available',
	url: intelli.plugins.url
};

Ext.onReady(function()
{
	$('a', '.nav-tabs').on('shown.bs.tab', function(e)
	{
		switch ($(this).attr('href'))
		{
			case '#tab-installed':
				if (intelli.plugins.markers.installed)
				{
					intelli.plugins.markers.installed = false;
					
					intelli.installed.store.reload();
					intelli.installed.grid.doLayout();
				}
				break;
			case '#tab-available':
				if (!intelli.available.config)
				{
					intelli.available = new IntelliGrid(intelli.available, false);
	/*				intelli.available.bottomBar = [
					 {
					 xtype: 'button',
					 text: _t('refresh'),
					 iconCls: 'x-tbar-loading',
					 handler: function()
					 {
					 intelli.available.dataStore.reload();
					 }
					 },
					 {
					 xtype: 'button',
					 text: _t('install'),
					 id: 'multi_install_btn',
					 disabled: true,
					 icon: intelli.config.ia_url + '/admin/templates/default/img/icons/install-grid-ico.png',
					 handler: install_click_multiple
					 }];*/
					intelli.available.toolbar = new Ext.Toolbar({items:[
					{
						xtype: 'textfield',
						id: 'availableFilter',
						width: 220,
						emptyText: _t('title'),
						listeners: {
							specialkey: function(field, e)
							{
								if (e.ENTER == e.getKey()) Ext.getCmp('search2').handler();
							}
						}
					},
					' ' + _t('mode') + ':',
					{
						xtype: 'combo',
						typeAhead: true,
						editable: false,
						store: new Ext.data.SimpleStore({fields: ['value', 'title'], data: [['local', _t('local')], ['remote', _t('remote')]]}),
						value: 'local',
						displayField: 'title',
						valueField: 'value',
						id: 'modeFilter'
					},{
						xtype: 'button',
						text: '<i class="i-search"></i> ' + _t('search'),
						id: 'search2',
						handler: function()
						{
							var title = Ext.getCmp('availableFilter').getValue();
							var mode = Ext.getCmp('modeFilter').getValue();

							if ('' != title || '' != mode)
							{
								if ('remote' == mode)
								{
									intelli.available.grid.getView().getHeaderCt().gridDataColumns[0].setVisible(false);
									intelli.available.grid.columns[6].setVisible(false);

									//Ext.getCmp('multi_install_btn').disable();
								}
								else
								{
									intelli.available.grid.getView().getHeaderCt().gridDataColumns[0].setVisible(true);
									intelli.available.grid.columns[6].setVisible(true);
								}

								intelli.available.grid.columns[1].sortable =
								intelli.available.grid.columns[2].sortable =
								intelli.available.grid.columns[3].sortable =
								intelli.available.grid.columns[4].sortable =
								intelli.available.grid.columns[5].sortable
									= ('remote' != mode);

								intelli.available.store.getProxy().extraParams.filter = title;
								intelli.available.store.getProxy().extraParams.mode = mode;
								intelli.available.store.loadPage(1);
							}
						}
					},{
						text: _t('reset'),
						handler: function()
						{
							Ext.getCmp('availableFilter').reset();
							Ext.getCmp('modeFilter').setValue('local');

							intelli.available.grid.getView().getHeaderCt().gridDataColumns[0].setVisible(true);
							intelli.available.grid.columns[6].setVisible(true);

							intelli.available.store.getProxy().extraParams = {type: 'available', mode: 'local'};
							intelli.available.store.loadPage(1);
						}}
					]});
					intelli.available.init();
				}

				if (intelli.plugins.markers.available)
				{
					intelli.plugins.markers.available = false;

					intelli.available.store.reload();
					intelli.available.grid.doLayout();
				}
		}
	});

	intelli.installed = new IntelliGrid(
	{
		columns: [
			'numberer',
			'expander',
			{name: 'version', title: _t('version'), width: 70, align: 'center', sortable: false},
			{name: 'title', title: _t('title'), width: 1},
			{name: 'date', title: _t('date'), width: 150},
			'status',
			{name: 'upgrade', title: _t('upgrade'), icon: 'box-remove', click: upgradeClick},
			{name: 'config', title: _t('go_to_config'), icon: 'cog', href: intelli.config.admin_url + '/configuration/{value}'},
			{name: 'manage', title: _t('go_to_manage'), icon: 'th-list', href: intelli.config.admin_url + '/{value}'},
			{name: 'info', title: _t('documentation'), icon: 'info', click: helpClick},
			{name: 'reinstall', title: _t('reinstall_plugin'), icon: 'loop', click: installClick},
			{name: 'uninstall', title: _t('uninstall'), icon: 'remove', click: uninstallClick}
		],
		expanderTemplate: '{summary}',
		fields: ['file', 'summary'],
		resizer: false,
		sort: 'date',
		sortDir: 'DESC',
		storeParams: {type: 'installed'},
		target: 'js-grid-installed',
		url: intelli.plugins.url
	}, false);
	intelli.installed.toolbar = new Ext.Toolbar({items:[
	{
		xtype: 'textfield',
		id: 'installedFilter',
		width: 220,
		emptyText: _t('title'),
		listeners: intelli.gridHelper.listener.specialKey
	},{
		xtype: 'button',
		text: '<i class="i-search"></i> ' + _t('search'),
		id: 'fltBtn',
		handler: function()
		{
			intelli.installed.store.getProxy().extraParams.filter = Ext.getCmp('installedFilter').getValue();
			intelli.installed.store.loadPage(1);
		}
	},{
		text: '<i class="i-close"></i> ' + _t('reset'),
		handler: function()
		{
			Ext.getCmp('installedFilter').reset();
			intelli.installed.store.getProxy().extraParams = {type: 'installed'};
			intelli.installed.store.loadPage(1);
		}
	}]});
	intelli.installed.init();
});