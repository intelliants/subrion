Ext.onReady(function()
{
	var pageUrl = intelli.config.admin_url + '/fields/group/';

	if ($('#js-grid-placeholder').length)
	{
		intelli.fieldgroups = new IntelliGrid(
		{
			columns: [
				'selection',
				{name: 'name', title: _t('name'), width: 1},
				{name: 'title', title: _t('title'), width: 1},
				{name: 'item', title: _t('item'), width: 130},
				{name: 'extras', title: _t('extras'), width: 130},
				{name: 'tabview', title: _t('view_as_tab'), width: 60, align: 'center', renderer: intelli.gridHelper.renderer.check},
				{name: 'collapsible', title: _t('collapsible'), width: 60, align: 'center', renderer: intelli.gridHelper.renderer.check},
				{name: 'order', title: _t('order'), width: 90, editor: 'number'},
				'update',
				'delete'
			],
			frame: true,
			texts: {delete_single: _t('are_you_sure_to_delete_fieldgroup')},
			url: pageUrl
		}, false);

		intelli.fieldgroups.toolbar = new Ext.Toolbar(
		{
			items:[
			{
				allowDecimals: false,
				allowNegative: false,
				emptyText: _t('id'),
				name: 'id',
				listeners: intelli.gridHelper.listener.specialKey,
				width: 90,
				xtype: 'numberfield'
			}, {
				displayField: 'title',
				editable: false,
				emptyText: _t('fields_item_filter'),
				name: 'item',
				store: new Ext.data.SimpleStore({fields: ['value', 'title'], data : intelli.config.items}),
				typeAhead: true,
				valueField: 'value',
				xtype: 'combo'
			}, {
				handler: function(){intelli.gridHelper.search(intelli.fieldgroups)},
				id: 'fltBtn',
				text: '<i class="i-search"></i> ' + _t('search')
			}, {
				handler: function(){intelli.gridHelper.search(intelli.fieldgroups, true)},
				text: '<i class="i-close"></i> ' + _t('reset')
			}]
		});

		intelli.fieldgroups.init();
	}
	else
	{
		$('#tabview, #field_item').on('change', function()
		{
			var $_fieldGroups = $('#js-fieldgroup-selectbox');
			var $_tabContainer = $('#js-tab-container');
			var $_collapsible = $('#js-collapsible');

			if (0 == $('#tabview').val())
			{
				var item = $('#field_item').val();
				var name = $('#group_name').val();

				$_fieldGroups.prop('disabled', true);
				if (item)
				{
					$.get(pageUrl + 'read.json', {action: 'gettabs', item: item, name: name}, function(response)
					{
						$_fieldGroups.children('option:not(:first)').remove();

						if (response.length > 0)
						{
							var selected = $('#tabcontainer').val();
							$.each(response, function(i, entry)
							{
								if (entry.name == selected)
								{
									selected = entry.name;
								}

								$_fieldGroups.append($('<option />').val(entry.name).text(_t('fieldgroup_' + entry.name)));
							});
							$_fieldGroups.val(selected);

							$_fieldGroups.prop('disabled', false);
							$_tabContainer.show();
						}
					});

					$_collapsible.show();
				}
			}
			else
			{
				$_collapsible.hide();
				$_tabContainer.hide();
			}
		}).change();

		$('#collapsible').on('change', function()
		{
			(0 == $(this).val()) ? $('#js-collapsed').hide() : $('#js-collapsed').show();
		}).change();
	}
});