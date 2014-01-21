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
 * PHP >= 5.0.0 only
 */
if (version_compare(phpversion(), '5.0.0', 'lt')) {
    trigger_error(
        'This version of Cgiapp2 requires PHP5 or greater', 
        E_USER_ERROR
    );
}

/**
 * Set include_path to ensure the directory in which Cgiapp2 sits is in it
 */
$_CGIAPP_PATH = ini_get('include_path');
ini_set('include_path', dirname(__FILE__) . PATH_SEPARATOR . $_CGIAPP_PATH);
unset($_CGIAPP_PATH);

/**
 * Cgiapp2_Exception_Error: Handle PHP errors as exceptions during run mode
 * execution
 */
require_once 'Cgiapp2/Exception/Error.class.php';

/**
 * Cgiapp2 - Framework for building reusable web-applications
 *
 * A PHP5 port of perl's CGI::Application, a framework for building reusable web
 * applications. 
 * 
 * <b>SYNOPSIS</b>
 *
 * <code>
 * // In "WebApp.class.php"...
 * require_once 'Cgiapp2.class.php';
 * class WebApp extends Cgiapp2 
 * {
 *     function setup() 
 *     {
 *         $this->start_mode('mode1');
 *         $this->mode_param('rm');
 *         $this->run_modes(array(
 *             'mode1' => 'do_stuff',
 *             'mode2' => 'do_more_stuff',
 *             'mode3' => 'do_something_else'
 *         ));
 *     }
 *     function do_stuff() { ... }
 *     function do_more_stuff() { ... }
 *     function do_something_else() { ... }
 * }
 * 
 * 
 * // In "webapp.php"...
 * require_once 'WebApp.class.php';
 * $webapp = new WebApp();
 * $webapp->run();
 * </code>
 * 
 * For more information, see the {@tutorial Cgiapp2/Cgiapp2.cls tutorials}. 
 * 
 * @package Cgiapp2
 * @tutorial Cgiapp2/Cgiapp2.cls
 * @version @release-version@
 */
abstract class Cgiapp2
{
    /**
     * Array of $_GET and $_POST variables; see also {@link query()}
     * @var array
     * @static
     * @access protected
     */
    private static $CGIAPP_REQUEST = array();

    /**
     * Array of PATH_INFO elements; see also {@link path_info()}
     * @var array
     * @static
     * @access protected
     */
    private static $CGIAPP_PATH_INFO = array();

    /**#@+
     * @access private
     */

    /**
     * Generated body content
     * @var string 
     */
    private $__body;

    /**
     * Array containing class hierarchy in reverse order
     * @var array
     */
    private $_CALLBACK_CLASSES = array();

    /**
     * Array of installed callbacks. Hooks allowed are:
     * <ul>
     *     <li><b>init</b> - hooks called during initialization; see 
     *     {@link cgiapp_init()}</li>
     *     <li><b>prerun</b> - hooks called during prerun; see 
     *     {@link cgiapp_prerun()}</li>
     *     <li><b>postrun</b> - hooks called during postrun; see 
     *     {@link cgiapp_postrun()}</li>
     *     <li><b>teardown</b> - hooks called during teardown; see 
     *     {@link teardown()}</li>
     *     <li><b>tmpl_path</b> - hooks called during {@link tmpl_path()}</li>
     *     <li><b>tmpl_assign</b> - hooks called during 
     *     {@link tmpl_assign()}</li>
     *     <li><b>tmpl_fetch</b> - hooks called during {@link load_tmpl()}</li>
     *     <li><b>error</b> - hooks called prior to calling the error mode
     *     during application {@link run()}</li>
     * </ul>
     * See {@link add_callback()} for more information.
     * @static
     * @var array
     */
    private static $_INSTALLED_CALLBACKS = array(
        // hook name        package             sub
        'init'        => array(),
        'prerun'      => array(),
        'postrun'     => array(),
        'teardown'    => array(),
        'tmpl_path'   => array(),
        'tmpl_assign' => array(),
        'tmpl_fetch'  => array(),
        'error'       => array()
    );

    /**
     * Array matching hook name to where the object instance will be called in
     * the argument list. 'true' means at beginning, 'false' means at end.
     * See {@link call_hook()} for more information.
     * @static
     * @var array
     */
    private static $_HOOK_ARG_PLACEMENT = array(
        'init'        => false,
        'prerun'      => false,
        'postrun'     => false,
        'teardown'    => true,
        'tmpl_path'   => true,
        'tmpl_fetch'  => true,
        'tmpl_assign' => true,
        'error'       => true
    );

    /**
     * Array of template hooks. Template hooks can only keep a single registered
     * callback at a time.
     * @static
     * @var array
     */
    private static $_TMPL_HOOKS = array(
        'tmpl_path', 
        'tmpl_assign',
        'tmpl_fetch'
    );

    /**
     * Array of Cgiapp2 internal methods; used by {@link run()} to determine if a
     * mode parameter that is a method name should call the method or retrieve
     * the CGI value instead.
     * @var array
     */
    private $_CGIAPP_METHODS = array(
        'cap_hash',
        '_send_headers',
        'add_callback',
        'array_to_hash',
        'call_hook',
        'cgiapp_init',
        'cgiapp_postrun',
        'cgiapp_prerun',
        '_class_hierarchy',
        'delete',
        'error_mode',
        'get_current_runmode',
        'header_props',
        'header_type',
        'is_assoc_array',
        'load_tmpl',
        'mode_param',
        'new_hook',
        'param',
        'prerun_mode',
        'query',
        'run',
        'run_modes',
        's_delete',
        's_param',
        'setup',
        'start_mode',
        'teardown',
        'tmpl_assign',
        'tmpl_path'
    );

    /**
     * Return content instead of echoing to screen; useful if your Cgiapp2-based
     * application will be used in a larger application. You can set it in your
     * PARAMS list, if desired.
     * @var bool
     */
    private $_CGIAPP_RETURN_ONLY;

    /**
     * The current run mode being processed.
     * @var string
     */
    private $_CURRENT_RUNMODE;

    /**
     * Your {@link error_mode()} should set this if called; if set, 
     * {@link run()} will use this value as the return value from the run mode.
     * @var string
     */
    private $_ERROR_BODY;

    /**
     * When set, indicates the class method to use to handle errors that occur
     * during execution of the run mode.
     * @var string
     */
    private $_ERROR_MODE;

    /**
     * Array of header types => values.
     * @var array
     */
    private $_HEADER_PROPS;

    /**
     * Valid values are 'none', 'redirect', and 'header'; used to determine
     * what, if any, HTTP headers to send.
     * @var string
     */
    private $_HEADER_TYPE;

    /**
     * The CGI parameter indicating what run mode to call.
     * @var string
     */
    private $_MODE_PARAM;

    /**
     * Array of parameters handled by param() method; typically, these should be
     * the only object properties allowed, and they will be normalized with
     * UPPERCASE keys. {@link param()}
     * @var array
     */
    private $_PARAMS;

    /**
     * Flag indicating whether the generated body content can be changed
     * @var bool
     */
    private $_POSTRUN_MODE_LOCKED;

    /**
     * When set, indicates that the run mode passed should be overridden with
     * the run mode provided in this property.
     * @var string
     */
    private $_PRERUN_MODE;

    /**
     * Flag indicating whether the current run mode can be changed.
     * @var bool
     */
    private $_PRERUN_MODE_LOCKED;

    /**
     * Array of allowed run modes for this application.
     * @var array
     */
    private $_RUN_MODES;

    /**
     * Name of the session ID to set or use to prepend to all session variables;
     * see {@link s_param()} for more information.
     * @var string
     */
    private $_SESSION_ID;

    /**
     * Indicates which run mode to run by default.
     * @var string
     */
    private $_START_MODE;

    /**
     * Base template directory; typically, base Smarty working directory.
     * @var string
     */
    private $_TMPL_PATH;

    /**#@-*/

    /**
     * Constructor
     * 
     * The constructor sets up default values for {@link _HEADER_TYPE}, 
     * {@link _MODE_PARAM}, and {@link _START_MODE}. If an associative array has
     * been passed to it as an argument, it processes it. The array should have
     * one or more of the following elements:
     * <ul>
     * <li>TMPL_PATH: an optional path argument passed to {@link tmpl_path()} 
     *   along with _TMPL_ARGS (if given) to instantiate the 
     *   template object. This should be the path to the base template
     *   directory; if using Smarty, this would be the directory holding the
     *   templates, templates_c, configs, and cache directories.</li>
     * <li>TMPL_ARGS: an optional associative array of arguments to pass to the
     *   template object constructor. These can include any valid arguments to
     *   the template object constructor.</li>
     * <li>PARAMS: an optional associative array of parameters that your
     *   application needs for setup purposes. These might include a database
     *   DSN, template file names, or specific environment selectors. Keys will
     *   be normalized using {@link cap_hash}, and all parameters will be made
     *   into class variables using the {@link param()} method.</li>
     * </ul>
     * Once the above have been processed, Cgiapp2 calls the 
     * {@link cgiapp_init()} method, followed by the {@link setup()} method;
     * both of these should be overridden in your class extension.
     *
     * @param array $args Optional, an associative array; see method and class
     * intro notes for details
     * @return bool success
     */
    final public function __construct($args = null)
    {
        // SET UP DEFAULT VALUES
        // We set them up here and not in the setup() because a subclass which
        // implements setup() still needs default values!

        $this->header_type('header');
        $this->mode_param('rm');
        $this->start_mode('start');

        // Setup some default hooks
        self::add_callback('init', array($this, 'cgiapp_init'));
        self::add_callback('prerun', array($this, 'cgiapp_prerun'));
        self::add_callback('postrun', array($this, 'cgiapp_postrun'));
        self::add_callback('teardown', array($this, 'teardown'));

        // Process optional new() parameters
        if (self::is_assoc_array($args)) {
            $rprops = self::cap_hash($args);
        } else {
            $rprops = array();
        }

        // Set tmpl_path using TMPL_PATH. If TMPL_ARGS has been passed, pass
        // that to tmpl_path() as well.
        if (isset($rprops['TMPL_PATH'])) {
            if (isset($rprops['TMPL_ARGS'])) {
                $this->_TMPL_ARGS = $rprops['TMPL_ARGS'];
                $this->tmpl_path($rprops['TMPL_PATH'], $rprops['TMPL_ARGS']);
            } else {
                $this->tmpl_path($rprops['TMPL_PATH']);
            }
        }

        // Set up init param() values
        if (isset($rprops['PARAMS'])) {
            if (!self::is_assoc_array($rprops['PARAMS'])) {
                self::carp("PARAMS is not an associative array");
            } else {
                $this->param($rprops['PARAMS']);
            }
        }

        // Lock prerun_mode from being changed until cgiapp_prerun()
        $this->_PRERUN_MODE_LOCKED = true;

        // Lock postrun_body from being called until cgiapp_postrun()
        $this->_POSTRUN_MODE_LOCKED = true;

        // Call init hook, which may be implemented in the sub-class or another
        // class entirely. Pass all constructor args forward.  This will allow
        // flexible usage down the line.
        $this->call_hook('init', $args);

        // Call setup() method, which should be implemented in the sub-class!
        $this->setup();

        return true;
    }

