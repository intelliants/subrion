/**
 * @license Copyright (c) 2003-2012, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config )
{
	config.allowedContent = true;
	config.language = 'en';
	config.filebrowserImageUploadUrl = intelli.config.ia_url + 'actions/?action=ckeditor_upload&Type=Image';
	config.extraPlugins = 'mediaembed';

	if (intelli.config.ckeditor_css)
	{
		config.contentsCss = intelli.config.baseurl + 'templates/' + intelli.config.tmpl + '/css/' + intelli.config.ckeditor_css;
	}

	if (1 == intelli.config.ckeditor_code_highlighting)
	{
		config.extraPlugins += ',syntaxhighlight';
	}

	if (typeof intelli.admin == 'undefined')
	{
		config.toolbar = 'Simple';
	}
	else
	{
		config.toolbar = 'Extended';
		config.extraPlugins += ',codemirror';
	}

	config.toolbar_Extended = [
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

	config.toolbar_Simple = [
		['Cut', 'Copy', 'Paste','PasteText','PasteFromWord'],
		['Bold','Italic','Underline','Strike'],
		['TextColor','BGColor'],
		['Link','Unlink'],
		['Image','MediaEmbed','Code']
	];
};