# Hooks

Subrion CMS is designed to make modules (plugins/packages) able to catch several actions by installing own hooks. They are portions of code, that might be embeded in pre-defined areas of the software logic & presentation.

Hooks are described in the install.xml file of a particular module. They might be related to an admin or front sections only, a page-specific or global. There might be several hooks called for the same action.

The script writes a hook record into the database when installing a module. It either contains a hook code, or defines a path to hook body.

Module's control code can also contain hooks execution. It could be done by calling the _startHook_ method of the _iaCore_ class. The function expects only 2 arguments:

* _(string)_ a name of hooks to call
* _(array)_ parameters (will be available as PHP variables)

```php
$iaCore->startHook('phpSomeEvent', ['someVar' => $someVar]);
```

### Types

The modules can install hooks of several types:

* PHP code
* HTML code (printed out as is)
* Smarty code (all of features of the Smarty engine are available)
* Plain text (printed out with the _nl2br_ replacement)

All of these types except PHP code are called from the Smarty engine and to be sent to a browser as a part of the software response.

### Hook installation

In order to install a hook using a module install.xml file, you need to add <hooks> section in a module installer. 

This hook includes the file, called hook.blocks-load.php, located in modules/MODULE_NAME/includes/ and executes it on all frontend pages.

```xml
<hooks>
    <hook name="phpCoreBeforePageDisplay" type="php" page_type="front" filename="hook.blocks-load"><![CDATA[]]></hook>
</hooks>
```

This hook executes the body of php hook on database page in admin dashboard.

```xml
<hooks>
    <hook name="phpAdminDatabaseConsistencyType" page_type="admin" pages="database">
        <![CDATA[
// php code here
        ]]>
    </hook>
</hooks>
```

### script note

The following construction is required for the 2.3.7 script and higher:
```php
if (iaView::REQUEST_HTML == $iaView->getRequestType()) {
    // your extension's code
}
```

This statement is required for the hooks of PHP type.

In case of absence of this check your code may assign variables to JSON output and if, for example, the current request is AJAX or XML request this potentially may break a correct response generation.