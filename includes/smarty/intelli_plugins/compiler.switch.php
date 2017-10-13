<?php
/**
 * Switch statement plugin for smarty.
 *    This smarty plugin provides php switch statement functionality in smarty tags.
 *    To install this plugin drop it into your smarty plugins folder.  You will also need to manually
 *      load the plugin sot hat all the hooks are registered properly.  Add the following line after
 *      you load smarty and create an instance of it in your source code.
 *
 * <code>
 *   $this->smartyObj->loadPlugin('smarty_compiler_switch');
 * </code>
 *
 * @author Jeremy Pyne <jeremy.pyne@gmail.com>
 * - Donations: Accepted via PayPal at the above address.
 * - Updated: 02/10/2010 - Version 3.2
 * - File: smarty/plugins/compiler.switch.php
 * - Licence: CC:BY/NC/SA  http://creativecommons.org/licenses/by-nc-sa/3.0/
 *
 * - Updates
 *    Version 2:
 *       Changed the break attribute to cause a break to be printed before the next case, instead of before this
 *          case.  This way makes more sense and simplifies the code.  This change in incompatible with code in
 *          from version one.  This is written to support nested switches and will work as expected.
 *    Version 2.1:
 *       Added {/case} tag, this is identical to {break}.
 *    Version 3:
 *       Updated switch statment to support Smarty 3.  This update is NOT backwards compatible but the old version is still maintained.
 *    Version 3.1:
 *       Added a prefilter to re-enable the shorthand {switch $myvar} support.  To use the shorthand form you will need to add the following line to your code.
 *       $smarty->loadPlugin('smarty_compiler_switch');
 *    Version 3.2:
 *       Fixed a bug when chaining multiple {case} statements without a {break}.
 *    Version 3.5:
 *       Updated to work with Smarty 3.0 release.  (Tested and working with 3.0.5, no longer compatible with 3.0rcx releases.)
 *    Version 3.6:
 *       Updated to work with Smarty 3.1 release.  (Tested and working on 3.1.3, No longer compatible with 3.0 releases.)
 *
 * - Bugs/Notes:
 *
 * @package Smarty
 * @subpackage plugins
 *
 * Sample usage:
 * <code>
 * {foreach item=$debugItem from=$debugData}
 *  // Switch on $debugItem.type
 *    {switch $debugItem.type}
 *       {case 1}
 *       {case "invalid_field"}
 *          // Case checks for string and numbers.
 *       {/case}
 *       {case $postError}
 *       {case $getError|cat:"_ajax"|lower}
 *          // Case checks can also use variables and modifiers.
 *          {break}
 *       {default}
 *          // Default case is supported.
 *    {/switch}
 * {/foreach}
 * </code>
 *
 * Note in the above example that the break statements work exactly as expected.  Also the switch and default
 *    tags can take the break attribute. If set they will break automatically before the next case is printed.
 *
 * Both blocks produce the same switch logic:
 * <code>
 *    {case 1 break}
 *       Code 1
 *    {case 2}
 *       Code 2
 *    {default break}
 *       Code 3
 * </code>
 *
 * <code>
 *    {case 1}
 *     Code 1
 *       {break}
 *    {case 2}
 *       Code 2
 *    {default}
 *       Code 3
 *       {break}
 * </code>
 *
 * Finally, there is an alternate long hand style for the switch statments that you may need to use in some cases.
 *
 * <code>
 * {switch var=$type}
 *    {case value="box" break}
 *    {case value="line"}
 *       {break}
 *    {default}
 * {/switch}
 * </code>
 */

//Register the post and pre filters as they are not auto-registered.
$this->registerFilter('post', 'smarty_postfilter_switch');

class Smarty_Compiler_Switch extends Smarty_Internal_CompileBase
{
    public $required_attributes = array('var');
    public $optional_attributes = array();
    public $shorttag_order = array('var');

