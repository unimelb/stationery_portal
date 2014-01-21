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
 * Cgiapp2 plugin
 */
require_once 'Cgiapp2.class.php';

/**
 * Implements Cgiapp2_Plugin_Template_Interface
 */
require_once 'Cgiapp2/Plugin/Template/Interface.class.php';

/**
 * Savant2 Template Plugin for Cgiapp2
 *
 * Implements {@link Cgiapp2_Plugin_Template_Interface} to create a Savant2
 * plugin for {@link Cgiapp2}. 
 *
 * Additionally, Cgiapp2_Plugin_Savant2 implements a singleton and dynamic proxy.
 * This allows you to do the following:
 * <code>
 * Cgiapp2_Plugin_Savant2::getInstance()->loadFilter('trimwhitespace');
 * </code>
 *
 * Registers with Cgiapp2's tmpl_path, tmpl_assign, and tmpl_fetch hooks;
 * registration is done with the Cgiapp2 class.
 * 
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @version @release-version@
 */
class Cgiapp2_Plugin_Savant2 implements Cgiapp2_Plugin_Template_Interface
{
    /**
     * Path to template storage root
     * 
     * @var string
     * @access protected
     */
    protected $tmpl_path;

    /**
     * Hold template instance
     *
     * @var string
     * @access private
     * @static 
     */
    private static $instance = false;

    /**
     * Savant2 instance
     * 
     * @var object
     * @access protected
     */
    protected $t;

    /**
     * Constructor 
     *
     * Creates an instance and initializes the {@link $tmpl_path} property.
     *
     * @param mixed $tmpl_path 
     * @param mixed $extra_params 
     * @access private
     * @return void
     */
    private function __construct($tmpl_path, $extra_params)
    {
        $savant2 = new Savant2($extra_params);

        $this->t         = $savant2;
        $this->tmpl_path = $tmpl_path;
        $this->setPath('template', $tmpl_path);
    }

    /**
     * Dynamic proxy for Savant2 methods
     *
     * Allows calling Savant2 methods as part of the Cgiapp2_Plugin_Savant2
     * object.  Since the class also implements the singleton pattern, you may
     * easily call Savant2 methods from anywhere:
     * <code>
     * Cgiapp2_Plugin_Savant2::getInstance()->loadFilter('trimwhitespace');
     * </code>
     * 
     * @param mixed $method 
     * @param mixed $args 
     * @access public
     * @return void
     */
    public function __call($method, $args)
    {
        if (method_exists($this->t, $method)) {
            return call_user_func_array(array($this->t, $method), $args);
        }
    }

    /**
     * Proxy: retrieve Savant2 property values
     * 
     * @access public
     * @param string $key 
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->t->$key)) {
            return $this->t->$key;
        }

        return;
    }

    /**
     * Proxy: set Savant2 property values
     * 
     * @access public
     * @param mixed $key 
     * @param mixed $val 
     * @return void
     */
    public function __set($key, $val)
    {
        $this->t->$key = $val;

        return;
    }

    /**
     * Singleton
     *
     * Returns false if unable to find instance, Savant2, or missing arguments.
     * You can access this at any time:
     * <code>
     * $tpl = Cgiapp2_Plugin_Savant2::getInstance();
     * </code>
     * 
     * @static
     * @access public
     * @param mixed $cgiapp Cgiapp2 instance object
     * @param mixed $tmpl_path Path to template root directory
     * @param mixed $extra_params Optional; extra parameters with which to
     * initialize Savant2
     * @return void
     * @throws Exception if unable to insantiate Savant2
     */
    public static function getInstance()
    {
        // Return instance if it exists already
        if (false !== ($instance = self::$instance)) {
            return $instance;
        }

        // Get arguments and any extra params
        $args = func_get_args();
        if (2 > count($args)) {
            throw new Exception('No instance found, and missing arguments to create one');
            return false;
        }
        $extra_params = null;
        $cgiapp    = array_shift($args);
        $tmpl_path = array_shift($args);
        if (0 < count($args)) {
            $extra_params = array_shift($args);
        }

        // Set Savant2 path and include Savant2 class
        if (false !== ($SAVANT2_PATH = $cgiapp->param('SAVANT2_PATH'))) {
            $PATH = ini_get('include_path');
            ini_set('include_path', $SAVANT2_PATH . ':' . $PATH);
        }
        @include_once 'Savant2.php';
        if (!class_exists('Savant2')) {
            throw new Exception('Savant2 not found');
            return false;
        }

        self::$instance = new Cgiapp2_Plugin_Savant2($tmpl_path, $extra_params);
        return self::$instance;
    }

