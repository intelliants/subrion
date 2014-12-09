Ext.onReady(function()
{
	if (Ext.get('js-grid-placeholder'))
	{
		var positionsStore = intelli.gridHelper.store.ajax(intelli.config.admin_url + '/blocks/positions.json');

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
					store: positionsStore,
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
			sorters: [{property: 'title'}],
			texts: {delete_single: _t('are_you_sure_to_delete_this_block')}
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
			store: positionsStore,
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
		$multiLanguage.on('change', function()
		{
			var _thisVal = $(this).val(),
				checked = false,
				type = $('#block_type').val();

			if (_thisVal == 0 && (type == 'php' || type == 'smarty'))
			{
				$('#box-multi_language').click();
			}

			if (_thisVal == 1)
			{
				$('#languages, #blocks_contents_multi').hide();
				$('#blocks_contents').show();

				if ('html' != $('#block_type').val() && CKEDITOR.instances.multi_contents)
				{
					CKEDITOR.instances.multi_contents.destroy();
				}
			}
			else
			{
				checked = true;
				$('#languages, #blocks_contents_multi').show();
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
				$(this).prop('checked', checked);
				initContentBox({lang: $(this).val(), checked: checked});
			});
		}).change();

		// Block visibility
		$('#sticky').on('change', function()
		{
			var $this = $(this);

			if ($this.is(':checked'))
			{
				$('.js-visibility-hidden').show();
				$('.js-visibility-visible').hide();
			}
			else
			{
				$('.js-visibility-hidden').hide();
				$('.js-visibility-visible').show();
			}
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

/* TEMPORARILY DISABLED FOR FUTURE IMPLEMENTATION

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
*/

		var pagesCount = $('input[name^="pages"]', '#js-pages-list').length;
		var selectedPagesCount = $('input[name^="pages"]:checked', '#js-pages-list').length;

		if (selectedPagesCount > 0 && pagesCount == selectedPagesCount)
		{
			$('#js-pages-select-all').prop('checked', true).click();
		}

		$('input[name^="pages"]', '#js-pages-list').on('click', function()
		{
			var checked = (pagesCount == $('input[name^="pages"]:checked', '#js-pages-list').length);
			$('#js-pages-select-all').prop('checked', checked);
		});

		$('#js-pages-select-all').on('click', function()
		{
			$('input[type="checkbox"]', '#js-pages-list').prop('checked', $(this).prop('checked')).change();
		});

		$('#all_pages').on('click', function()
		{
			$('input[type="checkbox"]', '#pages').prop('checked', $(this).prop('checked')).change();
		});

		$('input[name^="select_all_"], input[name^="all_pages_"]', '#js-pages-list').on('click', function()
		{
			$('input.' + $(this).data('group')).prop('checked', $(this).is(':checked')).change();
		});

		$('#header').on('change', function()
		{
			var collapsible = $('input[name="collapsible"]').closest('.row');
			$(this).val() == 1 ? collapsible.show() : collapsible.hide();

			var collapsed = $('input[name="collapsed"]').closest('.row');
			$(this).val() == 1 && $('#collapsible').val() == 1 ? collapsed.show() : collapsed.hide();
		}).change();

		$('#collapsible').on('change', function()
		{
			var obj = $('input[name="collapsed"]').closest('.row');
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

		$('#select_all_languages').on('click', function()
		{
			var checked = $(this).prop('checked');
			$('input.block_languages').each(function()
			{
				$(this).prop('checked', checked).change();
			});
		});

		if ($('input.block_languages:checked').length == $('input.block_languages').length)
		{
			$('#select_all_languages').prop('checked', true);
		}

		var last = '', last_multi = false;

		$('#block_type').on('change', function()
		{
			$('#pages').hide();
			var type = $(this).val();

			eAL.toggle_off('multi_contents');
			$('#EditAreaArroundInfos_multi_contents').hide();
			if ('html' == type)
			{
				$('textarea.js-ckeditor').each(function()
				{
					intelli.ckeditor($(this).attr('id'), {toolbar: 'Extended', height: '400px'});
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
				// $('#EditAreaArroundInfos_multi_contents').hide();

				$('#external_file_row').show();
			}
			else if (last_multi !== false)
			{
				if ($multiLanguage.val() != last_multi)
				{
					$('#box-multi_language').click();
				}

				last_multi = false;

				$('#external_file_row, #external_filename').hide();
				$('input[name="external"]').val(0);
			}
			else
			{
				$multiLanguage.parents('tr').show().change();

				$('#external_file_row, #external_filename').hide();
				$('input[name="external"]').val(0);
			}

			$('p[id^="type_tip_"]').hide();
			$('#type_tip_' + type).show();
			last = $(this).val();
		}).change();
	}

	$('#js-delete-block').on('click', function()
	{
		Ext.Msg.confirm(_t('confirm'), _t('are_you_sure_to_delete_this_block'), function(btn, text)
		{
			if (btn == 'yes')
			{
				var pageUrl = window.location.href;
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
		CKEDITOR.instances[name] || intelli.ckeditor(name, {toolbar: 'Extended'});
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