<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>elFinder Upload Manager</title>

	<!-- jQuery and jQuery UI (REQUIRED) -->
	<link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}plugins/elfinder/js/jqueryui/jquery-ui.min.css">
	<script src="{$smarty.const.IA_CLEAR_URL}js/jquery/jquery.js"></script>
	<script src="{$smarty.const.IA_CLEAR_URL}plugins/elfinder/js/jqueryui/jquery-ui.min.js"></script>

	<!-- elFinder CSS (REQUIRED) -->
	<link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}plugins/elfinder/includes/elfinder/css/elfinder.min.css">
	<link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}plugins/elfinder/includes/elfinder/css/theme.css">

	<!-- elFinder JS (REQUIRED) -->
	<script src="{$smarty.const.IA_CLEAR_URL}plugins/elfinder/includes/elfinder/js/elfinder.min.js"></script>

	<!-- elFinder initialization (REQUIRED) -->
	<script type="text/javascript" charset="utf-8">
		// Helper function to get parameters from the query string.
		function getUrlParam(paramName) {
			var reParam = new RegExp('(?:[\?&]|&amp;)' + paramName + '=([^&]+)', 'i') ;
			var match = window.location.search.match(reParam) ;

			return (match && match.length > 1) ? match[1] : '' ;
		}

		$().ready(function() {
			var funcNum = getUrlParam('CKEditorFuncNum');

			var elf = $('#elfinder').elfinder({
				url : '{$smarty.const.IA_ADMIN_URL}elfinder/read.json',
				getFileCallback : function(file) {
					window.opener.CKEDITOR.tools.callFunction(funcNum, file.url);
					window.close();
				},
				resizable: false
			}).elfinder('instance');
		});
	</script>
</head>
<body>
	<!-- Element where elFinder will be created (REQUIRED) -->
	<div id="elfinder"></div>
</body>
</html>