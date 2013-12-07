<?php
/**
 * stationery.class.php
 * a subclass of cgiapp2
 * an app to access the Chili publishing system
 *
 * this app expects to be logged into via passport,
 * and so refers to some SESSION variables
 * (session name is "stationery")
 * @author Patrick Maslen (pmaslen@unimelb.edu.au)
 *
 * uses Twig templating system, without the cgiapp2 interface to same
 */
/**
 * required files
 */
require_once(dirname(__FILE__) . "/../../lib/find_path.inc.php");
//require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/cgiapp2_Plugin_twig.class.php"); //also includes cgiapp2
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Cgiapp2-2.0.0/Cgiapp2.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
class Stationery extends Cgiapp2 {
  /**
   * @var string $username
   * obtained from passport session info, or defaulting to 'bobthemadjr'
   */
  private $username;
  /**
   * @var array(string mode_name => string description) $run_modes_default_text
   * default text to appear in links to the visible run modes
   */
  private $run_modes_default_text;
  /**
   * @var object $twig
   * Twig environment for templates
   * In this case the template environment may be
   * simpler to install than the template interface
   */
  private $twig;
  /**
   * @var object $loader
   * loader for twig environment
   */
  private $loader;

  function setup() {
    /** 
     * database
     */
    // $this->dbconnect_string = DBCONNECT;
    /** 
     * template
     */
    /*
      if ($this->param('template_path') and $this->param('template_params')) {
      $tpl_params = $this->param('template_params');
      $this->template_path = $this->param('template_path');
      $this->template_filename = $tpl_params['filename'];
      // initialise template
      if (! empty($this->template_filename)) {
      $this->tmpl_path($this->template_path, $this->param('template_params'));
      }
      foreach ($tpl_params as $param => $value) {
      if ($param != 'filename') {
      $this->tmpl_assign($param, $value);
      }
      }
    
      }*/
    /*prepare Twig environment */
    Twig_Autoloader::register();
    if ($this->param('template_path')) {
	$this->template_path = $this->param('template_path');
    }
    $this->loader = new Twig_Loader_Filesystem($this->template_path); 
    $this->twig = new Twig_Environment($this->loader, array(
					    "auto_reload" => true
					    ));
    $tpl_params = $this->param('template_params');
    $this->template_filename = $tpl_params['filename'];
    /**
     * set up the legal run modes => methods table
     * note that login screen is handled outside of the app
     * these run modes assume access is allowed
     * (see passport)
     */
    $this->run_modes(array(
			   'start' => 'showStart'
			   ));
    // should be an entry for each of the run modes above
    $this->run_modes_default_text = array(
					  'start' => 'University Stationery home'
					  );
    $this->start_mode('start');
    //$this->error_mode('handle_errors');
    $this->mode_param('mode');
    $this->action = $_SERVER['SCRIPT_NAME'];
    if(isset($_SESSION['username']))
      {
	$this->username = $_SESSION['username'];
      }
    else
      {
	$this->username = "bobmadjr"; // test username only
      }
  }
  /**
   * function to shut everything down after the app has run
   */
  function teardown() {
  }  
  /**
   * error handling
   */
  function handle_errors($errno, $errstr) {
  }
  /**
   * mode functions here
   */
  /**
   * showStart
   * Starting page -- shows instructions on how to use the app.
   */
  function showStart() {
    $t = 'base.html';
      /*if (!empty($this->template_filename)) {
      echo($this->template_filename); 
      
      }*/
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array());
    return $output;
  }
}
?>