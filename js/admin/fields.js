Ext.onReady(function()
{
	if ($('#js-grid-placeholder').length)
	{
		var grid = new IntelliGrid(
		{
			columns: [
				'selection',
				{name: 'name', title: _t('name'), hidden: true, width: 160},
				{name: 'title', title: _t('title'), sortable: false, width: 2},
				{name: 'item', title: _t('item'), width: 100, renderer: function(value){return _t(value, value);}},
				{name: 'group', title: _t('field_group'), width: 120},
				{name: 'type', title: _t('field_type'), width: 130, renderer: function(value){return _t('field_type_'+value, value);}},
				{name: 'relation', title: _t('field_relation'), hidden: true, width: 80},
				{name: 'length', title: _t('field_length'), width: 80},
				{name: 'order', title: _t('order'), width: 50, editor: 'number'},
				'status',
				'update',
				'delete'
			],
			events: {
				beforeedit: function(editor, e)
				{
					if (0 == e.record.get('delete'))
					{
						this.store.rejectChanges();
						e.cancel = true;

						intelli.notifFloatBox({autohide: true, msg: _t('status_change_not_allowed'), pause: 1400});
					}
				}
			},
			texts: {delete_single: _t('are_you_sure_to_delete_field')}
		}, false);

		grid.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			emptyText: _t('fields_item_filter'),
			name: 'item',
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			store: new Ext.data.SimpleStore({fields: ['value', 'title'], data: intelli.config.items}),
			displayField: 'title',
			valueField: 'value'
		},{
			emptyText: _t('field_relation'),
			name: 'relation',
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			store: new Ext.data.SimpleStore(
			{
				fields: ['value', 'title'],
				data : [['regular', _t('field_relation_regular')],['dependent', _t('field_relation_dependent')],['parent', _t('field_relation_parent')]]
			}),
			displayField: 'title',
			valueField: 'value'
		},{
			handler: function(){intelli.gridHelper.search(grid)},
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(grid, true)},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		grid.init();
	}
});

intelli.translateTreeNode = function(o)
{
	var tree = $.jstree.reference(o.reference),
		node = tree.get_node(o.reference);

	var data = {
		item: $('#input-item').val(),
		field: $('input[name="name"]').val(),
		id: node.id
	};

	$.ajax(
	{
		dataType: 'json',
		url: intelli.config.admin_url + '/fields/tree.json',
		data: data,
		success: function(response)
		{
			if (0 == response.length)
			{
				return;
			}

			var translationModal = new Ext.Window(
			{
				title: _t('translate'),
				layout: 'fit',
				width: 350,
				autoHeight: true,
				plain: true,
				items: [new Ext.FormPanel(
				{
					id: 'form-translate',
					labelWidth: 75,
					url: intelli.config.admin_url + '/fields/tree.json?item=' + data.item + '&field=' + data.field + '&id=' + data.id,
					frame: true,
					width: 350,
					autoHeight: true,
					defaults: {width: 230},
					defaultType: 'textfield',
					items: response,
					buttons: [
					{
						text: _t('save'),
						handler: function()
						{
							Ext.getCmp('form-translate').getForm().submit(
							{
								waitMsg : _t('saving'),
								success: function()
								{
									var formText = Ext.getCmp('form-translate').getForm().getValues()[intelli.config.lang];
									if (formText == '')
									{
										return;
									}
									tree.rename_node(node, formText);
									translationModal.close();
								}
							});
						}
					},{
						text: _t('cancel'),
						handler: function(){translationModal.close();}
					}]
				})]
			}).show();
		}
	});
};

