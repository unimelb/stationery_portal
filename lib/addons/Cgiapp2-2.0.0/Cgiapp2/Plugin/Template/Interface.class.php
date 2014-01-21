<?php
/**
 * Cgiapp2 - Framework for building reusable web-applications
 *
 * A PHP5 port of perl's CGI::Application, a framework for building reusable web
 * applications. 
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>; based on
 * CGI::Application, by Jesse Erlbaum <jesse@erlbaum.net>, et. al.
 * @copyright (c) 2004 - present, Matthew Weier O'Phinney
 * @license BSD License (http://www.opensource.org/licenses/bsd-license.php)
 * @category Tools and Utilities
 * @tutorial Cgiapp2/Cgiapp2.cls
 * @version $Id:$
 */

/**
 * Cgiapp2_Plugin_Template_Interface
 *
 * Defines an interface for a template plugin. A template engine should
 * implement {@link init() an initialization method}, 
 * {@link assign() a variable assignment method}, and 
 * {@link fetch() a method for fetching the compiled template}.
 *
 * Template plugins that utilize this interface will work seamlessly with
 * Cgiapp2's {@link Cgiapp2::tmpl_path()}, {@link Cgiapp2::tmpl_assign()}, and 
 * {@link Cgiapp2::load_tmpl()} methods.
 *
 * Since a {@link Cgiapp2} derived class is passed to each, the template engine
 * may choose to register itself with that class using the 
 * {@link Cgiapp2::param()} method. Alternatively, it may implement a singleton;
 * these details are left to the programmer.
 *
 * The plugin class should then register itself with Cgiapp2 or a Cgiapp2-derived
 * class via {@link Cgiapp2::add_callback()}:
 * <code>
 * class MyTemplatePlugin implements Cgiapp2_Plugin_Template_Interface { //... }
 *  Cgiapp2::add_callback('tmpl_path', array('MyTemplatePlugin', 'init'), 'Cgiapp2');
 *  Cgiapp2::add_callback('tmpl_assign', array('MyTemplatePlugin', 'assign'), 'Cgiapp2');
 *  Cgiapp2::add_callback('tmpl_fetch', array('MyTemplatePlugin', 'fetch'), 'Cgiapp2');
 * </code>
 * 
 * @package Cgiapp2 
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com> 
 * @copyright 2006-Present, Matthew Weier O'Phinney <mweierophinney@gmail.com> 
 * @version @release-version@
 */
interface Cgiapp2_Plugin_Template_Interface
{
    /**
     * Initialize template engine
     *
     * Note: Classes that implement must include the class hint for the $cgiapp
     * argument.
     * 
     * @static
     * @access public
     * @param Cgiapp2 $cgiapp 
     * @param string $tmpl_path 
     * @param array $extra_params 
     * @return bool
     */
    public static function init(Cgiapp2 $cgiapp, $tmpl_path, $extra_params = null);

    /**
     * Assign variables to the template
     * 
     * Note: Classes that implement must include the class hint for the $cgiapp
     * argument.
     * 
     * @static
     * @access public
     * @param Cgiapp2 $cgiapp 
     * @return bool
     */
    public static function assign(Cgiapp2 $cgiapp);

    /**
     * Fetch compiled template
     * 
     * Note: Classes that implement must include the class hint for the $cgiapp
     * argument.
     * 
     * @static
     * @access public
     * @param Cgiapp2 $cgiapp 
     * @param mixed $tmpl_file 
     * @return string
     */
    public static function fetch(Cgiapp2 $cgiapp, $tmpl_file);
}
