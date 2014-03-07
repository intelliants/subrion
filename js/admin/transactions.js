intelli.transactions = {
	columns: [
		'selection',
		{name: 'username', title: _t('username'), width: 1},
		{name: 'plan_title', title: _t('plan'), width: 120, renderer: function(value, metadata, record)
		{
			return (value && record.get('plan_id') > 0)
				? '<b><a href="' + intelli.config.admin_url + '/plans/edit/?id=' + record.get('plan_id') + '">' + value + '</a></b>'
				: '<b>' + value + '</b>';
		}},
		{name: 'item', title: _t('item'), width: 100},
		{name: 'item_id', title: _t('item_id'), width: 60},
		{name: 'email', title: _t('email'), width: 150, editor: 'text'},
		{name: 'order_number', title: _t('order_number'), editor: 'text', width: 100},
		{name: 'total', title: _t('total'), width: 80},
		'status',
		{name: 'date', title: _t('date'), width: 120},
		'delete'
	],
	fields: ['plan_id'],
	statuses: ['pending', 'passed', 'failed', 'refunded'],
	texts:{
		delete_single: _t('are_you_sure_to_delete_this_transaction'),
		delete_multiple: _t('are_you_sure_to_delete_transactions')
	},
	url: intelli.config.admin_url + '/transactions/'
};

