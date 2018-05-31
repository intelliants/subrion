{if isset($smarty.get.mode)}
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="utf-8">
        <title>elFinder Upload Manager</title>

        <!-- jQuery and jQuery UI (REQUIRED) -->
        <link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}js/jquery/plugins/elfinder/jquery-ui.min.css">
        <script src="{$smarty.const.IA_CLEAR_URL}js/jquery/jquery.js"></script>
        <script src="{$smarty.const.IA_CLEAR_URL}js/jquery/plugins/elfinder/jquery-ui.min.js"></script>

        <!-- elFinder CSS (REQUIRED) -->
        <link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}includes/elfinder/css/elfinder.min.css">
        <link rel="stylesheet" type="text/css" href="{$smarty.const.IA_CLEAR_URL}includes/elfinder/css/theme.css">

        <!-- elFinder JS (REQUIRED) -->
        <script src="{$smarty.const.IA_CLEAR_URL}includes/elfinder/js/elfinder.min.js"></script>

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
                        url : '{$smarty.const.IA_ADMIN_URL}uploads/read.json',
                        getFileCallback : function(file) {
                            window.opener.CKEDITOR.tools.callFunction(funcNum, file.url);
                            window.close();
                        },
                        resizable: true,
                        height: 600,
                        customData: {
                            '__st': '{$securityToken}'
                        }
                    }).elfinder('instance');
            });
        </script>
    </head>
    <body>
        <div id="elfinder"></div>
    </body>
    </html>
{else}
    <div id="elfinder"></div>

    {ia_print_js files='jquery/plugins/elfinder/jquery-ui.min,_IA_URL_includes/elfinder/js/elfinder.min'}
    {ia_print_css files='_IA_URL_js/jquery/plugins/elfinder/jquery-ui.min,_IA_URL_includes/elfinder/css/elfinder.min,_IA_URL_includes/elfinder/css/theme'}

{ia_add_js}
$(function(){
    var opts = {
        customData: { },
        url : intelli.config.admin_url + '/uploads/read.json',
        height: 450
    };
    opts.customData[intelli.securityTokenKey] = intelli.securityToken;
    $('#elfinder').elfinder(opts);
});
{/ia_add_js}
{/if}