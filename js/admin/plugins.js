var synchronizeAdminMenu = function(currentPage, extensionGroups)
{
	currentPage = currentPage || 'plugins';

	$.ajax(
	{
		data: {action: 'menu', page: currentPage},
		success: function(response)
		{
			var $menuSection = $('#panel-center'),
				$menus = $(response.menus);

			if (typeof extensionGroups != 'undefined')
			{
				$.each(extensionGroups, function(i, val)
				{
					$('#menu-section-' + val + ' a').append('<span class="menu-updated animated bounceIn"></span>');
				});
			}

			$('ul', $menuSection).remove();
			$menus.appendTo($menuSection);
		},
		type: 'POST',
		url: intelli.config.admin_url + '/index/read.json'
	});
};

intelli.plugins = {
	failure: function()
	{
		intelli.notifFloatBox({msg: _t('error_saving_changes'), type: 'error', autohide: true});
	},
	markers: {installed: false, available: false},
	url: intelli.config.admin_url + '/plugins/',
	refresh: function(response)
	{
		intelli.notifFloatBox({msg: response.message, type: response.result ? 'success' : 'error', autohide: true});

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

			synchronizeAdminMenu('plugins', response.groups);
		}
	}
};

var installClick = function(record, action)
{
	if ('reinstall' != action && 'remote' == Ext.getCmp('modeFilter').getValue() && (record.get('price') != '0.00'))
	{
		intelli.notifFloatBox({msg: _t('buy_before_install'), type: 'error', autohide: true});
		return;
	}

	if (record.get('notes'))
	{
		Ext.Msg.show({title: _t('invalid_plugin_dependencies'), msg: record.get('notes'), buttons: Ext.Msg.OK, icon: Ext.Msg.WARNING});
		return;
	}

	Ext.Msg.show(
	{
		title: _t('confirm'),
		msg: _t('are_you_sure_' + action + '_plugin'),
		buttons: Ext.Msg.YESNO,
		icon: Ext.Msg.QUESTION,
		fn: function(btn)
		{
			if ('yes' != btn)
			{
				return;
			}

			var params = {name: record.get('file')};
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
				failure: intelli.plugins.failure,
				type: 'POST',
				url: intelli.plugins.url + action + '.json',
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
var helpClick = function(record)
{
	$.ajax(
	{
		url: intelli.plugins.url + 'documentation.json',
		data: {name: (record.get('file') ? record.get('file') : record.get('name'))},
		error: intelli.plugins.failure,
		success: function(response)
		{
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
		failure: intelli.plugins.failure,
		type: 'POST',
		url: intelli.plugins.url + 'install.json',
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
				data: {name: record.get('file')},
				failure: intelli.plugins.failure,
				type: 'POST',
				url: intelli.plugins.url + 'uninstall.json',
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
		{name: 'title', title: _t('title'), width: 1, renderer: function(value, metadata, record)
		{
			return value + ' ' + ((record.get('price') && record.get('price') != '0.00') ? ' - $' + record.get('price') : '');
		}},
		{name: 'version', title: _t('version'), width: 70, sortable: false},
		{name: 'compatibility', title: _t('compatibility'), width: 70, renderer: function(value, metadata, record)
		{
			return '<span style="color:' + (record.get('install') ? 'green' : 'red') + ';">' + value + '</span>';
		}, sortable: false},
		{name: 'author', title: _t('author'), width: 120, sortable: false},
		{name: 'date', title: _t('date'), width: 105, sortable: false},
		{name: 'info', title: _t('documentation'), icon: 'info', click: helpClick},
		{name: 'install', title: _t('install'), icon: 'box-add', click: installClick}
	],
	expanderTemplate: '{description}',
	fields: ['file', 'description', 'price'],
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
	sorters: [{property: 'title', direction: 'ASC'}],
	statusBar: false,
	storeParams: {type: 'local'},
	target: 'js-grid-available'
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
					}, {
						xtype: 'button',
						text: '<i class="i-search"></i> ' + _t('search'),
						id: 'search2',
						handler: function()
						{
							var title = Ext.getCmp('availableFilter').getValue();

							if ('' != title)
							{
								intelli.available.store.getProxy().extraParams.filter = title;
								intelli.available.store.loadPage(1);
							}
						}
					}, {
						text: '<i class="i-close"></i> ' + _t('reset'),
						id: 'reset2',
						handler: function()
						{
							var type = Ext.getCmp('modeFilter').getValue();

							Ext.getCmp('availableFilter').reset();

							intelli.available.store.getProxy().extraParams = {type: type};
							intelli.available.store.loadPage(1);
						}
					},'->',_t('mode') + ':',{
						xtype: 'combo',
						typeAhead: true,
						editable: false,
						store: new Ext.data.SimpleStore({fields: ['value', 'title'], data: [['local', _t('local')], ['remote', _t('remote')]]}),
						value: 'local',
						displayField: 'title',
						valueField: 'value',
						id: 'modeFilter',
						listeners: {
							change: function()
							{
								var isLocalMode = ('remote' == this.getValue());

								intelli.available.grid.getView().getHeaderCt().gridDataColumns[0].setVisible(!isLocalMode);
								intelli.available.grid.columns[6].setVisible(!isLocalMode);

								intelli.available.grid.columns[5].sortable = isLocalMode;

								intelli.available.store.getProxy().extraParams.type = this.getValue();
								intelli.available.store.loadPage(1);
							}
						}
					}]});

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
			{name: 'date', title: _t('date'), width: 170},
			'status',
			{name: 'upgrade', title: _t('upgrade'), icon: 'box-remove', click: upgradeClick},
			{name: 'config', title: _t('go_to_config'), icon: 'cog', href: intelli.config.admin_url + '/configuration/{value}'},
			{name: 'manage', title: _t('manage_entries'), icon: 'th-list', href: intelli.config.admin_url + '/{value}'},
			{name: 'info', title: _t('documentation'), icon: 'info', click: helpClick},
			{name: 'reinstall', title: _t('reinstall_plugin'), icon: 'loop', click: installClick},
			{name: 'uninstall', title: _t('uninstall'), icon: 'remove', click: uninstallClick}
		],
		expanderTemplate: '{summary}',
		fields: ['file', 'summary'],
		resizer: false,
		sorters: [{property: 'date', direction: 'DESC'}],
		storeParams: {type: 'installed'},
		target: 'js-grid-installed'
	}, false);
	intelli.installed.toolbar = new Ext.Toolbar({items:[
	{
		xtype: 'textfield',
		id: 'installedFilter',
		width: 220,
		emptyText: _t('title'),
		listeners: intelli.gridHelper.listener.specialKey
	}, {
		xtype: 'button',
		text: '<i class="i-search"></i> ' + _t('search'),
		id: 'fltBtn',
		handler: function()
		{
			intelli.installed.store.getProxy().extraParams.filter = Ext.getCmp('installedFilter').getValue();
			intelli.installed.store.loadPage(1);
		}
	}, {
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