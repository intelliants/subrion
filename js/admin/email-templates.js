$(function()
{
	var tagsWindow = new Ext.Window(
	{
		title: intelli.admin.lang.email_templates_tags,
		layout: 'fit',
		modal: false,
		closeAction : 'hide',
		contentEl: 'template-tags',
		buttons: [
		{
			text: intelli.admin.lang.close,
			handler: function()
			{
				tagsWindow.hide();
			}
		}]
	});

	$('#js-view-tags').click(function(e)
	{
		e.preventDefault();
		tagsWindow.show();
	});

	$('#tpl').on('change', function(e)
	{
		var id = $(this).val();
		var switchers = $('#enable_sending, #use_signature');

		// hide if none selected
		if (!id)
		{
			$('#subject').val('').prop('disabled', true);
			CKEDITOR.instances.body.setData('');
			switchers.hide();
			$('button[type="submit"]', '#js-email-template-form').prop('disabled', true);

			return;
		}

		// get actual values
		$.get(intelli.config.admin_url + '/email-templates.json', {id: id}, function(response)
		{
			$('#subject').val(response.subject);
			CKEDITOR.instances.body.setData(response.body);

			$('#enable_sending').bootstrapSwitch('setState', response.config);
			$('#use_signature').bootstrapSwitch('setState', response.signature);
		},
		'json');

		switchers.show();
		$('#subject, #js-email-template-form button[type="submit"]').prop('disabled', false);
	});

	$('#js-email-template-form').submit(function(e)
	{
		e.preventDefault();

		var value = $('#tpl').val();

		if ('' == value)
		{
			return;
		}

		if ('object' == typeof CKEDITOR.instances.body)
		{
			CKEDITOR.instances.body.updateElement();
		}

		$.post(intelli.config.admin_url + '/email-templates/edit.json', {id: value, subject: $('#subject').val(),
			enable_template: $('#enable_template').val(), enable_signature: $('#enable_signature').val(), body: CKEDITOR.instances.body.getData()}, function(data)
		{
			if (data == 'ok')
			{
				intelli.notifFloatBox({msg: intelli.admin.lang.saved, type: 'notif', autohide: true, pause: 1500});
			}
		});
	});

	$('ul.js-tags a').click(function(e)
	{
		e.preventDefault();
		CKEDITOR.instances.body.insertHtml($(this).text());
	});
});