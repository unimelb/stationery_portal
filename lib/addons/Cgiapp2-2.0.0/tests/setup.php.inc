<?php
@include_once dirname(__FILE__) . '/../Cgiapp2.class.php';
if (!class_exists('Cgiapp2')) {
    if (!@include_once 'Cgiapp2.class.php') {
        die('Unable to find Cgiapp2') ;
    }
}
/**
 * Basic class for testing Cgiapp2
 *
 * This is a basic class used for testing Cgiapp2. It contains some common
 * methods and stubs in order to test functionality of the Cgiapp2 class and its
 * methods. It does nothing, really, but could possibly serve as an example of
 * how to use Cgiapp2 and/or as a basic superclass.
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @copyright (c) 2005 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @version 1.9.0
 */
class Test extends Cgiapp2 
{
    /**
     * Setup the basic application
     *
     * @access public
     */
    public function setup() 
    {
        $this->run_modes(array('method1', 'method2'), true);
    }

    /**
     * Test method
     */
    public function method1() 
    {
    }

    /**
     * Test method
     */
    public function method2() 
    {
        return "method2\n";
    }

    /**
     * static init hook
     */
    public static function initHookStatic($obj, $args)
    {
    }

    /**
     * object init hook
     */
    public function initHook($args)
    {
    }

    /**
     * test mode_param method
     */
    public function testModeMethod() 
    {
        return 'method2';
    }
}

/**
 * Basic class for testing Cgiapp2
 *
 * This is a basic class used for testing Cgiapp2. It contains some common
 * methods and stubs in order to test functionality of the Cgiapp2 class and its
 * methods. It does nothing, really, but could possibly serve as an example of
 * how to use Cgiapp2 and/or as a basic superclass.
 *
 * This class differs from Test in that it has a prerun_mode().
 *
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @copyright (c) 2005 Matthew Weier O'Phinney <matthew@weierophinney.net>
 * @version 1.9.0
 */
class Test2 extends Cgiapp2 
{
    /**
     * Setup the basic application
     *
     * @access public
     */
    function setup() 
    {
    }

    /**
     * Test method
     */
    function method1() {
    }

    /**
     * Test method
     */
    function method2() {
    }

    /**
     * Test cgiapp_prerun()
     *
     * Used to test functionality of prerun_mode(), specifically if it will work
     * from cgiapp_prerun();
     */
    function cgiapp_prerun() {
        $this->_PRERUN_MODE_LOCKED = false;
        $mode = $this->prerun_mode('mode');
        $this->_PRERUN_MODE_LOCKED = true;
        return $mode;
    }

    /**
     * Test method
     *
     * Used to test functionality of prerun_mode(), specifically if it will work
     * outside of cgiapp_prerun();
     */
    function test_prerun() {
        return $this->prerun_mode('mode');
    }

    function set_current_runmode() {
        $this->_CURRENT_RUNMODE = 'mode1';
    }
}

class plugin {
    function testMethod($obj, $args = null) {
        foreach ($args as $key) {
            echo $obj->param($key), "\n";
        }
    }
}

function testErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
{
    if (preg_match('/deprecated/i', $errstr)) {
        echo '';
    } else {
        echo $errstr, "\n";
    }
}
?>
