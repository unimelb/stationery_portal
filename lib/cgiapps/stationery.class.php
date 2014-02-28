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
  /** 
   * @var object $conn
   * PDO database connection
   */
  private $conn;
  /**
   * @var error
   * error messages
   */
  private $error;
  /** 
   * @var action
   * default action for forms
   */
  private $action;
  /** 
   * @var first_time
   * sets to true if a user has not set up a profile
   */
  private $first_time;
  function setup() {
    /** 
     * database
     */
    // $this->dbconnect_string = DBCONNECT;
    /* should put some error catching here */
    try {
      $this->conn = new PDO(DBCONNECT, DBUSER, DBPASS);
      $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
      $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }
    catch(PDOException $e) {
      $this->error = 'ERROR: ' . $e->getMessage();
      $this->conn = null;
    }
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
			   'new_profile' => 'createProfile',
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
    // close database connection
      $this->conn = null;
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
   * for this username ($_SESSION["username"])
   */
  function showStart() {
    /* check database for user name */
    try {
      //$conn = new PDO(DBCONNECT, DBUSER, DBPASS);
      //$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $stmt = $this->conn->prepare('SELECT * FROM user WHERE username = :id');
      $stmt->execute(array('id' => $_SESSION["username"]));
      if ($stmt->rowCount() == 0) {
	// go to profile page
	return $this->createProfile();
      }
    } catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    $t = 'start.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text,
			       'error' => $error
			       ));
    return $output;
  }
  function createProfile() {
    /* not sure if this is necessary PMGM 22/2/2014 */
    $first_time = true;
    $inserts = array(
		     'INSERT INTO user VALUES(:username, :firstname, :lastname, :telephone, :email);',
		     'INSERT INTO user_department VALUES(:username, :department_id)'
		     );
$selects = array(
		     'SELECT * FROM user WHERE username = :id',
		     'SELECT name, acronym, department_id FROM department'
		     );
    if (isset($_REQUEST["submitted"])) {
      $error = print_r($_REQUEST);
      try {
	$stmt = $pdo->prepare($inserts[0]);
	/*$stmt2 = $pdo->prepare($inserts[1]);*/
	$stmt->execute(array(
			     'username' => $_SESSION["username"],
			     'firstname' => $_REQUEST['firstname'],
			     'lastname' => $_REQUEST['lastname'],
			     'telephone' => $_REQUEST['telephone'],
			     'email' => $_REQUEST['email']
			     ));
	$error .= "<p>Adding details for ". $this->username . ".</p>";
	return $this->showProfile();
      }
      catch(PDOException $e) {
	$error .= $e->getMessage();
      }
    }
    $first_name = $_SESSION["given_names"];
    $surname = $_SESSION["family_name"];
    $email = $_SESSION["email"];
/* get department names */
    $departments = array();
    try {
      $stmt = $this->conn->prepare($selects[1]);
      $stmt->execute(array());
      while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	array_push ($departments, $row);
      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    /* divide department list into 2 for styling */
    $list1_length = ceil(count($departments)/2);
    $departments1 = array_slice($departments, 0, $list1_length );
    $departments2 = array_slice($departments, -1 * ($list1_length-1));
    $t = 'profile.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text,
			       'user' => $this->username,
			       'first_time' => $first_time,
			       'first_name' => $first_name,
			       'surname' => $surname,
			       'email' => $email,
			       'phone' => $phone,
			       'departments1'=> $departments1,
			       'departments2'=> $departments2,
			       'action' => $this->action,
			       'error' => $error
			       ));
    return $output;
  }
  function showProfile() {
    /* edit account profile
     * default if no account setup
     * save profile in database
     */
    $selects = array(
		     'SELECT * FROM user WHERE username = :id',
		     'SELECT name, acronym, department_id FROM department'
		     );
    $updates = array(
		     'UPDATE user SET firstname = :firstname, lastname = :lastname, telephone = :telephone, email = :email WHERE username = :id',
		     );
    $deletes = array(
		     'DELETE FROM user_department WHERE username = :id AND department_id = :department_id)'
		     );
    /* check for form submission first */
    if (isset($_REQUEST["submitted"])) {
      /* if the form has been submitted, insert if it the first time
       * for this user; update the record otherwise
       */
      $error = print_r($_REQUEST, true) . " username: ". $_SESSION["username"];

      $error .= "<p>Updated ". $this->username . ".</p>";
    }
    /* get user details */
    $phone = "";
    /* get info for form */
    try {
      $stmt = $this->conn->prepare($selects[0]);
      $stmt->execute(array('id' => $_SESSION["username"]));
      
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$first_name = $row["given_name"];
	$surname = $row["family_name"];
	$email = $row["email"];
	$phone = $row["phone"];

      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    /* get department names */
    $departments = array();
    try {
      $stmt = $this->conn->prepare($selects[1]);
      $stmt->execute(array());
      while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	array_push ($departments, $row);
      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    /* divide department list into 2 for styling */
    $list1_length = ceil(count($departments)/2);
    $departments1 = array_slice($departments, 0, $list1_length );
    $departments2 = array_slice($departments, -1 * ($list1_length-1));
 
    $t = 'profile.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->run_modes_default_text,
			       'user' => $this->username,
			       'first_time' => $first_time,
			       'first_name' => $first_name,
			       'surname' => $surname,
			       'email' => $email,
			       'phone' => $phone,
			       'departments1'=> $departments1,
			       'departments2'=> $departments2,
			       'action' => $this->action,
			       'error' => $error
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