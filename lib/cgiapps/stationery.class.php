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
			   'start' => 'showStart',
			   'profile' => 'showProfile',
			   'template' => 'selectTemplate',
			   'edit' => 'editTemplate',
			   'history' => 'showHistory',
			   'detail' => 'showJobDetail',
			   'confirm' => 'showConfirmation',
			   'thanks' => 'showFinal'
			   ));
    // should be an entry for each of the run modes above
    /* not yet used 2013-12-13 */
    $this->run_modes_default_text = array(
					  'start' => 'University Stationery home',
					  'profile' => 'Edit your Profile',
					  'template' => 'Select a Template',
					  'edit' => 'Edit your Template',
					  'history' => 'Order history',
					  'detail' => 'Previous job details',
					  'confirm' => 'Confirm and Print',
					  'thanks' => 'Thank you'
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
   * redirect to showProfile if no profile is defined locally
   * for this username
   */
  function showStart() {
    $t = 'start.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function showProfile() {
  /* edit account profile
   * default if no account setup
   */
    $t = 'profile.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function selectTemplate() {
  /* choose from one of the available CHILI templates */
    $t = 'template.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function editTemplate() {
  /* embed the CHILI editor and submit button */
    $t = 'edit.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function showHistory() {
  /* show a list of past jobs for this user */
    $t = 'history.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function showJobDetail() {
  /* show details of a specific past job
   * include a 're-order' button which defines
   * a new job with the parameters of this one
   */
     $t = 'detail.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function showConfirmation() {
  /* confirmation screen requires
   * THEMIS code
   * quantity
   * special comments
   * pressing Submit here: 
   * updates the local job database, 
   * contacts CHILI for a job number
   * prints job as proof pdf
   * sends proof and job data to temporary storage area
   * redirect to showFinal 
   */ 
    $t = 'confirm.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
function showFinal() {
    $t = 'final.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text
			       ));
    return $output;
  }
}
?>