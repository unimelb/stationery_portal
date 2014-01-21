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
 * Cgiapp2 PHP Error Handling
 *
 * Cgiapp2_Error implements an Observer pattern for PHP errors. You may build a
 * observer classes that receivee notifications when PHP errors are raised. Such
 * classes need only have an event() method that accepts a Cgiapp2_Error object,
 * and have to register their class or an instance with Cgiapp2_Error using
 * {@link attach()}.
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @copyright (c) 2006 - present Matthew Weier O'Phinney
 * @version @release-version@
 */
class Cgiapp2_Error 
{
    /**@+
     * @access public
     */

    /**
     * Error level integer
     * @var int 
     * @access public
     */
    public $errno;

    /**
     * Error message
     * @var string 
     * @access public
     */
    public $errstr;

    /**
     * Filename in which error was raised
     * @var string 
     * @access public
     */
    public $errfile;

    /**
     * Line number in which error was raised
     * @var int 
     * @access public
     */
    public $errline;

    /**
     * Symbol table at point where error occurred
     * @var array 
     * @access public
     */
    public $errcontext;

    /**
     * Array of observers
     * @var array
     * @static
     * @access protected
     */
    private static $observers = array();

	/**
	 * Constructor
	 *
	 * @access public
	 * @param string
     * @param int
     * @param int
	 */
    public function __construct($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
	{
        $this->errno      = $errno;
        $this->errstr     = $errstr;
        $this->errfile    = $errfile;
        $this->errline    = $errline;
        $this->errcontext = $errcontext;

        $this->notify();
	}

    /**
     * Handle PHP Errors
     *
     * Creates a Cgiapp2_Error from a PHP error (if the PHP error handler has
     * been set to this method).
     *
     * @static
     * @access public
     */
    public static function handler($errno, $errstr, $errfile = null, $errline = null, $errcontext = null)
    {
        return new Cgiapp2_Error($errno, $errstr, $errfile, $errline, $errcontext);
    }

    /**
     * String representation of exception
     * 
     * @access public
     * @return void
     */
    public function __toString()
    {
        return __CLASS__ . ': [' . $this->code . ']: ' . $this->message . "\n";
    }

    /**
     * Attach an observer
     * 
     * @static
     * @access public
     * @param string|object $class Classname or object
     * @return void
     */
    final public static function attach($class)
    {
        if (is_object($class) || class_exists($class)) {
            array_push(self::$observers, $class);
        }
    }

    /**
     * Notify observers of an error event
     * 
     * @access public
     * @return void
     */
    final public function notify()
    {
        foreach (self::$observers as $class) {
            if (is_callable(array($class, 'event'))) {
                call_user_func(array($class, 'event'), $this);
            }
        }
    }
}

