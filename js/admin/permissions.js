$(function()
{
	$('#btn-fieldset-admin_pages, #btn-fieldset-pages').on('click', function()
	{
		var text = this.id;
	    var id = text.replace('btn-', '');
	    $('#'+id).toggle();
	});
	
	$('.p_collapse').on('click', function()
	{
		var id = $(this).attr('rel');
		if($(this).hasClass('show'))
		{
			$(this).removeClass('show');
			$('.object-'+id+':first').show();
			$('.object-'+id+':not(:first)').hide();
		}
		else
		{
			$(this).addClass('show');
			$('.object-'+id+':first').hide();
			$('.object-'+id+':not(:first)').show();
		}
	});
	$(".hide_btn, .show_btn").on('click', function()
	{
		var hide = $(this).hasClass('hide_btn');
		var group_class = $(this).attr("rel");
		$('div.' + group_class).css('display', hide ? 'none' : 'block');
		$('fieldset.' + group_class + ' .'+(hide?'hide_btn':'show_btn')).hide();
		$('fieldset.' + group_class + ' .'+(hide?'show_btn':'hide_btn')).show();
	});
    function reqs(data)
	{
        Ext.Ajax.request(
		{
			url: intelli.config.admin_url + '/permissions.json' + window.location.search,
			method: 'post',
			params: data,
            success: function(d)
			{
                var response = Ext.decode(d.responseText);
                intelli.admin.notifBox({msg: response.msg, type: response.type, autohide: true});
            },
            failure: function(resp, opts)
			{
                intelli.admin.notifBox({
                    msg: 'Error with ajax request',
                    type: 'error',
                    autohide: true
                });
            }
		});
    }
    var first = true;
	$('#input-all--admin_login--read').on('change', function()
	{
		if(this.value == 1)
		{
			$('#div-'+this.id).show();
		}
		else
		{
			$('#div-'+this.id).hide();
		}
		//var act = this.id.replace('input-', '');
        var data = {action:'save', 'acts[read]': this.value, obj: 'admin_login', type: 'all', modified: true};
        if(!first) reqs(data);
        else first = false;
	}).change();
	var save_perms = function(p_cont)
	{
		var divs = p_cont.find('.'+p_cont.attr('id').replace('p_cont-', 'object-')+' input[id^="input-"]');
		var modified = p_cont.hasClass('modified');
		var data = {action:'save', acts: {}};
		divs.each(function(){
			act = this.id.split('--')[2];
			data.obj = this.id.split('--')[1];
			data.type = this.id.replace('input-', '').split('--')[0];
			data['acts['+act+']'] = $(this).val();
		});
		p_cont.removeClass('p_save');
		data.modified = modified;
        reqs(data);
	};
	$('.light input[type="hidden"]').on('change', function()
	{
		var id = this.id.replace('input-', '');
		var obj = id.split('--');
		var p_cont = $('#p_cont-'+obj[0]+'-'+obj[1]);
		if(this.value == 0)
		{
			$('#'+id).addClass('act-false').removeClass('act-true');
		}
		else
		{
			$('#'+id).removeClass('act-false').addClass('act-true');
		}
		if($(this).hasClass('default'))
		{
			p_cont.removeClass('modified');
			$(this).removeClass('default');
			p_cont.find('.save').hide();
		}
		else
		{
			p_cont.addClass('modified');
			if(p_cont.find('input[id^="input-"]').length > 1)
			{
				p_cont.addClass('p_save');
				p_cont.find('.save').show();
			}
			else
			{
				save_perms(p_cont);
			}
		}
	});
	$('.p_links div')
		.mousedown(function()
		{
			$(this).addClass('click');
		})
		.mouseup(function()
		{
			$(this).removeClass('click');
		})
		.mouseleave(function()
		{
			$(this).removeClass('click');
		});
	$('.p_links .save').on('click', function(e)
	{
		e.preventDefault();

		$(this).hide();
		save_perms($($(this).parents('.p_hover')));
	}).hide();
	$('.p_links .disallow').on('click', function(e)
	{
		e.preventDefault();

		var parent = $($(this).parents('.p_hover'));
		var divs = parent.find('.'+parent.attr('id').replace('p_cont-', 'object-')+' input[id^="default-"]');
		divs.each(function()
		{
			var id = this.id.replace('default-', '');
			$('#input-'+id).val(0);
			if ($('#box-'+id).is(':checked'))$('#box-'+id).prop('checked', false).change();
		});
		parent.addClass('modified');
	});
	$('.p_links .allow').on('click', function(e)
	{
		e.preventDefault();

		var parent = $($(this).parents('.p_hover'));
		var divs = parent.find('.'+parent.attr('id').replace('p_cont-', 'object-')+' input[id^="default-"]');
		divs.each(function(){
			var id = this.id.replace('default-', '');
			$('#input-'+id).val(1);
			if(!$('#box-'+id).is(':checked')) $('#box-'+id).prop('checked', true).change();
		});
		parent.addClass('modified');
	});
	$('.p_links .set_default').on('click', function(e)
	{
		e.preventDefault();

		var parent = $($(this).parents('.p_hover'));
		var divs = parent.find('.'+parent.attr('id').replace('p_cont-', 'object-')+' input[id^="default-"]');
		divs.each(function(){
			var id = this.id.replace('default-', '');
			$('#input-'+id).val(this.value).addClass('default');
			$('#box-'+id).prop('checked', this.value == 1).change();
		});
		parent.removeClass('modified');
		$(parent.find('.save')).hide();
		save_perms(parent);
	});
});