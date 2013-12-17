Ext.onReady(function()
{
	$('.js-cmd-info').click(function(e)
	{
		e.preventDefault();

		Ext.Ajax.request(
		{
			url: intelli.config.admin_url + '/templates.json',
			method: 'POST',
			params: {get: 'info', name: $(this).attr('rel')},
			failure: function()
			{
				Ext.MessageBox.alert(_t('error_while_doc_tabs'));
			},
			success: function(response)
			{
				response = Ext.decode(response.responseText);
				var tabs = response.tabs;
				var info = response.info;

				if (null != tabs)
				{
					tabs = new Ext.TabPanel(
					{
						region: 'center',
						bodyStyle: 'padding: 5px;',
						activeTab: 0,
						defaults: {autoScroll: true},
						items: tabs
					});

					info = new Ext.Panel(
					{
						region: 'east',
						split: true,
						minWidth: 200,
						collapsible: true,
						html: info,
						bodyStyle: 'padding: 5px;'
					});

					var win = new Ext.Window(
					{
						title: _t('extra_documentation'),
						closable: true,
						width: 800,
						height: 550,
						border: false,
						plain: true,
						layout: 'border',
						items: [tabs, info]
					});

					win.show();
				}
				else
				{
					Ext.Msg.show(
					{
						title: _t('error'),
						msg: _t('doc_extra_not_available'),
						buttons: Ext.Msg.OK,
						icon: Ext.Msg.ERROR
					});
				}
			}
		});
	});
});