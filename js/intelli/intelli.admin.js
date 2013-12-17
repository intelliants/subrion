intelli.admin = function()
{
	/*
	 * Constants 
	 */

	/**
	 * AJAX loader box id
	 * @type String
	 */
	var BOX_AJAX_ID = 'js-ajax-loader';

	// use on login page
	if (typeof Ext != 'undefined')
	{
		Ext.Ajax.defaultHeaders = {'X-FlagToPreventCSRF': 'using ExtJS'};
	}

	$.ajaxSetup(
	{
		global: true,
		beforeSend: function(xhr)
		{
			xhr.setRequestHeader('X-FlagToPreventCSRF', 'using jQuery');
		}
	});

	function ajaxLoader()
	{
		/* show and hide ajax loader box */
		var loaderBox = Ext.get(BOX_AJAX_ID);

		Ext.Ajax.on('beforerequest', loaderBox.show);
		Ext.Ajax.on('requestcomplete', function()
		{
			loaderBox.hide({duration: '1'});
		});

		$('#' + BOX_AJAX_ID)
			.ajaxStart(function(){$(this).fadeIn('1000');})
			.ajaxStop(function(){$(this).fadeOut('1000');});

		return loaderBox;
	}

	return {
		/**
		 * Assign event for displaying AJAX actions
		 *
		 * @return object of box
		 */
		initAjaxLoader: ajaxLoader,
		/**
		 * Show or hide element 
		 *
		 * @opt array array of options
		 * @el string id of element
		 * @action string the action (show|hide|auto)
		 *
		 * @return object of element
		 */
		display: function(opt)
		{
			if (!opt.el)
			{
				return false;
			}

			var obj = ('string' == typeof opt.el) ? Ext.get(opt.el) : opt.el;
			var act = opt.action || 'auto';

			if ('auto' == act)
			{
				act = obj.isVisible() ? 'hide' : 'show';
			}

			obj[act]();

			return obj;
		},

		/**
		 * Show alert notification message 
		 *
		 * @opt array array of options
		 * @msg string the message
		 * @title string the title of box
		 * @type string the type of message
		 *
		 * @return void
		 */
		alert: function(opt)
		{
			if (Ext.isEmpty(opt.msg))
			{
				return false;
			}

			opt.title = (Ext.isEmpty(opt.title)) ? 'Alert Message' : opt.title;
			opt.type = intelli.inArray(opt.type, ['error', 'notif']) ? opt.type : 'notif';

			var icon = ('error' == opt.type) ? Ext.MessageBox.ERROR : Ext.MessageBox.WARNING;

			Ext.Msg.show({title: opt.title, msg: opt.msg, buttons: Ext.Msg.OK, icon: icon});
		},

		/**
		 * Reload the admin menu tree
		 *
		 * @return void
		 */
		synchronizeAdminMenu: function(currentPage)
		{
			currentPage = currentPage || 'plugins';

			$.ajax({
				data: {action: 'menu'},
				success: function(response)
				{
					var menus = response.menus;
					$('.nav-main li').not(':first').each(function()
					{
						var name = $(this).attr('id').split('menu-section-')[1];
						var html = '';
						if (menus[name])
						{
							var items = menus[name].items;
							if (items && items.length > 0)
							{
								for (var i = 0; i < items.length; i++)
								{
									html += items[i].name
										? '<li' + (currentPage == items[i].name ? ' class="active"' : '') + '>'
											+ '<a href="' + items[i].url + '"' + (undefined === items[i].attr ? '' : items[i].attr) + '>' + items[i].title + '</a>'
											+ (items[i].config ? '<a href="configuration/' + items[i].config + '/" class="nav-sub__config" title="' + _t('settings') + '"><i class="i-cog"></i></a>' : '')
											+ '</li>'
										: '<li class="heading">' + items[i].title + '</li>';
								}
							}

							$('#nav-sub-' + name).html(html);
						}
						if ('' == html)
						{
							$('#menu-section-' + name).hide();
							$(this).hide();
						}
					});
				},
				type: 'POST',
				url: intelli.config.admin_url + '/index/read.json'
			});
		},

		updatePictureTitle: function(object, title)
		{
			var $_this = $(object);

			var field = $_this.data('field');
			var item = $_this.data('item');
			var itemid = $_this.data('item-id');
			var path = $_this.data('picture-path');

			$.post(intelli.config.admin_url + '/actions.json',
				{action: 'edit_picture_title', item: item, field: field, path: path,itemid: itemid, value: title},
				function(data)
				{
					if ('boolean' == typeof data.error && !data.error)
					{
						intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});

						$_this.hide();
					}
			});
		},

		removeFile: function(path, link, item, field, itemid)
		{
			Ext.Msg.confirm(_t('confirm'), _t('sure_rm_file'), function(btn, text)
			{
				if (btn == 'yes')
				{
					$.post(intelli.config.admin_url + '/actions.json',
						{action: 'delete-file', item: item, field: field, path: path, itemid: itemid},
						function(data)
						{
							if ('boolean' == typeof data.error && !data.error)
							{
								if ($(link).closest('.input-group').hasClass('thumbnail-single'))
								{
									$('#field_' + field).closest('.input-group').find('input[type="text"]').attr('placeholder', _t('file_click_to_upload'));
									$(link).closest('.input-group').remove();
								}

								$(link).closest('.file-upload, .input-group').remove();

								var counter = $('#' + field);
								try {
									counter.val(parseInt(counter.val()) + 1);
									if (counter.val() == 0)
									{
										$('.file-upload:hidden', '#upload-group-' + field).show();
									}
								}
								catch (e) {}

								intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
							}
						}
					);
				}
			});

			return false;
		}
	}
}();

intelli.admin.lang = {};