    /**
     * Run the application
     * 
     * The run() method is called upon your Application object, from
     * your Instance Script.  When called, it executes the functionality 
     * in your Application Class.
     * 
     * <code>
     *     $webapp = new WebApp();
     *     $webapp->run();
     * </code>
     * 
     * This method first determines the application state by looking at the
     * value of the CGI parameter specified by {@link mode_param()} (defaults to
     * 'rm' for "Run Mode"), which is expected to contain the name of the mode
     * of operation.  If not specified, the state defaults to the value of
     * {@link start_mode()}.
     * 
     * Once the mode has been determined, run() looks at the dispatch table
     * stored in {@link run_modes()} and finds the function pointer which is
     * keyed from the mode name.  If found, the function is called and the data
     * is returned to STDOUT (usually the browser).  If the specified mode is
     * not found in the {@link run_modes()} table, run() will {@link croak()}.
     */
    final public function run() 
    {
        $rm_param = $this->mode_param();
        if (empty($rm_param)) {
            self::croak("No run mode param specified");
            return;
        }

        $REQUEST =& self::query();

        // Get run mode
        if (self::is_assoc_array($rm_param)) {
            // Run mode defined by PATH_INFO; grab from array
            $rm = $rm_param['run_mode'];
        } elseif (function_exists($rm_param)) {
            // $rm_param matches a function name.
            // Determine if run mode is a user-defined function
            $functions = get_defined_functions();
            $user_func = $functions['user'];
            if (in_array($rm_param, $user_func)) {
                // Get run mode from user-defined function
                $rm = $rm_param();
            }
        } elseif (method_exists($this, $rm_param) &&
            !in_array($rm_param, $this->_CGIAPP_METHODS)) 
        {
            // Get run-mode from method
            $rm = $this->$rm_param();
        }
        
        // Get run-mode from CGI param
        if (!isset($rm)){
            if (isset($REQUEST[$rm_param])) {
                $rm = $REQUEST[$rm_param];
            }
        }
        // If run mode not passed, or unknown runmode passed, use the start_mode
        if (empty($rm) || !isset($this->_RUN_MODES[$rm])) {
            $rm = $this->start_mode();
        }

        // Set get_current_runmode() for access by user later
        $this->_CURRENT_RUNMODE = $rm;

        // Allow prerun_mode to be changed
        $this->_PRERUN_MODE_LOCKED = false;

        // Call PRE-RUN hook, now that we know the run-mode
        // This hook can be used to provide run-mode specific behaviors
        // before the run-mode actually runs.
        $this->call_hook('prerun', $rm);

        // Lock prerun_mode from being changed after cgiapp_prerun()
        $this->_PRERUN_MODE_LOCKED = true;

        // If prerun_mode has been set, use it!
        $prerun_mode = $this->prerun_mode();
        if (!empty($prerun_mode) 
            && is_string($prerun_mode)) 
        {
            $rm = $prerun_mode;
            $this->_CURRENT_RUNMODE = $rm;
        }

        $rmodes = $this->run_modes();

        if (isset($rmodes[$rm])) {
            $rmeth = $rmodes[$rm];
        } else {
            $msg  = "No such run-mode '$rm'\n";
            $msg .= "Available run-modes: " . implode(', ', $rmodes);
            self::croak($msg);
            return;
        }

        // Process run mode!
        // However, we need to check for errors. Check for exceptions; if any
        // are caught, run the error_mode() with the exception as its argument
        // and use its return value for the body.
        $error_mode = $this->error_mode();
        set_error_handler(array('Cgiapp2_Exception_Error', 'handler'), E_USER_ERROR);
        try {
            $this->__body = $this->$rmeth();
        } catch (Cgiapp2_Exception $e) {
            $this->call_hook('error', $e);
            if ($error_mode) {
                $this->__body = $this->$error_mode($e);
            }
        }
        restore_error_handler();

        // Call cgiapp_postrun() hook, first unlocking _POSTRUN_MODE_LOCKED
        $this->_POSTRUN_MODE_LOCKED = false;
        $this->call_hook('postrun', $this->__body);

        // Re-Lock _POSTRUN_MODE_LOCKED, now that postrun is done
        $this->_POSTRUN_MODE_LOCKED = true;

        // Send HTTP headers, if necessary
        $this->_send_headers();

        // Send output to browser (unless we're in serious debug mode!)
        if (!$this->_CGIAPP_RETURN_ONLY) {
            echo $this->__body;
        }

        // clean up operations
        $this->call_hook('teardown');

        return $this->__body;
    }


    // SUBCLASSABLE METHODS
    // These methods do nothing unless subclassed!

    /**
     * Perform application specific initialization behaviour
     *
     * cgiapp_init() is called during the 
     * {@link $_INSTALLED_CALLBACKS init hook}, which is during object
     * instantiation, and prior to calling of the {@link setup()} method.
     * 
     * When used, this method provides an optional initialization hook, which
     * allows for customization of the class and all descendents. 
     *
     * The first argument received is the array of arguments sent to the 
     * {@link __construct() constructor method}.
     *
     * As a {@link add_callback() callback hook}, the second argument expected
     * is an object instance; this may be safely ignored if implementing in a
     * Cgiapp2-based class. 
     * 
     * An example of the benefits provided by utilizing this hook is creating a
     * custom "application super-class" from which which all your applications
     * would inherit, instead of Cgiapp2.
     * 
     * Consider the following:
     * 
     * <code>
     *   // In MySuperclass.php:
     *   require_once 'Cgiapp2.class.php';
     *   class MySuperclass extends Cgiapp2 
     *   {
     *       function cgiapp_init($args, $cgiapp) 
     *       {
     *         // Perform some project-specific init behavior
     *         // such as to load settings from a database or file.
     *       }
     *   }
     * 
     * 
     *   // In MyApplication.php:
     *   class MyApplication extends MySuperclass
     *   {
     *       function setup { ... }
     *       function teardown { ... }
     *       // The rest of your Cgiapp2-based class follows...  
     *   }
     * </code>
     * 
     * By using Cgiapp2 and the cgiapp_init() method as illustrated, a suite of
     * applications could be designed to share certain characteristics, such as
     * a database connection, template engine, etc.  This has the potential for
     * much cleaner code built on object-oriented inheritance.
     *
     * <b>UPGRADE NOTE:</b>
     * For those upgrading from a pre-2.0.0 version of Cgiapp2, please note that
     * this method now accepts a second argument, an object instance.
     *
     * @param array $args Uses $args sent to constructor
     * @param object $cgiapp Observed Cgiapp2-based object
     */
    protected function cgiapp_init($args, $cgiapp)
    {
        // Must be overridden
    }

    /**
     * Perform operations before selected run mode is executed
     * 
     * cgiapp_prerun() implements the {@link $_INSTALLED_CALLBACKS prerun hook}
     * of the callback system. If utilized, the hook is called automatically
     * right before the selected run mode method is called.  
     *
     * The first argument passed is the name of the requested run mode.
     *   
     * Since this is a {@link add_callback()} callback hook, the second argument
     * is an object instance; if implementing in a Cgiapp2-based class, you can
     * typically ignore this value. 
     *
     * Use this hook and the run mode argument to provide functionality prior to
     * the requested run mode method being called.  Uses include checking ACLs
     * for permissions to the requested run mode. You may call 
     * {@link prerun_mode()} from this method to then change the requested run
     * mode.
     *
     * Another benefit provided by utilizing this hook is creating a custom
     * "application super-class" from which all your CGI applications would
     * inherit, instead of Cgiapp2.
     * 
     * Consider the following:
     * 
     * <code>
     *   // In MySuperclass.php:
     *   class MySuperclass extends Cgiapp2 {
     *       function cgiapp_prerun($rm, $cgiapp) {
     *         // Perform some project-specific init behavior
     *         // such as to implement run mode specific
     *         // authorization functions.
     *       }
     *   }
     * 
     * 
     *   // In MyApplication.php:
     *   class MyApplication extends MySuperclass {
     *       function setup() 
     *       { 
     *           ... 
     *       }
     *       function teardown() 
     *       { 
     *           ... 
     *       }
     *       // The rest of your Cgiapp2-based class follows...  
     *   }
     * </code>
     * 
     * By using Cgiapp2 and the cgiapp_prerun() method as illustrated, 
     * a suite of applications could be designed to share certain 
     * characteristics, such as a common authorization scheme.
     * 
     * <b>UPGRADE NOTE:</b>
     * For those upgrading from a pre-2.0.0 version of Cgiapp2, please note that
     * this method now accepts a second argument, an object instance.
     *
     * @param string $rm Current selected run mode
     * @param object $cgiapp Observed Cgiapp2-based object
     */
    protected function cgiapp_prerun($rm, $cgiapp)
    {
        // Must be overridden
    }


