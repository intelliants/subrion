# Templates

Subrion CMS utilizes Smarty template engine. Smarty ideally fits for separating application logic and presentation. Check [Smarty Docs](https://www.smarty.net/docs/en/ ":target=_blank") for more information on template engine.

Subrion templates are based on overrides. You can find all default template files that are used on front-end in this folder: `[root]/templates/_common` It's not recommended to make any changes in these files. You're required to make a copy of template file, you wish to modify to your template folder and make changes there.  
The same logic can be applied to modules template files. In order to modify a template related file, you need to copy it into `[root]/templates/TEMPLATE_NAME/modules/MODULE_NAME/` and modify it.

### Installation

Starting from 3.1.1 version Subrion CMS has the **Remote Template Installation** feature.  It allows to install any available template in seconds.

There are several steps you need to perform to install any template:

1. Log into the Subrion Admin Dashboard
2. Go to Extensions -> Templates
3. Choose template you like and click on **Download** button
4. Click on **Activate** button once it is downloaded

!> **NOTE** Some templates are designed specifically for certain modules only. So please be careful when choosing a template for any package. For example, _Cardealer_ template is designed for Autos premium module. So it requires Autos to be installed in order to run properly.

## Naming conventions

### Template folder

Subrion template *.tpl files should be kept in a separate folder. Folder name contains lowercase Latin letters and underscores only. Examples:

```
simpla_autos
publish_it
segin
locality
```

### Template files

Template file names should only contain Latin letters, digits, and dash. You should not use underscores, slashes, etc. in template filenames. Only `.tpl` file extension is allowed for template files. Examples:

:x: *Incorrect:*
`view_account.tpl`

:white_check_mark: *Correct:*
`view-account.tpl`

## Template structure

The main file for the template is `layout.tpl` file. 
