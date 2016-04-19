(function($)
{
	var iaDropdown = function()
	{
		return {
			init: function(element, options)
			{
				this.cache = {};
				this.children = [];
				this.options = $.extend({exclusions: false}, options);

				this.$element = $(element);
				this.$label = $('#' + this.options.label);
				this.$value = $('input[name="' + this.options.valueHolder + '"]');

				this.onChange = this.options.onChange || null;

				var self = this;
				this.$element.on('change', function()
				{
					self.fnHandlerOnChange($(this));
				});

				if (this.$element.data('children'))
				{
					this.children = String(this.$element.data('children')).split(',');
					var val = this.children.shift();
					this.$element.val(val);
					this.$element.trigger('change');
				}

				return this;
			},
			updateLabel: function()
			{
				var paths = [];
				$(this.options.selector + ' option:selected:not([value=""])').each(function()
				{
					paths.push($(this).text());
				});

				this.$label.val(paths.length ? paths.join(' > ') : _t('_not_selected_'));
			},
			addDropdown: function(data, $parent, selected)
			{
				if (data && data.length)
				{
					var className = this.options.selector.split('.');
					className.shift();

					var $option = $('<option>').val('').text(_t('_select_'));
					var $select = $('<select>').addClass(className.join()).append($option);

					$.each(data, function(index, item)
					{
						$option = $('<option>').val(item.id).text(item.title);
						if (item.id == selected) $option.attr('selected', 'selected');
						if (item.locked == 1) $option.prop('disabled', true);
						$option.data({l: item.left, r: item.right});
						$select.append($option);
					});

					$parent.after($select);

					var self = this;
					$select.on('change', function()
					{
						self.fnHandlerOnChange($(this));
					});

					if (selected) $select.trigger('change');
				}
			},
			fnHandlerOnChange: function($element)
			{
				var value = $element.val();

				if (!value)
				{
					var $prev = $element.prev();

					if (!$prev.length)
					{
						$(this.options.selector).not(':first').remove();
					}
					else
					{
						value = $prev.val();
						$prev.nextAll().remove();
					}
				}
				else
				{
					$element.nextAll().remove();

					var selected = 0;
					if (this.children && this.children.length > 0)
					{
						selected = this.children.shift();
					}

					if (!this.cache[value])
					{
						var img = $('<img>').attr({'class': 'spinner', src: intelli.config.ia_url + 'templates/common/img/preloader.gif'}).insertAfter($element);
						var self = this;

						var params = {pid: value};
						if (this.options.exclusions)
						{
							params.id = this.$element.data('id');
						}
						$.getJSON(this.options.url + '?fn=?', params, function(response)
						{
							img.remove();
							self.cache[value] = true;
							if (response && response.length > 0)
							{
								self.cache[value] = response;
								self.addDropdown(response, $element, selected);
							}
						});
					}
					else if (this.cache[value] !== true)
					{
						this.addDropdown(this.cache[value], $element, selected);
					}
				}

				this.$value.val(value);

				if (this.$label.length) this.updateLabel();

				if (this.onChange) this.onChange(value);
			}
		};
	};


	$.fn.iaDropdown = function(options)
	{
		if (this.length)
		{
			options.selector = this.selector;
			return this.each(function()
			{
				var iadropdown = new iaDropdown;
				iadropdown.init(this, options);
				$.data(this, 'iadropdown', iadropdown);
			});
		}
	};
}(jQuery));