    /**
     * Execute code following execution of the current run mode method
     * 
     * cgiapp_postrun() implements the 
     * {@link $_INSTALLED_CALLBACKS postrun hook} of the callback system.
     * If implemented, the hook is called after the run mode method has returned
     * its output, but before HTTP headers are generated or content presented.
     * This will give you an opportunity to modify the content and headers
     * before they are returned to the web browser.
     * 
     * A typical use for this hook is piping the output of a run mode through a
     * series of "filter" processors.  For example:
     *
     * <ul>
     *     <li>You want to enclose the output of all your run modes within a
     *         larger, site-wide template.</li>
     *     <li>Your run modes return structured data (such as XML), which you
     *         want to transform using a standard mechanism (such as XSLT).</li>
     *     <li>You want to modify HTTP headers in a particular way across all
     *         run modes, based on particular criteria.</li>
     * </ul>
     *
     * The first argument received is the current generated body content.
     *
     * Since this is a {@link add_callback()} callback hook, the second argument
     * is an object instance; if implementing in a Cgiapp2-based class, you can
     * typically ignore this value. 
     *
     * A potential cgiapp_postrun() method might be implemented as follows:
     * 
     * <code>
     *     function cgiapp_postrun($body, $cgiapp) 
     *     {
     *         // Enclose output HTML table
     *         $new_output  = "<table border=1>";
     *         $new_output .= "<tr><td> Hello, World! </td></tr>";
     *         $new_output .= "<tr><td>". $body ."</td></tr>";
     *         $new_output .= "</table>";
     *   
     *         # Replace old output with new output
     *         $cgiapp->postrun_body($body);
     *     }
     * </code>
     *
     * <b>NOTE:</b> {@link run()} passes the output from your run mode to
     * cgiapp_postrun(), but does not expect anything to return from the method
     * (in point of fact, it does nothing with the method's return value).
     * If you plan on modifying that content or replacing it, call
     * {@link postrun_body()} with the new or modified content, as shown in the
     * example above.
     * 
     * Obviously, with access to the Cgiapp2 object you have full access to use
     * all the methods normally available in a run mode.  You could, for
     * example, use {@link load_tmpl()} to replace the static HTML in this
     * example with content from a template (assigning $body to the template).
     * You could change the HTTP headers (via {@link header_type()} and 
     * {@link header_props()} methods) to set up a redirect.  You could also
     * use the objects properties to apply changes only under certain
     * circumstance, such as a in only certain run modes, and when a 
     * {@link param()} is a particular value.
     *
     * <b>UPGRADE NOTE:</b>
     * For those upgrading from a pre-2.0.0 version of Cgiapp2, please note that
     * this method now accepts a second argument, an object instance. Also note
     * that $body is no longer passed by reference, and that to update or change
     * the generated $body, you must use {@link postrun_body()}.
     *
     * @param string $body Content returned by the run mode
     * @param object $cgiapp Observed Cgiapp2-based object
     */
    protected function cgiapp_postrun($body, $cgiapp)
    {
        // Must override
    }


    /**
     * Setup the application's environment
     * 
     * This method is called by the inherited constructor method.  The setup()
     * method should be used to define the following property/methods:
     * <ul>
     *     <li>{@link mode_param()} - set the name of the run mode CGI
     *         param.</li>
     *     <li>{@link start_mode()} - text scalar containing the default run
     *         mode.</li>
     *     <li>{@link run_modes()} - hash table containing mode => function
     *         mappings.</li>
     *     <li>{@link tmpl_path()} - text scalar containing path to template
     *         files.</li>
     * </ul>
     * Your setup() method may call any of the instance methods of your
     * application.  This function is a good place to define properties specific
     * to your application via the {@link param()} method.
     * 
     * Your setup() method might be implemented something like this:
     * 
     * <code>
     *     function setup() 
     *     {
     *         $this->tmpl_path('/path/to/my/templates/');
     *         $this->start_mode('putform');
     *         $this->run_modes(array(
     *             'putform'  => 'my_putform_func',
     *             'postdata' => 'my_data_func'
     *         ));
     *         $this->param('myprop1');
     *         $this->param('myprop2', 'prop2value');
     *         $this->param('myprop3', array('p3v1', 'p3v2', 'p3v3'));
     *     }
     * </code>
     *
     * @abstract
     */
    abstract protected function setup();


    /**
     * Perform cleanup after running the application
     * 
     * teardown() implements the {@link $_INSTALLED_CALLBACKS teardown hook} of
     * the callback system, which is called automatically after your application
     * runs; it can be used to clean up after your operations.  
     *
     * A typical use of the teardown() method is to disconnect a database
     * connection which was established in the {@link setup()} function (or init
     * hook).  You could also use the teardown() method to store state
     * information about the application to the server.
     *
     * <b>UPGRADE NOTE:</b>
     * For those upgrading from a pre-2.0.0 version of Cgiapp2, please note that
     * this method now accepts an argument, an object instance.
     *
     * @param object $cgiapp Observed Cgiapp2-based object
     */
    protected function teardown($cgiapp) 
    {
        // Must be overridden
    }

    // CALLBACK METHODS

    /**
     * Register a callback for a class instance
     *
     * The add_callback() method allows you to register a callback function that
     * is to be called at the given stage of execution.  Valid hooks include
     * 'init', 'prerun', 'postrun' and 'teardown', 'tmpl_path', 'tmpl_fetch',
     * and any other hooks defined using the {@link new_hook()} method.
     * 
     * The callback should be a valid PHP callback; see 
     * {@link http://php.net/callback PHP callback documentation} for more
     * information.
     * 
     * If multiple callbacks are added to the same hook, they will all be
     * executed one after the other.  The exact order depends on which class
     * installed each callback, and the order in which they were registered.
     *
     * If no $class is specified, and an object callback is specified, and the
     * object is a Cgiapp2-derived class, that object's class will be used.
     *
     * Callbacks are executed first for the current class, then the parent
     * class, and on up the class hierarchy. Additionally, if multiple callbacks
     * are registered for a given class, then they are executed in the order in
     * which they are registered.
     * 
     * Callbacks are stored in the {@link $_INSTALLED_CALLBACKS} static
     * property.  $_INSTALLED_CALLBACKS is a multi-dimensional array with the
     * first key being the hook, and the second the classname; callbacks are
     * stored under $_INSTALLED_CALLBACKS[$hook][$classname].
     *
     * Class-based callbacks are useful for plugins to add features to all web
     * applications.
     * 
     * Another feature of class-based callbacks is that your plugin can create
     * hooks and add callbacks at any time - even before the web application's
     * object has been initialized. You could do this in your class definition
     * file:
     * 
     * <code>
     * class Cgiapp2_Plugin_MyPlugin
     * {
     *     function my_setup($cgiapp)
     *     {
     *         // do some stuff...
     *     }
     * }
     * Cgiapp2::add_callback('init', array('Cgiapp2_Plugin_MyPlugin', 'my_setup'));
     * </code>
     *
     * The above code would register a global plugin that would execute in the
     * cgiapp_init() method.
     *
     * NOTE: Due to the nature of templating, the various tmpl_* hooks are setup
     * so that they <b>always</b> register with the Cgiapp2 class, and such that
     * only one callback is allowed per template hook; the last callback to
     * register will be the callback used.
     *
     * @static
     * @access public
     * @param string $hook
     * @param string|array $callback
     * @param string|object|null $class
     * @return boolean
     */
    final public static function add_callback($hook, $callback, $class = null) 
    {
        $hook = strtolower($hook);
        if (!isset(self::$_INSTALLED_CALLBACKS[$hook])) {
            self::croak("Unknown hook ($hook)");
            return false;
        }

        // Check for valid callback
        if (!is_callable($callback)) {
            self::croak("Invalid callback");
            return false;
        }

        // If callback is object callback and a Cgiapp2-based class, use that for
        // the class
        if (empty($class) 
            && is_array($callback) 
            && is_object($callback[0])
            && is_subclass_of($callback[0], 'Cgiapp2')) 
        {
            $class = get_class($callback[0]);
        }

        // Get class name for registering hook
        if (!is_string($class) && is_object($class)) {
            $className = get_class($class);
        } elseif (is_string($class)) {
            $className = $class;
        } elseif (empty($class) && is_array($callback)) {
            if (is_string($callback[0])) {
                $className = $callback[0];
            }
        } elseif (empty($class)) {
            $className = 'Cgiapp2';
        } else {
            // Bad class name provided; return false
            return false;
        }

        // Initialize hook/class callback array
        if (!isset(self::$_INSTALLED_CALLBACKS[$hook][$className])) {
            self::$_INSTALLED_CALLBACKS[$hook][$className] = array();
        }

        // Register callback
        if (in_array($hook, self::$_TMPL_HOOKS)) {
            // Template hooks are always registered at the Cgiapp2 level, and
            // only one callback for a hook type can be registered at a time.
            self::$_INSTALLED_CALLBACKS[$hook]['Cgiapp2'] = array($callback);
        } else {
            // All other hooks can be registered anywhere along the class
            // inheritance chain
            array_push(self::$_INSTALLED_CALLBACKS[$hook][$className], $callback);
        }

        return true;
    }

    /**
     * Create a new hook location in which to register callbacks
     *
     * Allows developers to create a new location to register callbacks. Takes
     * up to two arguments, a hook name, and a class name or object. If no class
     * name or object is specified, the hook is registered with the global
     * Cgiapp2 class.
     *
     * See {@link call_hook()} for information on how hooks are called.
     *
     * @access public
     * @param string $hook
     * @param string|object|null $class
     * @return boolean
     */
    final public static function new_hook($hook, $class = null)
    {
        if (!is_string($hook)) {
            return false;
        }

        if (is_string($class)) {
            self::$_INSTALLED_CALLBACKS[$hook][$class] = array();
        } elseif (is_object($class)) {
            $className = get_class($class);
            self::$_INSTALLED_CALLBACKS[$hook][$className] = array();
        } elseif (empty($class)) {
            self::$_INSTALLED_CALLBACKS[$hook]['Cgiapp2'] = array();
        } else {
            return false;
        }

        self::$_HOOK_ARG_PLACEMENT[$hook] = true;
        return true;
    }

