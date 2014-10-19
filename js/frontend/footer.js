$(function()
{
	if ($('#error').length > 0)
	{
		$('html, body').animate({scrollTop: $('.page-header').offset().top});
	}

	if ('object' == typeof jQuery.tabs)
	{
		$('#ia-tab-container').tabs();
	}

	// hide tab if content is empty
	$('.tab-pane').each(function()
	{
		if ($.trim($(this).html()) == '')
		{
			var tabId = '#';
			tabId += $(this).attr('id');
			$(this).remove();
			$('a[href='+ tabId +']').parent('li').remove();
		}
	});

	// hide tab list if no tabs exists
	$('.tabbable').each(function()
	{
		if (!$(this).children('.nav-tabs').children('li').length)
		{
			$(this).remove();
		}
	});

	if ($('.tabbable').length > 0)
	{
		$('.tabbable .nav-tabs li a:first').tab('show');
	}

	$('input[placeholder]').each(function()
	{
		inputPlaceholder(this);
	});

	$('.js-filter-numeric').each(function()
	{
		$(this).numeric();
	});

	$('.search-text').focus(function () {
		$(this).parent().addClass('focused');
	}).focusout(function () {
		$(this).parent().removeClass('focused');
	});

	// Navbar clickable parent menus
	$('.nav > li:has(ul)').hover(function()
	{
		var _this = $(this);
		_this.addClass('open');
		var thisHref = $('> a', _this).attr('href');
		_this.click(function(){
			window.location = thisHref;
		});
		// disable click on More dropdown
		$('.dropdown-more').unbind('click');
	}, function()
	{
		var $this = $(this);
		setTimeout(function(){$this.removeClass('open');});
	});

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

	// update picture titles
	var pictureTitles = $('.js-edit-picture-title');
	if (pictureTitles.length)
	{
		$(pictureTitles).editable(
		{
			url: intelli.config.ia_url + 'actions.json',
			type: 'text',
			params: function(params)
			{
				var $self = $(this);

				params.action = 'edit-picture-title';
				params.field = $self.data('field');
				params.item = $self.data('item');
				params.itemid = $self.data('item-id');
				params.path = $self.data('picture-path');

				return params;
			},
			success: function(response)
			{
				var success = ('boolean' == typeof response.error && !response.error);
				intelli.notifFloatBox({msg: success ? _t('saved') : response.message, type: success ? 'success' : 'error', autohide: true});
			}
		});
	}

	// delete picture
	$('.js-delete-file').click(function(e)
	{
		e.preventDefault();

		var self = $(this);

		var path = self.data('picture-path');
		var id = self.data('item-id');
		var item = self.data('item');
		var field = self.data('field');

		if (confirm(_t('sure_rm_file')))
		{
			$.post(intelli.config.ia_url + 'actions/read.json', {action: 'delete-file', item: item, field: field, path: path, itemid: id}, function(data)
			{
				if ('boolean' == typeof data.error && !data.error)
				{
					self.closest('.thumbnail').remove();

					var counter = $('#' + field);
					if (counter.val() == 0)
					{
						$('#wrap_' + field).show();
					}

					intelli.notifFloatBox({msg: data.message, type: 'success', autohide: true});
				}
			});
		}
	});

	// add/delete pictures fields
	function detectFilename()
	{
		$('.upload-wrap').on('change', 'input[type="file"]', function()
		{
			var filename = $(this).val();
			var lastIndex = filename.lastIndexOf("\\");
			if (lastIndex >= 0) {
				filename = filename.substring(lastIndex + 1);
			}
			$(this).prev().find('.uneditable-input').text(filename);
		});
	}

	detectFilename();

	var addImgItem = function(btn)
	{
		var thisParent = $(btn).closest('.upload-gallery-wrap-outer');
		var clone = thisParent.clone(true);
		var name = $('input[type="file"]', thisParent).attr('name').replace('[]', '');
		var num = parseInt($('#' + name).val());

		if (num > 1)
		{
			$('input[type="file"]', clone).val('');
			$('.uneditable-input', clone).text(intelli.lang.click_here_to_upload);
			$('.upload-title', clone).val('');
			thisParent.after(clone);
			$('#' + name).val(num - 1);
		}
		else
		{
			intelli.notifFloatBox({msg: intelli.lang.no_more_files, autohide: true, pause: 2500});
		}

		detectFilename();
	};

	var removeImgItem = function(btn)
	{
		var thisParent = $(btn).closest('.upload-gallery-wrap-outer');
		var name = $('input[type="file"]', thisParent).attr('name').replace('[]', '');
		var num = parseInt($('#' + name).val());

		if (thisParent.prev().hasClass('upload-gallery-wrap-outer') || thisParent.next().hasClass('upload-gallery-wrap-outer'))
		{
			thisParent.remove();
			$('#' + name).val(num + 1);
		}
	};

	$('.js-add-img').on('click', function(e)
	{
		e.preventDefault();
		addImgItem(this);
	});

	$('.js-remove-img').on('click', function(e)
	{
		e.preventDefault();
		removeImgItem(this);
	});
});

function inputPlaceholder(input, color)
{
  if (!input) return null;

  // Do nothing if placeholder supported by browser (Webkit, Firefox 3.7)
  if (input.placeholder && 'placeholder' in document.createElement(input.tagName)) return input;

  color = color || '#AAA';
  var default_color = input.style.color;
  var default_type = input.type;
  var placeholder = input.getAttribute('placeholder');

  if (input.value === '' || input.value == placeholder) {
	input.value = placeholder;
	input.style.color = color;
	if (default_type == 'password') input.type = 'text';
  }

  var add_event = /*@cc_on'attachEvent'||@*/'addEventListener';

  input[add_event](/*@cc_on'on'+@*/'focus', function()
  {
	input.style.color = default_color;
	if (input.value == placeholder) {
	  input.value = '';
	  if (default_type == 'password') input.type = 'password';
	}
  }, false);

  input[add_event](/*@cc_on'on'+@*/'blur', function()
  {
	if (input.value === '') {
	  input.value = placeholder;
	  input.style.color = color;
	  if (default_type == 'password') input.type = 'text';
	} else {
	  input.style.color = default_color;
	}
  }, false);

  input.form && input.form[add_event](/*@cc_on'on'+@*/'submit', function()
  {
	if (input.value == placeholder) {
		input.value = '';
	}
  }, false);

  return input;
}