intelli.search = (function()
{
	var paramsMapping = {page: '__p', sortingField: '__s', sortingOrder: '__so'},

	decodeUri = function(uriComponent)
	{
		return (decodeURIComponent(uriComponent) + '').replace(/\+/g,' ');
	},

	events = {},
	params = {},

	$form = $('#js-item-filters-form'),

	composeParams = function(formValues)
	{
		return (formValues != '' ? formValues + '&' : '') + $.param(params);
	},

	fireEvent = function(name)
	{
		if ('function' == typeof events[name])
		{
			events[name]();
		}
	},

	parseHash = function()
	{
		var result = false,
			hash = window.location.hash.substring(1);

		if (hash != '')
		{
			result = {};
			hash = hash.split('&');

			for (var i = 0; i < hash.length; i++)
			{
				var a = hash[i].split('=');
				result[decodeUri(a[0])] = decodeUri(a[1])
			}
		}

		return result;
	};

	return {
		setParam: function(name, value)
		{
			if (undefined !== paramsMapping[name])
			{
				params[paramsMapping[name]] = value;
			}
		},

		bindEvents: function(fnStart, fnFinish)
		{
			if ('function' == typeof fnStart) events['start'] = fnStart;
			if ('function' == typeof fnStart) events['finish'] = fnFinish;
		},

		run: function(pageNum)
		{
			fireEvent('start');

			this.setParam('page', pageNum);

			$.ajax(
			{
				data: composeParams($form.serialize()),
				url: $form.attr('action'),
				success: function(response)
				{
					if (response.url !== undefined)
					{
						window.location = response.url;
						return;
					}

					window.location.hash = response.hash;

					if (response.html !== undefined)
					{
						$('#js-search-results-container').html(response.html);
						$('#js-search-results-num').html(response.total);
						$('#js-search-results-pagination').html(response.pagination);
					}

					fireEvent('finish');
				},
				error: function()
				{
					fireEvent('finish');
				}
			});
		},

		initFilters: function()
		{
			var values = parseHash();

			if (!values)
			{
				return;
			}

			for (var name in values)
			{
				var $ctl = $('[name="' + name + '"]', $form),
					value = values[name];

				if (!$ctl.length || !value)
				{
					continue;
				}

				switch ($ctl[0].nodeName.toLowerCase())
				{
					case 'input':
						switch ($ctl.attr('type'))
						{
							case 'checkbox':
							case 'radio':
								$ctl.filter('[value="' + value + '"]').prop('checked', true);
								break;
							default:
								$ctl.val(value);
						}

						break;
					case 'select':
						var $option = $('option[value="' + value + '"]', $ctl);

						if ($option.length)
							$option.prop('selected', true).trigger('change');
						else $ctl.data('value', value);
				}
			}

			this.run();
		}
	};
})();