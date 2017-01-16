/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config)
{
	config.language = 'en';
	config.skin = intelli.config.ckeditor_skin || 'moono-lisa';
	config.filebrowserImageUploadUrl = intelli.config.ia_url + 'actions/?action=ckeditor_upload&Type=Image';
	config.allowedContent = true;
	config.extraPlugins = 'embed,autoembed,codemirror';
	config.extraAllowedContent = 'a[rel]';
	CKEDITOR.dtd.$removeEmpty['span'] = false;

	if (intelli.config.ckeditor_css)
	{
		config.contentsCss = intelli.config.baseurl + 'templates/' + intelli.config.tmpl + '/css/' + intelli.config.ckeditor_css;
	}

	config.toolbar_extended = [
		['Source', 'ShowBlocks', '-', 'Maximize'],
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo'],
		['Link','Unlink'],
		['Image','MediaEmbed','CodeSnippet','Table','HorizontalRule','SpecialChar'],
		['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
		'/',
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['Styles','Format','Font','FontSize'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', '-', 'BidiLtr', 'BidiRtl'],
		['NumberedList','BulletedList', '-', 'Outdent', 'Indent'],
		['TextColor','BGColor']
	];

	config.toolbar_dashboard = [
		['Source', 'ShowBlocks', '-', 'Maximize'],
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink'],
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['Image','MediaEmbed','CodeSnippet', 'Table','HorizontalRule','SpecialChar']
	];

	config.toolbar_simple = [
		['Maximize'],
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord'],
		['Bold','Italic','Underline','Strike'],
		['TextColor','BGColor'],
		['Link','Unlink'],
		['Image','MediaEmbed']
	];

	if (typeof intelli.admin == 'undefined')
	{
		config.toolbar = 'simple';
	}
	else
	{
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