intelli.menus = function()
{
	var positions = [];
	if ('string' == typeof intelli.config.block_positions)
	{
		var array = intelli.config.block_positions.split(',');
		for (i in array) positions.push([array[i], array[i]]);
	}

	return {
		columns: [
			'selection',
			{name: 'title', title: _t('title'), width: 1, editor: 'text'},
			{name: 'name', title: _t('name'), width: 150},
			'status',
			{name: 'position', title: _t('position'), width: 150, editor: Ext.create('Ext.form.ComboBox',
			{
				typeAhead: true,
				editable: false,
				lazyRender: true,
				store: Ext.create('Ext.data.SimpleStore', {fields: ['value', 'title'], data: positions}),
				displayField: 'title',
				valueField: 'value'
			})},
			{name: 'order', title: _t('order'), width: 75, editor: 'number'},
			'update',
			'delete'
		],
		texts: {
			delete_single: _t('are_you_sure_to_delete_this_menu'),
			delete_multiple: _t('are_you_sure_to_delete_selected_menus')
		},
		url: intelli.config.admin_url + '/menus/'
	}
}();

function menusSave()
{
	var items = [];
	Ext.getCmp('menus').getRootNode().cascadeBy(function(node)
	{
		items.push(node.data)
	});

	$('#js-menu-data').val(JSON.stringify(items));
	$('#js-form-menus').submit();
}

Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		intelli.menus = new IntelliGrid(intelli.menus);
	}
	else
	{
		var changed = false;

		var handlerMenuEdit = function(menu)
		{
			var selectionModel = Ext.getCmp('menus').getSelectionModel();
			if (selectionModel.hasSelection())
			{
				var selectedNode = selectionModel.getSelection()[0];
				var nolinkRegExp = / \(no link\)/;
				var text = selectedNode.data.text.replace(/ \(custom\)/, '').replace(nolinkRegExp, '');
				var win, form;

				$.ajax(
				{
					dataType: 'json',
					url: intelli.config.admin_url + '/menus.json',
					data: {
						action: 'titles',
						id: selectedNode.data.id,
						current: text,
						menu: $('#name').val(),
						'new': (menu == 'add') ? 1 : 0
					},
					success: function(response)
					{
						if (!response.languages || response.length == 0)
						{
							return false;
						}

						var nodeId = ('add' == menu)
							? 'node_' + Math.floor(Math.random(1000) * 100000)
							: selectedNode.data.id;

						win = new Ext.Window(
						{
							layout: 'fit',
							width: 350,
							autoHeight: true,
							plain: true,
							items: [new Ext.FormPanel(
							{
								id: 'form-panel',
								labelWidth: 75,
								url: intelli.config.admin_url + '/menus.json?action=save&menu=' + $('#name').val() + '&node=' + nodeId,
								frame: true,
								width: 350,
								autoHeight: true,
								defaults: {width: 230},
								defaultType: 'textfield',
								items: response.languages,
								buttons: [
								{
									text: _t('save'),
									handler: function()
									{
										changed = true;
										Ext.getCmp('form-panel').getForm().submit(
										{
											waitMsg : 'Saving...',
											success: function()
											{
												var formText = Ext.getCmp('form-panel').getForm().getValues()[intelli.config.lang];
												if (formText == '')
												{
													return;
												}
												if ('add' == menu)
												{
													var target = selectedNode.data.leaf
														? selectedNode.data.parentNode
														: selectedNode;

													target.appendChild({id: nodeId, text: formText + ' (no link)', leaf: true, cls: 'folder'});
													target.expand();
												}
												else
												{
													selectedNode.set('text', formText + (selectedNode.data.text.match(nolinkRegExp) ? ' (no link)' : ' (custom)'));
												}
												win.close();
										}});
									}
								},{
									text: _t('cancel'),
									handler: function(){win.close();}
								}]
							})]
						}).show();
					}
				});
			}
		};

		var contextMenu = Ext.create('Ext.menu.Menu',
		{
			id: 'mainContext',
			items: [
/*			{
				id: 'item-add',
				text: _t('add_menu'),
				handler: function(contentMenu, e){handlerMenuEdit('add');}
			},*/{
				id: 'item-edit',
				text: _t('edit_menu'),
				handler: handlerMenuEdit
			},{
				id: 'item-delete',
				text: _t('delete'),
				handler: function(){Ext.getCmp('menus').getSelectionModel().getSelection()[0].remove();}
			}]
		});

		var menus = new Ext.tree.TreePanel(
		{
			id: 'menus',
            listeners:
			{
				itemcontextmenu: function(view, record, item, index, e)
				{
					contextMenu.showAt(e.getXY());
					e.stopEvent();
				}
            },
			renderTo: 'js-placeholder-menus',
			root: {id: 0, text: _t('menus'), expanded: true},
			store: Ext.create('Ext.data.TreeStore',
			{
				proxy:
				{
					type: 'ajax',
					url: intelli.config.admin_url + '/menus.json?id=' + intelli.urlVal('id') + '&action=menus'
				}
			}),
			viewConfig:
			{
				listeners:
				{
					beforedrop: function(node, data, overModel, dropPosition, dropFunction)
					{
						if (data.view.panel.id != this.panel.id) // copy the node if dropped to another tree, just move otherwise
						{
							if (!data.records[0].data.leaf) // we permit the drop of pages group
							{
								return false;
							}

							data.copy = true; // mark that node should be copied

							// we have to actually copy the node instead of just cloning it
							var record = data.records[0].copy();

							record.data.id += '_' + Math.floor(Math.random(1000) * 10000);
							record.data.leaf = false;

							data.records[0] = record;
						}

						return true;
					}
				},
				plugins: {ptype: 'treeviewdragdrop', allowContainerDrops: true}
			}
		});

		var pages = new Ext.tree.TreePanel(
		{
			id: 'pages',
			renderTo: 'js-placeholder-pages',
			rootVisible: false,
			singleExpand: true,
			store: Ext.create('Ext.data.TreeStore',
			{
				proxy:
				{
					type: 'ajax',
					url: intelli.config.admin_url + '/menus.json?action=pages'
				}
			}),
			viewConfig:
			{
				listeners:
				{
					beforedrop: function(node, data, overModel, dropPosition, dropFunction)
					{
						if (data.view.panel.id == this.panel.id) // permit the movement between nodes of this tree
						{
							return false;
						}

						return true;
					}
				},
				plugins: {ptype: 'treeviewdragdrop'}
			}
		});

		$('input[name="visible_on_pages[]"]').change(function()
		{
			$(this).is(':checked')
				? $($(this).parent().children('.subpages').get(0)).show()
				: $($(this).parent().children('.subpages').get(0)).hide();
		}).change();

		$('.subpages').click(function()
		{
			var temp = $(this).attr('rel').split('::');
			var div = $('#subpage_'+temp[1]);
			var url = intelli.config.admin_url + '/' + temp[0] + '.json?a=subpages&ids=' + div.val();
			var tree = new Ext.tree.TreePanel(
			{
				height: 465,
				width: 335,
				useArrows: true,
				autoScroll: true,
				animate: true,
				enableDD: true,
				containerScroll: true,
				rootVisible: false,
				frame: true,
				root: {nodeType: 'async'},
				dataUrl: url,
				buttons: [{
					text: _t('reset'),
					handler: function()
					{
						tree.getRootNode().cascade(function(n)
						{
							var ui = n.getUI();
							ui.toggleCheck(false);
						});
						div.val('');
						win.close();
					}
				},{
					text: _t('cancel'),
					handler: function()
					{
						temp = false;
						win.close();
					}
				},{
					text: 'Save sub pages',
					handler: function()
					{
						var msg = '', selNodes = tree.getChecked();
						Ext.each(selNodes, function(node)
						{
							if (msg.length > 0){
								msg += '-';
							}
							msg += node.id;
						});

						div.val(msg);
						win.close();
					}
				}]
			});

			var win = new Ext.Window(
			{
				title: 'Sub pages list',
				closable: true,
				width: 352,
				autoScroll: true,
				height: 500,
				plain: true,
				listeners:
				{
					beforeclose: function(panel)
					{
						var msg = '', selNodes = tree.getChecked();
						Ext.each(selNodes, function(node)
						{
							if (msg.length > 0){
								msg += '-';
							}
							msg += node.id;
						});

						if (div.val() != msg && temp)
						{
							Ext.Msg.show(
							{
							   title: _t('save_changes') + '?',
							   msg: _t('closing_window_with_unsaved_changes'),
							   buttons: Ext.Msg.YESNO,
							   fn: function(btnID)
							   {
								   if (btnID == 'yes')
								   {
									   div.val(msg);
									   return true;
								   }
								   else if (btnID == 'no')
								   {
									   return true;
								   }

								   return false;
							   },
							   icon: Ext.MessageBox.QUESTION
							});
						}
						temp = true;
						return true;
					}
				},
				items: [tree]
			});
			tree.getRootNode().expand();
			win.show();
		});

		$('input[name="sticky"]').change(function()
		{
			var obj = $('#acos');
			$(this).val() == 0 ? obj.show() : obj.hide();
		}).change();

		$('#js-select-all-pages').on('click', function()
		{
			$('input[type="checkbox"]:not(#all_pages)', '#acos')
				.prop('checked', $(this).is(':checked'))
				.change();
		});

		$('input[name^="all_pages_"]', '#acos').on('click', function()
		{
			$('input.' + $(this).data('group'))
				.prop('checked', $(this).is(':checked'))
				.change();
		});
    }
});