    /**
     * Start a new switch statement.
     *    A variable must be passed to switch on.
     *  Also, the switch can only directly contain {case} and {default} tags.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);
        $_output = '';

        $this->openTag($compiler, 'switch', array($compiler->tag_nocache));

        if (is_array($attr['var'])) {
            $_output .= "<?php if (!isset(\$_smarty_tpl->tpl_vars[".$attr['var']['var']."])) \$_smarty_tpl->tpl_vars[".$attr['var']['var']."] = new Smarty_Variable;";
            $_output .= "switch (\$_smarty_tpl->tpl_vars[".$attr['var']['var']."]->value = ".$attr['var']['value']."){?>";
        } else {
            $_output .= '<?php switch (' . $attr['var'] . '){?>';
        }
        return $_output;
    }
}

class Smarty_Compiler_Case extends Smarty_Internal_CompileBase
{
    public $required_attributes = array('value');
    public $optional_attributes = array('break');
    public $shorttag_order = array('value', 'break');

    /**
     * Print out a case line for this switch.
     *    A condition must be passed to match on.
     *    This can only go in {switch} tags.
     *    If break is passed, a {break} will be rendered before the next case.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);
        $_output = '';

        list($last_tag, $last_attr) = $this->compiler->_tag_stack[count($this->compiler->_tag_stack) - 1];

        if ($last_tag == 'case') {
            list($break, $compiler->tag_nocache) = $this->closeTag($compiler, array('case'));
            if ($last_attr[0]) {
                $_output .= '<?php break;?>';
            }
        }
        $this->openTag($compiler, 'case', array(isset($attr['break']) ? $attr['break'] : false, $compiler->tag_nocache));

        if (is_array($attr['value'])) {
            $_output .= "<?php if (!isset(\$_smarty_tpl->tpl_vars[".$attr['value']['var']."])) \$_smarty_tpl->tpl_vars[".$attr['value']['var']."] = new Smarty_Variable;";
            $_output .= "case \$_smarty_tpl->tpl_vars[".$attr['value']['var']."]->value = ".$attr['value']['value'].":?>";
        } else {
            $_output .= '<?php case ' . $attr['value'] . ':?>';
        }
        return $_output;
    }
}

class Smarty_Compiler_Default extends Smarty_Internal_CompileBase
{
    public $required_attributes = array();
    public $optional_attributes = array('break');
    public $shorttag_order = array('break');

    /**
     * Print out a default line for this switch.
     *    This can only go in {switch} tags.
     *    If break is passed, a {break} will be rendered before the next case.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */
    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);
        $_output = '';

        list($last_tag, $last_attr) = $this->compiler->_tag_stack[count($this->compiler->_tag_stack) - 1];
        if ($last_tag == 'case') {
            list($break, $compiler->tag_nocache) = $this->closeTag($compiler, array('case'));
            if ($last_attr[0]) {
                $_output .= '<?php break;?>';
            }
        }
        $this->openTag($compiler, 'case', array(isset($attr['break']) ? $attr['break'] : false, $compiler->tag_nocache));

        $_output .= '<?php default:?>';

        return $_output;
    }
}


class Smarty_Compiler_Break extends Smarty_Internal_CompileBase
{
    public $required_attributes = array();
    public $optional_attributes = array();
    public $shorttag_order = array();

    /**
     * Print out a break command for the switch.
     *    This can only go inside of {case} tags.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */

    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);

        list($break, $compiler->tag_nocache) = $this->closeTag($compiler, array('case'));

        return '<?php break;?>';
    }
}

class Smarty_Compiler_Caseclose extends Smarty_Internal_CompileBase
{
    public $required_attributes = array();
    public $optional_attributes = array();
    public $shorttag_order = array();

    /**
     * Print out a break command for the switch.
     *    This can only go inside of {case} tags.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */

    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);

        list($break, $compiler->tag_nocache) = $this->closeTag($compiler, array('case'));

        return '<?php break;?>';
    }
}

class Smarty_Compiler_Switchclose extends Smarty_Internal_CompileBase
{
    public $required_attributes = array();
    public $optional_attributes = array();
    public $shorttag_order = array();

    /**
     * End a switch statement.
     *
     * @param string $tag_arg
     * @param Smarty_Compiler $smarty
     * @return string
     */

    public function compile($args, $compiler)
    {
        $this->compiler = $compiler;
        $attr = $this->getAttributes($compiler, $args);

        list($last_tag, $last_attr) = $this->compiler->_tag_stack[count($this->compiler->_tag_stack) - 1];
        if (($last_tag == 'case' || $last_tag == 'default')) {
            list($break, $compiler->tag_nocache) = $this->closeTag($compiler, array('case'));
        }
        list($compiler->tag_nocache) = $this->closeTag($compiler, array('switch'));

        return '<?php }?>';
    }
}

/**
 * Filter the template after it is generated to fix switch bugs.
 *    Remove any spaces after the 'switch () {' code and before the first case.  Any tabs or spaces
 *       for layout would cause php errors witch this reged will fix.
 *
 * @param string $compiled
 * @param Smarty_Compiler $smarty
 * @return string
 */
function smarty_postfilter_switch($compiled, &$smarty)
{
    // Remove the extra spaces after the start of the switch tag and before the first case statement.
    return preg_replace('/({ ?\?>)\s+(<\?php case)/', "$1\n$2", $compiled);
}
