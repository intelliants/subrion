$(function()
{
	setTimeout(function()
	{
		$('#js-ajax-loader').fadeOut();
		intelli.cookie.write('loader', 'loaded');
	}, 2000);

	// panel toggle
	$('.panel-toggle').on('click', function(e)
	{
		e.preventDefault();

		var $o = $('.overall-wrapper');

		if (!$o.hasClass('moved'))
		{
			$o
				.animate({marginLeft: 0, marginRight: '-370px'})
				.addClass('moved');
		} else {
			$o
				.animate({marginLeft: '-370px', marginRight: 0})
				.removeClass('moved');
		}
	});

	$('#user-logout').on('click', function()
	{
		intelli.cookie.write('loader', 'notloaded');
	});

	// main nav
	$('.nav-main > li > a').on('click', function(e)
	{
		if (!$(this).hasClass('dashboard'))
		{
			e.preventDefault();

			var toggler = $(this).data('toggle');

			$(this).parent().addClass('active').siblings().removeClass('active');
			$('#' + toggler).addClass('active').siblings().removeClass('active');

			if ($(window).scrollTop() > 0)
			{
				$('html, body').animate({scrollTop: 0}, 'fast');
			}
		}
	});

	// minmax
	var widgetsState = JSON.parse(intelli.cookie.read('widgetsState'));
	if (typeof widgetsState == 'undefined' || widgetsState == null)
	{
		widgetsState = {};
	}

	$('.widget').each(function()
	{
		if ('collapsed' == widgetsState[$(this).attr('id')])
		{
			$(this).addClass('collapsed');
		}
	});

	$('.widget-toggle').on('click', function(e)
	{
		e.preventDefault();

		var $obj = $(this).closest('.widget');
		var objContent = $obj.find('.widget-content');
		var objId = $obj.attr('id');

		if (!$obj.hasClass('collapsed'))
		{
			objContent.slideUp('fast', function()
			{
				$obj.addClass('collapsed');
			});

			widgetsState[objId] = 'collapsed';
		}
		else
		{
			objContent.slideDown('fast', function()
			{
				$obj.removeClass('collapsed');
				if (objContent.hasClass('mCustomScrollbar'))
				{
					objContent.mCustomScrollbar('update');
				}
			});

			widgetsState[objId] = '';
		}

		intelli.cookie.write('widgetsState', JSON.stringify(widgetsState));
	});


	// changelog
	$('.widget-content .changelog-item:last-child', '#widget-changelog').show();
	$('.nav-pills .dropdown-menu a', '#widget-changelog').on('click', function(e)
	{
		e.preventDefault();

		var changelogItem = $(this).data('item');
		var changelogNum  = $(this).text();

		$(this).parent().addClass('active').siblings().removeClass('active');
		$(this).closest('.nav').find('.dropdown-toggle').html(changelogNum + ' <span class="caret"></span>');
		$(changelogItem).show().siblings().hide();
	});


	// Tree toggle
	$('.js-categories-toggle').on('click', function(e)
	{
		e.preventDefault();

		var toggleWhat = $(this).data('toggle');

		$(toggleWhat).toggle();
	});

	$('textarea.js-code-editor').each(function()
	{
		editAreaLoader.init(
		{
			id : $(this).attr('id'),
			syntax: 'php',
			start_highlight: true,
			toolbar: 'search, go_to_line, |, undo, redo'
		});
	});

	$('textarea.js-wysiwyg').each(function()
	{
		intelli.ckeditor($(this).attr('id'), {height: '200px'});
	});

	// quick search
	$('.dropdown-menu a', '#quick-search').on('click', function(e)
	{
		e.preventDefault();

		var $form = $('#quick-search');

		if ('reset' == $(this).attr('rel'))
		{
			$('input[type="text"]:first', $form).val('');
			$('.dropdown-menu a:first', $form).trigger('click');
		}
		else
		{
			$(this).parent().parent().find('li').removeClass('active');
			$(this).parent().addClass('active');

			$form.attr('action', $(this).attr('href'));

			$(this).closest('ul').prev().html($(this).text() + ' <span class="caret"></span>');
		}
	});
	$('li.active a:first', '#quick-search').trigger('click');
	$('#quick-search').on('submit', function(e)
	{
		$(this).attr('action') || e.preventDefault();
		$(this).find('input[type="text"]:first').val() || e.preventDefault();
	});


	// switching
	$('.js-input-switch').on('switch-change', function(e, data)
	{
		$('input', this).val(data.value == true ? 1 : 0);
	});


	// file upload
	var fileUpload = function(elem)
	{
		var parent  = $(elem).closest('.file-upload'),
			trigger = $('input[type="file"]', parent);

		trigger.trigger('click');
		trigger.on('change', function()
		{
			var filename = trigger.val();
			var lastIndex = filename.lastIndexOf("\\");

			if (lastIndex >= 0)
			{
				filename = filename.substring(lastIndex + 1);
			}

			$('input[type="text"]:not(.file-title)', parent).val(filename);
		});
	};

	var addFileUploadField = function(elem)
	{
		var clone = $(elem).closest('.file-upload').clone();
		var counterObj = $('#' + $('input[type="file"]', clone).attr('name').replace('[]', ''));
		var counterNum = parseInt(counterObj.val());

		if (1 < counterNum)
		{
			$('input[type="file"], input[type="text"]', clone).val('');
			$(elem).closest('.file-upload').after(clone);
			counterObj.val(counterNum - 1);
		}
		else
		{
			intelli.notifFloatBox({msg: intelli.admin.lang.no_more_files, type: 'error', autohide: true, pause: 2500});
		}
	};

	var removeFileUploadField = function(elem)
	{
		var parent = $(elem).closest('.file-upload');
		var counterObj = $('#'+$('input[type="file"]', parent).attr('name').replace('[]', ''));
		var counterNum = parseInt(counterObj.val());

		if (parent.prev().hasClass('file-upload') || parent.next().hasClass('file-upload'))
		{
			parent.remove();
			counterObj.val(counterNum + 1);
		}
	};

	// activating buttons
	$('.upload-group')
		.on('click', '.js-file-browse', function(e)
		{
			e.preventDefault();
			fileUpload(this);
		})
		.on('click', '.js-file-add', function(e)
		{
			e.preventDefault();
			addFileUploadField(this);
		})
		.on('click', '.js-file-remove', function(e)
		{
			e.preventDefault();
			removeFileUploadField(this);
		});

	$('.js-file-delete').on('click', function(e)
	{
		e.preventDefault();
		// removeFile(this);
	});

	// tooltips
	$('.js-tooltip').tooltip().on('click', function(e)
	{
		e.preventDefault();
	});

	// add password generator
	$('.js-input-password').passField({showWarn: false, showTip: false});

	if ($('.right-column .box').length > 1)
	{
		var items = [];
		var name,rand;
		$('.right-column .box').each(function()
		{
			rand = 'box'+Math.random();
			if ($(this).attr('id'))
			{
				name = $(this).attr('id');
				$(this).attr('id', rand);
			}
			else
			{
				name = rand;
			}
			$(this).find('.box-content').attr('id', name).show();
			items.push(
			{
				contentEl: name,
				title: '<div class="tab-caption">'+$(this).find('.box-caption').text()+'</div>'
			});
		}).hide();


		$('.right-column .box:first').before('<div id="ext_tabs"></div>');
		$('.x-tab-panel > div').removeClass('x-tab-panel-header');
	}

	if ($().datepicker)
	{
		$('.js-datepicker').datepicker({
			showTime: true,
			format: 'yyyy-mm-dd H:i:s',
			language: intelli.config.lang
		});

		$('.js-datepicker-toggle').on('click', function(e)
		{
			e.preventDefault();

			$(this).prev().datepicker('show');
		});
	}

	/* header-menu show/hide START */
	if ($('#alert').length)
	{
		$('#alert').show();
	}
	if ($('#success'))
	{
		var text = []; 
		$('#success .inner li').each(function()
		{
			text.push($(this).html());
		});
		$('#success').html('');
		if (text.length > 0)
		{
			intelli.admin.notifBox({msg: text, type: 'notification', autohide: true});
		}
	}
	if ($('#notification').length)
	{
		$('#notification').show();
	}

	/* feedback form START */
	$('#feedback_subject').change(function()
	{
		var subject = $(this).val(), classname = '';

		if ('feature_request' == subject)
		{
			classname = 'i-bug';
		}
		else if ('bug_report' == subject)
		{
			classname = 'i-lightning';
		}
		else if ('custom_modification' == subject)
		{
			classname = 'i-fire';
		}

		$('#feedback_subject_label').html('<i class="' + classname + '"></i> ' + _t('subject'));
	});

	$('#feedback_fullname, #feedback_email').focus(function()
	{
		var _this = $(this);
		if (_this.data('def') == _this.val())
		{
			_this.val('');
		}
	}).blur(function()
	{
		var _this = $(this);
		if (_this.val() == '')
		{
			_this.val(_this.data('def'));
		}
	});

	$('#js-cmd-send-feedback').click(function()
	{
		var $subject = $('#feedback_subject');
		var body = $('#feedback_body').val();

		if ('' != body && '' != $subject.val())
		{
			$.ajax(
			{
				data: {action: 'request', subject: $subject.find('option:selected').text(), body: body, fullname: $('#feedback_fullname').val(), email: $('#feedback_email').val()},
				success: function(response)
				{
					$('#feedback-modal').modal('hide');
					intelli.notifFloatBox({msg: response.message, type: response.result ? 'success' : 'error', autohide: true});
				},
				type: 'POST',
				url: intelli.config.admin_url + '.json'
			});
		}
		else
		{
			intelli.notifFloatBox({msg: _t('body_incorrect'), type: 'error', autohide: true});
		}
	});

	$('#clearFeedback').on('click', function()
	{
		$('#feedback_body').val('');
	});
	/* feedback form END */

	$('div.minmax').each(function()
	{
		$(this).on('click', function()
		{
			if ($(this).next('.box-content').css('display') == 'block')
			{
				$(this).next('.box-content').slideUp();
				Ext.util.Cookies.set(this.id, 0);
			}
			else
			{
				$(this).next('.box-content').slideDown();
				Ext.util.Cookies.set(this.id, 1);
			}
			$(this).toggleClass('white-close white-open');
		});
	});

	function getMousePosition(e)
	{
		return {x: e.clientX + document.documentElement.scrollLeft, y: e.clientY + document.documentElement.scrollTop};
	}

	// get substring count with limit
	$.fn.substrCount = function(needle)
	{
		var h = this.text();
		var times = 0;
		while((pos=h.indexOf(needle)) != -1)
		{
			h = h.substr(pos+needle.length);
			times++;
		}

		return times;
	};

	function stopPropagation(ev)
	{
		ev = ev||event;/* get IE event ( not passed ) */
		ev.stopPropagation? ev.stopPropagation() : ev.cancelBubble = true;
	}

	textareaResizer = function()
	{
		$('textarea.resizable').each(function()
		{
		var obj = $(this);

		cl = obj.attr('class');
		if (cl && -1 != cl.indexOf('noresize'))
		{
			return false;
		}

		var content = obj.text();
		var Height = 75;
		if (content.length)
		{
			// IE - doesnt find \n I gave up I don't know why it is so ..
			// Firefox works just as it must work as well as Opera
			var times = obj.substrCount(navigator.userAgent.match(/msie/i) ? "\r" : "\n");
			if (times > 20)
			{
				Height = 200;
			}
			else
			{
				Height = 70+10*times;
			}
		}

		obj.height(Height);

		var offset = null;

		$(this).wrap('<div class="resizable-textarea"></div>').after($('<div class="resizable-textarea2"></div>').bind("mousedown", dragBegins));


		var image = $('div.resizable-textarea2', $(this).parent())[0];
		image.style.marginRight = (image.offsetWidth - $(this)[0].offsetWidth) +'px';

		function dragBegins(e)
		{
			offset = obj.height() - getMousePosition(e).y;
			if ($.browser.opera)
			{
				offset -= 6;
			}
			$(document)
				.bind('mousemove', doDrag)
				.bind('mouseup', dragEnds);
			stopPropagation(e);
		}

		function doDrag(e)
		{
			obj.height(Math.max(15, offset + getMousePosition(e).y) + 'px');
			stopPropagation(e);
		}

		function dragEnds(e)
		{
			$(document).unbind();
		}
	  });
	}();

	/*
	 * Help tooltips
	 */
	$('.tip-header').each(function()
	{
		var id = $(this).attr('id').replace('tip-header-', '');

		if ($('#tip-content-' + id).length > 0)
		{
			$(this).append('<span class="question" id="tip_'+ id +'"><img src="'+intelli.config.admin_url+'/templates/'+intelli.config.admin_tmpl+'/img/icons/sp.gif" alt="" width="16" height="17" /></span>').find("span.question").each(function()
			{
				new Ext.ToolTip(
				{
					target: this,
					dismissDelay: 0,
					contentEl: 'tip-content-' + id
				});
			});
		}
	});

	/*
	 * Init AJAX notification box
	 */
	$('.collapsed[rel]').on('click', function()
	{
		$($(this).attr('rel')).toggle();
	});

	/*
	 * Resolving issues
	 */

	if ($('.notifications.alerts').length > 0)
	{
		// remove installer
		var $installerAlert = $('.alert-danger:contains("module.install.php")');
		if ($installerAlert.length > 0)
		{
			$installerAlert.on('click', '.b-resolve__btn', function(event)
			{
				event.preventDefault();
				event.stopPropagation();

				var $this = $(this);

				if (!$this.hasClass('disabled'))
				{
					$this.animate(
					{
						left: '10px',
						opacity: 0
					}, 150, function()
					{
						$this.hide().prev().show(function()
						{
							$.post(intelli.config.admin_url + '/actions/read.json', {action: 'remove-installer'}, function(response)
							{
								if (!response.error)
								{
									$this.prev().animate(
									{
										left: '10px',
										opacity: 0
									}, 150, function()
									{
										$this.prev().hide().prev().show();
									});

									$installerAlert
										.removeClass('alert-danger')
										.addClass('alert-info');

									setTimeout(function()
									{
										clearNotification($installerAlert);
									}, 2000);
								}
							});
						});
					});
				}
			});

			var resolveBtnHtml = '<div class="b-resolve__wrapper">' + 
									'<span href="#" class="b-resolve__btn b-resolve__btn--result" title="' + _t('notification_resolve--resolved') + '"><i class="i-checkmark"></i> ' + _t('notification_resolve--resolved') + '</span>' + 
									'<span href="#" class="b-resolve__btn b-resolve__btn--progress" title="' + _t('notification_resolve--working') + '"><i class="i-spinner"></i> ' + _t('notification_resolve--working') + '</span>' + 
									'<a href="#" class="b-resolve__btn" title="' + _t('notification_resolve--resolve') + '"><i class="i-wrench"></i> ' + _t('notification_resolve--resolve') + '</a>' + 
								 '</div>';
			$installerAlert.addClass('b-resolve').append(resolveBtnHtml);
		}
	}
});

function clearNotification(el)
{
	var $nLabel = $('.notifications.alerts > a .label-info'),
		$nLabelCount = parseInt($nLabel.text()),
		$nBlock = $('.notifications.alerts .dropdown-block');

	if (0 != $nLabelCount)
	{
		$nLabel.text($nLabelCount - 1);
	}

	if ($('.alert', $nBlock).length >= 2)
	{
		el.animate(
		{
			top: '-10px',
			opacity: 0
		}, 150, function()
		{
			el.hide();
		})
	}
	else
	{
		el.closest('.dropdown').find('.dropdown-toggle').dropdown('toggle');
	}
}