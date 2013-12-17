Ext.onReady(function()
{
	intelli.usergroups = new IntelliGrid(
	{
		columns: [
			'numberer',
			{name: 'title', title: _t('title'), width: 150, editor: 'text', renderer: function(value, metadata, record)
			{
				if (1 == record.get('admin'))
				{
					value = '<b style="color:green;">' + value + '</b>';
				}
				return value;
			}},
			{name: 'members', title: _t('all_members'), width: 2, renderer: function(value, metadata, record)
			{
				return value
					? value.replace(/, $/, '')
					: '<span style="color:red;font-style:italic;">-no members-</span>';
			}},
			{name: 'count', title: _t('members'), width: 80, align: 'center'},
			{name: 'admin', title: _t('admin_panel'), width: 110, renderer: function(value, metadata, record)
			{
				return (1 == record.get('admin'))
					? '<span style="color:green;">Allowed</span>'
					: '<span style="color:red;">Not allowed</span>';
			}},
			/*
			{name: 'permissions', title: _t('permissions'), href: intelli.config.admin_url + '/permissions/?group={id}', icon: 'folder'},
			{name: 'config', title: _t('go_to_config'), href: intelli.config.admin_url + '/configuration/?group={id}', icon: 'cogs'},
			*/
			'delete'
		],
		texts: {delete_single: _t('are_you_sure_to_delete_this_usergroup')},
		url: intelli.config.admin_url + '/usergroups/'
	});
});