/**
 * Class for creating listing fields section.
 * @class This is the Listing fields class.  
 *
 * @param {Array} conf
 *
 * @param {String} conf.id The id div of fields element
 * @param {Array} conf.session The array of last state
 * @param {Boolean} conf.restore Restoring data after updating box
 * @param {String} conf.listingId The listing id
 *
 */
intelli.fields = function(conf)
{
	var obj = (-1 != conf.id.indexOf('#')) ? $(conf.id) : $('#' + conf.id);
	var restore = conf.restore ? conf.restore : false;
	var lastState = (typeof conf.session != 'undefined') ? conf.session : new Array();
	var listingId = conf.listingId || 0;
	var part = conf.part || 'suggest';

	var listingFields = new Array();

	/**
	 * Return id current category 
	 *
	 * @return {Integer}
	 */
	var getIdCategory = function()
	{
		return $('#category_id').val();
	};

	/**
	 * Return id current plan
	 *
	 * @return {Integer}
	 */
	var getIdPlan = function()
	{
		var id = '';
		
		if($('#plans').length > 0)
		{
			id = $("#plans input[type='radio']:checked").val();
		}

		return id;
	};

	var addImgItem = function(btn)
	{
		var clone = btn.parent().clone(true);
		var name = btn.siblings("input[type='file']").attr("name").replace('[]', '');
		var num = $("#" + name + "_num_img").val();

		if(num > 0)
		{
			$('input:file', clone).val('');
			btn.parent().after(clone);
			$("#" + name + "_num_img").val(num - 1);
		}
		else
		{
			alert(intelli.lang.no_more_files);
		}
	};

	var removeImgItem = function(btn)
	{
		var name = btn.siblings("input[type='file']").attr("name").replace('[]', '');
		var num = $("#" + name + "_num_img").val();

		if (btn.parent().prev().attr('class') == 'pictures' || btn.parent().next().attr('class') == 'pictures')
		{
			btn.parent().remove();
			$("#" + name + "_num_img").val(num * 1 + 1);
		}
	};

	/**
	 * Create a listing field
	 *
	 * @param {Array} conf Array of setting listing field
	 */
	var createField = function(conf)
	{
		var html = '';
		var length = conf.length.split(',');

		var min = parseInt(length[0]);
		var max = parseInt(length[1]);

		html += '<p class="field" id="form_field_'+ conf.name +'">';
		html += '<strong>' + conf.title + ':</strong>&nbsp;';
		if('' != conf.tooltip)
		{
			html += '<img class="field_tooltip" title="' + conf.tooltip + '" id="field_tooltip_'+ conf.name +'" src="templates/'+ intelli.config.tmpl +'/img/sp.gif" />';
		}
		html += '<br />';

		switch(conf.type)
		{
			case 'text':
				html += '<input type="text" class="text" size="35" id="f_' + conf.name + '" value="'+ conf['default'] +'" name="' + conf.name + '" />';
				break;
			case 'textarea':
				html += '<textarea cols="45" rows="8" id="f_' + conf.name + '" name="' + conf.name + '">'+ conf['default'] +'</textarea>';

				if(!isNaN(min) || !isNaN(max))
				{
					html += '<br /><input type="text" id="counter_'+ conf.name +'" size="4" readonly />&nbsp;' + intelli.lang.characters_count;
				}
				break;
			case 'image':
				html += '<input type="file" id="f_' + conf.name + '" name="'+ conf.name +'" size="35" />';
				break;
			case 'pictures':
				var length = parseInt(conf.length) - 1;

				html += '<div class="pictures">';
				html += '<input type="file" name="'+ conf.name +'[]" size="35" />';
				html += '<input type="button" value="+" class="add_img" />';
				html += '<input type="button" value="-" class="remove_img" />';
				html += '</div>';
				html += '<input type="hidden" value="' + length + '" name="num_images" id="'+ conf.name +'_num_img" />';
				break;
			case 'combo':
				var values = conf.values.split(',');
				var selected = '';
				
				html += '<select name="'+ conf.name +'" id="f_' + conf.name + '">';

				for(var i = 0; i < values.length; i++)
				{
					selected = '';
					selected = (conf['default'] == values[i]) ? 'selected="selected"' : "";

					html += '<option value="'+ values[i] +'" '+ selected +'>';
					html += conf.labels[values[i]];
					html += '</option>';
				}

				html += '</select>';
				break;
			case 'radio':
				var values = conf.values.split(',');
				var checked = '';

				for(var i = 0; i < values.length; i++)
				{
					checked = '';
					checked = (conf['default'] == values[i]) ? 'checked="checked"' : "";

					html += '<input type="radio" name="'+ conf.name +'" id="f_'+ conf.name + '_' + i +'" value="'+ values[i] +'"'+ checked +'/>';
					html += '<label for="f_'+ conf.name + '_' + i +'">'+ conf.labels[values[i]] +'</label><br />'
				}

				break;
			case 'checkbox':
				var values = conf.values.split(',');
				var defaults = conf['default'].split(',');
				var checked = '';

				for(var i = 0; i < values.length; i++)
				{
					checked = '';
					checked = (intelli.inArray(values[i], defaults)) ? 'checked="checked"' : "";

					html += '<input type="checkbox" name="'+ conf.name +'[]" id="f_'+ conf.name + '_' + i +'" value="'+ values[i] +'"'+ checked +'/>';
					html += '<label for="f_'+ conf.name + '_' + i +'">'+ conf.labels[values[i]] +'</label><br />'
				}

				break;
			case 'storage':
				html += '<input type="file" id="f_' + conf.name + '" name="'+ conf.name +'" size="35" />';
				break;
			case 'number':
				html += '<input type="text" class="text" size="35" id="f_' + conf.name + '" value="'+ conf['default'] +'" name="' + conf.name + '" />';
				break;
			default:
				break;
		}

		html += '</p>';
		html += '<p class="field" id="val_field_'+ conf.name +'" style="display: none;">';
		html += '<strong>' + conf.title + ':</strong>&nbsp;';
		html += '<img class="edit-field" title="'+ intelli.lang.edit + ' '+ conf.title +'" alt="'+ intelli.lang.edit + ' '+ conf.title +'" src="templates/'+ intelli.config.tmpl +'/img/sp.gif" id="edit_button_'+ conf.name +'" />';
		html += '<span></span>';
		html += '</p>';

		obj.append(html);

		// help tooltip initialization
		if('' != conf.tooltip)
		{
			$('#field_tooltip_' + conf.name).tooltip(
			{
				showURL: false, 
				showBody: " - "
			});
		}

		// textarea WSYWYG editor
		if('textarea' == conf.type && 1 == conf.editor)
		{
			if(CKEDITOR.instances["f_" + conf.name])
			{
				delete CKEDITOR.instances["f_" + conf.name];
			}

			if(!isNaN(min) || !isNaN(max))
			{
				var opt = {toolbar: 'Basic', counter: 'counter_' + conf.name, max_length: max};
			}
			else
			{
				var opt = {toolbar: 'Basic'};
			}

			intelli.ckeditor("f_" + conf.name, opt);
		}

		// textcounter for textarea fields initialization
		if('textarea' == conf.type && 0 == conf.editor && (!isNaN(min) || !isNaN(max)))
		{
			var textcounter = new intelli.textcounter(
			{
				textarea_el: 'f_' + conf.name,
				counter_el: 'counter_' + conf.name,
				min: min,
				max: max
			});

			textcounter.init();
		}

		if('number' == conf.type)
		{
			$('#f_' + conf.name).keydown(function(e)
			{
				var code = e.which || e.keyCode;

				if(code > 31 && (code < 48 || code > 57))
				{
					return false;
				}

				return true;
			});
		}

		// image gallery
		if('pictures' == conf.type)
		{
			$("div.pictures").find("input[type='button']").each(function()
			{
				$(this).click(function()
				{
					var action = $(this).attr('class');

					if('add_img' == action)
					{
						addImgItem($(this));
					}
					else
					{
						removeImgItem($(this));
					}
				});
			});
		}

		// edit button event handler
		$('#edit_button_'+ conf.name).click(function()
		{
			var nameField = $(this).attr('id').replace('edit_button_', '');

			$('#divSuggestButton').hide();
			$('#saveChanges').show();

			$('#val_field_' + nameField).hide();
			$('#form_field_' + nameField).show();			

			$('#divSuggestButton').hide();
			$('#saveChanges').show();
		});
	};

	/**
	 * Fill up the fields section 
	 */
	this.fillFields = function()
	{
		// Save current values
		if(restore)
		{
			saveState();
		}

		// Clearing field box
		obj.empty();

		$(obj).hide();

		// Get default ids category and plan
		var idCategory = getIdCategory();
		var idPlan = getIdPlan();
		var params = {action: 'getfields', part: part, idcategory: idCategory};

		if(idPlan)
		{
			params['idplan'] = idPlan;
		}

		if(listingId)
		{
			params['idlisting'] = listingId;
		}

		$.ajaxSetup({async: false});

		// Getting listings fields by AJAX
		$.getJSON('get-fields.php', params, function(fields)
		{
			for(var i = 0; i < fields.length; i++)
			{
				if(restore && '' != lastState)
				{
					for(var j = 0; j < lastState.length; j++)
					{
						if(fields[i].name == lastState[j].name)
						{
							fields[i]['default'] = lastState[j].value;
						}
					}
				}
				createField(fields[i]);
			}
			listingFields = fields;
		});
		
		$.ajaxSetup({async: true});

		$(obj).show();
	};

	/**
	 * Conversion the form
	 */
	this.conversion = function()
	{
		for(var i = 0; i < listingFields.length; i++)
		{
			var html = '';

			switch(listingFields[i].type)
			{
				case 'text':
					html += '<br />';
					html += '<i>';
					html += $('#f_' + listingFields[i].name).val();
					html += '</i>';
					break;
				case 'textarea':
					html += '<br />';
					html += '<i>';
					html += $('#f_' + listingFields[i].name).val();
					html += '</i>';
					break;
				case 'combo':
					html += '<br />';
					html += '<i>';
					html += listingFields[i].labels[$('#f_' + listingFields[i].name).val()];
					html += '</i>';
					break;
				case 'radio':
					// TODO: use selectors for getting selected radio element
					var values = listingFields[i].values.split(',');

					for(var j = 0; j < values.length; j++)
					{
						if($('#f_' + listingFields[i].name + '_' + values[j]).prop('checked'))
						{
							html += '<br />';
							html += '<i>';
							html += listingFields[i].labels[$('#f_' + listingFields[i].name + '_' + values[j]).val()];
							html += '</i>';
						}
					}
					break;
				case 'checkbox':
					// TODO: use selectors for getting selected radio element
					var values = listingFields[i].values.split(',');
					var firstElement = true;

					html += '<br />';
					html += '<i>';

					for(var j = 0; j < values.length; j++)
					{
						if($('#f_' + listingFields[i].name + '_' + values[j]).prop('checked'))
						{
							if(values.length > 1)
							{
								if(!firstElement)
								{
									html += ',&nbsp;';
								}
								else
								{
									firstElement = false;
								}
							}
							html += listingFields[i].labels[$('#f_' + listingFields[i].name + '_' + values[j]).val()];
						}
					}

					html += '</i>';
					break;
				case 'storage':
					html += '<br />';
					html += '<i>';
					html += $('#f_' + listingFields[i].name).val();
					html += '</i>';
					break;
				case 'image':
					html += '<br />';
					html += '<i>';
					html += $('#f_' + listingFields[i].name).val();
					html += '</i>';
					break;
				case 'number':
					html += '<br />';
					html += '<i>';
					html += $('#f_' + listingFields[i].name).val();
					html += '</i>';
					break;
				default:
					break;
			}

			$('#val_field_' + listingFields[i].name + ' > span').empty();
			$('#val_field_' + listingFields[i].name + ' > span').append(html);

			$('form_field_' + listingFields[i].name).hide();
			$('val_field_' + listingFields[i].name).show();
		}
	};

	/**
	 * Saving the values in the form
	 *
	 * @return {Array}
	 *
	 * TODO: Optimize function
	 */
	var saveState = function()
	{
		for(var i = 0; i < listingFields.length; i++)
		{
			switch(listingFields[i].type)
			{
				case 'text':
					lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
					break;
				case 'textarea':
					if('1' == listingFields[i].editor)
					{
						var contents = CKEDITOR.instances['f_' + listingFields[i].name].getData();
						
						lastState[i] = {name: listingFields[i].name, value: contents};
					}
					else
					{
						lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
					}
					break;
				case 'image':
				case 'pictures':
					lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
					break;
				case 'combo':
					lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
					break;
				case 'radio':
					var values = listingFields[i].values.split(',');

					for(var j = 0; j < values.length; j++)
					{
						if($('#f_' + listingFields[i].name + '_' + values[j]).prop('checked'))
						{
							lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name + '_' + values[j]).val()};
						}
					}
					break;
				case 'checkbox':
					// TODO: use selectors for getting selected radio element
					var values = listingFields[i].values.split(',');
					var firstElement = true;
					var save = '';

					for(var j = 0; j < values.length; j++)
					{
						if($('#f_' + listingFields[i].name + '_' + values[j]).prop('checked'))
						{
							if(values.length > 1)
							{
								if(!firstElement)
								{
									save += ',';
								}
								else
								{
									firstElement = false;
								}
							}
							save += $('#f_' + listingFields[i].name + '_' + values[j]).val();
						}
					}

					lastState[i] = {name: listingFields[i].name, value: save};
					break;
				case 'storage':
					lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
					break;
				case 'number':
					lastState[i] = {name: listingFields[i].name, value: $('#f_' + listingFields[i].name).val()};
				default:
					break;
			}
		}

		return lastState;
	};

	/**
	 * Return the array of last values
	 *
	 * @return {Array} 
	 */
	this.getLastState = function()
	{
		return saveState();
	};

	/**
	 * Return the current id category
	 *
	 * @return {Integer}
	 */
	this.getIdCategory = function()
	{
		return getIdCategory();
	};

	/**
	 * Return the current id plan
	 *
	 * @return {Integer}
	 */
	this.getIdPlan = function()
	{
		return getIdPlan();
	};

	this.transform = function()
	{
		var i = 0;

		$(obj).find(":input").each(function()
		{
			lastState[i++] = {name: $(this).attr("name"), value: $(this).val()};
		});
	};
};
