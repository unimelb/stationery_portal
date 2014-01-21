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
 * Extends Cgiapp2
 */
require_once 'Cgiapp2.class.php';

/**
 * Cgiapp2_FrontController
 *
 * Basic front controller structure that builds the dispatch table based on
 * classes that are attached to the controller via {@link attach()}. Any class
 * attached in such a manner then has all public static methods available as
 * 'views' of that class. Such methods should accept a Cgiapp2_FrontController
 * object (basically a Cgiapp2 object) as their sole argument.
 *
 * Any return values from these static methods are then passed to 
 * {@link tmpl_assign()} (note: you should probably return an associative
 * array), and the template named in the style 'namepace/view.tpl' is loaded
 * with those values and returned to the controller.
 *
 * A basic instance script might then look like:
 * <code>
 * require_once 'Cgiapp2/FrontController.php';
 *
 * $controller = new Cgiapp2_FrontController(array(
 *     'TMPL_PATH' => '/path/to/templates',
 * ));
 *
 * // Try to load dispatch cache file
 * if (!$controller->attachFromFile('/path/to/dispatch.cache')) {
 *     // Load required classes
 *     require_once 'Views/Gallery.php';
 *     require_once 'Views/Wiki.php';
 *     require_once 'Views/Contact.php';
 *
 *     // Attach classes under namespaces and with default view methods
 *     $controller->attach('Views_Gallery', 'gallery', 'album');
 *     $controller->attach('Views_Wiki', 'wiki', 'page');
 *     $controller->attach('Views_Contact', 'contact', 'form');
 *
 *     // Create dispatch cache file
 *     $controller->createHandlerFile('/path/to/dispatch.cache');
 * }
 *
 * // Run controller
 * $controller->run();
 * </code>
 *
 * A special 'page' namespace is reserved and initially set as the default
 * handler. This corresponds to the {@link page()} method, and simply loads
 * static page content from 'page/REQUESTED_VIEW.tpl'.
 *
 * If using a dispatch cache file (which is recommended), view classes should be
 * named such that an __autoload() function will load the class. An __autoload()
 * implementation has been provided that utilizes PEAR's naming scheme to find
 * classes.
 *
 * @package Cgiapp2
 * @version @release-version@
 */
class Cgiapp2_FrontController extends Cgiapp2 
{
    /**
     * Array of handler methods. Each key is a 'namespace' that will appear in
     * the request URI, and points to an array with the keys 'views' (an
     * associative array of view => callback) and 'default' (the default view).
     * @var array 
     * @access protected
     */
    protected $_handlers = array();

    /**
     * Current method. The 'class' element of the namespace accessed via the
     * requested URI.
     * @var string 
     * @access protected
     */
    protected $_currentMethod = null;

    /**
     * Current view. The 'clasews' element of the namespace accessed via the
     * requested URI.
     * @var string 
     * @access protected
     */
    protected $_currentView = null;

    /**
     * Default namespace to use if none passed in URI.
     * @var string 
     * @access protected
     */
    protected $DEFAULT_METHOD;

    /**
     * Default view template to use for static page views; defaults to 'index'.
     * @var string 
     * @access protected
     */
    protected $DEFAULT_PAGE_VIEW;

    /**
     * Index within PATH_INFO/REQUEST_URI where method may be found. Defaults to
     * 0.
     * @var int 
     * @access protected
     */
    protected $METHOD_INDEX;

    /**
     * Index within PATH_INFO/REQUEST_URI where view may be found. Defaults to
     * 1.
     * @var int 
     * @access protected
     */
    protected $VIEW_INDEX;

    /**
     * Setup
     *
     * @access public
     */
    public function setup() 
    {
        $this->run_modes(array('handler', 'page'), true);
        $this->start_mode('handler');
        $this->mode_param('set_mode');

        // Set default method to 'page' if not set by caller
        if (!$this->param('default_method')) {
            $this->param('default_method', 'page');
        }

        // Set the default page view if not set by caller
        if (!$this->param('default_page_view')) {
            $this->param('default_page_view', 'index');
        }
    }

