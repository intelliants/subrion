Ext.onReady(function()
{
	if ($('#js-grid-placeholder').length)
	{
		intelli.fields = {
			columns: [
				'selection',
				{name: 'name', title: _t('name'), hidden: true, width: 160},
				{name: 'title', title: _t('title'), sortable: false, width: 1},
				{name: 'item', title: _t('item'), width: 150, renderer: function(value){return _t(value, value);}},
				{name: 'group', title: _t('field_group'), width: 120},
				{name: 'type', title: _t('fields_type'), width: 160, renderer: function(value){return _t('fields_type_'+value, value);}},
				{name: 'relation', title: _t('field_relation'), hidden: true, width: 80},
				{name: 'length', title: _t('field_length'), width: 70},
				{name: 'order', title: _t('order'), width: 50, editor: 'number'},
				'update',
				'delete'
			],
			texts: {delete_single: _t('are_you_sure_to_delete_field')},
			url: intelli.config.admin_url + '/fields/'
		};

		var currentItem = $('#js-current-item-ph').val();
		if (currentItem)
		{
			intelli.fields.storeParams = {item: currentItem};
		}

		intelli.fields = new IntelliGrid(intelli.fields, false);
		intelli.fields.toolbar = Ext.create('Ext.Toolbar', {items:[
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
			handler: function(){intelli.gridHelper.search(intelli.fields)},
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(intelli.fields, true)},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});
		intelli.fields.init();
	}
});

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

	// populate & activate field groups select
	$('#js-item-name').on('change', function()
	{
		var $fieldGroup = $('#js-fieldgroup-selectbox');
		$fieldGroup.empty().append('<option selected="selected" value="">' + _t('_select_') + '</option>').prop('disabled', true);

		var $pagesList = $('#js-pages-list-row');
		var itemName = $(this).val();

		if (itemName)
		{
			$('.checkbox', $pagesList).each(function()
			{
				$(this).data('item') == itemName ? $(this).show() : $(this).hide();
			});

			$pagesList.is(':visible') || $pagesList.slideDown();

			// get item field groups
			$.get(intelli.config.admin_url + '/fields.json', {get: 'groups', item: itemName}, function(response)
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

	$('#type').on('change', function()
	{
		var type = $(this).val();

		$('div.field_type').css('display', 'none');
		$('#js-row-use-editor').css('display', ('textarea' != type ? 'none' : 'block') );

		var object = $('#js-row-empty-text');
		($.inArray(type, ['text', 'textarea', 'number']) !== -1) ? object.show() : object.hide();

		if (type && $.inArray(type, ['textarea', 'text', 'number', 'storage', 'image', 'url', 'pictures']) !== -1)
		{
			$('#' + type).css('display', 'block');
			if ($('#searchable').val() == '1' && ('textarea' == type || 'text' == type) && 'none' == $('#fulltext_search_zone').css('display'))
			{
				$('#fulltext_search_zone').fadeIn('slow');
			}
		}
		else if (type && $.inArray(type, ['combo', 'radio', 'checkbox']) !== -1)
		{
			$('#multiple').css('display', 'block');
			('checkbox' == type) ? $('#textany_meta_container').hide() : $('#textany_meta_container').show();
		}

		(type && $.inArray(type, ['text', 'number', 'image', 'date', 'combo', 'radio']) !== -1)
			? $('#link-to-details').show()
			: $('#link-to-details').hide();
	}).change();

	$('#relation').change(function()
	{
		var value = $(this).val();
		(value == 'dependent') ? $('#regular_field').show() : $('#regular_field').hide();
		(value == 'parent') ? $('.main_fields').show() : $('.main_fields').hide();
	});

	$('#searchable').change(function()
	{
		($(this).val() == 1) ? $('#show_in_search_as').show() : $('#show_in_search_as').hide();
	}).change();

	$('#add_item').click(function(e)
	{
		e.preventDefault();
		$('div[id^="item-value-"]:first')
			.clone(true)
			.attr('id', 'item-value-' + Math.ceil(Math.random() * 10000))
			.insertBefore($(this))
			.find('input').each(function(){	$(this).val(''); });

		intelli.displayUpdown();
	});

	$('select[name="pic_resize_mode"]').change(function()
	{
		$('.help-block[id^=pic_resize_mode_tip_]').hide();
		$('#pic_resize_mode_tip_'+$(this).val()).show();
	}).change();

	$('select[name="resize_mode"]').change(function()
	{
		$('.help-block[id^=resize_mode_tip_]').hide();
		$('#resize_mode_tip_'+$(this).val()).show();
	}).change();

	$('.js-actions').on('click', function(e)
	{
		e.preventDefault();

		var action = $(this).data('action');
		var type = $('#type').val();
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

	$('.js-filter-numeric').numeric();

	$('#type').change(function()
	{
		$('#field_type_tip .help-block').hide();
		$('#type_tip_' + $(this).val()).show();
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
		(this.value == 0) ? $('#regular_field').show() : $('#regular_field').hide();
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

	$('#js-item-name').trigger('change');
});