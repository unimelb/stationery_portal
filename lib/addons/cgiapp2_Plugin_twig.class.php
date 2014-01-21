<?php
/**
 * Cgiapp2_Plugin_twig
 * Twig plugin for cgiapp2
 * take 2 -- no Singleton this time as per the 1st example in:
 * http://cgiapp.sourceforge.net/cgiapp2_doc/Cgiapp2/tutorial_Cgiapp27.cls.html
 * 
 * @package helpers
 * @copyright University of Melbourne, 2008
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 * Required files
 */
require_once(dirname(__FILE__) . "/../../lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Cgiapp2-2.0.0/Cgiapp2.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Cgiapp2-2.0.0/Cgiapp2/Plugin/Template/Interface.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
/**
 * A class which allows cgiapp2 to use Twig
 * An instance of this class should be passed as a param() to an instance of cgiapp2.
 * The Singleton version seemed excessively complex to me.
 * Dammit -- looks like I might have to use a Singleton after all due to the static nature
 * of the so-called interface (more like a straitjacket).
 *
 * Twig docs http://twig.sensiolabs.org/doc/api.html
 * 
 * Usage: include this file in your cgiapp2 webapp class definition file
 * Use webapp::tmpl_path(template-path, template-params)
 * where template-path is the directory of your template file
 * and template-params is an associative array of (parameter => value) for the template,
 * which should include the 'filename' parameter. The filename should be just that,
 * without path information (that is obtained from template-path).
 *
 * Both template-path and template-params should be passed as params to the webapp.
 */
class Cgiapp2_Plugin_Twig implements Cgiapp2_Plugin_Template_Interface
{
	/**
	 * path, the path to the template directory
	 */
	public $path;
	/**
	 * twig, the internal Template file
	 * @var object
	 * @access protected
	 */
	protected $twig;
	/**
	 * instance for Singleton use
	 */
	private static $instance;
	
	/**
	 * constructor
	 */
	public function __construct($tmpl_path, $extra_params) {
	  Twig_Autoloader::register();
	  $loader = new Twig_Loader_Filesystem($this->path);
	  $this->path = $tmpl_path;
	  $extra_params['filename'] = $this->path . $extra_params['filename'];
	  $this->twig = new Twig_Environment($loader, $extra_params);
	}
	/**
	 * Singleton pattern
	 * A simpler version than in the examples
	 */
	public static function getInstance()
	{
		$c = __CLASS__;
		if (empty(self::$instance)) {
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
			self::$instance = new $c($tmpl_path, $extra_params);
			}	
		return self::$instance;
		}
	/**
	 * Initialise the template
	 */
	public static function init(Cgiapp2 $cgiapp, $tmpl_path, $extra_params = null)
	{
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
	  /*
	    if (!$cgiapp->param('TemplateEngine_Instance')) 
	    {
	    $tmpl_engine = new MyTemplateEngine($tmpl_path, $extra_params);
	    } 
	    else 
	    {
	    $tmpl_engine = $cgiapp->param('TemplateEngine_Instance');
	    $tmpl_engine->path = $tmpl_path;
	    }
	  */
	  
	  $instance = self::getInstance($cgiapp, $tmpl_path, $extra_params);
	  if ($instance->path != $tmpl_path) {
	    $instance->setTmplPath($tmpl_path);
	  }
	
	  return true;
	}
	/**
	 * used by init() to set the path
	 */
	public function setTmplPath($tmpl_path)
	{
		$this->path = $tmpl_path;
    	}
	/**
	 * Assign variables to the template
	 * Note: Classes that implement must include the class hint for the $cgiapp
     	 * argument.
     	 * 
	 * This is almost identical to the Savant3 and Smarty examples of this function
	 * (except for the use of the Singleton).
	 * Twig wants the template name and context (array)
	 * 'filename' is one of the parameters for the template
	 * -- extract it and send it internally to twig->render(filename, context)
     	 * @static
     	 * @access public
      	 * @param Cgiapp2 $cgiapp 
     	 * @return bool
	 */
	public static function assign(Cgiapp2 $cgiapp) {
	  $args   = func_get_args();
	  $cgiapp = array_shift($args);
	  $argc   = count($args);
	
	  if (1 == $argc) {
	    $values = array_shift($args);
	    if (Cgiapp2::is_assoc_array($values)) {
	      $filename = $values['filename'];
	      self::getInstance()->twig->render($filename, $values);
	    } 
	    else {
	      throw new Exception('Bad array passed to Cgiapp2_Plugin_twig::assign()');
	      return false;
	    }
	  }
	  /*} elseif (2 == $argc) {
	    $key = array_shift($args);
	    if (is_string($key)) {
	    $val = array_shift($args);
	    self::getInstance()->twig->render(trim($key), $val);
	    } else {
	    throw new Exception('Attempting to assign non-string key');
	    return false;
	    }
	    } else {
	    throw new Exception('Bad number or type of arguments passed to Cgiapp2_Plugin_twig::assign()');
	    return false;
	    }*/
	
	  return true;
	}
	
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
	public static function fetch(Cgiapp2 $cgiapp, $tmpl_file) {
		if (empty($tmpl_file) || !is_string($tmpl_file)) {
			return '';
		}
		$output = self::getInstance()->twig->render($tmpl_file, array());
		print $output;
		return $output;
	}
}
/**
 * Register callbacks with tmpl_path, tmpl_assign, and tmpl_fetch callback hooks
 * of Cgiapp2
 */
Cgiapp2::add_callback('tmpl_path', array('Cgiapp2_Plugin_twig', 'init'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_assign', array('Cgiapp2_Plugin_twig', 'assign'), 'Cgiapp2');
Cgiapp2::add_callback('tmpl_fetch', array('Cgiapp2_Plugin_twig', 'fetch'), 'Cgiapp2');
?>
