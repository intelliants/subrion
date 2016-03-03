/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config)
{
	config.allowedContent = true;
	config.language = 'en';
	config.filebrowserImageUploadUrl = intelli.config.ia_url + 'actions/?action=ckeditor_upload&Type=Image';
	config.extraPlugins = 'mediaembed';
	config.skin = 'bootstrapck';
	config.extraAllowedContent = 'a[rel]';
	CKEDITOR.dtd.$removeEmpty['span'] = false;

	if (intelli.config.ckeditor_css)
	{
		config.contentsCss = intelli.config.baseurl + 'templates/' + intelli.config.tmpl + '/css/' + intelli.config.ckeditor_css;
	}

	if (1 == intelli.config.ckeditor_code_highlighting)
	{
		config.extraPlugins += ',syntaxhighlight';
	}

	config.toolbar_extended = [
		['Source', '-', 'Maximize'],
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo'],
		['Link','Unlink'],
		['Image','MediaEmbed','Table','HorizontalRule','SpecialChar'],
		['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
		'/',
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['Styles','Format','Font','FontSize'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock', '-', 'BidiLtr', 'BidiRtl'],
		['NumberedList','BulletedList', '-', 'Outdent', 'Indent'],
		['TextColor','BGColor']
	];

	config.toolbar_dashboard = [
		['Source', '-', 'Maximize'],
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord','-','Undo','Redo'],
		['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
		['NumberedList','BulletedList'],
		['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
		['Link','Unlink'],
		['Styles','Format','Font','FontSize'],
		['TextColor','BGColor'],
		['Image','MediaEmbed','Table','HorizontalRule','SpecialChar']
	];

	config.toolbar_simple = [
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord'],
		['Bold','Italic','Underline','Strike'],
		['TextColor','BGColor'],
		['Link','Unlink'],
		['Image','MediaEmbed','Code']
	];

	if (typeof intelli.admin == 'undefined')
	{
		config.toolbar = 'simple';
	}
	else
	{
		config.toolbar = 'dashboard';
		config.extraPlugins += ',codemirror';
		config.protectedSource.push(/<ins[\s|\S]+?<\/ins>/g); // Protects <INS> tags

		if (typeof intelli.config['elfinder_ckeditor_integration'] !== 'undefined' && 1 == intelli.config.elfinder_ckeditor_integration)
		{
			config.filebrowserBrowseUrl = intelli.config.admin_url + '/elfinder/?mode=file';
			config.filebrowserImageBrowseUrl = intelli.config.admin_url + '/elfinder/?mode=image';
			config.filebrowserFlashBrowseUrl = intelli.config.admin_url + '/elfinder/?mode=flash';
		}
	}
};