    /**
     * Call (execute) a hook
     *
     * Executes a callback registered at the given hook. The first argument is
     * the hook name. All remaining arguments are passed to the callback at that
     * hook location.
     *
     * The first argument passed to the hook will always be the current object.
     * Thus, your callback should, at the very least, accept a single argument:
     * <code>
     *     function myHookCallback($cgiapp)
     *     {
     *     }
     * </code>
     *
     * Some hooks may accept additional arguments (e.g., {@link cgiapp_init()},
     * {@link cgiapp_prerun()}, and {@link cgiapp_postrun()}); this will be
     * determined by how the hook is called.
     *
     * Make sure that the callback is executable (i.e., the visibility allows
     * call_hook() to call it).
     *
     * Looks in the current class, and then up the class hierarchy for any hooks
     * registered, giving those further up the last call.
     *
     * If more than one hook is executed or has a return value, than all return
     * values will be returned as an array. Otherwise, call_hook() returns a
     * single value as designated by the callback. You may want to pass
     * references as arguments to your callbacks if you want them to cascade
     * values.
     *
     * @access public
     * @param string $hook
     * @return mixed
     */
    final public function call_hook()
    {
        $args = func_get_args();
        $hook = array_shift($args);

        // Valid hook?
        if (!is_string($hook)) {
            self::carp("Invalid hook type");
            return false;
        }

        if (!isset(self::$_INSTALLED_CALLBACKS[$hook])) {
            self::croak("Unknown hook ($hook)");
            return false;
        }

        // Determine what classes to check for
        if (empty($this->_CALLBACK_CLASSES)) {
            $this->_class_hierarchy($this);
        }

        // Add this object to the argument list
        if (self::$_HOOK_ARG_PLACEMENT[$hook]) {
            array_unshift($args, $this);
        } else {
            array_push($args, $this);
        }

        // Loop through the callback class hierarchy...
        $return = array();
        foreach ($this->_CALLBACK_CLASSES as $class) {
            // Is there a hook for this class?
            if (!isset(self::$_INSTALLED_CALLBACKS[$hook][$class])) {
                continue;
            }

            $executed_callback = array();

            // Loop through callback array
            foreach (self::$_INSTALLED_CALLBACKS[$hook][$class] as $callback) {
                // Check to see if this callback has already been executed
                $key = false;
                if (is_array($callback)
                    && (2 == count($callback))) 
                {
                    if (is_object($callback[0])
                        && is_string($callback[1])) 
                    {
                        // if it's an object method, get the class name so we
                        // can serialize it
                        $array = array(get_class($callback[0]), $callback[1]);
                        $key = serialize($array);
                    } elseif (is_string($callback[0])
                        && is_string($callback[1])) 
                    {
                        // class methods can be simply serialized
                        $key = serialize($callback);
                    }
                } elseif (is_string($callback)) {
                    // function callbacks are simple
                    $key = $callback;
                }

                if (!$key) {
                    self::croak('Invalid call hook (bad callback)');
                    return;
                }

                if (isset($executed_callback[$key])) {
                    // Callback has been executed already... 
                    continue;
                }

                // Execute callback
                try {
                    $return[] = call_user_func_array($callback, $args);
                } catch (Exception $e) {
                    self::croak("Error executing $class callback in $hook stage: " . $e->getMessage());
                    die();
                }
                $executed_callback[$key] = true;
            }
        }

        if (1 == count($return)) {
            $return = $return[0];
        }

        return $return;
    }

    /**
     * Get class hierarchy for a class name or object
     *
     * Determines the class hierarchy for a class, as an ordered array (current
     * class to eldest grandparent). Sets {@link $_CALLBACK_CLASSES}.
     *
     * @access protected
     * @param string|object
     * @return void
     */
    protected function _class_hierarchy($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }

        $classes = array($class);
        while ($class = get_parent_class($class)) {
            array_push($classes, $class);
        }

