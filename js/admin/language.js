Ext.onReady(function()
{
	var languages = [], j = 0;
	for (var i in intelli.languages)
	{
		languages[j++] = [i, intelli.languages[i]];
	}

	var languagesStore = new Ext.data.SimpleStore({fields: ['value','title'], data: languages});
	var categoriesStore = new Ext.data.SimpleStore(
	{
		fields: ['value','title'],
		data: [['admin', 'Administration Board'],['frontend', 'User Frontend'],['common', 'Common'],['tooltip', 'Tooltip']]
	});

	var addPhrasePanel = new Ext.FormPanel(
	{
		frame: true,
		title: _t('add_new_phrase'),
		bodyStyle: 'padding: 5px 5px 0',
		renderTo: 'js-add-phrase-dialog-placeholder',
		id: 'add_phrase_panel',
		hidden: true,
		items: [
		{
			fieldLabel: _t('key'),
			name: 'key',
			xtype: 'textfield',
			allowBlank: false,
			anchor: '40%'
		},{
			fieldLabel: _t('value'),
			name: 'value',
			xtype: 'textarea',
			allowBlank: false,
			anchor: '40%'
		},{
			fieldLabel: _t('language'),
			name: 'language',
			xtype: 'combo',
			allowBlank: false,
			editable: false,
			lazyRender: true,
			value: intelli.config.language,
			store: languagesStore,
			displayField: 'title',
			valueField: 'value'
		},{
			fieldLabel: _t('category'),
			name: 'category',
			xtype: 'combo',
			allowBlank: false,
			editable: false,
			lazyRender: true,
			value: 'admin',
			store: categoriesStore,
			displayField: 'title',
			valueField: 'value',
			listWidth: 200
		}],
		tools: [
		{
			id: 'close',
			handler: function(event, tool, panel){addPhrasePanel.hide();}
		}],
		buttons: [
		{
			text: _t('add'),
			handler: function()
			{
				addPhrasePanel.getForm().submit(
				{
					url: intelli.config.admin_url + '/language/add.json',
					method: 'POST',
					params: {prevent_csrf: $('input[name="prevent_csrf"]', '#js-add-phrase-dialog-placeholder').val()},
					failure: function(form, action)
					{
						intelli.notifBox({msg: action.result.message, type: 'error', autohide: true});
					},
					success: function(form, action)
					{
						intelli.notifBox({msg: action.result.message, type: 'notif', autohide: true});
						Ext.Msg.show(
						{
							title: _t('add_new_phrase'),
							msg: _t('add_one_more_phrase'),
							buttons: Ext.Msg.YESNO,
							fn: function(btn)
							{
								'yes' == btn || addPhrasePanel.hide();
								form.reset();
							},
							icon: Ext.MessageBox.QUESTION
						});
					}
				});
			}
		},{
			text: _t('cancel'),
			handler: function()
			{
				$('#js-add-phrase-dialog-placeholder').css('margin', '0');
				addPhrasePanel.hide();
			}
		}]
	});

	if (Ext.get('js-grid-placeholder'))
	{
		intelli.language = new IntelliGrid(
		{
			columns: [
				{name: 'key', title: _t('key'), width: 250, editor: 'text', renderer: function(value, metadata, row)
					{
						if (1 == row.data.modified)
						{
							metadata.css = 'grid-status-unconfirmed';
						}
						return value;
					}
				},
				{name: 'original', title: _t('original'), width: 250, renderer: Ext.util.Format.htmlEncode},
				{name: 'value', title: _t('value'), width: 1, editor: 'text-wide', renderer: Ext.util.Format.htmlEncode},
				{name: 'code', title: _t('language'), width: 100, hidden: true},
				{name: 'category', title: _t('category'), width: 100, editor: Ext.create('Ext.form.ComboBox',
				{
					typeAhead: true,
					editable: false,
					store: categoriesStore,
					value: 'admin',
					displayField: 'title',
					valueField: 'value'
				})},
				'delete'
			],
			fields: ['original', 'modified'],
			storeParams: {lang: intelli.urlVal('language')},
			texts: {delete_multiple: _t('are_you_sure_to_delete_selected_phrases')},
			url: intelli.config.admin_url + '/language/'
		}, false);
/*		intelli.language.bottomBar = ['-',
		{
			emptyText: _t('category'),
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			lazyRender: true,
			store: categoriesStore,
			displayField: 'title',
			disabled: true,
			id: 'categoryCmb',
			valueField: 'value'
		},
		{
			text: '<i class="i-arrow-right-2"></i> ' + _t('do'),
			disabled: true,
			id: 'goBtn',
			handler: function()
			{
				var rows = intelli.language.grid.getSelectionModel().getSelections();
				var category = Ext.getCmp('categoryCmb').getValue();
				var ids = [];

				for (var i = 0; i < rows.length; i++)
				{
					ids[i] = rows[i].json.id;
				}

				Ext.Ajax.request(
				{
					url: url + 'update.json',
					method: 'POST',
					params:
					{
						action: 'update',
						'ids[]': ids,
						field: 'category',
						value: category
					},
					failure: function()
					{
						Ext.MessageBox.alert(_t('error_saving_changes'));
					},
					success: function(data)
					{
						var response = Ext.decode(data.responseText);
						var type = response.error ? 'error' : 'notif';

						intelli.notifBox({msg: response.msg, type: type, autohide: true});

						intelli.language.grid.getStore().reload();
					}
				});
			}
		}];*/
		intelli.language.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			emptyText: _t('key'),
			listeners: intelli.gridHelper.listener.specialKey,
			name: 'key',
			xtype: 'textfield'
		},{
			emptyText: _t('value'),
			listeners: intelli.gridHelper.listener.specialKey,
			name: 'value',
			xtype: 'textfield'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('category'),
			name: 'category',
			store: new Ext.data.SimpleStore(
			{
				fields: ['value', 'title'],
				data: [['admin', 'Administration Board'],['frontend', 'User Frontend'],['common', 'Common'],['tooltip', 'Tooltip']]
			}),
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('extras'),
			name: 'extras',
			store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/language.json?get=plugins'),
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			handler: function(){intelli.gridHelper.search(intelli.language)},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(intelli.language, true)},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});
		intelli.language.init();
	}

	$('#js-add-phrase-cmd').click(function(e)
	{
		e.preventDefault();
		$('#js-add-phrase-dialog-placeholder').css({height: 'auto', margin: '10px 0 15px'});
		Ext.getCmp('add_phrase_panel').show();
	});

	$('.js-remove-lang-cmd').each(function()
	{
		$(this).click(function(e)
		{
			e.preventDefault();

			var link = $(this);

			Ext.Msg.show(
			{
				title: _t('confirm'),
				msg: _t('are_you_sure_to_delete_selected_language'),
				buttons: Ext.Msg.YESNO,
				fn: function(btn)
				{
					if ('yes' == btn)
					{
						window.location = link.attr('href');
					}
				},
				icon: Ext.MessageBox.QUESTION
			});
		});
	});

	if (Ext.get('comparison'))
	{
		var languagesStore = [];
		var j = 0;
		for (var i in intelli.languages)
		{
			languagesStore[j++] = [i, intelli.languages[i]];
		}

		intelli.languageComparison = new IntelliGrid(
		{
			target: 'comparison',
			columns: [
				{name: 'key', title: _t('key'), width: 200},
				{name: 'lang1', title: _t('default_language'), width: 300, renderer: Ext.util.Format.htmlEncode},
				{name: 'value', title: _t('language'), width: 1, editor: 'text-wide', renderer: Ext.util.Format.htmlEncode},
				{name: 'category', title: _t('category'), width: 100, editor: Ext.create('Ext.form.ComboBox',
				{
					typeAhead: true,
					editable: false,
					store: categoriesStore,
					value: 'admin',
					displayField: 'title',
					valueField: 'value'
				})},
				'delete'
			],
			storeParams: {get: 'comparison'},
			texts: {delete_multiple: _t('are_you_sure_to_delete_selected_phrases')},
			url: intelli.config.admin_url + '/language/'
		}, false);

		intelli.languageComparison.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			displayField: 'title',
			emptyText: _t('default_language'),
			listeners: intelli.gridHelper.listener.specialKey,
			id: 'lang1',
			name: 'lang1',
			store: languagesStore,
			valueField: 'code',
			xtype: 'combo'
		},{
			displayField: 'title',
			emptyText: _t('language'),
			listeners: intelli.gridHelper.listener.specialKey,
			id: 'lang2',
			name: 'lang2',
			store: languagesStore,
			valueField: 'code',
			xtype: 'combo'
		},{
			emptyText: _t('value'),
			listeners: intelli.gridHelper.listener.specialKey,
			id: 'value',
			name: 'value',
			xtype: 'textfield'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('category'),
			id: 'category',
			name: 'category',
			store: new Ext.data.SimpleStore(
			{
				fields: ['value', 'title'],
				data: [['admin', 'Administration Board'],['frontend', 'User Frontend'],['common', 'Common'],['tooltip', 'Tooltip']]
			}),
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('extras'),
			id: 'plugin',
			name: 'plugin',
			store: intelli.gridHelper.store.ajax(intelli.config.admin_url + '/language.json?get=plugins'),
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			handler: function()
			{
				var language1 = Ext.getCmp('lang1').getValue();
				var language2 = Ext.getCmp('lang2').getValue();

				if ('' != language1 || '' != language2)
				{
					// notify if comparing same languages
					if (language1 == language2)
					{
						intelli.notifFloatBox({msg: _t('error_compare_same_languages'), type: 'error', autohide: true, pause: 5000});

						return false;
					}

					intelli.languageComparison.store.getProxy().extraParams.lang1 = language1;
					intelli.languageComparison.store.getProxy().extraParams.lang2 = language2;
					intelli.languageComparison.store.getProxy().extraParams.key = Ext.getCmp('value').getValue();
					intelli.languageComparison.store.getProxy().extraParams.category = Ext.getCmp('category').getValue();
					intelli.languageComparison.store.getProxy().extraParams.plugin = Ext.getCmp('plugin').getValue();

					intelli.languageComparison.store.loadPage(1);
				}
			},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('compare')
		},{
			handler: function()
			{
				Ext.getCmp('value').reset();
				Ext.getCmp('category').reset();
				Ext.getCmp('plugin').reset();

				intelli.languageComparison.store.getProxy().extraParams = {
					get: 'comparison',
					lang1: Ext.getCmp('lang1').getValue(),
					lang2: Ext.getCmp('lang2').getValue()
				};

				intelli.languageComparison.store.loadPage(1);
			},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		intelli.languageComparison.init();
	}
});