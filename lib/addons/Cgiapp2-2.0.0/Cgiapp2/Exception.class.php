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
 * Cgiapp2 Exception Handling
 *
 * Cgiapp2_Exception implements an Observer pattern. You may build observer
 * classes that receive notifications when Cgiapp2_Exceptions (or classes
 * derived from it) are raised. Such classes need only have an event() method
 * that accepts a Cgiapp2_Exception object, and have to register their class or
 * an instance with Cgiapp2_Exception using {@link attach()}.
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @copyright (c) 2006 - present Matthew Weier O'Phinney
 * @version @release-version@
 */
class Cgiapp2_Exception extends Exception 
{
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
	public function __construct($message, $code = 0) 
	{
		parent::__construct($message, $code);

        $this->notify();
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

