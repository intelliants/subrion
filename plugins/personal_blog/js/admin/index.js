Ext.onReady(function()
{
	var pageUrl = intelli.config.admin_url + '/blog/';

	if (Ext.get('js-grid-placeholder'))
	{
		var urlParam = intelli.urlVal('status');

		intelli.blog =
		{
			columns: [
				'selection',
				{name: 'title', title: _t('title'), width: 2, editor: 'text'},
				{name: 'alias', title: _t('title_alias'), width: 220},
				'status',
				{name: 'date', title: _t('date'), width: 120, editor: 'date'},
				'update',
				'delete'
			],
			storeParams: urlParam ? {status: urlParam} : null,
			url: pageUrl
		};
		intelli.blog = new IntelliGrid(intelli.blog, false);
		intelli.blog.toolbar = Ext.create('Ext.Toolbar', {items:[
		{
			emptyText: _t('text'),
			name: 'text',
			listeners: intelli.gridHelper.listener.specialKey,
			width: 275,
			xtype: 'textfield'
		},{
			displayField: 'title',
			editable: false,
			emptyText: _t('status'),
			id: 'fltStatus',
			name: 'status',
			store: intelli.blog.stores.statuses,
			typeAhead: true,
			valueField: 'value',
			xtype: 'combo'
		},{
			handler: function(){intelli.gridHelper.search(intelli.blog);},
			id: 'fltBtn',
			text: '<i class="i-search"></i> ' + _t('search')
		},{
			handler: function(){intelli.gridHelper.search(intelli.blog, true);},
			text: '<i class="i-close"></i> ' + _t('reset')
		}]});
		if (urlParam)
		{
			Ext.getCmp('fltStatus').setValue(urlParam);
		}
		intelli.blog.init();
	}
	else
	{
		$('.js-date-field').datetimepicker({format: 'yyyy-mm-dd', autoclose: true, todayBtn: true, startView: 2, pickerPosition: 'top-left', minView: 2, maxView: 4});

		$('#input-title, #input-alias').on('blur', function()
		{
			var alias = $('#input-alias').val();
			var title = alias != '' ? alias : $('#input-title').val();

			if ('' != title)
			{
				$.get(pageUrl + 'read.json', {get: 'alias', title: title}, function(data)
				{
					if ('' != data.url)
					{
						$('#title_url').text(data.url);
						$('#title_box').fadeIn();
					}
					else
					{
						$('#title_box').hide();
					}
				});
			}
			else
			{
				$('#title_box').hide();
			}
		});
	}
});