intelli = {
	/**
	 * Name of the current page
	 */
	pageName: '',

	lang: {},

	/**
	 *  Check if value exists in array
	 *
	 *  @param {Array} val value to be checked
	 *  @param {String} arr array
	 *
	 *  @return {Boolean}
	 */
	inArray: function(val, arr)
	{
		if (typeof arr == 'object' && arr)
		{
			for (var i = 0; i < arr.length; i++)
			{
				if (arr[i] == val)
				{
					return true;
				}
			}
		}

		return false;
	},

	cookie: {
		/**
		 * Returns the value of cookie
		 *
		 * @param {String} name cookie name
		 *
		 * @return {String}
		 */
		read: function(name)
		{
			var nameEQ = name + '=';
			var ca = document.cookie.split(';');
			for (var i = 0; i < ca.length; i++)
			{
				var c = ca[i];
				while (c.charAt(0)==' ') c = c.substring(1, c.length);
				if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
			}

			return null;
		},

		/**
		 * Creates new cookie
		 *
		 * @param {String} name cookie name
		 * @param {String} value cookie value
		 * @param {Integer} days number of days to keep cookie value for
		 * @param {String} value path value
		 */
		write: function(name, value, days, path)
		{
			var expires = '';
			if (days)
			{
				var date = new Date();
				date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
				expires = '; expires=' + date.toGMTString();
			}

			path = path || '/';

			document.cookie = name + '=' + value + expires + '; path=' + path;
		},

		/**
		 * Clear cookie value
		 *
		 * @param {String} name cookie name
		 */
		clear: function(name)
		{
			intelli.cookie.write(name, '', -1);
		}
	},

	urlVal: function(name)
	{
		name = name.replace(/[\[]/, "\\\[").replace(/[\]]/, "\\\]");

		var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
		var results = regex.exec(window.location.href);

		return (null === results)
			? null
			: decodeURIComponent(results[1]);
	},

	notifBox: function(opt)
	{
		var msg = opt.msg;
		var type = opt.type || 'info';
		var autohide = opt.autohide || (type == 'notification' || type == 'success' || type == 'error' ? true : false);
		var pause = opt.pause || 10;
		var html = '';

		if ('notif' == type || type == 'notification')
		{
			type = 'success';
		}

		var boxid = 'notification';
		if (opt.boxid)
		{
			boxid = opt.boxid;
		}

		var obj = $('#' + boxid);
		if ($.isArray(msg))
		{
			html += '<ul class="unstyled">';
			for (var i = 0; i < msg.length; i++)
			{
				if ('' != msg[i])
				{
					html += '<li>' + msg[i] + '</li>';
				}
			}
			html += '</ul>';
		}
		else
		{
			html += ['<div>', msg, '</div>'].join('');
		}

		obj.attr('class', 'alert alert-' + type).html(html).show();

		if (autohide)
		{
			obj.delay(pause * 1000).fadeOut('slow');
		}

		$('html, body').animate({scrollTop: obj.offset().top}, 'slow');

		return obj;
	},

	notifFloatBox: function(options)
	{
		var msg = options.msg,
			type = options.type || 'info',
			pause = options.pause || 3000,
			autohide = options.autohide,
			html = '';

		// building message box
		html += '<div id="notifFloatBox" class="notifFloatBox notifFloatBox--' + type + '"><a href="#" class="close">&times;</a>';
		if ($.isArray(msg))
		{
			html += '<ul>';
			for (var i = 0; i < msg.length; i++)
			{
				if ('' != msg[i])
				{
					html += '<li>' + msg[i] + '</li>';
				}
			}
			html += '</ul>';
		}
		else
		{
			html += '<ul><li>' + msg + '</li></ul>';
		}
		html += '</div>';

		// placing message box
		if (!$('#notifFloatBox').length > 0)
		{
			$(html).appendTo('body').css('display', 'block').addClass('animated bounceInDown');

			if (autohide)
			{
				setTimeout(function()
				{
					$('#notifFloatBox').fadeOut(function(){$(this).remove();});
				}, pause);
			}

			$('.close', '#notifFloatBox').on('click', function(e)
			{
				e.preventDefault();
				$('#notifFloatBox').fadeOut(function(){$(this).remove();});
			});
		}
	},

	is_email: function(email)
	{
		return (email.search(/^([a-zA-Z0-9_\.\-\+])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z]{2,3})+$/) > -1);
	},

	ckeditor: function(name, params)
	{
		if (CKEDITOR.instances[name])
		{
			return false;
		}

		params = params || {};
		params.baseHref = intelli.config.ia_url;

		if ('1' == intelli.config.ckeditor_mobile)
		{
			CKEDITOR.env.isCompatible = true;
		}

		CKEDITOR.replace(name, params);
	},

	add_tab: function(name, text)
	{
		var $tab = $('<li>').append($('<a>').attr({'data-toggle': 'tab', href: '#' + name}).text(text));
		var $content = $('<div>').attr('id', name).addClass('tab-pane');

		if ($('.nav-tabs', '.tabbable').children().length == 0)
		{
			$tab.addClass('active');
			$content.addClass('active');
		}

		$('.nav-tabs', '.tabbable').append($tab);
		$('.tab-content', '.tabbable').append($content);
	},

	actionFavorites: function(item_id, item, action)
	{
		$.ajax(
		{
			url: intelli.config.ia_url + 'profile/favorites/read.json',
			type: 'get',
			data: {item: item, item_id: item_id, action: action},
			success: function(data)
			{
				if (!data.error)
				{
					window.location.href = window.location.href;
				}
			}
		});
	}
};

function _t(key, def)
{
	if (intelli.admin && intelli.admin.lang[key])
	{
		return intelli.admin.lang[key];
	}

	return _f(key, def);
}

function _f(key, def)
{
	if (intelli.lang[key])
	{
		return intelli.lang[key];
	}

	return (def ? (def === true ? key : def) : '{' + key + '}');
}