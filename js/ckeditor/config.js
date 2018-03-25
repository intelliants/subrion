/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function (config) {
    config.language = 'en';
    config.skin = intelli.config.ckeditor_skin || 'moono-lisa';
    config.filebrowserImageUploadUrl = intelli.config.url + 'actions/?action=ckeditor_upload&Type=Image';
    config.allowedContent = true;
    config.extraPlugins = 'embedbase,embedsemantic,embed,autoembed,codemirror,youtube';
    config.extraAllowedContent = 'a[rel]';
    CKEDITOR.dtd.$removeEmpty['span'] = false;
    CKEDITOR.dtd.$removeEmpty['i'] = false;

    if (intelli.config.ckeditor_css) {
        config.contentsCss = intelli.config.baseurl + 'templates/' + intelli.config.tmpl + '/css/' + intelli.config.ckeditor_css;
    }

    config.toolbar_extended = [
        ['Source', 'ShowBlocks', '-', 'Maximize'],
        ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
        ['Link', 'Unlink'],
        ['Image', 'Embed', 'Youtube', 'CodeSnippet', 'Table', 'HorizontalRule', 'SpecialChar'],
        // ['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
        '/',
        ['Styles', 'Format', 'Font', 'FontSize'],
        ['TextColor', 'BGColor'],
        '/',
        ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],
        ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
        ['NumberedList', 'BulletedList', '-', 'Outdent', 'Indent']
    ];

    config.toolbar_dashboard = [
        ['Source', 'ShowBlocks', '-', 'Maximize'],
        ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo'],
        ['Bold', 'Italic', 'Underline', 'Strike', '-', 'Subscript', 'Superscript'],
        ['NumberedList', 'BulletedList'],
        ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
        ['Link', 'Unlink'],
        ['Styles', 'Format', 'Font', 'FontSize'],
        ['TextColor', 'BGColor'],
        ['Image', 'Embed', 'Youtube', 'CodeSnippet', 'Table', 'HorizontalRule', 'SpecialChar']
    ];

    config.toolbar_simple = [
        ['Maximize'],
        ['Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord'],
        ['Bold', 'Italic', 'Underline', 'Strike'],
        ['TextColor', 'BGColor'],
        ['Link', 'Unlink'],
        ['Image', 'Embed', 'Youtube']
    ];

    if (typeof intelli.admin == 'undefined') {
        config.toolbar = 'simple';

        config.removeDialogTabs = 'link:target;link:advanced;image:Link;image:advanced';
    }
    else {
        config.toolbar = 'dashboard';
        config.protectedSource.push(/<ins[\s|\S]+?<\/ins>/g); // Protects <INS> tags

        config.filebrowserBrowseUrl = intelli.config.admin_url + '/uploads/?mode=file';
        config.filebrowserImageBrowseUrl = intelli.config.admin_url + '/uploads/?mode=image';
        config.filebrowserFlashBrowseUrl = intelli.config.admin_url + '/uploads/?mode=flash';
    }

    config.codemirror = {

        // Whether or not you want Brackets to automatically close themselves
        autoCloseBrackets: true,

        // Whether or not you want tags to automatically close themselves
        autoCloseTags: true,

        // Whether or not to automatically format code should be done when the editor is loaded
        autoFormatOnStart: true,

        // Whether or not to automatically format code which has just been uncommented
        autoFormatOnUncomment: true,

        // Whether or not to continue a comment when you press Enter inside a comment block
        continueComments: true,

        // Whether or not you wish to enable code folding (requires 'lineNumbers' to be set to 'true')
        enableCodeFolding: true,

        // Whether or not to enable code formatting
        enableCodeFormatting: true,

        // Whether or not to enable search tools, CTRL+F (Find), CTRL+SHIFT+F (Replace), CTRL+SHIFT+R (Replace All), CTRL+G (Find Next), CTRL+SHIFT+G (Find Previous)
        enableSearchTools: true,

        // Whether or not to highlight all matches of current word/selection
        highlightMatches: true,

        // Whether, when indenting, the first N*tabSize spaces should be replaced by N tabs
        indentWithTabs: false,

        // Whether or not you want to show line numbers
        lineNumbers: true,

        // Whether or not you want to use line wrapping
        lineWrapping: true,

        // Define the language specific mode 'htmlmixed' for html  including (css, xml, javascript), 'application/x-httpd-php' for php mode including html, or 'text/javascript' for using java script only
        mode: 'htmlmixed',

        // Whether or not you want to highlight matching braces
        matchBrackets: true,

        // Whether or not you want to highlight matching tags
        matchTags: true,

        // Whether or not to show the showAutoCompleteButton   button on the toolbar
        showAutoCompleteButton: true,

        // Whether or not to show the comment button on the toolbar
        showCommentButton: true,

        // Whether or not to show the format button on the toolbar
        showFormatButton: true,

        // Whether or not to show the search Code button on the toolbar
        showSearchButton: true,

        // Whether or not to show Trailing Spaces
        showTrailingSpace: true,

        // Whether or not to show the uncomment button on the toolbar
        showUncommentButton: true,

        // Whether or not to highlight the currently active line
        styleActiveLine: true,

        // Set this to the theme you wish to use (codemirror themes)
        theme: 'default',

        // "Whether or not to use Beautify for auto formatting
        useBeautify: false
    };
};

CKEDITOR.on('dialogDefinition',function(e){var t=e.data.name,n=e.data.definition;if('image'===t)for(var i=n.getContents('Upload'),a=0;a<i.elements.length;a++){var l=i.elements[a];'fileButton'===l.type&&(l.onClick=function(){var e=this.getDialog(),t=e.getContentElement(this['for'][0],this['for'][1]),n=e.getParentEditor();n._.filebrowserSe=this;var i=$(t.getInputElement().getParent().$);return $('input[type="file"]',i).get(0).files.length?(i.append('<input type="hidden" name="'+intelli.securityTokenKey+'" value="'+intelli.securityToken+'">'),!0):!1})}});