        $this->_CALLBACK_CLASSES = $classes;
    }


    // UTILITY METHODS

    /**
     * dump debugging information to the screen
     *
     * The dump() method is a debugging function which will return a 
     * chunk of text which contains all the environment and CGI form 
     * data of the request, formatted nicely for human readability.  
     * Useful for debugging in a CLI environment.
     * 
     * <b>Example:</b>
     * <code>
     *     echo $webapp->dump();
     * </code>
     * 
     */
    public function dump() 
    {
        $output = '';

        // Dump run-mode
        $current_runmode = $this->get_current_runmode();

        $output .= "Current Run-mode: '$current_runmode'\n";

        // Dump Params
        $output .=  "\nQuery Parameters:\n";
        $params  =& self::query();
        $output .=  print_r($params, true);

        return $output;
    }    


    /**
     * Dump debugging information as HTML to the screen
     * 
     * The dump_html() method is a debugging function which will return 
     * a chunk of text which contains all the environment and CGI form 
     * data of the request, formatted nicely for human readability via 
     * a web browser.  Useful for outputting to a browser.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $output = $webapp->dump_html();
     * </code>
     */
    public function dump_html() 
    {
        $output = '';

        // Dump run-mode
        $current_runmode = $this->get_current_runmode();

        $output .= "<p>\nCurrent Run-mode: '<b>$current_runmode</b>'<br />\n";

        // Dump Params
        $output .= "Query Parameters:</p>\n<ul>\n";

        $params =& self::query();
        foreach($params as $q => $v) {
            $output .= "<li><b>$q :</b><pre>\n";
            $output .= print_r($v, 1);
            $output .= "</pre>\n</li>\n";
        }
        $output .= "</ul>\n";

        return $output;
    }    


    // HTTP HEADER METHODS

    /**
     * Set or return a list of header properties
     * 
     * The header_props() method expects an associative array of valid  HTTP
     * header properties.  These properties will be passed directly to PHP's
     * header() method.  Refer to the PHP header function documentation for
     * exact usage details.  
     * 
     * If called multiple times, each set of headers is merged with the list
     * already contained; thus, later headers of the same type will overwrite
     * previous headers.
     * 
     * Headers are not sent until the entire document is ready to be returned.
     *
     * <b>Example usage:</b>
     * <code>
     *     $webapp->header_props(array(
     *         'type'    => 'image/gif',
     *         'expires' => '+3d'
     *     ));
     * </code>
     * 
     * @access public
     * @param array $data Optional; associative array of header types and values
     * @return array Array of header properties
     */
    final public function header_props($data = null) 
    {
        // First use?  Create new _HEADER_PROPS!
        if (!isset($this->_HEADER_PROPS)) {
            $this->_HEADER_PROPS = array();
        }

        $rh_p = $this->_HEADER_PROPS;

        // If data is provided, set it!
        if (self::is_assoc_array($data)) {
            // This is an associative array; make a copy
            $rh_p = array_merge($rh_p, $data);
        } elseif (is_array($data) && ((count($data) % 2) == 0)) {
            $rh_p = array_merge($rh_p, self::array_to_hash($data));
        } elseif (is_array($data) || (!is_array($data) && !is_null($data))) {
            self::carp("Bad data passed to header_props()");
        }

        // If we've gotten this far, set and return the value!
        $this->_HEADER_PROPS = $rh_p;
        return $rh_p;
    }    


    /**
     * Set the header type for the current instance of the application
     *
     * The header_type() method expects to be passed either 'header',
     * 'redirect', or 'none'.  This method specifies the type of HTTP headers
     * which should be sent back to the browser.  If not specified, defaults is
     * 'header'.  You can use this method to specify additional or alternate
     * HTTP headers for your application or run mode; this could be useful for
     * returning alternate content (a PDF or image file, for instance).
     * 
     * To perform a redirect using Cgiapp2, you would do the following:
     * 
     * <code>
     *   function some_redirect_mode() 
     *   {
     *       $new_url = "http://site/path/doc.html";
     *       $this->header_type('redirect');
     *       $this->header_props(array('url' => $new_url));
     *       return "Redirecting to $new_url";
     *   }
     * </code>
     * 
     * If you wish to suppress HTTP headers entirely (as might be the case if
     * you're working in a slightly more exotic environment), you can set
     * header_type() to "none".  This will send no headers. Be warned: since you
     * are programming in PHP, <b>some</b> headers <b>will</b> be passed if you
     * are running your script via a web server.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $webapp->header_type('redirect');
     * </code>
     * 
     * @param string $header_type Type of header to set: 'none', 'redirect', or
     * 'header' are valid.
     * @return string $header_type
     */
    final public function header_type($header_type = null) 
    {
        $allowed_header_types = array('header', 'redirect', 'none');

        // First use?  Create new _HEADER_TYPE!
        if (!isset($this->_HEADER_TYPE)) {
            $this->_HEADER_TYPE = 'header';
        }

        // If data is provided, set it!
        if (!empty($header_type) 
            && is_string($header_type)) 
        {
            $header_type = strtolower($header_type);
            if (!in_array($header_type, $allowed_header_types)) {
                self::carp("Invalid header_type '$header_type'");
            } else {
                $this->_HEADER_TYPE = $header_type;
            }
        }

        // If we've gotten this far, return the value!
        return $this->_HEADER_TYPE;
    }    

    // TEMPLATE METHODS

    /**
     * load_tmpl() - fetch a compiled template
     *
     * load_tmpl() takes the name of a template file and returns the text
     * generated by filling that template file. It does so by calling the
     * {@link add_callback() tmpl_fetch hook}.
     *
     * If more than one callback hook has been registered for the tmpl_fetch
     * hook, and each returns a value, than all values will be concatenated with
     * a newline.
     *
     * <b>Example usage:</b>
     * <code>
     *     // Fetching a template from an object instance:
     *     $text = $webapp->load_tmpl('some.tpl');
     *
     *     // Fetching a template from within a class method:
     *     // Common usage is to return the fetched and populated template as
     *     // the value of the run mode:
     *     function someRunMode() {
     *         // Do some processing...
     *         return $this->load_tmpl('some.tpl');
     *     }
     * </code>
     * 
     * <b>UPGRADE NOTE</b>
     * This class may no longer be overridden, as it implements the 
     * {@link add_callback() callback hook} 'tmpl_fetch'.  Also, in order to use
     * it, you will need to include a template plugin in your application or
     * superclass:
     * <code>
     * require_once 'Cgiapp2.class.php';
     * require_once 'Cgiapp2/Plugin/Smarty.class.php';
     * </code>
     *
     * @param string $tmpl_file Name of a template to load
     * @return string $output Contents of the compiled template
     */
    final public function load_tmpl($tmpl_file) 
    {
        return $this->call_hook('tmpl_fetch', $tmpl_file);
    }    


    /**
     * Assign content to a template
     *
     * Attempts to assign content to a template. If a tmpl_assign 
     * callback hook is registered, it will use that callback (see 
     * {@link $_INSTALLED_CALLBACKS} and {@link add_callback()} for information
     * on plugin callbacks), passing it all arguments.
     * 
     * <b>Example usage:</b>
     * <code>
     *     // Assigning values via an object instance
     *     $webapp->tmpl_assign('var_name', 'value');
     *
     *     // From within the class:
     *     function someRunMode()
     *     {
     *         // Assign a single value
     *         $this->tmpl-assign('var_name', $value);
     *
     *         // Assign several values
     *         $this->tmpl_assign(array(
     *             'var1' => $value1,
     *             'var2' => $value2
     *         ));
     * </code>
     * 
     * <b>UPGRADE NOTE</b>
     * This class may no longer be overridden, as it implements the 
     * {@link add_callback() callback hook} 'tmpl_assign'.  Also, in order to
     * use it, you will need to include a template plugin in your application or
     * superclass:
     * <code>
     * require_once 'Cgiapp2.class.php';
     * require_once 'Cgiapp2/Plugin/Smarty.class.php';
     * </code>
     *
     * @param mixed $data An associative array of var => val pairs to assign to
     * the template
     * @param mixed $var,$val Assign $val to $var in the template
     */
    final public function tmpl_assign()
    {
        $args = func_get_args();
        array_unshift($args, 'tmpl_assign');
        return call_user_func_array(array($this, 'call_hook'), $args);
    }


    /**
     * Set template path and initialize template object
     *
     * tmpl_path() sets the file path to the base directory where the templates
     * are stored.  It then calls the {@link add_callback() tmpl_path hook}.
     *
     * tmpl_path() also takes an optional argument of an associative array of
     * parameters to pass to the template engine constructor; this can be used
     * to customize the template engine's behaviour for your application. It is
     * passed to the tmpl_path hook.
     *
     * See {@link Cgiapp2_Plugin_Smarty::init()} for an example of a tmpl_path
     * hook in practice.
     *
     * <b>Example usage:</b>
     * <code>
     *     $webapp->tmpl_path('/path/to/some/templates/');
     * </code>
     * 
     * <b>UPGRADE NOTE</b>
     * This class may no longer be overridden, as it implements the 
     * {@link add_callback() callback hook} 'tmpl_path'.  Also, in order to use
     * it, you will need to include a template plugin in your application or
     * superclass:
     * <code>
     * require_once 'Cgiapp2.class.php';
     * require_once 'Cgiapp2/Plugin/Smarty.class.php';
     * </code>
     *
     * @param string $tmpl_path Optional; path to base template directory
     * @param array $extra_params Optional; associative array of arguments to
     * pass to template engine constructor
     * @return string Template path
     */
    final public function tmpl_path($tmpl_path = null, $extra_params = null) 
    {
        // First use?  Create new _TMPL_PATH!
        if (!isset($this->_TMPL_PATH)) {
            $this->_TMPL_PATH = '.';
        }

        // If data is provided, set it and initialize template object
        if (isset($tmpl_path)     
            && is_string($tmpl_path)
            && is_dir($tmpl_path)) 
        {
            $this->_TMPL_PATH = $tmpl_path;

            // Call hook to create template engine object
            $e = $this->call_hook('tmpl_path', $tmpl_path, $extra_params);
            
            if (!$e) {
                self::carp('No tmpl_path hook set');
            }
        }

        // If we've gotten this far, return the value!
        return $this->_TMPL_PATH;
    }

    // APPLICATION METHODS
    // These methods are Cgiapp2 methods that are critical for standard usage of
    // the class and should, in most events, never be overridden.

    /**
     * Set the CGI parameter used to indicate the run mode
     * 
     * mode_param() is generally called in the {@link setup()} method.  The
     * mode_param() method is used to tell the {@link run()} method how to
     * determine the requested run mode. Three possibilities exist:
     * <ol>
     *     <li>Use a {@link $CGIAPP_REQUEST} parameter (this is the default,
     *     and 'rm' is the
     *     default value)</li>
     *     <li>Provide a function or a method name to use as a callback</li>
     *     <li>Provide the index in PATH_INFO to use</li>
     * </ol>
     * As noted in (1), above, if mode_param() is not specified by the
     * developer, the method defaults to using the {@link $CGIAPP_REQUEST}
     * parameter 'rm'.
     *
     * In the first case, you would call mode_param() simply as:
     * <code>
     *     $webapp->mode_param('mode');
     * </code>
     * <b>Note:</b> if the parameter name you specify matches a method or
     * function you've defined, Cgiapp2 will use that function or method instead.
     * This is probably a bug.
     * 
     * Alternatively you can set mode_param() to use a method or function name,
     * as described in (2), above:
     * <code>
     *     $webapp->mode_param('some_method');
     * </code>
     * This would allow you to create an instance method whose output would be
     * used as the value of the current run mode.  E.g., a "mode param method":
     * <code>
     *     function some_method() 
     *     {
     *         // Do some stuff here, such as looking at SERVER_NAME or
     *         // PATH_INFO, and return a mode parameter accordingly
     *         return 'run_mode_x';
     *     }
     * </code>
     *
     * In the third case mentioned above, you may specify the index of the
     * PATH_INFO element to utilize as the run mode, where PATH_INFO elements
     * are separated by a '/' character. With this method, it is also wise to
     * pass a PARAM key which specifies a {@link $CGIAPP_REQUEST} parameter to
     * use as a fallback. For example:
     * <code>
     *     $webapp->mode_param(array('path_info' => 2, 'param' => 'q'));
     * </code>
     * would look for the second element in the PATH_INFO string for the run
     * mode, or the 'q' {@link $CGIAPP_REQUEST} key if not found. So, in the
     * following:
     * <code>
     *     /path/to/webapp.php/do/view/2
     * </code>
     * the run mode would be set to 'view' (as that's the second element in the
     * PATH_INFO string). But in this:
     * <code>
     *     /path/to/webapp.php/do/?q=form&id=2
     * </code>
     * since there is no second element in the PATH_INFO string, the application
     * would grab the parameter from the 'q' {@link $CGIAPP_REQUEST} element.
     *
     * If you use a negative index, then Cgiapp2 will look from the end of the
     * PATH_INFO list. In the example above, if '-2' were passed as the index,
     * it would use the item 'view' (second from the end).
     *
     * If 'param' is not specified in the array passed to mode_param(), then the
     * default run mode of 'rm' is assumed.
     * 
     * <b>NOTE:</b> As of 1.5.2, if the value passed to mode_param() is the name
     * of a Cgiapp2 internal method (other than {@link dump()}, {@link
     * dump_html()}, {@link carp()}, or {@link croak()}) or PHP internal
     * function, the run mode will be pulled from the {@link $CGIAPP_REQUEST}
     * parameter. This is to enhance security and also to allow using common
     * keywords as mode parameters.
     * 
     * <b>NOTE 2:</b> Try not to use PHP internal function names as mode
     * parameters. In order to check against the PHP internal function list when
     * a mode parameter matching a function is passed, Cgiapp2 calls
     * get_defined_functions(), which causes a significant performance hit.
     *
     * @param string|array $mode_param Optional; the name of the CGI run mode
     * parameter, the name of a function or class method that can return a run
     * mode, or an array specifying the index in PATH_INFO to use and an
     * optional PARAM that could specify a run mode
     * @return string Run mode parameter
     */
    final public function mode_param($mode_param = null) 
    {
        // First use?  Create new _MODE_PARAM!
        if (empty($this->_MODE_PARAM)) {
            $this->_MODE_PARAM = 'rm';
        }

        // If data is provided, set it!
        if (!empty($mode_param)
            && is_string($mode_param))
        {
            // String passed
            // Originally checked to see if mode_param was a method or function
            // first, but run() does that already. For now, we can just set it
            // directly to the string
            $this->_MODE_PARAM = $mode_param;
        } elseif (!empty($mode_param)
            && self::is_assoc_array($mode_param)) 
        {
            // Possibly retrieving from path info
            $params = self::cap_hash($mode_param);

            // Grab the param element, if available
            $mode_param = ((isset($params['PARAM'])) ? $params['PARAM'] : 'rm');

            // Check to see of PATH_INFO passed
            if (isset($params['PATH_INFO']) 
                && ($params['PATH_INFO'] == strval(intval($params['PATH_INFO']))))
            {
                // Attempting to use PATH_INFO
                $idx = $params['PATH_INFO'];
                $idx = (0 > $idx) ? $idx + 1 : $idx - 1;
                if (false !== ($param = self::path_info($idx))) 
                {
                    // Using PATH_INFO
                    $mode_param = array('run_mode' => $param);
                }
            }

            // Set _MODE_PARAM
            $this->_MODE_PARAM = $mode_param;
        }

        // If we've gotten this far, return the value!
        return $this->_MODE_PARAM;
    }    

    /**
     * Define or return a list of the valid run modes for the application
     * 
     * run_modes() expects an associative array which specifies the
     * dispatch table for the different CGI states.  The {@link run()} method
     * uses the data in this table to send the CGI to the correct function as
     * determined by reading the CGI parameter specified by {@link mode_param()}
     * (defaults to 'rm' for "Run Mode").  These functions are referred to as
     * "run mode methods".
     * 
     * The associative array set by this method is expected to contain the mode
     * name as a key.  The value should be either a reference to a function or
     * method  to the run mode method which you want to be called when the CGI
     * enters the specified run mode, or the name of the run mode method to be
     * called (preferred):
     * <code>
     *     'mode_name_by_ref'  => &mode_function()
     *     'mode_name_by_name' => 'mode_function'
     * </code>
     * The run_mode() method specified is expected to return a block of text
     * (e.g.: HTML) which will eventually be sent back to the web browser.
     * Commonly, this block of text will be returned by using a construction
     * like:
     * <code>
     *     return $this->load_tmpl('some_template.tpl');
     * </code>
     * An advantage of specifying your run mode methods by name instead of by
     * reference is that you can more easily create derivative applications
     * using inheritance.  For instance, if you have a new application which is
     * exactly the same as an existing application with the exception of one run
     * mode, you could simply inherit from that other application and override
     * the run mode method which is different.  If you specified your run mode
     * method by reference, your child class would still use the function from
     * the parent class.
     * 
     * In perl, there's a speed advantage to assigning by reference; however, in
     * PHP this is not the case, which provides yet another reason to specify
     * your run modes by name rather than reference. 
     * 
     * The run_modes() method may be called more than once.  Additional values
     * passed into run_modes() will be added to the run modes table.  In the
     * case that an existing run mode is re-defined, the new value will override
     * the existing value.  This behavior might be useful for applications which
     * are created via inheritance from another application, or some advanced
     * application which modifies its own capabilities based on user input.
     * 
     * A second interface for designating run modes and their methods is via an
     * indexed array:
     * <code>
     *     $webapp->run_modes(array('mode1', 'method1, 'mode2', 'method2'));
     * </code>
     * This is the same as the following, via an associative array:
     * <code>
     *     $webapp->run_modes(array(
     *         'mode1' => 'method1',
     *         'mode2' => 'method2'
     *     ));
     * </code>
     * 
     * Finally, you can also pass an array <b>plus</b> an optional flag set to
     * true to. This will assign each element of the array to the
     * correspondingly named method as its run mode:
     * <code>
     *     // Sets run mode 'mode1' to point to method 'mode1', and so on
     *     $webapp->run_modes(array('mode1', 'mode2'), true);
     * </code>
     * 
     * Note that another importance of specifying your run modes in either an
     * array or associative array is to assure that only those methods which are
     * specifically designated may be called via your application.  Application
     * environments which don't specify allowed methods and disallow all others
     * are insecure, potentially opening the door to allowing execution of
     * arbitrary code.  Cgiapp2 maintains a strict "default-deny" stance on all
     * method invocation, thereby allowing secure applications to be built upon
     * it.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $webapp->run_modes(array(
     *         'mode1' => 'some_sub_by_name', 
     *         'mode2' => &some_other_sub_by_ref()
     *     ));
     * </code>
     * 
     * @param array $data (Associative) array of run mode => method mappings
     * @param bool $flag Optional flag indicating that $data is a regular array
     * and that each element refers to a corresponding class method.
     * @return array Associative array of run mode => method mappings
     */
    final public function run_modes($data = null, $flag = null) 
    {
        // First use?  Create new _RUN_MODES!
        if (!isset($this->_RUN_MODES)) {
            $this->_RUN_MODES = array();
        }

        $rr_m = $this->_RUN_MODES;

        // If data is provided, set it!
        if (self::is_assoc_array($data)) {
            // Received a hash; merge it with the current run modes
            $rr_m = array_merge($rr_m, $data);
        } elseif (is_array($data) 
            && ((count($data) % 2) == 0) 
            && empty($flag)) 
        {
            // Received an even number of elements; create a hash out of them
            // and merge with existing contents
            $rr_m = array_merge($rr_m, self::array_to_hash($data));
        } elseif (is_array($data) && $flag) {
            // Received an array and $flag is true; create an associative array
            // where each element of data points to itself.
            $run_modes = array();
            foreach ($data as $mode) {
                // Only assign if the method actually exists
                if (method_exists($this, $mode)) {
                    $run_modes[$mode] = $mode;
                }
            }
            $rr_m = array_merge($rr_m, $run_modes);
        } elseif (!empty($data)) {
            // Don't quite understand what we got here...
            self::carp("Odd number of elements passed to run_modes().  Not a valid hash");
        }

        // If we've gotten this far, return the value!
        $this->_RUN_MODES = $rr_m;
        return $rr_m;
    }


    /**
     * Set the default run mode to use when no run mode is specified by the
     * client
     *
     * The start_mode() contains the name of the mode as specified in the 
     * {@link run_modes()} table.  Default mode is "start".  The mode key
     * specified here will be used whenever the value of the CGI form parameter
     * specified by {@link mode_param()} is not defined.  Generally, this is the
     * first time your application is executed.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $webapp->start_mode('mode1');
     * </code>
     * 
     * @param string $start_mode Optional; the name of the default run mode
     * @return string Name of the default run mode
     */
    final public function start_mode($start_mode = null) 
    {
        // First use?  Create new _START_MODE!
        if (empty($this->_START_MODE)) {
            $this->_START_MODE = 'start';
        }

        // If data is provided, set it!
        if (!empty($start_mode) && is_string($start_mode)) {
            $this->_START_MODE = $start_mode;
        }

        // If we've gotten this far, return the value!
        return $this->_START_MODE;
    }    



    /**
     * Override the run mode that was passed to the application
     * 
     * The prerun_mode() method is an accessor/mutator which can be used within
     * your {@link cgiapp_prerun()} method to change the run mode which is about
     * to be executed.  For example, consider:
     * <code> 
     *     // In WebApp.pm:
     *     require_once 'Cgiapp2.class.php';
     *     class WebApp extends Cgiapp2
     *     {
     *         function cgiapp_prerun($rm)
     *         {
     *             # Get the web user name, if any
     *             $user = $this->s_param('remote_user');
     *       
     *             # Redirect to login, if necessary
     *             if (!$user) {
     *                 $this->prerun_mode('login');
     *             }
     *         }
     *     }
     * </code>
     * In this example, the web user will be forced into the "login" run mode
     * unless they have already logged in.  The prerun_mode() method permits a
     * scalar text string to be set which overrides whatever the run mode would
     * otherwise be.
     * 
     * The use of prerun_mode() within {@link cgiapp_prerun()} differs from
     * setting {@link mode_param()} to use a call-back via subroutine reference.
     * It differs because {@link cgiapp_prerun()} allows you to selectively set
     * the run mode based on some logic in your {@link cgiapp_prerun()} method.
     * The call-back facility of {@link mode_param()} forces you to entirely
     * replace Cgiapp2's mechanism for determining the run mode with your own
     * method.  The prerun_mode() method should be used in cases where you want
     * to use Cgiapp2's normal run mode switching facility, but you want to make
     * selective changes to the mode under specific conditions.
     * 
     * <b>Note:</b>  The prerun_mode() method may ONLY be called in the context
     * of a {@link cgiapp_prerun()} method.  Your application will die() if you
     * call prerun_mode() elsewhere, such as in {@link setup()} or a run mode
     * method.
     * 
     * <b>Example usage</b>
     * <code>
     *     $webapp->prerun_mode('new_run_mode');
     * </code>
     * 
     * @param string $prerun_mode String containing name of the new run mode to
     * be used
     * @return string Name of the run mode that will override the requested run
     * mode
     */
    final public function prerun_mode($prerun_mode = null) 
    {
        // First use?  Create new _PRERUN_MODE
        if (!isset($this->_PRERUN_MODE)) {
            $this->_PRERUN_MODE = '';
        }

        // Was data provided?
        if (!empty($prerun_mode) && is_string($prerun_mode)) {
            // Are we allowed to set prerun_mode?
            if ($this->_PRERUN_MODE_LOCKED) {
                // Not allowed!  Throw an exception.
                self::croak("prerun_mode() can only be called within cgiapp_prerun()");
                return;
            } else {
                // If data is provided, set it!
                $this->_PRERUN_MODE = $prerun_mode;
            }
        }

        // If we've gotten this far, return the value!
        return $this->_PRERUN_MODE;
    }    

    /**
     * Reset the body contents post-run mode, but prior to returning or echoing
     * the value. Use this method from {@link cgiapp_postrun()} or a postrun
     * {@link call_hook() callback hook} to reset the content returned by the
     * run mode. A typical usage would be to place the content in a sitewide
     * template.
     * 
     * @final
     * @access public
     * @param string $body 
     * @return void
     */
    final public function postrun_body($body)
    {
        if ($this->_POSTRUN_MODE_LOCKED) {
            self::croak('postrun_body() can only be called from cgiapp_postrun() or a postrun callback hook');
            return;
        }

        $this->__body = $body;
    }

    /**
     * Display the currently selected run mode
     * 
     * The get_current_runmode() method will return a text scalar containing the
     * name of the run mode which is currently being executed.  If the run mode
     * has not yet been determined, such as during {@link setup()}, this method
     * will return undef.
     *
     * <b>Example usage</b>
     * <code>
     *     $webapp->get_current_runmode();
     * </code>
     * 
     * @return string Name of currently selected run mode
     */
    public function get_current_runmode()
    {
        // It's OK if we return undef if this method is called too early
        return $this->_CURRENT_RUNMODE;
    }    

    /**
     * Set or retrieve the method to use for handling errors
     * 
     * The error_mode() contains the name of a run mode to call in the event
     * that the planned run mode call fails. No error_mode is defined by
     * default. The death of your error_mode() run mode is not trapped, so you
     * can also use it to die in your own special way.
     * 
     * error_mode() is used in the {@link run()} method. If your run mode (or a
     * method it calls) generates an exception, error_mode() is called to
     * determine a method to run that should generate the content to return to
     * the user (i.e., the method should act like a run mode).
     * 
     * @param string $error_mode
     * @return string
     */
    final public function error_mode($error_mode = null)
    {
        // First use?  Create new __ERROR_MODE
        if (!isset($this->_ERROR_MODE)) {
            $this->_ERROR_MODE = '';
        }

        // If data is provided, and the method exists, set it
        if (!empty($error_mode) 
            && is_string($error_mode) 
            && method_exists($this, $error_mode))
        {
            $this->_ERROR_MODE = $error_mode;
        }

        return $this->_ERROR_MODE;
    }

    /**
     * Set one or more application parameters; retrieve an application
     * parameter; or retrieve a list of available application parameters
     *
     * The param() method provides a facility through which you may set 
     * application instance properties which are accessible throughout 
     * your application.
     * 
     * The param() method may be used in two basic ways.  First, you may use it 
     * to get or set the value of a parameter:
     * <code>
     *     $webapp->param('scalar_param', '123');
     *     $scalar_param_values = $webapp->param('some_param');
     * </code>
     * Second, when called in the context of an array, with no parameter name
     * specified, param() returns an array containing all the parameters which
     * currently exist:
     * <code>
     *     $all_params = $webapp->param();
     * </code>
     * The param() method also allows you to set a bunch of parameters at once
     * by passing in an associative array or array:
     * <code>
     *     $webapp->param(array(
     *         'key1' => 'val1',
     *         'key2' => 'val2',
     *         'key3' => 'val3'
     *     ));
     * 
     *     // or
     * 
     *     $webapp->param(array(
     *         'key1', 'val1',
     *         'key2', 'val2',
     *         'key3', 'val3'
     *     ));
     * </code>
     * The param() method enables a very valuable system for customizing your
     * applications on a per-instance basis.  One Application Class might be
     * instantiated by different Instance Scripts.  Each Instance Script might
     * set different values for a set of parameters.  This allows similar
     * applications to share a common code-base, but behave differently.  For
     * example, imagine a mail form application with a single Application Class,
     * but multiple Instance Scripts.  Each Instance Script might specify a
     * different recipient.  Another example would be a web bulletin boards
     * system.  There could be multiple boards, each with a different topic and
     * set of administrators.
     * 
     * The constructor method provides a shortcut for specifying a number of
     * run-time parameters at once.  Internally, Cgiapp2 calls the param() method
     * to set these properties.  The param() method is a powerful tool for
     * greatly increasing your application's re-usability.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $webapp->param('pname', $somevalue);
     * </code>
     * 
     * @param mixed $data Optional
     * @return mixed What is returned depends on what is given; see the docs
     */
    final public function param() 
    {
        // param(): set or return an object property. This method normalizes the
        // property name (to upper case), and allows setting either a single
        // parameter (param($key, $val)) or multiple parameters (my passing
        // either an associative array or an even-count array with ($key, $val,
        // $key2, $val2) structure). Calling it with an empty string returns a
        // list of all parameters that have been registered with it. Calling it
        // with the name of an unregistered paremeter
        // ($this->param('param_name')) will return false.

        // First use?  Create new _PARAMS!
        if (!isset($this->_PARAMS)) {
            $this->_PARAMS = array();
        }

        $rp = $this->_PARAMS;

        // Determine how many arguments were sent
        $numargs = func_num_args();
        if (0 == $numargs) {
            // If no values passed, return the list of parameters
            $params = array();
            foreach($rp as $idx => $param) {
                $params[$idx] = $this->$idx;
            }
            return $params;
        } elseif (1 == $numargs) {
            // one argument passed to param; grab it.
            $data = func_get_arg(0);
            if (is_string($data)) {
                // Argument is a scalar
                $data = strtoupper(trim($data));
                if (isset($rp[$data])) {
                    // Argument is in parameter list; return it
                    return $this->$data;
                }

                // Argument is not in parameter list; return false
                return false;
            } elseif (!is_array($data)) {
                // Object sent to param; error!
                self::carp("Bad arguments (object) sent to param()");
                return;
            } elseif (!self::is_assoc_array($data) 
                && (0 == (count($data) % 2)))
            {
                // even-indexed array passed to method; turn it into a hash
                $data = self::array_to_hash($data);
            } elseif (!self::is_assoc_array($data) 
                && (0 != (count($data) % 2)))
            {
                // odd-indexed array passed to method -- error!
                self::carp("Bad arguments (array) sent to param()");
                return;
            }

            // At this point, we know we have an associative array.
            // Initialize each parameter in it and add it to our parameter list
            $params = array();
            foreach ($data as $key => $val) {
                $key          = strtoupper(trim($key)); // normalize the key
                $this->$key   = $val;                   // set the property
                $params[$key] = true;                   // store param for later
            }
            $rp = array_merge($rp, $params);
        } elseif (2 == $numargs) {
            // Two arguments passed
            $key = func_get_arg(0);
            if (!is_string($key)) {
                // Non-scalar key sent -- error
                self::carp("Bad key (non-string) sent to param()");
                return;
            }
            $key        = strtoupper(trim($key));
            $this->$key = func_get_arg(1);
            $rp         = array_merge($rp, array($key => true));
        } else {
            // Two many arguments sent to param.
            self::carp("Too many arguments sent to param()");
            return;
        }

        // If we got here, we set one or more parameters; set the attribute
        $this->_PARAMS = $rp;
        return true;
    }

    /**
     * Delete an application parameter
     * 
     * The delete() method is used to delete a parameter that was previously
     * stored inside of your application either by using the PARAMS associative
     * array that was passed when instantiating the object or by a call to the
     * {@link param()} method.  It is useful if your application makes decisions
     * based on the existence of certain params that may have been removed in
     * previous sections of your app or simply to clean-up your param()s.
     * 
     * <b>Example usage:</b>
     * <code>
     *     $webapp->delete('my_param');
     * </code>
     * 
     * @param string $key The name of the parameter to delete
     * @return bool
     */
    final public function delete($key = null)
    {
        // Is the _PARAMS property set? if not, return
        if (!isset($this->_PARAMS)) {
            self::carp('Cannot call delete without params');
            return false;
        }

        if (!is_string($key)) {
            self::carp('Called delete without key');
            return false;
        }

        $key = strtoupper(trim($key));
        if (isset($this->_PARAMS[$key])) {
            unset($this->_PARAMS[$key]);
            unset($this->$key);
            return true;
        }

        return false;
    }


    /**
     * Add, list, or retrieve session parameters
     * 
     * Similar to {@link param()}, s_param() sets a session parameter for use
     * with the application.  Internally, it does the following:
     * <ul>
     *     <li>Checks to see if session handling is active; if not, it
     *         produces a warning and returns false.</li>
     *     <li>Determines the session ID:
     *         <ul>
     *             <li>It checks for the existence of a parameter called 
     *                 '{@link $_SESSION_ID}' and fetches it if found;</li>
     *             <li>If the {@link $_SESSION_ID} parameter is *not* found,
     *                 call PHP's session_name() and use that value.</li>
     *         </ul>
     *     </li>
     *     <li>If no values are passed, returns an array of all $_SESSION
     *         variables, whether or not they were set with the s_param
     *         method.</li>
     *     <li>If a single string value is passed to the method, prepend the 
     *         {@link $_SESSION_ID} to the value and return the value of that
     *         session key; if the session key does not exist, return false; if
     *         the session key is invalid (array, object), emit a warning.</li>
     *     <li>If two values are passed to the method, and the first value is a
     *         string, prepend the {@link $_SESSION_ID} to the first value and
     *         set a session variable with that name and assign it a value of
     *         the second parameter sent to s_param().</li>
     *     <li>If an associative array is passed to the method, prepend each key
     *         with the {@link $_SESSION_ID} and create session variables with
     *         each key and value.</li>
     *     <li>If an array with an even number of elements is passed to the
     *         method, use {@link array_to_hash()} to create an associative
     *         array out of it, and do the same as the above.</li>
     *     <li>If more than two values are passed, emit a warning.</li>
     * </ul>
     * @param mixed $data Optional
     * @return mixed What is returned depends on what is given; see the docs
     */
    final public function s_param()
    {
        // function s_param(): initialize or return a session variable.  Use
        // s_param() to initialize session variables. It will prepend all
        // session variables with the current session id, or, if set, the
        // _session_id parameter, plus the character '_',  and normalize the
        // name to upper case to ensure (1) uniqueness of name and (2) to
        // correct typos.

        if (!isset($_SESSION)) {
            self::carp('Session handling has not been activated');
            return false;
        }

        // Get the current session name
        if ($this->param('_session_id')) {
            $session_id = $this->param('_session_id');
        } else {
            $session_id = session_name();
            $this->param('_session_id', $session_id);
        }

        // Determine how many arguments were sent
        $numargs = func_num_args();
        if (2 < $numargs) {
            // Bad number of arguments passed to s_param
            self::carp("Too many arguments sent to s_param()");
            return false;
        } elseif (0 == $numargs) {
            return $_SESSION;
        } elseif (1 == $numargs) {
            // One argument passed
            $data = func_get_arg(0);
            if (is_string($data)) {
                // Scalar arument passed -- return the session value associated
                // with it
                $skey = $session_id . '_' . strtoupper(trim($data));
                if (isset($_SESSION[$skey])) {
                    return $_SESSION[$skey];
                }

                return false;
            } elseif (!is_array($data)) {
                self::carp("Bad argument(s) sent to s_param()");
                return false;
            } elseif (!self::is_assoc_array($data) 
                && (0 == (count($data) % 2)))
            {
                // even-indexed array passed to method; turn it into a hash
                $data = self::array_to_hash($data);
            } elseif (!self::is_assoc_array($data) 
                && (1 == (count($data) % 2)))
            {
                // odd-indexed array passed to method -- error!
                self::carp("Bad argument (array) sent to s_param()");
                return false;
            }

            // We have an associative array; set each key/val pair accordingly
            // in the SESSION array
            $params = array();
            foreach ($data as $key => $val) {
                $_SESSION[$session_id . '_' . strtoupper(trim($key))] = $val;
            }
        } else {
            // two arguments passed; assume key/val pair
            $key = func_get_arg(0);
            if (empty($key) || !is_string($key)) {
                self::carp("Bad key passed to s_param()");
                return false;
            }

            // Set key and val
            $key = $session_id . '_' . strtoupper(trim($key));
            $val = func_get_arg(1);

            // Otherwise, set the value
            $_SESSION[$key] = $val;
        }

        return true;
    }

    /**
     * Remove a session variable
     *
     * Like {@link delete()}, s_delete() deletes a session parameter.
     * 
     * @param string $key The name of the session parameter to delete
     */
    final public function s_delete($key = null)
    {
        if (!isset($_SESSION)) {
            self::carp('Session handling has not been activated');
            return false;
        }

        if (empty($key) || !is_string($key)) {
            self::carp('Bad key passed to s_delete');
            return false;
        }

        // Get the current session name
        if ($this->param('_session_id')) {
            $session_id = $this->param('_session_id');
        } else {
            $session_id = session_name();
            $this->param('_session_id', $session_id);
        }

        $skey = $session_id . '_' . strtoupper(trim($key));
        if (isset($_SESSION[$skey])) {
            unset($_SESSION[$skey]);
            return true;
        }

        return false;
    }

    // PRIVATE METHODS

    /**
     * _send_headers() - send HTTP headers before displaying content
     * 
     * @access protected
     */
    final protected function _send_headers() 
    {
        $header_type  = $this->header_type();
        $header_props = $this->header_props();

        if (($header_type == 'redirect') 
            && !headers_sent()) 
        {
            // If it's a redirect, we'll send that right away
            if (self::is_assoc_array($header_props) 
                && isset($header_props['Location']))
            {
                header("Location: " . $header_props['Location']);
                exit(0);
            }
        } elseif (($header_type == 'header') && !headers_sent()) {
            // If we're sending extra headers, we'll loop through each 
            if (self::is_assoc_array($header_props)) {
                foreach ($header_props as $header => $value) {
                    $header = ucfirst($header);
                    header("$header: $value");
                }
            }
        } elseif (($header_type != 'none') && !headers_sent()) {
            // self::croak() if we have an unknown header type
            self::carp("Invalid header_type '$header_type' given to _send_headers");
            return;
        }

        // Do nothing if header type eq "none".
        // Simply return
        return;
    }    


    // STATIC METHODS
    // These methods are used throughout the class, and may be used in any
    // subclass. In addition, they may be called using the Cgiapp2:: syntax as
    // they do nothing with the object.

    /**
     * Get POST and GET variables and store them for usage
     *
     * This method grabs all POST and GET variables and puts them in a single,
     * protected array, {@link $CGIAPP_REQUEST}. This array may then be
     * referenced when you wish to scrutinize GET or POST variables, but don't
     * care where they come from. It is somewhat safer than using $_REQUEST as
     * it does not utilize $_COOKIE variables, thus limiting one more source for
     * a particular variable.
     *
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @return array Reference to {@link $CGIAPP_REQUEST}
     */
    public static function &query()
    {
        if (empty(self::$CGIAPP_REQUEST)) {
            self::$CGIAPP_REQUEST = array_merge($_GET, $_POST);
        }

        return self::$CGIAPP_REQUEST;
    }

    /**
     * Generate array of PATH_INFO arguments
     *
     * If PATH_INFO exists in the server API, creates an array of elements found
     * in PATH_INFO. 
     *
     * If called with no arguments, the entire array is passed. If provided a
     * positive integer argument, the element in the array with that index is
     * returned (if it exists); a negative integer argument returns that element
     * from the end of the array.
     *
     * Additionally, if passed a string argument, it will look for a path
     * element matching that string and pass the value to its right:
     * <code>
     * // path is: /controller/action/article/1/page/2
     * $page    = Cgiapp2::path_info('page');
     * $article = Cgiapp2::path_info('article');
     * $action  = Cgiapp2::path_info('action');
     * </code>
     *
     * Returns false if PATH_INFO is unavailable or an index does not exist.
     *
     * NOTE: Indices begin with 0!
     * 
     * @static
     * @access public
     * @param int $idx Optional index of element to fetch
     * @return array|false
     */
    public static function path_info($idx = false)
    {
        if (empty(self::$CGIAPP_PATH_INFO)) {
            if (!isset($_SERVER['PATH_INFO'])) {
                if (isset($_SERVER['REQUEST_URI'])) {
                    // Get path info from request URI
                    $pi = $_SERVER['REQUEST_URI'];

                    // See if we can remove the path to the script from the
                    // request URI
                    $script  = $_SERVER['SCRIPT_FILENAME'];
                    $docroot = $_SERVER['DOCUMENT_ROOT'];
                    if (strstr($script, $docroot)) {
                        // Okay, the document root is in the script_filename --
                        // good!
                        $request = substr($script, 0, strlen($docroot));
                        if (strstr($pi, $request)) {
                            // The script path is in the request uri!
                            $pi = substr($pi, strlen($request) - 1);
                        }
                    }

                    // Remove the query string, if present
                    if (strstr($pi, '?')) {
                        $pi = substr($pi, 0, strpos($pi, '?'));
                    }
                } else {
                    // Otherwise, we have no way to know.
                    return false;
                }
            } else {
                $pi = $_SERVER['PATH_INFO'];
            }

            // Trim beginning and ending slashes, and explode on the slash
            $pi = trim($pi, '/');
            $pi = explode('/', $pi);

            // Loop through the elements, and make associative elements for each
            // string, allowing fetching by 'key'
            $last = count($pi) - 1;
            for ($i = 0; $i < $last; $i++) {
                $key = $pi[$i];
                $val = $pi[$i + 1];
                $pi[$key] = $val;
            }

            self::$CGIAPP_PATH_INFO = $pi;
        }

        if (false === $idx) {
            return self::$CGIAPP_PATH_INFO;
        }

        // Fetch text indices
        if ($idx != intval($idx)) {
            if (isset(self::$CGIAPP_PATH_INFO[$idx])) {
                return self::$CGIAPP_PATH_INFO[$idx];
            }
            return false;
        }

        // Fetch by numeric index
        if (0 <= $idx) {
            if (isset(self::$CGIAPP_PATH_INFO[$idx])) {
                return self::$CGIAPP_PATH_INFO[$idx];
            }
        } else {
            $reversed = array_reverse(self::$CGIAPP_PATH_INFO);
            $idx = abs($idx);
            if (isset($reversed[$idx])) {
                return $reversed[$idx];
            }
        }

        return false;
    }

    /**
     * Convert the keys of an associative array to UPPER CASE
     * 
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @param array $rprops
     */
    public static function cap_hash($rprops = null) 
    {
        // Return a false value if we weren't passed an associative array
        if (!self::is_assoc_array($rprops)) {
            return false;
        }

        return array_change_key_case($rprops, CASE_UPPER);
    }    

    /**
     * Test an array to determine if it is associative
     * 
     * is_assoc_array() tests an array to determine if it is an associative
     * array; this is necessary functionality for a number of other methods,
     * including {@link param()} and {@link run_modes()}. It is based on a
     * function found in the document comments for is_array on php.net. It's
     * primary bug is that an associative array made with sequential integer
     * keys will look just like a regular array and thus return a false result.
     * 
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @param array $php_val A value to test
     * @return bool Success
     */
    public static function is_assoc_array($php_val = null) 
    {
        if(!is_array($php_val)){
            # Neither an associative, nor non-associative array.
            return false;
        }

        return array_keys($php_val) !== range(0, count($php_val) - 1);
    }

    /**
     * Convert an even-itemed array into an associative array
     * 
     * array_to_hash() can be used to turn an even-itemed array into an
     * associative arary, where the even elements become keys for the odd
     * elements. In a nutshell,
     * <code>
     *     array('key1', 'val1', 'key2', 'val2')
     * </code>
     * becomes
     * <code>
     *     array('key1' => 'val1', 'key2' => 'val2')
     * </code>
     * 
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @param array $data The array to convert
     * @return array An associative array
     */
    public static function array_to_hash($data = null)
    {
        // Given an indexed array with an even number of elements, create
        // an associative array out of them
        if (!is_array($data) 
            || self::is_assoc_array($data) 
            || (is_array($data) 
                && ((count($data) % 2) != 0)))
        {
            return false;
        }

        $hash = array();
        for ($i = 0; $i < count($data); $i = $i + 2) {
            $k = $data[$i];
            if (is_string($k)) {
                $hash[$k] = $data[$i + 1];
            }
        }

        return $hash;
    }

    /**
     * Echo a warning to screen
     * 
     * Carp is the name of a perl module and is used to provide warnings -- in
     * other words, to notify the user/developer of non-fatal errors. The carp()
     * method does similarly, but uses PHP's native trigger_error() function to
     * do so, with an E_USER_WARNING level error.
     * <b>Note:</b> If your application sets custom headers, a carp() may break
     * them, if the INI setting 'display_errors' is on.
     * 
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @param string $message The warning message to display
     */
    public static function carp($message)
    {
        if (!is_scalar($message)) {
            $message = print_r($message, 1);
        }
        trigger_error($message, E_USER_WARNING);
    }

    /**
     * Exit the script with a warning message
     *
     * croak() was originally a method of the perl Carp module; it's purpose is
     * to trigger fatal errors. The croak() method here does the same, utilizing
     * PHP's native trigger_error() function with an E_USER_ERROR level error.
     * 
     * <b>UPGRADE NOTE:</b>
     * This method is now static.
     *
     * @static
     * @access public
     * @param string $message The warning message to display
     */
    public static function croak($message)
    {
        if (!is_scalar($message)) {
            $message = print_r($message, 1);
        }
        trigger_error($message, E_USER_ERROR);
    }
}