intelli.visual = {form: null, panel: null};

Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		var searchParam = intelli.urlVal('status');
		if (searchParam)
		{
			intelli.transactions.storeParams = {status: searchParam};
		}

		intelli.transactions = new IntelliGrid(intelli.transactions, false);
		intelli.transactions.toolbar = Ext.create('Ext.Toolbar', {items:
		[
			{
				xtype: 'textfield',
				name: 'username',
				emptyText: _t('username'),
				listeners: intelli.gridHelper.listener.specialKey
			}, {
				xtype: 'textfield',
				name: 'order_number',
				emptyText: _t('order_number'),
				listeners: intelli.gridHelper.listener.specialKey
			}, {
				emptyText: _t('item'),
				xtype: 'combo',
				typeAhead: true,
				editable: false,
				store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/transactions.json?get=items'),
				displayField: 'title',
				name: 'item',
				valueField: 'value'
			}, {
				emptyText: _t('status'),
				name: 'status',
				id: 'fltStatus',
				xtype: 'combo',
				typeAhead: true,
				editable: false,
				store: intelli.transactions.stores.statuses,
				displayField: 'title',
				valueField: 'value'
			}, {
				handler: function(){intelli.gridHelper.search(intelli.transactions)},
				id: 'fltBtn',
				text: '<i class="i-search"></i> ' + _t('search')
			}, {
				handler: function(){intelli.gridHelper.search(intelli.transactions, true)},
				text: '<i class="i-close"></i> ' + _t('reset')
			}
		]});

		if (searchParam)
		{
			Ext.getCmp('fltStatus').setValue(searchParam);
		}

		intelli.transactions.init();
	}

	$('#js-add-transaction-cmd').bind('click', function(e)
	{
		e.preventDefault();

		if (!intelli.visual.panel)
		{
			var items = new Ext.form.ComboBox(
			{
				fieldLabel: _t('item'),
				typeAhead: true,
				allowBlank: false,
				width: '100%',
				editable: false,
				id: 'item_name',
				name: 'item_name',
				lazyRender: true,
				store: intelli.gridHelper.store.ajax(intelli.transactions.url + 'read.json?get=items'),
				displayField: 'title',
				listeners: {
					select: function(combo, record, index)
					{
						var itemname = combo.getValue();

						('members' == itemname) ? Ext.getCmp('itemid').hide() : Ext.getCmp('itemid').show();

						Ext.getCmp('item_plan').clearValue();

						Ext.getCmp('item_plan').getStore().baseParams = {itemname: itemname};
						Ext.getCmp('item_plan').getStore().reload({params: {itemname: itemname}});
					}
				}
			});

			var plans = new Ext.form.ComboBox(
			{
				fieldLabel: _t('plan'),
				typeAhead: true,
				allowBlank: false,
				width: '100%',
				id: 'item_plan',
				editable: false,
				name: 'plan',
				lazyRender: true,
				store: intelli.gridHelper.store.ajax(intelli.transactions.url + 'read.json?get=plans'),
				displayField: 'title'
			});

			var gateways = new Ext.form.ComboBox(
			{
				fieldLabel: _t('payment_gateway'),
				typeAhead: true,
				allowBlank: true,
				width: '100%',
				editable: false,
				name: 'payment',
				lazyRender: true,
				store: intelli.gridHelper.store.ajax(intelli.transactions.url + 'read.json?get=gateways'),
				displayField: 'title'
			});

			var members = new Ext.form.ComboBox(
			{
				fieldLabel: _t('member'),
				typeAhead: true,
				allowBlank: false,
				width: '100%',
				editable: true,
				name: 'username',
				lazyRender: true,
				store: intelli.gridHelper.store.ajax(intelli.transactions.url + 'read.json?get=members'),
				displayField: 'title'
			});

			var dateFields = [
				new Ext.form.DateField(
				{
					name: 'date',
					editable: true,
					format: 'Y-m-d',
					width: 100
				}),
				new Ext.form.TimeField(
				{
					name: 'time',
					editable: true,
					format: 'H:i:s',
					increment: 30,
					width: 80
				})
			];

			intelli.visual.form = new Ext.FormPanel(
			{
				width: 400,
				autoHeight: true,
				bodyStyle: 'padding: 15px 15px 10px;',
				renderTo: Ext.getCmp('visual_panel'),
				items: [items, plans, gateways,
				{
					fieldLabel: _t('order_number'),
					name: 'order',
					width: '100%',
					allowBlank: false,
					xtype: 'textfield'
				}, members, {
					fieldLabel: _t('email'),
					name: 'email',
					width: '100%',
					vtype: 'email',
					allowBlank: false,
					xtype: 'textfield'
				}, {
					fieldLabel: _t('total'),
					name: 'total',
					width: '100%',
					allowBlank: false,
					xtype: 'textfield'
				}, {
					fieldLabel: _t('id'),
					name: 'itemid',
					allowBlank: true,
					hidden: true,
					id: 'itemid',
					width: '100%',
					xtype: 'numberfield'
				}, {
					fieldLabel: _t('date'),
					xtype: 'container',
					layout: 'column',
					width: '100%',
					defaultType: 'field',
					items: dateFields
				}]
			});

			intelli.visual.panel = new Ext.Window(
			{
				title: _t('add_transaction'),
				id: 'visual_panel',
				items: intelli.visual.form,
				closeAction: 'hide',
				buttons: [
				{
					text: _t('save'),
					autoWidth: true,
					handler: function()
					{
						var f = intelli.visual.form.getForm();

						if (f.isValid())
						{
							f.submit(
							{
								url: intelli.transactions.url + 'add.json',
								success: function(form, data)
								{
									Ext.Msg.show(
									{
										title: _t('confirm'),
										msg: _t('add_new_transaction'),
										buttons: Ext.Msg.YESNO,
										icon: Ext.Msg.QUESTION,
										fn: function(btn)
										{
											'yes' == btn || intelli.visual.panel.hide();
											f.reset();
										}
									});

									intelli.transactions.store.reload();
								},
								failure: function(form, data)
								{
									intelli.notifBox({msg: data.result.message, type: 'error', autohide: true});
								}
							});
						}
					}
				},
				{
					text: _t('cancel'),
					autoWidth: true,
					handler: function()
					{
						intelli.visual.panel.hide();
						intelli.visual.form.getForm().reset();
					}
				}]
			});
		}

		intelli.visual.panel.show();

		$('.x-form-text[name="username"]').typeahead(
		{
			source: function(query, process)
			{
				return $.ajax(
				{
					url: intelli.config.admin_url + '/members/search.json',
					type: 'get',
					dataType: 'json',
					data: {q: query},
					success: function(data)
					{
						process(data.options);
					}
				});
			}
		});
	});
});