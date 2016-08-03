/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function(config)
{
	config.language = 'en';
	config.skin = intelli.config.ckeditor_skin || 'bootstrapck';
	config.filebrowserImageUploadUrl = intelli.config.ia_url + 'actions/?action=ckeditor_upload&Type=Image';
	config.allowedContent = true;
	config.extraPlugins = 'embed,autoembed';
	config.extraAllowedContent = 'a[rel]';
	CKEDITOR.dtd.$removeEmpty['span'] = false;

	if (intelli.config.ckeditor_css)
	{
		config.contentsCss = intelli.config.baseurl + 'templates/' + intelli.config.tmpl + '/css/' + intelli.config.ckeditor_css;
	}

	config.toolbar_extended = [
		['Source', '-', 'Maximize'],
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
		['Source', '-', 'Maximize'],
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
};