$(function()
{
	if ($('#js-grid-placeholder').length)
	{
		return;
	}

	intelli.displayUpdown = function()
	{
		$('div[id^="item"] .itemUp, div[id^="item"] .itemDown').show();
		$('div[id^="item"] .itemUp:first').hide();
		$('div[id^="item"] .itemDown:last').hide();
	};
/*
	var wfields = function(item)
	{
		var temp;
		var div = $(item).parent().find('input:first');
		var info = $(item).parent().find('.list:first');
		var url = intelli.config.admin_url + '/fields.json?a=fields&item='+$('#field_item').val()+'&ids=' + div.val();
		var tree = new Ext.tree.TreePanel(
		{
			height: 465,
			width: 335,
			useArrows:true,
			autoScroll:true,
			animate:true,
			enableDD:true,
			containerScroll: true,
			rootVisible: false,
			frame: true,
			root: {nodeType: 'async'},
			dataUrl: url,
			buttons: [{
				text: _t('reset'),
				handler: function(){
					tree.getRootNode().cascade(function(n)
					{
						var ui = n.getUI();
						ui.toggleCheck(false);
					});
					div.val('');
					info.html('');
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
				text: _t('save'),
				handler: function()
				{
					var msg = [], selNodes = tree.getChecked(), title = [];
					Ext.each(selNodes, function(node)
					{
						msg.push(node.id);
						title.push(node.text);
					});

					div.val(msg.join(', '));
					info.html(title.join(', '));
					win.close();
				}
			}]
		});

		var win = new Ext.Window(
		{
			title: 'Fields List',
			closable: true,
			width: 352,
			autoScroll: true,
			height: 500,
			plain: true,
			listeners: {
				beforeclose: function()
				{
					var msg = [], selNodes = tree.getChecked();
					Ext.each(selNodes, function(node){msg.push(node.id);});
					msg = msg.join(', ');

					if (div.val() != msg && temp)
					{
						Ext.Msg.show({
							title: _t('save_changes')+'?',
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
	};
*/

	$('#input-type').on('change', function()
	{
		var type = $(this).val();

		$('div.field_type').css('display', 'none');
		$('#js-row-use-editor').css('display', ('textarea' != type ? 'none' : 'block') );

		var object = $('#js-row-empty-text');
		($.inArray(type, ['text', 'textarea', 'number']) !== -1) ? object.show() : object.hide();

		if (type && $.inArray(type, ['textarea', 'text', 'number', 'storage', 'image', 'url', 'date', 'pictures', 'tree']) !== -1)
		{
			$('#' + type).css('display', 'block');
			if ($('#searchable').val() == '1' && ('textarea' == type || 'text' == type) && 'none' == $('#fulltext_search_zone').css('display'))
			{
				$('#fulltext_search_zone').fadeIn('slow');
			}
		}
		else if (type && $.inArray(type, ['combo', 'radio', 'checkbox']) !== -1)
		{
			$('#js-multiple').css('display', 'block');
		}

		(type && $.inArray(type, ['text', 'number', 'image', 'date', 'combo', 'radio']) !== -1)
			? $('#link-to-details').show()
			: $('#link-to-details').hide();

		var annotation = $('option:selected', this).data('annotation');
		var $helpBlock = $(this).next();
		annotation ? $helpBlock.text(annotation).show() : $helpBlock.hide();
	}).change();

	$('#js-field-relation').change(function()
	{
		var value = $(this).val();

		(value == 'parent') ? $('.main_fields').show() : $('.main_fields').hide();
		(value == 'dependent') ? $('#regular_field').show() : $('#regular_field').hide();
	});

	$('#searchable').on('change', function()
	{
		var $ctl = $('#show_in_search_as');
		($(this).val() == 1) ? $ctl.show() : $ctl.hide();
	}).change();

	$('#js-cmd-add-value').on('click', function(e)
	{
		e.preventDefault();

		$('div[id^="item-value-"]:first')
			.clone(true)
			.attr('id', 'item-value-' + Math.ceil(Math.random() * 10000))
			.insertBefore($(this))
			.find('input').each(function(){	$(this).val(''); });

		intelli.displayUpdown();
	});

	$('select[name="resize_mode"], select[name="pic_resize_mode"]').on('change', function()
	{
		$(this).next().text($('option:selected', this).data('annotation'));
	}).change();

	$('.js-actions').on('click', function(e)
	{
		e.preventDefault();

		var action = $(this).data('action');
		var type = $('#input-type').val();
		var val = $(this).parent().parent().find('input[name="values[]"]:first').val();
		var defaultVal = $('#multiple_default').val();
		var allDefault = defaultVal.split('|');

		if ('removeItem' == action)
		{
			$(this).parents('.wrap-row').remove();
		}
		else if ('clearDefault' == action)
		{
			$('#multiple_default').val('');
		}
		else if ('setDefault' == action)
		{
			if ('' != val)
			{
				if ('checkbox' == type)
				{
					if ('' != defaultVal)
					{
						if (!intelli.inArray(val, allDefault))
						{
							allDefault[allDefault.length++] = val;
						}

						$('#multiple_default').val(allDefault.join('|'));
					}
					else
					{
						$('#multiple_default').val(val);
					}
				}
				else
				{
					$('#multiple_default').val(val);
				}
			}
		}
		else if ('removeDefault' == action)
		{
			if ('' != defaultVal)
			{
				if (allDefault.length > 1)
				{
					var array = [];
					for (i = 0; i < allDefault.length; i++)
					{
						if (allDefault[i] != val)
						{
							array[array.length] = allDefault[i];
						}
					}
					$('#multiple_default').val(array.join('|'));
				}
				else if (defaultVal == val)
				{
					$('#multiple_default').val('');
				}
			}
		}
		else if ('itemUp' == action || 'itemDown' == action)
		{
			var current = {
				id: $(this).parents('.wrap-row').attr('id'),
				item: $(this).parents('.wrap-row'),
				index: null
			};
			var parent = current.item.parent();
			var items = parent.children('.wrap-row');

			$.each(items, function(index, item)
			{
				if ($(item).attr('id') == current.id)
				{
					current.index = index;
				}
			});
			if (action == 'itemUp')
			{
				if (current.index >= 1)
				{
					current.index--;
					$('.wrap-row:eq(' + current.index + ')', parent).before($(current.item).clone(true));
					$(current.item).remove();
				}
			}
			else
			{
				if (current.index < items.length)
				{
					current.index++;
					$('.wrap-row:eq('+current.index+')', parent).after($(current.item).clone(true));
					$(current.item).remove();
				}
			}
		}
		else if ('removeNumItem' == action)
		{
			$(this).parent().remove();

			if ('' != defaultVal)
			{
				if (allDefault.length > 1)
				{
					var array = [];
					for (i = 0; i < allDefault.length; i++)
					{
						if (allDefault[i] != val)
						{
							array[array.length] = allDefault[i];
						}
					}
					$('#multiple_default').val(array.join('|'));
				}
				else if (defaultVal == val)
				{
					$('#multiple_default').val('');
				}
			}
		}
		intelli.displayUpdown();
	});

	intelli.displayUpdown();

	$('#toggle-pages')
		.data('checked', true)
		.click(function(e)
		{
			e.preventDefault();
			var checked = $(this).data('checked');
			if (checked)
			{
				$(this).html('<i class="i-lightning"></i> ' + _t('select_none'));
				$('input[type="checkbox"]:visible', '#js-pages-list-row').prop('checked', true);
			}
			else
			{
				$(this).html('<i class="i-lightning"></i> ' + _t('select_all'));
				$('input[type="checkbox"]:visible', '#js-pages-list-row').prop('checked', false);
			}
			$(this).data('checked', !checked);
		});

	$('input[name="relation_type"]').on('change', function()
	{
		var $field = $('#regular_field');
		(this.value == 0) ? $field.show() : $field.hide();
	});

	$('input[name="required"]').on('change', function()
	{
		if (this.value == 1)
		{
			$('#tr_required').show();
			$('#for_plan_only').hide();
		}
		else
		{
			$('#tr_required').hide();
			$('#for_plan_only').show();
		}
	});

	// populate & activate field groups select
	$('#input-item').on('change', function()
	{
		var $fieldGroup = $('#input-fieldgroup');
		$fieldGroup.empty().append('<option value="" selected>' + _t('_select_') + '</option>').prop('disabled', true);

		var $pagesList = $('#js-pages-list-row');
		var itemName = $(this).val();

		if (itemName)
		{
			$('.checkbox', $pagesList).each(function()
			{
				$(this).data('item') == itemName ? $(this).show() : $(this).hide();
			});

			$pagesList.is(':visible') || $pagesList.slideDown();

			$('.js-dependent-fields-list').each(function()
			{
				var $this = $(this);
				($this.data('item') == itemName) ? $this.show() : $this.hide();
			});

			// get item field groups
			$.get(intelli.config.admin_url + '/fields/read.json', {get: 'groups', item: itemName}, function(response)
			{
				if (response.length > 0)
				{
					$.each(response, function(i, entry)
					{
						$fieldGroup.append($('<option>').val(entry.id).text(_t('fieldgroup_' + entry.name)));
					});

					$fieldGroup.prop('disabled', false);
				}
			});
		}
		else
		{
			$pagesList.slideUp();
		}

		$('input[type="checkbox"]:not(:visible)', $pagesList).prop('checked', false);
	});

	// accordion for host fields
	$('.js-dependent-fields-list').on('click', 'a.list-group-item', function(e)
	{
		e.preventDefault();

		var $this = $(this);

		if (!$this.hasClass('active'))
		{
			$this.siblings('.active').removeClass('active').next().slideUp('fast', function()
			{
				$this.addClass('active').next().slideDown('fast');
			});
		}
	});


	// tree field
	var $tree = $('#input-nodes');

	$('.js-tree-action').on('click', function(e)
	{
		e.preventDefault();

		var tree = $tree.jstree(true),
			selection = tree.get_selected();

		switch ($(this).data('action'))
		{
			case 'create':
				selection = selection.length > 0 ? selection[0] : null;
				selection = tree.create_node(selection, {type: 'file'});
				if (selection) tree.edit(selection);
				break;
			case 'update':
				tree.edit(selection[0]);
				break;
			case 'delete':
				tree.delete_node(selection);
		}
	});

	var nodes = $('input[name="nodes"]').val();

	$tree.jstree(
	{
		core: {check_callback: true, data: ('' != nodes) ? JSON.parse(nodes) : null}, plugins: ['dnd','contextmenu'],
		contextmenu: {items: function(){ return {translate: {label: _t('translate'), action: intelli.translateTreeNode, _disabled: 'edit' != $('#input-nodes').data('action')}};}}
	})
	.on('changed.jstree', function(e, data)
	{
		var actionButtons = $('.js-tree-action[data-action="update"], .js-tree-action[data-action="delete"]');
		data.selected.length > 0 ? actionButtons.removeClass('disabled') : actionButtons.addClass('disabled');
	})
	.on('create_node.jstree rename_node.jstree delete_node.jstree move_node.jstree copy_node.jstree', function(e, data)
	{
		$('input[name="nodes"]').val(JSON.stringify(data.instance.get_json('#', {flat: true})));
	});
});