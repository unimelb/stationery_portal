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
 * Extends Cgiapp2_Exception
 */
require_once 'Cgiapp2/Exception.class.php';

/**
 * Cgiapp2 Error -> Exception Handling
 *
 * This class is used to turn errors into exceptions for Cgiapp2. It is derived
 * from a comment found at:
 *
 * http://www.zend.com/php5/articles/php5-exceptions.php
 *
 * @package Cgiapp2
 * @copyright (c) 2006 - present, Matthew Weier O'Phinney
 * <mweierophinney@gmail.com>
 * @version @release-version@
 */
class Cgiapp2_Exception_Error extends Cgiapp2_Exception 
{
    /**
     * Create an Exception from an Error
     *
     * Creates a Cgiapp2_Exception_Error from a PHP error (if the PHP error
     * handler has been set to this method).
     *
     * @static
     * @access public
     * @throws Cgiapp2_ErrorException
     */
    public static function handler($errno, $errstr)
    {
        throw new Cgiapp2_Exception_Error($errstr, $errno);
    }
}