    /**
     * Set the template path
     *
     * Uses $tmpl_path to add a path via Savant2's setPath() method.
     * 
     * @access public
     * @param mixed $tmpl_path 
     * @return void
     */
    public function setTmplPath($tmpl_path)
    {
        $this->tmpl_path = $tmpl_path;
        $this->setPath('template', $tmpl_path);
    }

    /**
     * Initialize a template instance and/or set the template path
     * 
     * If no Cgiapp2_Plugin_Savant2 instance currently exists, it is first
     * initialized via {@link getInstance()} using the template path, extra
     * parameters, and Cgiapp2-based object instance. Savant2 is looked for in
     * the Cgiapp2 instance parameter 'SAVANT2_PATH'; if not found, or an error
     * occurs initializing Savant2, a warning is raised.
     *
     * init() is used by Savant2 to set the template path.  If the template path
     * has not changed, nothing is done.
     *
     * @static
     * @access public
     * @param mixed $cgiapp 
     * @param mixed $tmpl_path 
     * @param mixed $extra_params 
     * @return bool
     */
    public static function init(Cgiapp2 $cgiapp, $tmpl_path, $extra_params = null)
    {
        // Get savant2 object
        $instance = self::getInstance($cgiapp, $tmpl_path, $extra_params);

        if ($instance->tmpl_path != $tmpl_path) {
            $instance->setTmplPath($tmpl_path);
        }

        return true;
    }

    /**
     * Assign a variable or variables to a template
     * 
     * assign() can be used to assign data to a template. Internally, it calls
     * Savant2's assign() method.
     *
     * You can also send it an associative array of variable names => values,
     * and all elements included will be sent to the template.
     *
     * @static
     * @access public
     * @return bool
     * @throws Exception when bad data passed
     */
    public static function assign(Cgiapp2 $cgiapp)
    {
        $args   = func_get_args();
        $cgiapp = array_shift($args);
        $argc   = count($args);

        if (1 == $argc) {
            $values = array_shift($args);
            if (Cgiapp2::is_assoc_array($values)) {
                self::getInstance()->t->assign($values);
            } else {
                throw new Exception('Bad array passed to Cgiapp2_Plugin_Savant2::assign()');
                return false;
            }
        } elseif (2 == $argc) {
            $key = array_shift($args);
            if (is_string($key)) {
                $val = array_shift($args);
                self::getInstance()->t->assign(trim($key), $val);
            } else {
                throw new Exception('Attempting to assign non-string key');
                return false;
            }
        } else {
            throw new Exception('Bad number or type of arguments passed to Cgiapp2_Plugin_Savant2::assign()');
            return false;
        }

        return true;
    }

    /**
     * Fetch template contents
     *
     * If not template file is provided, returns an empty string. If unable to
     * locate a Cgiapp2_Plugin_Savant2 instance, returns an empty string and
     * raises a warning.
     *
     * Internally, calls Savant2's fetch() method:
     * <code>
     *     $savant2->fetch('some.tpl');
     * </code>
     * 
     * @static
     * @access public
     * @param mixed $cgiapp 
     * @param mixed $tmpl_file 
     * @return string
     */
    public static function fetch(Cgiapp2 $cgiapp, $tmpl_file)
    {
        if (empty($tmpl_file) || !is_string($tmpl_file)) {
            return '';
        }

        $output = self::getInstance()->t->fetch($tmpl_file);
        return $output;
    }
}

/**
 * Register callbacks with tmpl_path, tmpl_assign, and tmpl_fetch callback hooks
 * of Cgiapp2
 */
Cgiapp2::add_callback('tmpl_path', array('Cgiapp2_Plugin_Savant2', 'init'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_assign', array('Cgiapp2_Plugin_Savant2', 'assign'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_fetch', array('Cgiapp2_Plugin_Savant2', 'fetch'), 'Cgiapp2');
?>
