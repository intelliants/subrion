Ext.onReady(function()
{
	var pageUrl = intelli.config.admin_url + '/blocks/';

	if (Ext.get('js-grid-placeholder'))
	{
		var positions = [];
		if ('string' == typeof intelli.config.block_positions)
		{
			var array = intelli.config.block_positions.split(',');
			for (i in array) positions.push([array[i], array[i]]);
		}

		intelli.blocks = new IntelliGrid(
		{
			columns: [
				'selection',
				'expander',
				{name: 'title', title: _t('title'), width: 2, editor: 'text'},
				{name: 'position', title: _t('position'), width: 85, editor: Ext.create('Ext.form.ComboBox',
				{
					typeAhead: true,
					editable: false,
					lazyRender: true,
					store: Ext.create('Ext.data.SimpleStore', {fields: ['value','title'], data: positions}),
					displayField: 'title',
					valueField: 'value'
				})},
				{name: 'extras', title: _t('extras'), width: 150},
				{name: 'type', title: _t('type'), width: 85},
				'status',
				{name: 'order', title: _t('order'), width: 50, editor: 'number'},
				'update',
				'delete'
			],
			expanderTemplate: '<pre style="font-size: 0.9em">{contents}</pre>',
			fields: ['contents'],
			texts: {delete_single: _t('are_you_sure_to_delete_this_block')},
			url: pageUrl
		}, false);

		intelli.blocks.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			xtype: 'textfield',
			name: 'title',
			emptyText: _t('title'),
			listeners: intelli.gridHelper.listener.specialKey
		},{
			emptyText: _t('status'),
			name: 'status',
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			store: intelli.blocks.stores.statuses,
			displayField: 'title',
			valueField: 'value'
		},{
			emptyText: _t('type'),
			name: 'type',
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			store: new Ext.data.SimpleStore(
			{
				fields: ['value', 'title'],
				data : [['plain', 'plain'],['smarty', 'smarty'],['php', 'php'],['html', 'html'],['menu', 'menu']]
			}),
			displayField: 'title',
			valueField: 'value'
		},{
			emptyText: _t('position'),
			name: 'position',
			xtype: 'combo',
			typeAhead: true,
			editable: false,
			store: new Ext.data.SimpleStore({fields: ['value','title'], data: positions}),
			displayField: 'title',
			valueField: 'value'
		},{
			handler: function(){intelli.gridHelper.search(intelli.blocks)},
			id: 'fltBtn',
			text:'<i class="i-search"></i> ' +  _t('search')
		},{
			handler: function(){intelli.gridHelper.search(intelli.blocks, true)},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});

		intelli.blocks.init();
	}
	else
	{
		var $multiLanguage = $('#multi_language');
		$multiLanguage.change(function()
		{
			var _thisVal = $(this).val();
			var checked = false;
			var type = $('#block_type').val();

			if (_thisVal == 0 && (type == 'php' || type == 'smarty'))
			{
				$('#box-multi_language').click();
			}

			if (_thisVal == 1)
			{
				$('#languages').hide();
				$('#blocks_contents_multi').hide();
				$('#blocks_contents').show();

				if ('html' != $('#block_type').val() && CKEDITOR.instances.multi_contents)
				{
					CKEDITOR.instances.multi_contents.destroy();
				}
			}
			else
			{
				checked = true;
				$('#languages').show();
				$('#blocks_contents_multi').show();
				$('#blocks_contents').hide();
				if ('html' == type)
				{
					intelli.ckeditor('multi_contents', {toolbar: 'Extended', height: '400px'});
				}
				else if (type == 'php' || type == 'smarty')
				{
					editAreaLoader.init(
					{
						id: 'multi_contents',
						start_highlight: true,
						allow_resize: 'yes',
						allow_toggle: true,
						syntax: 'php',
						toolbar: 'search, go_to_line, |, undo, redo',
						min_height: 350
					});
				}
			}
			$('input.block_languages').each(function()
			{
				checked ? $(this).prop('checked', true) : $(this).prop('checked', false);
				initContentBox({lang: $(this).val(), checked: checked});
			});
		}).change();

		$('#sticky').on('change', function()
		{
			var obj = $('#acos');
			$(this).val() == 0 ? obj.show() : obj.hide();
		}).change();

		$('input[name="visible_on_pages[]"]').change(function()
		{
			$(this).is(':checked')
				? $($(this).parent().children('.subpages').get(0)).show()
				: $($(this).parent().children('.subpages').get(0)).hide()
		}).change();

		$('input[name="external"]').change(function()
		{
			if ($(this).val() == 0)
			{
				$('#multi_contents_row').show();
				$('#external_filename').hide();
			}
			else
			{
				$('#multi_contents_row').hide();
				$('#external_filename').show();
			}
		}).change();

		$('.subpages').click(function()
		{
			var temp = $(this).attr('rel').split('::');
			var div = $('#subpage_' + temp[1]);
			var url = intelli.config.admin_url + '/' + temp[0] + '.json?a=subpages&ids=' + div.val();
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
						tree.getRootNode().cascade(function(n) {
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
					text: _t('save'),
					handler: function()
					{
						var msg = '', selNodes = tree.getChecked();
						Ext.each(selNodes, function(node){
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

			var win = new Ext.Window({
				title: 'Subpages List',
				closable: true,
				width: 352,
				autoScroll: true,
				height: 500,
				plain:true,
				listeners:
				{
					beforeclose: function(panel)
					{
						var msg = '', selNodes = tree.getChecked();
						Ext.each(selNodes, function(node){
							if (msg.length > 0){
								msg += '-';
							}
							msg += node.id;
						});

						if (div.val() != msg && temp)
						{
							Ext.Msg.show({
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

		var pagesCount = $('#acos input[name^="visible_on_pages"]').length;
		var selectedPagesCount = $("#acos input[name^='visible_on_pages']:checked").length;

		if (selectedPagesCount > 0 && pagesCount == selectedPagesCount)
		{
			$('#select_all').prop('checked', true).click();
		}

		$('input[name^="visible_on_pages"]', '#acos').click(function()
		{
			var checked = (pagesCount == $("input[name^='visible_on_pages']:checked", '#acos').length) ? 'checked' : '';
		});

		$('#select_all').click(function()
		{
			var obj = $('input[type="checkbox"]', '#acos');

			$(this).prop('checked') ? obj.prop('checked', true) : obj.prop('checked', false);
			obj.change();
		});

		$('#all_pages').click(function()
		{
			var obj = $('input[type="checkbox"]', '#pages');

			$(this).prop('checked') ? obj.prop('checked', true) : obj.prop('checked', false);
			obj.change();
		});

		$('#acos input[name^="select_all_"], input[name^="all_pages_"]').click(function()
		{
			var group = $('input.' + $(this).data('group'));

			$(this).is(':checked') ? group.prop('checked', true) : group.prop('checked', false);
			group.change();
		});

		$('#header').on('change', function()
		{
			var obj = $('input[name="collapsible"]').closest('.row');
			$(this).val() == 1 ? obj.show() : obj.hide();
		}).change();

		$('input.block_languages').change(function()
		{
			initContentBox({lang: $(this).val(), checked: $(this).prop('checked')})
		});

		$('input.block_languages:checked').each(function()
		{
			initContentBox({lang: $(this).val(), checked: $(this).prop('checked')})
		});

		$('#select_all_languages').click(function()
		{
			var checked = $(this).prop('checked') ? true : false;
			$('input.block_languages').each(function()
			{
				checked ? $(this).prop('checked', true) : $(this).prop('checked', false);
				$(this).change();
			});
		});

		if ($('input.block_languages:checked').length == $('input.block_languages').length)
		{
			$('#select_all_languages').prop('checked', true);
		}

		var last = '';
		var last_multi = false;
		$('#block_type').change(function()
		{
			$('#pages').hide();
			var type = $(this).val();

			eAL.toggle_off('multi_contents');
			if ('html' == type)
			{
				$("textarea.js-wysiwyg:visible").each(function()
				{
					intelli.ckeditor($(this).attr("id"), {toolbar: 'Extended', height: '400px'});
				});
			}
			else
			{
				$.each(CKEDITOR.instances, function(i, o)
				{
					o.destroy();
				});
			}

			if ('php' == type || 'smarty' == type)
			{
				last_multi = $multiLanguage.val();
				if ($multiLanguage.val() != 1)
				{
					$('#box-multi_language').click();
				}
				//$multiLanguage.parents('div').hide();
				//multi_language.val(1).change();
				//eAL.toggle_on('multi_contents');
				editAreaLoader.init(
				{
					id: 'multi_contents',
					start_highlight: true,
					allow_resize: 'yes',
					allow_toggle: true,
					syntax: 'php',
					toolbar: 'search, go_to_line, |, undo, redo',
					min_height: 350
				});

				$('#external_file_row').show();
			}
			else if (last_multi !== false)
			{
				if ($multiLanguage.val() != last_multi)
				{
					$('#box-multi_language').click();
				}
				//$multiLanguage.parents('div').show();
				last_multi = false;

				$('#external_file_row').hide();
				$('#external_filename').hide();
				$('input[name="external"]').val(0);
			}
			else
			{
				$multiLanguage.parents('tr').show();
				$multiLanguage.change();

				$('#external_file_row').hide();
				$('#external_filename').hide();
				$('input[name="external"]').val(0);
			}

			$('p[id^="type_tip_"]').hide();
			$("#type_tip_" + type).show();
			last = $(this).val();
		}).change();
	}

	$('#js-delete-block').click(function()
	{
		Ext.Msg.confirm(_t('confirm'), _t('are_you_sure_to_delete_this_block'), function(btn, text)
		{
			if (btn == 'yes')
			{
				$.ajax(
				{
					data: {'id[]': $('input[name="id"]').val()},
					dataType: 'json',
					failure: function()
					{
						Ext.MessageBox.alert(_t('error'));
					},
					type: 'POST',
					url: pageUrl + 'delete.json',
					success: function(response)
					{
						if ('boolean' == typeof response.result && response.result)
						{
							intelli.notifFloatBox({msg: response.message, type: response.result ? 'success' : 'error'});
							document.location = pageUrl;
						}
					}
				});
			}
		});
	});
});

function initContentBox(o)
{
	var name = 'contents_' + o.lang;
	var display = o.checked ? 'block' : 'none';
	var blockType = $('#block_type').val();

	if ('html' == blockType)
	{
		CKEDITOR.instances[name] || intelli.ckeditor(name, {toolbar: 'Extended', height: '400px'});
	}
	else
	{
		if (CKEDITOR.instances[name])
		{
			CKEDITOR.instances[name].destroy();
		}
	}

	$('#blocks_contents_' + o.lang).css('display', display);
}