    /**
     * Attach a class as a handler
     *
     * Attaches a class as a handler under $namespace. $defaultView indicates
     * the default method to use when no view is provided.
     *
     * Throws an exception if the class is not found, or the view does not exist
     * in it.
     *
     * @access public
     * @param string $class
     * @param string $namespace
     * @param string $defaultView
     * @return void
     */
    public function attach($class, $namespace, $defaultView) 
    {
        // Handle this class differently
        if ($class == __CLASS__) {
            // Do nothing!
            return true;
        }

        // Get reflection object
        if (is_string($class) && class_exists($class)) {
            $reflection = new ReflectionClass($class);
        } else {
            throw new Cgiapp2_Exception('Invalid handler');
        }

        $defaultExists = false;
        $views         = array();
        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic() && $method->isStatic()) {
                // Create callback
                $methodName = $method->getName();
                $views[$methodName] = array($class, $methodName);

                if ($defaultView == $methodName) {
                    $defaultExists = true;
                }
            }
        }

        if (!$defaultExists) {
            throw new Cgiapp2_Exception("Default view does not exist: $class::$defaultView");
        }

        $this->_handlers[$namespace] = array(
            'class'   => $class,
            'default' => $defaultView,
            'views'   => $views
        );

        return true;
    }

    /**
     * Attaches handlers from a cached file
     *
     * Returns a boolean, indicating success or failure. False is also returned
     * if no elements are found in {@link $_handlers} after loading from the
     * file.
     *
     * @access public
     * @param string $file
     * @return bool
     */
    public function attachFromFile($file) 
    {
        if (!file_exists($file) || !is_readable($file)) {
            return false;
        }

        if (false === ($cached = @file_get_contents($file))) {
            return false;
        }

        $handlers = @unserialize($cached);

        foreach ($handlers as $method => $info) {
            $this->_handlers[$method] = $info;
        }

        return true;
    }

    /**
     * Set the default handler
     *
     * Sets the default handler to $method, assuming $method exists.
     *
     * @access public
     * @param string $method
     * @return bool
     * @throws Cgiapp2_Exception
     */
    public function setDefaultHandler($method) 
    {
        if (!isset($this->_handlers[$method])) {
            throw new Cgiapp2_Exception('Cannot set default method; does not exist');
        }

        $this->param('default_method', $method);

        return true;
    }

    /**
     * Creates a handler cache file
     *
     * Creates a handler cache file at $file from the available handlers in
     * {@link $_handlers}. Returns boolean success or failure.
     *
     * @access public
     * @param string $file
     * @return bool
     */
    public function createHandlerFile($file) 
    {
        if (!file_exists($file) && !is_writable(dirname($file))) {
            return false;
        }

        // Store
        if (0 === @file_put_contents($file, serialize($this->_handlers))) {
            return false;
        }

        return true;
    }

    /**
     * Set run mode
     *
     * Sets run mode to {@link handler()} for all requests. In addition, sets
     * the values for {@link $_currentMethod} and {@link $_currentView} based on
     * the {@link path_info() PATH_INFO} segments, setting to default values for
     * each if not found.
     *
     * {@link $_currentMethod} is set to the 'class' element of the
     * corresponding {$link $_handlers} element. {@link $_currentView} is set to
     * the view requested.
     *
     * If the {@link $DEFAULT_METHOD} is 'page', it returns 'page' as the run
     * mode, passing execution on to {@link page()}. The {@link $_currentView}
     * is defined from the path, or from {@link $DEFAULT_PAGE_VIEW}.
     *
     * @access protected
     * @return string
     */
    protected function set_mode() 
    {
        $path = $this->path_info();

        if (!$methodIndex = $this->param('method_index')) {
            $methodIndex = 0;
        }

        if (false === ($viewIndex = $this->param('page_index'))) {
            $viewIndex = 1;
        }

        // Get method
        $method = $this->param('default_method');
        if (!empty($path) && isset($path[$methodIndex])) {
            $try = $path[$methodIndex];
            if (isset($this->_handlers[$try])) {
                $method = $try;
            }
        }

        // Handle 'page' requests
        if ('page' == $method) {
            // Page views are handled differently.
            $view = $this->param('default_page_view');
            if (isset($path[$viewIndex])) {
                $view = $path[$viewIndex];
            }

            $this->_currentMethod = 'page';
            $this->_currentView   = $view;

            // Use page() for run mode
            return 'page';
        }

        // All other requests
        // Get view
        $view = $this->_handlers[$method]['default'];
        if (isset($path[$viewIndex])
            && ('page' != $method)
            && isset($this->_handlers[$method]['views'][$path[$viewIndex]])) 
        {
            $view = $path[$viewIndex];
        }

        // Set current method and view
        $this->_currentMethod = $method;
        $this->_currentView   = $view;

        // Use handler for run mode
        return 'handler';
    }

    /**
     * Return the current method
     * 
     * @access public
     * @return string
     */
    public function getMethod()
    {
        return $this->_currentMethod;
    }

    /**
     * Return the current view
     * 
     * @access public
     * @return string
     */
    public function getView()
    {
        return $this->_currentView;
    }

    /**
     * Forwards request to different controller and view
     *
     * If $method is null, defaults to the {@link $DEFAULT_METHOD}. If $view is
     * null or not a view in the selected $method, the default view for that
     * method will be used. If $method is 'page', and no $view is provided, the
     * view selected will be {@link $DEFAULT_PAGE_VIEW}.
     *
     * When done, the values for {@link $_currentMethod} and 
     * {@link $currentView} are reset.
     * 
     * @access public
     * @param string $method 
     * @param string $view 
     * @return void
     */
    public function forward($method = null, $view = null)
    {
        // Set method
        if ((null === $method) || !isset($this->_handlers[$method])) {
            $method = $this->param('default_method');
        }

        // Handle 'page' requests
        if ((null === $view) && ('page' == $method)) {
            // Page views are handled differently.
            $view = $this->param('default_page_view');
        } elseif ((null === $view)
            || !isset($this->_handlers[$method]['views'][$view]))
        {
            $view = $this->_handlers[$method]['default'];
        }

        // Set current method and view
        $this->_currentMethod = $method;
        $this->_currentView   = $view;
    }

    /**
     * Change the view
     *
     * Allows a handler to change the view; particularly useful for changing the
     * template used from a handler.
     *
     * @access public
     * @param string $view
     */
    public function setView($view) 
    {
        $this->_currentView = $view;
    }

    /**
     * Dispatch handler
     *
     * Dispatches by calling {@link $_currentMethod}::{@link $_currentView}; the
     * return value of that callback is then returned.
     *
     * @access public
     * @return string
     */
    public function handler() 
    {
        $method = $this->_currentMethod;
        if ('page' == $method) {
            return $this->page();
        }

        $view   = $this->_currentView;

        return call_user_func($this->_handlers[$method]['views'][$view], $this);
    }

    /**
     * Show a static page
     *
     * Displays a static page based on the view: page/{@link $_currentView}.tpl.
     *
     * @access public
     * @return string
     */
    public function page() 
    {
        return $this->load_tmpl("page/{$this->_currentView}.tpl");
    }
}

if (!function_exists('__autoload')) {
    /**
     * Autoload implementation
     *
     * Autoload class files based on PEAR naming conventions
     *
     * @access public
     * @param string
     */
    function __autoload($class) 
    {
        // use pear functionality
        $file  = str_replace('_', '/', $class) . '.php';
        if (! @include_once($file)) {
            throw new Cgiapp2_Exception('Unable to load class file ' . $file);
        }
    }
}
