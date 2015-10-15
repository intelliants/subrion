{ia_print_js files='_IA_URL_plugins/elfinder/js/jqueryui/jquery-ui.min,_IA_URL_plugins/elfinder/includes/elfinder/js/elfinder.min'}
{ia_print_css files='_IA_URL_plugins/elfinder/js/jqueryui/jquery-ui.min,_IA_URL_plugins/elfinder/includes/elfinder/css/elfinder.min,_IA_URL_plugins/elfinder/includes/elfinder/css/theme'}

<div id="elfinder"></div>

{ia_add_js}
$(function()
{
	$('#elfinder').elfinder(
	{
		url : intelli.config.admin_url + '/elfinder/read.json'
	});
});
{/ia_add_js}