Ext.onReady(function()
{
	$('.js-reinstall').click(function(e) {
		e.preventDefault();

		var $this = $(this);
		Ext.Msg.show({
			title: _t('confirm'),
			msg: _t('are_you_sure_reinstall_template'),
			buttons: Ext.Msg.YESNO,
			icon: Ext.Msg.QUESTION,
			fn: function (btn) {
				if ('yes' == btn) {
					document.location = $this.attr('href');
				}
			}
		});
	});
});