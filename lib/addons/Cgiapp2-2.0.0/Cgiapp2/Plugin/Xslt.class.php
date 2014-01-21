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
 * XSLT processor
 *
 * Implements {@link Cgiapp2_Plugin_Template_Interface} to create an XSLT
 * plugin for {@link Cgiapp2}. 
 *
 * Registers with Cgiapp2's tmpl_path, tmpl_assign, and tmpl_fetch hooks;
 * registration is done with the Cgiapp2 class.
 *
 * By default, it turns on registerPHPFunctions(), allowing you to use PHP
 * functions as helpers with XSLT stylesheets. If you pass the
 * {@link $params 'registerPHPFunctions' key} to 
 * {@link getInstance() the constructor}, you can turn off this functionality or
 * restrict which functions are allowed. See 
 * {@link http://php.net/xsl the PHP XSL extension documentation} for more
 * information.
 *
 * {@link fetch()} operates differently than other template plugins in that you
 * will typically need to pass an array with two elements, the XML to transform
 * and the XSL to transform it with. However, if you provide the 'xsl' key in
 * the extra parameters sent to {@link init()} or {@link getInstance()}, it will
 * use that value for transforming the XML; this is useful if you have an
 * overriding template that includes required templates on demand based on the
 * variables set.
 * 
 * @package Cgiapp2
 * @author Matthew Weier O'Phinney <mweierophinney@gmail.com>
 * @version @release-version@
 */
class Cgiapp2_Plugin_Xslt implements Cgiapp2_Plugin_Template_Interface
{
    /**
     * Hold template instance
     *
     * @var string
     * @access private
     * @static 
     */
    private static $_instance = false;

    /**
     * Template path
     * @var string 
     * @access public
     */
    public $tmpl_path = null;

    /**
     * Extra parameters passed to the constructor
     * Valid parameters include:
     * - 'xsl' - name of an XSL file or XSL string to use for all
     *   transformations
     * - 'registerPHPFunctions' - boolean, string, or array. 'true' (default)
     *   indicates the XSLT processor should allow processing of any PHP
     *   function; 'false' turns this off. If a string is passed, that single
     *   PHP function will be allowed; if an array is passed, any function in
     *   that array will be allowed.
     * @var null|array 
     * @access public
     */
    public $params = null;

    /**
     * Assigned variables
     * @var array 
     * @access public
     */
    public $vars = array();

    /**
     * Constructor 
     *
     * Creates an instance and initializes the {@link $tmpl_path} property.
     *
     * @param mixed $extra_params 
     * @access private
     * @return void
     */
    private function __construct($params = null)
    {
        if (null !== $params && is_array($params)) {
            if (is_array($params)) {
                if (isset($params['xsl']) && !is_string($params['xsl'])) {
                    throw new Cgiapp2_Exception('Invalid XSL specified');
                }

                $this->params = $params;
            }
        }
    }

    /**
     * Overloading: retrieve property values
     * 
     * @access public
     * @param string $key 
     * @return mixed
     */
    public function __get($key)
    {
        if (isset($this->vars[$key])) {
            return $this->vars[$key];
        }

        return null;
    }

    /**
     * Overloading: set property values
     * 
     * @access public
     * @param mixed $key 
     * @param mixed $val 
     * @return void
     */
    public function __set($key, $val)
    {
        $this->vars[$key] = utf8_encode($val);

        return;
    }

    /**
     * Singleton
     *
     * Returns false if unable to find instance or missing arguments.
     * You can access this at any time:
     *
     * <code>
     * $tpl = Cgiapp2_Plugin_Xslt::getInstance();
     * </code>
     * 
     * @static
     * @access public
     * @param mixed $cgiapp Cgiapp2 instance object
     * @param mixed $tmpl_path Path to template root directory
     * @return void
     */
    public static function getInstance($extra_params = null)
    {
        // Return instance if it exists already
        if (!self::$_instance) {
            self::$_instance = new self($extra_params);
        }

        return self::$_instance;
    }

    /**
     * Initialize a template instance and/or set the template path
     * 
     * If no instance currently exists, it is first initialized via {@link
     * getInstance()} using the extra parameters.
     * 
     * init() is used to set the template path.  If the template path has not
     * changed, nothing is done.
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
        // Get xslt object
        $self = self::getInstance($extra_params);

        if ($self->tmpl_path != $tmpl_path) {
            $self->tmpl_path  = $tmpl_path;
        }

        return true;
    }

    /**
     * Assign a variable or variables to a template
     * 
     * assign() can be used to assign data to a template.
     *
     * You can also send it an associative array of variable names => values,
     * and all elements included will be sent to the template.
     *
     * @static
     * @access public
     * @return bool
     * @throws Cgiapp2_Exception when bad data passed
     */
    public static function assign(Cgiapp2 $cgiapp)
    {
        $argv   = func_get_args();
        $cgiapp = array_shift($argv);
        $argc   = count($argv);

        $self   = self::getInstance();

        if (1 == $argc) {
            $values = array_shift($argv);
            if (Cgiapp2::is_assoc_array($values)) {
                foreach ($values as $key => $value) {
                    $self->$key = $value;
                }
            } else {
                throw new Cgiapp2_Exception('Bad array passed to Cgiapp2_Plugin_Xslt::assign()');
                return false;
            }
        } elseif (2 == $argc) {
            $key = array_shift($argv);
            if (is_string($key)) {
                $val = array_shift($argv);
                $self->$key = $val;
            } else {
                throw new Cgiapp2_Exception('Attempting to assign non-string key');
                return false;
            }
        } else {
            throw new Cgiapp2_Exception('Bad number or type of arguments passed to Cgiapp2_Plugin_Xslt::assign()');
            return false;
        }

        return true;
    }

    /**
     * Fetch template contents
     *
     * $args may be one of the following:
     * - An XML string
     * - The path to an XML file to transform
     * - An array with two elements, the xml string or file to transform and the
     *   xsl stylesheet to transform it with
     *
     * In the first two options, the XSL stylesheet provided by 
     * {@link $params $params['xsl']} will be used. 
     * 
     * If no xml or xsl file is provided or either is invalid, returns an empty
     * string. 
     *
     * @static
     * @access public
     * @param mixed $cgiapp 
     * @param mixed $args
     * @return string
     */
    public static function fetch(Cgiapp2 $cgiapp, $args)
    {
        $self = self::getInstance();
        $parameters = null;

        // Check arguments
        if (empty($args)) {
            return '';
        } elseif (is_array($args) && (2 == count($args))) {
            $xml = $args[0];
            $xsl = $args[1];
        } elseif (is_string($args)) {
            $xml = $args;
            if (!isset($self->params['xsl'])) {
                throw new Cgiapp2_Exception('No XSL stylesheet provided to Cgiapp2 instance');
            }
            $xsl = $self->params['xsl'];
        } else {
            throw new Cgiapp2_Exception('Invalid arguments provided to Xslt');
        }

        // Initialize XSLT handle
        // * Create DOMDocument object
        // * Determine type of XSL:
        if (0 !== strpos(trim($xsl), '<xsl:stylesheet')) {
            $xsl = $self->tmpl_path . '/' . $xsl;
            $xslDoc = DOMDocument::load($xsl);
        } else {
            $xslDoc = DOMDocument::loadXML($xsl);
        }
        if (!$xslDoc instanceof DOMDocument) {
            throw new Cgiapp2_Exception('Invalid XSL or XSL file');
        }

        // * Create XSLTProcessor
        $proc = new XSLTProcessor();

        // Determine if we should register PHP functions with the XSLT
        // processor; if so, do we need to restrict them?
        if (isset($self->params['registerPHPFunctions'])) {
            if (false !== ($functions = $self->params['registerPHPFunctions']))
            {
                $proc->registerPHPFunctions($functions);
            }
        } else {
            $proc->registerPHPFunctions();
        }
        
        // * Load XSL into processor
        $proc->importStylesheet($xslDoc);

        // * Load parameters
        if (is_array($self->vars) && !empty($self->vars)) {
            foreach ($self->vars as $key => $value) {
                $ns = ($self->namespace ? $self->namespace : '');
                $proc->setParameter($ns, $key, $value);
            }
        }

        // * Prepare XML DOMDocument
        $xmlDoc = new DOMDocument();
        if (0 !== strpos(trim($xml), '<?xml')) {
            $xml = $self->tmpl_path . '/' . $xml;
            $xmlDoc = DOMDocument::load($xml);
        } else {
            $xmlDoc = DOMDocument::loadXML($xml);
        }
        if (!$xmlDoc instanceof DOMDocument) {
            throw new Cgiapp2_Exception('Unable to load XML');
        }

        // EVENTUALLY
        // * Allow the ability to register PHP functions as helpers

        // * Process
        if (!$output = $proc->transformToXML($xmlDoc)) {
            throw new Cgiapp2_Exception('Unable to transform XML with XSLT');
        }

        return $output;
    }
}

/**
 * Register callbacks with tmpl_path, tmpl_assign, and tmpl_fetch callback hooks
 * of Cgiapp2
 */
Cgiapp2::add_callback('tmpl_path', array('Cgiapp2_Plugin_Xslt', 'init'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_assign', array('Cgiapp2_Plugin_Xslt', 'assign'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_fetch', array('Cgiapp2_Plugin_Xslt', 'fetch'), 'Cgiapp2');
