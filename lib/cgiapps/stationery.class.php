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
 * Also requires PDO
 */
/**
 * required files
 */
require_once(dirname(__FILE__) . "/../../lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Cgiapp2-2.0.0/Cgiapp2.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/dbconnect.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/chili.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/storage.inc.php");
include_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/email_admin.inc.php");
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
  /**
   * @var $select, $insert, $update, $delete
   * arrays to store prepared sql statements used by the program
   */
   private $select;
   private $insert;
   private $update;
   private $delete;
   /**
    * @var chili_apikey
    * @var chiliservice
    * object to connect to chili server
    */
   //private $chiliservice;
   private $apikey;
   private $chili_user;
   private $chili_pass;
   private $client;
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
 
   /*prepare Twig environment */
    Twig_Autoloader::register();
    if ($this->param('template_path')) {
	$this->template_path = $this->param('template_path');
    }
    $this->loader = new Twig_Loader_Filesystem($this->template_path);
    /* for testing, 
     * make auto_reload true and cache false
     */
    $twig_options = array(
		      "auto_reload" => false
		      );
    if (is_dir($_SERVER["DOCUMENT_ROOT"] . LIBPATH . 'twigcache')) {
      $twig_options['cache'] = 'twigcache';
    }
    else {
      $twig_options['cache'] = false;
    }
    /* for testing, 
     * make auto_reload true and cache false
     */
    $testing = false;
    if($testing) {
      $twig_options["auto_reload"] = true;
      $twig_options['cache'] = false;
    }
    $this->twig = new Twig_Environment($this->loader, $twig_options);
    /* allows twig to parse object values as arrays */
    $this->twig->addFilter(new Twig_SimpleFilter('cast_to_array', function ($stdClassObject) { return (array)$stdClassObject; }));
    $tpl_params = $this->param('template_params');
    $this->template_filename = $tpl_params['filename'];
    try{
    /* obtain chili api key */
    /* remove , array('trace' => 1) after testing */
    $this->client = new SoapClient(CHILI_APP . "main.asmx?wsdl");
    $this->getChiliUser();
    $keyrequest = $this->client->GenerateApiKey(array("environmentNameOrURL" => CHILI_ENV,"userName" => $this->chili_user, "password" => $this->chili_pass));
    $dom = new DOMDocument();
    $dom->loadXML($keyrequest->GenerateApiKeyResult);
    $this->apikey = $dom->getElementsByTagName("apiKey")->item(0)->getAttribute("key");
    }
    catch(Exception $e){
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
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
			   'proof' => 'showProof',
			   'history' => 'showHistory',
			   'confirm' => 'showConfirmation',
			   'thanks' => 'showFinal',
			   'errors' => 'handle_errors',
			   'template_admin' => 'modifyTemplate',
			   'department_admin' => 'modifyDepartment',
			   'category_admin' => 'modifyCategory',
			   'administrator_admin' => 'modifyAdmin',
			   'template_price_admin' => 'modifyTemplate',
               'printer_admin' => 'modifyPrinter',
			   'add_item' => 'addItem',
			   'update_item' => 'updateItem',
			   'delete' => 'confirmDelete',
			   'admin_guide' => 'showGuide'
			   ));
    // should be an entry for each of the run modes above
    $this->run_modes_default_text = array(
					  'start' => 'Home',
					  'profile' => 'Profile',
					  'template' => 'Select Template',
					  'edit' => 'Edit Template',
					  'proof' => 'Show Proof',
					  'history' => 'History',
					  'confirm' => 'Confirm',
					  'thanks' => 'Thanks',
					  'errors' => 'A problem',
					  'template_admin' => 'Modify Templates',
					  'department_admin' => 'Modify Departments',
					  'category_admin' => 'Modify Categories',
                      'printer_admin' => 'Modify Printers',
					  'administrator_admin' => 'Administrator access',
					  'add_item' => 'Create new item',
					  'update_item' =>'Update item',
					  'delete' => 'Confirm delete',
					  'admin_guide' => 'Administrator Guide'
					  );
    $this->user_visible_modes = array(
			      'template' => 'Select Template',
			      'history' => 'History',
			      );
    $admin_visible = array(
			   'template_admin' => 'Modify Template',
			   'category_admin' => 'Modify Category',
			   'department_admin' => 'Modify Department',
               'printer_admin' => 'Modify Printer',
			   'administrator_admin' => 'Admin access',
			   'admin_guide' => 'Guide'
			   );
    $this->start_mode('start');
    $this->error_mode('handle_errors');
    $this->mode_param('mode');
    $this->action = $_SERVER['SCRIPT_NAME'];
    $this->sqlstatements();
    if(isset($_SESSION['username']))
      {
	$this->username = $_SESSION['username'];
	if ($this->isAdmin()) {
	  $visible_modes = array_merge($admin_visible, $this->user_visible_modes);
	  $this->user_visible_modes = $visible_modes;

	}
  
      }
    else
      {
	$this->username = "bobmadjr"; // test username only
      }

    //$this->upload_dir = $_SERVER["DOCUMENT_ROOT"] . LIBPATH . FILESTORE;
  }
 
   /* select a random user for chili api functions */
   private function getChiliUser() {
     // users need to be moved to database
     $chili_users_inc = array(
			 array('username'=>'StatUser1', 'password'=>'cmyk011'),
			 array('username'=>'StatUser2', 'password'=>'cmyk011'), 
			 array('username'=>'StatUser3', 'password'=>'cmyk011'), 
			 array('username'=>'StatUser4', 'password'=>'cmyk011'), 
			 array('username'=>'StatUser5', 'password'=>'cmyk011'), 
			 array('username'=>'StatUser6', 'password'=>'cmyk011')
			 );
     $x = mt_rand(0,count($chili_users_inc)-1);
     $user_array = $chili_users_inc[$x];
     $this->chili_user = $user_array['username'];
     $this->chili_pass = $user_array['password'];
   }

  /**
   * setup PDO prepared sql statements for use by the program
   * arrays of SELECT, INSERT, UPDATE and DELETE statements
   * this function is a convenient holder for all the SQL 
   * to prevent duplication
   */
  private function sqlstatements() {
    $this->select = array(
			  'SELECT * FROM user WHERE username = :id',
			  'SELECT name, acronym, department_id FROM department ORDER by acronym',
			  'SELECT department_id from user_department where username = :id',
			  "SELECT * FROM template WHERE category_id = :category_id AND department_id in ( jjjdepartments ) OR category_id = :category_id2 AND department_id IS NULL ORDER BY full_name ASC",
			  'SELECT * FROM job WHERE username = :username ORDER BY job_id DESC LIMIT 1',
			  'SELECT j.job_id, j.username, c.description FROM job j, category c, template t WHERE t.template_id = j.template_id AND t.category_id = c.category_id and j.job_id = :job_id',
			  'SELECT id FROM template WHERE template_id = :template_id AND chili_id = :chili_id',
			  'SELECT t.full_name FROM template t, job j WHERE j.job_id= :job_id and j.template_id = t.template_id',
			  'SELECT quantity, sell_price as price_AUD FROM customer_price_view WHERE category_id = :category_id',
			  'SELECT * FROM address where address_id = :address_id',
			  "SELECT * FROM template WHERE category_id = :category_id AND department_id IS NULL ORDER BY full_name ASC",
			  'SELECT * from user_group where group_id = 1 and username = :username'
			  );
    $this->insert = array(
			  'INSERT INTO user VALUES(:username, :firstname, :lastname, :telephone, :email, DEFAULT);',
			  'INSERT INTO user_department VALUES(:username, :department_id)',
			  'INSERT INTO job (job_id, username, template_id) VALUES(DEFAULT, :username, :template_id)',
			  'INSERT INTO address(address_id, addressee, location, street_number, street, town, postcode) VALUES (DEFAULT, :addressee, :location, :street_number, :street, :town, :postcode)',
			  'INSERT INTO :entity (xxx) VALUES (yyy)'
			  );
    $this->update = array(
			  'UPDATE user SET given_name = :firstname, family_name = :lastname, phone = :phone, email = :email WHERE username = :id',
			  'UPDATE job SET chili_id = :chili_id WHERE username = :username AND job_id = :job_id',
			  'UPDATE :entity SET xxx WHERE entity_id = :id'
			  );
    $this->delete = array(
			  'DELETE FROM user_department WHERE username = :username AND department_id = :department_id'
			  );
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
   * an Exception gets sent to this function
   */
  function handle_errors($e) {
    $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    $t = 'base.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'error' => $error
			       ));
    return $output;
  }
  /**
   * mode functions here
   */
  
  /* get info about a user. Used in final and showprofile */
  /* returns false if no matched profile */
  private function getProfile($username) {
    $profile = false;
    try {
      $stmt = $this->conn->prepare($this->select[0]);
      $stmt->execute(array('id' => $username));
      while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	$profile = $row;
      }
    }
    catch(Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    return $profile;
  }
  /* returns true if the user is a member of the 'admin' group;
   * false otherwise */
  private function isAdmin() {
    $count = 0;
    $group_membership = array();
    try {
      $statement = $this->select[11];
      $stmt = $this->conn->prepare($statement);
      $stmt->execute(array(':username' => $this->username));
      while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	$count = array_push($group_membership, $row);
      }
    }
    catch(Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    if($count > 0) {
      return true;
    }
    else {
      return false;
    }
  }
    /**
   * showStart
   * Starting page -- shows instructions on how to use the app.
   * redirect to showProfile if no profile is defined locally
   * for this username ($_SESSION["username"])
   */
  function showStart() {
    /* check database for user name */
    $error = $this->error;
    try {
      $stmt = $this->conn->prepare($this->select[0]);
      $stmt->execute(array('id' => $_SESSION["username"]));
      if ($stmt->rowCount() == 0) {
	//go to profile page
	//return $this->createProfile();
	$modes = array();
      }
      else {
	$modes = $this->user_visible_modes;
      }
    } catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
     $t = 'start.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $modes,
			       'error' => $error
			       ));
    return $output;
  }
  private function isDepartment($string) {
    $isDept = false;
    if(strpos($string, "department")!== false) {
      $isDept = true;
    }
    return $isDept;
  }
  function createProfile() {
    $first_time = true;
    $error = "";
    $action = $this->action . "?mode=" . "new_profile";
    if (isset($_REQUEST["submitted"])) {
      try {
	$stmt = $this->conn->prepare($this->insert[0]);
	/* first add user */
	$stmt->execute(array(
			     'username' => $_SESSION["username"],
			     'firstname' => $_REQUEST['firstname'],
			     'lastname' => $_REQUEST['lastname'],
			     'telephone' => $_REQUEST['phone'],
			     'email' => $_REQUEST['email']
			     ));
	
	$stmt2 = $this->conn->prepare($this->insert[1]);
	$stmt2->bindParam(':username', $username);
	$stmt2->bindParam(':department_id', $department_id);
	$department_keys = array();
	foreach(array_keys($_REQUEST) as $key) {
	  if ($this->isDepartment($key)) {
	    $department_keys[$key] = $_REQUEST[$key];
	  }
	}
	foreach($department_keys as $dept => $value) {
	  $username = $_SESSION["username"];
	  $department_id = $value;
	  $stmt2->execute();
	}
	$this->error .= "<p>Adding details for ". $this->username . ".</p>";
	return $this->showProfile();
      }
      catch(PDOException $e) {
	$error .= $e->getMessage();
      }
    }
    $first_name = $_SESSION["given_names"];
    $surname = $_SESSION["family_name"];
    $email = $_SESSION["email"];
    $phone = "";
/* get department names */
    $departments = array();
    try {
      $stmt = $this->conn->prepare($this->select[1]);
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
    /* output */
    $t = 'profile.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'user' => $this->username,
			       'first_time' => $first_time,
			       'first_name' => $first_name,
			       'surname' => $surname,
			       'email' => $email,
			       'phone' => $phone,
			       'departments1'=> $departments1,
			       'departments2'=> $departments2,
			       'action' => $action,
			       'error' => $error
			       ));
    return $output;
  }
  function showProfile() {
    /* edit account profile
     * default if no account setup
     * save profile in database
     */
    $action = $this->action . "?mode=" . "profile";
    $first_time = false;
    $error = $this->error;
    /* check for form submission first */
    if (isset($_REQUEST["submitted"])) {
      /* if the form has been submitted, update the record */
      /* modify update statement depending on what has changed */
      $form_keywords = array("firstname", "lastname", "phone", "email");
      $used_keywords = array();
      $update_string = "";
      foreach ($form_keywords as $keyword) {
	if (array_key_exists($keyword, $_REQUEST)) {
	  $update_string .= $keyword . " = :" . $keyword . " ";
	  $used_keywords[$keyword] = $_REQUEST[$keyword];
	}
      }
      if (count($form_keywords) == count($used_keywords)) {
	$statement = $this->update[0];
      }
      else {
	$statement = substr_replace($this->update[0], $update_string, 16,80); 
      }
      $used_keywords["id"] = $_SESSION["username"];
      try {
	$stmt = $this->conn->prepare($statement);
	$stmt->execute($used_keywords);
      }
      catch(Exception $e) {
	$error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      }
      /* update departments */
      $old_depts = array(); /* the previously selected departments */
      $new_depts = array(); /* the newly selected departments */
      foreach(array_keys($_REQUEST) as $key) {
	if ($this->isDepartment($key)) {
	  $new_depts[] = $_REQUEST[$key];
	}
      }
      try {
	$stmt_depts = $this->conn->prepare($this->select[2]);
	$stmt_depts->execute(array('id' => $_SESSION["username"]));
	while($row = $stmt_depts->fetch(PDO::FETCH_ASSOC)) {
	  array_push ($old_depts, $row["department_id"]);
	}
      }
      catch(Exception $e) {
	$error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      }
      $common_depts = array_intersect($old_depts, $new_depts);
      $to_insert = array_diff($new_depts, $common_depts, $old_depts );
      $to_delete = array_diff($old_depts, $common_depts, $new_depts );
      try {
	$stmt = $this->conn->prepare($this->insert[1]);
	$stmt->bindParam(':username', $username);
	$stmt->bindParam(':department_id', $department_id);
	$stmt2 = $this->conn->prepare($this->delete[0]);
	$stmt2->bindParam(':username', $username);
	$stmt2->bindParam(':department_id', $department_id);
	if (count($to_insert) > 0){
	  foreach ($to_insert as $dept_id) {
	    $username = $_SESSION["username"];
	    $department_id = $dept_id;
	    $stmt->execute(array(
				 'username' => $username,
				 'department_id' => $department_id
				 ));
	  }
	}
	if (count($to_delete) > 0) {
	  foreach ($to_delete as $dept_id) {
	    $username = $_SESSION["username"];
	    $department_id = $dept_id;
	    $stmt2->execute(array(
				  'username' => $username,
				  'department_id' => $department_id
				  ));
	  }
	}	
      }
      catch(Exception $e) {
	$this->error .= '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      }
      $error .= "<p>Updated ". $this->username . ".</p>";
    }
    /* get user details */
    /* get info for form */
    try {
      $stmt = $this->conn->prepare($this->select[0]);
      $stmt->execute(array('id' => $_SESSION["username"]));
      if ($stmt->rowCount() == 0) {
	return $this->createProfile();
      }
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
    /* find active departments */
    $active_depts = array();
    try {
      $stmt = $this->conn->prepare($this->select[2]);
      $stmt->execute(array('id' => $_SESSION["username"]));
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	array_push ($active_depts, $row["department_id"]);
      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    /* get department names */
    $departments = array();
    try {
      $stmt = $this->conn->prepare($this->select[1]);
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
    $departments2 = array_slice($departments, count($departments1));
    //$departments2 = array_diff($departments, $departments1);
    /* output */
    $t = 'profile.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'user' => $this->username,
			       'first_time' => $first_time,
			       'first_name' => $first_name,
			       'surname' => $surname,
			       'email' => $email,
			       'phone' => $phone,
			       'departments1'=> $departments1,
			       'departments2'=> $departments2,
			       'action' => $action,
			       'error' => $error,
			       'active_depts' => $active_depts
			       ));
    return $output;
  }
  function selectTemplate() {
    /* choose from one of the available CHILI templates */
    /* get categories */
    /* buscards = 1, letheads = 2, withcomps = 3, buscards_DS = 4, 
     *  Envelopes = 5 (unused)*/
    $error = "";
    /*$stationery_type_list = array(
				  array(), array(), array()
				  );*/
    $stationery_type_list = array();
    $dept_ids = array();
    /* get department ids into comma separated string */
    try {
      $stmt_depts = $this->conn->prepare($this->select[2]);
      $stmt_depts->execute(array('id' => $_SESSION["username"]));
      while($row = $stmt_depts->fetch(PDO::FETCH_ASSOC)) {
	array_push ($dept_ids, $row["department_id"]);
      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    } 
    $department_list = implode(",", $dept_ids);
    /* get category ids and names into $categories */
    $categories = $this->getListFromDB('category');
    /* get templates */
    $final_categories = array();
    /* DS business cards (cat 4) are amalgamated with SS (cat 0)
     * for presentation purposes */
    $exceptions = array( '4' => 0);
    $exceptions_keys = array_keys($exceptions);
    foreach ($categories as $cat) {
      /* DS business cards are amalgamated with SS
       * for presentation purposes */
      /*if( ! in_array($cat->category_id, $exceptions_keys)) {
	$final_categories[] = $cat;
	}*/
      if($cat->is_active == 'yes') {
	$final_categories[] = $cat;
      }
    }
    
    //print_r($final_categories);
    for($i = 0; $i< count($final_categories); $i++) {
      $stationery_type_list[] = array();
    }
    $categories_count = count($final_categories);
    $basic_url = 'index.php?mode=edit';

    $statement1 = $this->select[3];
    $category_ids = array('category_id' => 0);
    if (count($dept_ids) == 0) {
      $statement = $this->select[10]; 
    }
    else {
      // replace :department with $department_list
      $statement = str_replace("jjjdepartments", $department_list, $statement1);
      $category_ids['category_id2'] = 0;
      }
    try {
      $stmt = $this->conn->prepare($statement);
      $category_id = 1;
      foreach($categories as $category) {
	foreach(array_keys($category_ids) as $key){
	  $category_ids[$key] = $category->category_id;
	}
	//print_r($category_ids);
	
	$stmt->execute($category_ids
		       );
	while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	  $row->url = $basic_url . '&id=' . $row->chili_id . '&base=' . $row->template_id;
	  if ($category->is_active == 'yes') {
	    if (in_array($category->category_id, $exceptions_keys)){
	      $destination_array = $exceptions[$category->category_id];
	    }
	    else {
	      /* find out how many elements in the exceptions list the category is greater than */
	      $modifier_counter = 1;
	      foreach($exceptions_keys as $key) {
		if ($category->category_id > $key) {
		  $modifier_counter += 1;
		}
	      }

	      $destination_array = $category->category_id - ($modifier_counter);
	    }
	    //print_r($destination_array);
	    array_push($stationery_type_list[$destination_array], $row);
	  }
	}
      }
      
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    /* sort business cards so that double sided and single sided are together */
    usort($stationery_type_list[0], function($a, $b)
	  {
	    return strcmp($a->full_name, $b->full_name);
	  });

    $final_stationery_list = array();
    $categories_available = array();
    foreach ($stationery_type_list as $stationery_list) {
      if(!empty($stationery_list)) {
	$final_stationery_list[] = $stationery_list;
      }
    }
    foreach ($final_categories as $cat) {
      if (! in_array($cat->category_id, $exceptions_keys)) {
	$categories_available[] = $cat;
      }
    }
    //print_r($final_stationery_list);
    //print_r($categories_available);
    $t = 'template.html';
     $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'error' => $error,
			       'modes' => $this->user_visible_modes,
			       'buscards'=> $stationery_type_list[0],
			       'letheads'=> $stationery_type_list[1],
			       'withcomps'=> $stationery_type_list[2],
			       'stationery_types' => $final_stationery_list,
			       'categories' => $categories_available,
			       'image_path' => LIBPATH
			       ));
     return $output;
  }
 /* embed the CHILI editor and submit button */
  /* if no template_id in url, arbort and return to select a template
  /* create new job
   * get new chili job id based on template_id
   * open iframe with chili_job for editing
  /* submit button goes to confirm screen */ 
  /* API calls:
   * 1. ResourceItemCopy to create new doc from template
   * public string ResourceItemCopy ( string apiKey, string resourceName, string itemID, string newName, string folderPath );
   * 2. DocumentGetHTMLEditorURL to get URL for new document
   * public string DocumentGetHTMLEditorURL ( string apiKey, string itemID, string workSpaceID, string viewPrefsID, string constraintsID, bool viewerOnly, bool forAnonymousUser );
   */
  function editTemplate() {
    $blankDocTemplateID = $_REQUEST["id"];
    $base = $_REQUEST["base"];
    $error = $this->error;
    $folderPath = 'USERFILES/';
    /* as a basic check */
    /* kick them back to select if the id is not the right length */
    if (strlen($blankDocTemplateID) != 36) {
      return $this->selectTemplate();
    }
    
    /* check for $_REQUEST["proof"] --> generate proof pdf, load samesame page

     * check for $_REQUEST["submit"] --> go to confirm screen
     * check for $_REQUEST["samesame"] --> use job_id directly instead of copy
     * proof will also have samesame by default
     * non-submitted should also be samesame
     */
    if (isset($_REQUEST["samesame"])) {
	if ($_REQUEST["samesame"]=="same" and isset($_REQUEST["job"])) {
	  /* use job_id directly instead of copy */
	  /* unless base + id  is one of the base templates */
	  /*if (! $this->isBaseTemplate($blankDocTemplateID, $base)) {

	    }*/
	  $job_id = $_REQUEST["job"];
	  $itemID = $this->getChiliId($job_id);
	}
      }
    else {
      /* create new job locally */
      $job_id = $this->createJob($base);

      $itemID = $this->duplicateTemplate($job_id, $blankDocTemplateID);
      /* update job with new template_id */
      $var_array = array('chili_id' => $itemID);
      $this->updateJob($job_id, $var_array);
    }
    $proofurl = $this->action . "?mode=proof&base=$base&proof=true&samesame=same&job=$job_id";
    $submiturl = $this->action . "?mode=confirm&job=$job_id";
    $job_category_id = $this->getCategoryFromJob($job_id);
    $doc = $itemID;
    $ws = CHILI_WS;
    $apikey = $this->apikey;
    $username = $this->chili_user;
    $password = $this->chili_pass;
    $DocumentGetHTMLEditorURL_params = array(
					 "apiKey" => $this->apikey,
					 "itemID" => $itemID,
					 "workSpaceID" => $ws,
					 "viewPrefsID" => "",
					 "constraintsID" => "",
					 "viewerOnly" => false,
					 "forAnonymousUser" => false
					 );
    $urlinfo = $this->client->DocumentGetHTMLEditorURL($DocumentGetHTMLEditorURL_params);
    $dom = new DOMDocument();
    $dom->loadXML($urlinfo->DocumentGetHTMLEditorURLResult);
    $src = $dom->getElementsByTagName("urlInfo")->item(0)->getAttribute("url");
    $src_extra = "&username=$username&password=$password&fullWS=true";
    $error = "";
 
    $t = 'edit.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'proofurl' => $proofurl,
			       'submiturl' => $submiturl,
			       'error' => $error,
			       'modes' => $this->user_visible_modes,
			       'iframesrc' => $src . $src_extra,
                   'category_id' => $job_category_id
			       ));
    return $output;
  }
  /* returns a chili id for a particular job */
  private function getChiliId($job_id) {
    $chili_id = "";
    $statement = $this->select[4];
    $replaced = str_replace(":username", ":username and job_id = :job_id", $statement);
    try {
      $stmt = $this->conn->prepare($replaced);
      $stmt->execute(array('username' => $this->username,
			   'job_id' => $job_id));
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$chili_id = $row["chili_id"];
      }
      
    }
    catch (Exception $e){
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    return $chili_id;
  }

  /* create a new job based on the username
   * return job_id or false if it failed
   */
  private function createJob($base_template_id) {
    /* get job id for documentName below */
    /* $this->select[4] */
    $job_id = 2; // obviously a dummy function
    /* create new job locally */
    try {
      $stmt = $this->conn->prepare($this->insert[2]);
      $stmt->execute(array('username' => $this->username, 'template_id' => $base_template_id));
    }
    catch (Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      $job_id = false;
    }
    /* get job id for job just created */
    if ($job_id !== false) {
      try {
	$stmt2 = $this->conn->prepare($this->select[4]);
	$stmt2->execute(array('username' => $this->username));
	while($row = $stmt2->fetch(PDO::FETCH_ASSOC)) {
	  $job_id = $row["job_id"];
	}
      
      }
      catch (Exception $e){
	$this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
	$job_id = false;
      }
    }
    return $job_id;
  }

  /* returns true if the combination of base template id and chili id 
   * is one of the chili base templates
   */
  private function isBaseTemplate($base_template_id, $chili_id) {
    $isBaseTemplate = false;
    $template_id = -1;
    try {
      $stmt = $this->conn->prepare($this->select[6]);
      $stmt->execute(array('template_id' => $base_template_id,
			   'chili_id' => $chili_id));
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$template_id = $row["template_id"];
      }
      if ($template_id != -1) {
	$isBaseTemplate = true;
      }
    }
    catch (Exception $e){
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    return $isBaseTemplate;
  }


  /* add chili id to job
   * yeah, could be more general 
   * $var_array is array of variables to change
   */
  private function updateJob($job_id, $var_array) {
    /* create statement from $var_array */
    //$var_array = array('chili_id' => $chili_id);

    /* this part can be abstracted out */
    $settings = array();
    $statement = "";
    $key_conditions = array();
    foreach ($var_array as $key => $value) {
      if (is_string($value))
	{
	  $value = trim($value);
	  if (strlen($value) == 0)
	    {
	      $value = null;
	    }
	}
      $lcasekey = strtolower($key);
      $settings[] = $lcasekey . " = " . ":" . $lcasekey;
    }
    $settext = implode(", ", $settings);
    $primary_key = 'job_id'; //this would be find primary column
    $keytext = $primary_key . " = :" . $primary_key;
    $statement = "update job set " . $settext . " where " . $keytext;
    /* to here */
    $var_array["job_id"] = $job_id;
    try {

      $stmt = $this->conn->prepare($statement);
      $stmt->execute($var_array);
    }
    catch (Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
  }
  
  /* returns a string document name in the format:
   * job_id-username-category (no spaces)
   * or the derived name from the job_id
   * or false if no name is available
   * $template_id is a string
   */
  private function getTemplateName($job_id) {
    $template_name_array = array();
    $template_name = "";
    try {
      $stmt = $this->conn->prepare($this->select[5]);
      $stmt->execute(array('job_id' => $job_id));
      while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
	$template_name_array = array(
				     str_pad($row["job_id"], 4, "0", STR_PAD_LEFT),
				$row["username"],
				$row["description"]
				);
      }
    }
    catch(Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      $template_name = false;
    }
    $template_name = implode('-',$template_name_array);
    return str_replace(' ', '', $template_name);
  }
  /* copies a CHILI template, returns the chili_id of the duplicate */
  /* requires $blankDocTemplateID, the chili id of the template to copy */
  private function duplicateTemplate($job_id, $blankDocTemplateID) {
    $folderPath = 'USERFILES/'; //make it an include?
    $documentName = $this->getTemplateName($job_id);
    $soap_params = array(
			 "apiKey" => $this->apikey,
			 "resourceName" => "Documents",
			 "itemID" => $blankDocTemplateID,
			 "newName" => $documentName,
			 "folderPath" =>  $folderPath,
			 );
    $resourceItemXML = $this->client->ResourceItemCopy($soap_params);
    $dom = new DOMDocument();
    $dom->loadXML($resourceItemXML->ResourceItemCopyResult);
    $itemID = $dom->getElementsByTagName("item")->item(0)->getAttribute("id");
    return $itemID;
  }
  function showProof() {
    /* display proof PDF and allow user to return to editing or sumbit to print */
    $base = $_REQUEST["base"];
    $error = $this->error;
    $job_id = $_REQUEST["job"];
    $itemID = $this->getChiliId($job_id);

    $submiturl = $this->action . "?mode=confirm&job=$job_id";
    /*check for samesame */
    /* if not present, take it out of $editurl */
    if (isset($_REQUEST["samesame"])) {
      if($_REQUEST["samesame"] != "same") {
	/* duplicate and update job, as in edit page */
	$job_id_new = $this->createJob($base);
	$itemID_new = $this->duplicateTemplate($job_id_new, $itemID);
	/* update job with new template_id */
	$var_array = array('chili_id' => $itemID_new);
	$this->updateJob($job_id_new, $var_array);
	/* submiturl needs to be updated too */
	$submiturl = $this->action . "?mode=confirm&job=$job_id_new";
	$job_id = $job_id_new;
	$itemID = $itemID_new;
      }
    }
    $editurl = $this->action . "?mode=edit&base=$base&id=$itemID&samesame=same&job=$job_id";
    $pdfurl = "";
    if (isset($_REQUEST["proof"])) {
      /* get settingsXML for PDF settings resource PROOF */
      /* ResourceItemGetDefinitionXML used in API sample:
       * public string ResourceItemGetDefinitionXML ( string apiKey, string resourceName, string itemID );
      /* public string ResourceItemGetXML ( string apiKey, string resourceName, string itemID ); */
      $pdf_resource_params = array(
				   "apiKey" => $this->apikey,
				   "resourceName" => "PDFExportSettings",
				   "itemID" => CHILI_PROOF
				   );
      $settingsXML = $this->client->ResourceItemGetDefinitionXML($pdf_resource_params);

      /*generate pdf with api */
      /* public string DocumentCreatePDF ( string apiKey, string itemID, string settingsXML, int taskPriority ); */
      $soap_params = array(
			   "apiKey" => $this->apikey,
			   "itemID" => $itemID,
			   "settingsXML" => $settingsXML->ResourceItemGetDefinitionXMLResult,
			   "taskPriority" => 1
			   );
      $taskXML = $this->client->DocumentCreatePDF($soap_params);
      $dom = new DOMDocument();
      $dom->loadXML($taskXML->DocumentCreatePDFResult);
      $task_id = $dom->getElementsByTagName("task")->item(0)->getAttribute("id");
    }
    // check task status until task is finished, then get URL
    $task_params = array(
			 "apiKey" => $this->apikey,
			 "taskID" => $task_id
			 );
    $status = "";
    try{
      do {
	$task_statusXML =  $this->client->TaskGetStatus($task_params);
	$dom = new DOMDocument();
	$dom->loadXML($task_statusXML->TaskGetStatusResult);
	$status = $dom->getElementsByTagName("task")->item(0)->getAttribute("finished");
      } while ($status != "True");
      $result = $dom->getElementsByTagName("task")->item(0)->getAttribute("result");
      $dom2 = new DOMDocument();
      $dom2->loadXML($result);
      $relativeURL = $dom2->getElementsByTagName("result")->item(0)->getAttribute("relativeURL");
      $pdfurl = CHILI_APP . $relativeURL; 
    }
    catch (Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }

    $t = 'showproof.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'editurl' => $editurl,
			       'pdfurl' => $pdfurl,
			       'submiturl' => $submiturl
			       ));
    return $output;
  }
  function showHistory() {
    /* show a list of past jobs for this user */
    /* get the jobs */
    $jobslist = array();
    $incomplete = array();
    $statement1 = $this->select[4];
    $statement = str_replace('ORDER BY job_id DESC LIMIT 1', 'ORDER BY ordered DESC', $statement1);
    try {
      $stmt = $this->conn->prepare($statement);
      $stmt->execute(array('username' => $this->username));
      while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
	$row->template_name = $this->getTemplateNameFromJob($row->job_id);
	if (is_null($row->ordered))
	  {
	    $row->url = $this->action . "?mode=proof&base=" .$row->template_id . "&proof=true&samesame=same&job=" . $row->job_id;
	    array_push($incomplete, $row);
	  }
	else {
	  $row->url = $this->action . "?mode=proof&base=" .$row->template_id . "&proof=true&samesame=noway&job=" . $row->job_id;
	  $date = date_create_from_format('Y-m-d H:i:s', $row->ordered);
	  #$row->ordered = date_format($date, 'd F Y');
      $row->ordered = $date->getTimestamp();
	  array_push ($jobslist, $row);
	}
      }
    }
    catch(Exception $e) {
      $error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    $t = 'history.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'error' => $this->error,
			       'jobs' => $jobslist,
			       'incomplete' => $incomplete
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
  $default_address_list = $this->getListFromDB('address', 
					       array('address_id' => 1));
  if (!empty($default_address_list)){
    $default_address = $default_address_list[0];
  }
  else {
    $default_address = '';
  }
    
  
  $action = $this->action . "?mode=thanks";
  $job_id = $_REQUEST["job"];
  $stationery_type = $this->getTemplateNameFromJob($job_id);
  $quantities = $this->getPricelistFromJob($job_id);
  $stationery_title = "To print: " . $stationery_type;
  /* this comment is used in showFinal also */
  $pick_up_comment = ' Please pick up from External Relations.';
  $t = 'confirm.html';
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'action' => $action,
			       'job_id' => $job_id,
			       'stationery' => $stationery_title,
			       'quantities' => $quantities,
			       'default_address' => $default_address,
			       'pick_up_comment' =>  $pick_up_comment
			       ));
    return $output;
  }
/* takes a job id and returns the base template name string*/
private function getTemplateNameFromJob($job_id) {
  $template_name = "None";
  try {
    $stmt = $this->conn->prepare($this->select[7]);
    $stmt->execute(array('job_id' => $job_id));
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $template_name = $row["full_name"];
    }
  }
  catch (Exception $e){
    $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
  }
  return $template_name;
}
/* gets category_id from job_id, or 0 if none */
private function getCategoryFromJob($job_id) {
  $category_id=0;
   $statement = $this->select[7];
   $statement2 = str_replace("t.full_name", "t.category_id", $statement);

  try {
    $stmt = $this->conn->prepare($statement2);
    $stmt->execute(array('job_id' => $job_id));
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $category_id = $row["category_id"];
    }
  }
  catch (Exception $e){
    $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
  }
  return $category_id;
}
/* this has become a wrapper function */
private function getPricelistFromJob($job_id) {
  $category_id=0;
  $category_id = $this->getCategoryFromJob($job_id);
  
  $pricelist = array();

  $pricelist = $this->getPricelistFromCategory($category_id);
  return $pricelist;
}
private function getPricelistFromCategory($category_id) {
  $pricelist = array();
  $statement3 = $this->select[8];
  try {
    $stmt2 = $this->conn->prepare($statement3);
    $stmt2->execute(array('category_id' => $category_id));
    while($row = $stmt2->fetch(PDO::FETCH_OBJ)) {
      array_push($pricelist, $row);
    }
  }
 
  catch (Exception $e){
    $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
  }
  return $pricelist;
}
/* Takes an array of address details
("addressee", "location", "street_number", "street", "town", "postcode")
[and optional "country_code"]
and adds an address to the database. Returns the (integer) address_id if successful;
false otherwise
*/
private function addAddress($address_details) {
  //$address_id = false;
  $address_id = -1;
try {
      $stmt = $this->conn->prepare($this->insert[3]);
      $stmt->execute(array(
			   "addressee" => $address_details["addressee"],
			   "location" => $address_details["location"],
			   "street_number" => $address_details["street_number"],
			   "street" => $address_details["street"],
			   "town" => $address_details["town"],
			   "postcode" => $address_details["postcode"]
			   ));
}
    catch (Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      $address_id = false;
    }
    /* get address id for address just created */
    if ($address_id !== false) {
      $address_id = $this->conn->lastInsertId();
    }
    return $address_id;  
}
/* similar to addAddress above but more generic
 * thing is an entity name, eg. template, department
 * thing_details is an array of (property_name => property_value)
 * for the thing
 * returns the id of the new thing
 */
private function addThing($thing, $thing_details) {
  $thing_id = -1;
  $statement = $this->insert[4];
  $column_names = array_keys($thing_details);
  $statement = str_replace('xxx', implode(', ', $column_names), $statement);
  $statement = str_replace('yyy', ':yyy', $statement);
  $statement = str_replace('yyy', implode(', :', $column_names), $statement);
  $statement = str_replace(':entity', $thing, $statement);
try {
      $stmt = $this->conn->prepare($statement);
      $stmt->execute($thing_details);
}
    catch (Exception $e) {
      $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
      $thing_id = false;
    }
    /* get address id for address just created */
    if ($thing_id !== false) {
      $thing_id = $this->conn->lastInsertId();
    }

    return $thing_id;  
}

/* general updater
 * $thing is the entity (a string, lowercase)
 * $thing_id is an integer,
 * or an array of integers (for IN clause)
 * or an associative array of identifying columns:
 * column=>values which together define a unique table entry
 * for those tables with no primary key.
 * $thing_details is an array of column=>value
 */
private function updateThing($thing, $thing_id, $thing_details) {
  //print_r($thing_details);
  //print_r($thing_id);
  $returnid = -1;
  /* get settext */
   $settings = array();
    $statement = "";
    $conditions = array();
    $nullparams = array();
    foreach ($thing_details as $key => $value) {
      if (is_string($value))
	{
	  $value = trim($value);
	  if (strlen($value) == 0)
	    {
            // empty strings become NULL
          $nullparams[] = $key;
	    }
	}
      $lcasekey = $key; // don't need lower case
      $settings[] = $lcasekey . " = " . ":" . $lcasekey;
    }
    // change appropriate values in thing_details to null
    foreach ($nullparams as $nullkey) {
        $thing_details[$nullkey] = null;
    }
    $settext = implode(", ", $settings);
    //print_r("settext: $settext\n");
    $conditions = array();
    if (is_array($thing_id) && $this->is_assoc($thing_id)) {
	/* no primary key, build $keytext from columns */
	foreach ($thing_id as $column_name => $value) {
	  $conditions[] = $column_name . " = " . $value;
	  }
	//print_r($conditions);
	$keytext = implode(" AND ", $conditions);
	$returnid = $thing_details;
    }
    else {
      $primary_key = strtolower($thing) . '_' . 'id';
      $conditions[$primary_key] = $thing_id;
      $keytext = $this->makeConstraintSQL($conditions);
      $returnid = $thing_id;
    }

    //$keytext = $primary_key . " = :" . $primary_key;
    $statement = "update $thing set " . $settext . " where " . $keytext;

    //print_r($statement);
    //print_r($thing_details);
    try {
      $stmt = $this->conn->prepare($statement);
      $stmt->execute($thing_details);
 
     }
    catch(Exception $e) {
      $this->error .= '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    }
    return $returnid;
}
/* deletes from database
 * $thing is the entity name 
 * $id can be an integer or array of integers,
 * representing an IN clause 
 * or an array of arrays,
 * each representing a record,
 * if there is no primary key to be deleted
 */
protected function deleteThings($thing, $id) {

  $conditions = $id;
  $keytext = $this->makeConstraintSQL($conditions);
  $statement = "delete from $thing where " . $keytext;
  //print_r($statement);
  try {
    $stmt = $this->conn->prepare($statement);
    $stmt->execute();
  }
  catch (Exception $e) {
    $this->error .= '<pre>ERROR: ' . $statement . ': ' . $e->getMessage() . '</pre>';
  }
}

/* helper function to determine if an array is associative
 * from Captain kurO, http://stackoverflow.com/questions/173400/php-arrays-a-good-way-to-check-if-an-array-is-associative-or-sequential
 * assumes:
 * 
 * is_array($array) == true
 * If there is at least one string key, 
 * $array will be regarded as associative array
 * so it just checks for string keys, not true check for associative,
 * but enough for this program
 */
private function is_assoc($array) {
  return (bool)count(array_filter(array_keys($array), 'is_string'));
}
/* currently a mammoth function, well worthy of refactoring (esp. those marked X):
 * 1. updates job with address from confirm screen
 * 2. generates a print pdf in CHILI X
 * 3. gets its url (CHILI) X
 * 4. copies url to output folder with curl X
 * 5. creates a text file for the job
 * 6. zips text file and pdf X
 * 7. mails text file info to recipient
 * 8. mails zip file url to admin
 * 9. shows text file info on screen
 */
function showFinal() {
  /*
   * add address to address table
   */
  $error = "";

   $instructions = '';
   $stationery_title = '';
  if(isset($_REQUEST["comments"])) {
    $instructions = $_REQUEST["comments"];
  }
  if(isset($_REQUEST["stationerytitle"])) {
      $stationery_title = str_replace('"', '', $_REQUEST["stationerytitle"]);
  }
  if (isset($_REQUEST["collect"]) and $_REQUEST["collect"] == 'yes') {
    $address_id = 1;
    $default_address_list = $this->getListFromDB('address', 
					       array('address_id' => $address_id));
    if (!empty($default_address_list)){
      $default_address = $default_address_list[0];
      $address_info = array(
			    "addressee" => $default_address->addressee,
			    "location" => $default_address->location,
			    "street_number" => $default_address->street_number,
			    "street" => $default_address->street,
			    "town" => $default_address->town,
			    "postcode" => $default_address->postcode
			    );
    }
  }
  else {
    $address_info = array(
			"addressee" => $_REQUEST["addressee"],
			"location" => $_REQUEST["location"],
			"street_number" => $_REQUEST["number"],
			"street" => $_REQUEST["street"],
			"town" => $_REQUEST["town"],
			"postcode" => $_REQUEST["postcode"]
			); 
    $address_id = $this->addAddress($address_info);
  }
  /*
 *** update job entity with address and other details
 Array
 (
 [mode] => thanks
 [quantity] => 3000@620.00 Job
 [themis] => 1212122 Job
 [addressee] => 1212 Address
 [location] => Address
 [number] => Address
 [street] => Address
 [town] => 1212 Address
 [postcode] => 1212 Address
 [comments] => Job
 [job] => 34 Job
 [submitted] => Confirm details and PRINT
 )
  */

  if(isset($_REQUEST['job'])) {
    $job_id = $_REQUEST['job'];
  }
  else {
    /* no job, no confirmation! */
    return $this->selectTemplate();
  }
  /*if (!$address_id) {
    /* need a delivery address 
    return $this->showConfirmation();
    }*/
  $quantity = 0;
  if(isset($_REQUEST['quantity'])) {
    $quantityprice = $_REQUEST['quantity'];
    $quantity = substr($_REQUEST["quantity"], 0, strpos($quantityprice, '@'));
    $price = substr($_REQUEST['quantity'], strpos($quantityprice, '@')+1);
  }
 


  $today = date('Y-m-d H:i:s');
  $var_array = array(
		     'quantity' => $quantity,
		     'themis_code' => $_REQUEST["themis"],
		     'instructions' => $instructions,
		     'address_id' => $address_id,
		     'ordered' => $today
		     );
  $this->updateJob($job_id, $var_array);
  /*
 *** generate print pdf
 Like proof only print
  */

  /* get settingsXML for PDF settings resource PROOF */
  /* ResourceItemGetDefinitionXML used in API sample:
   * public string ResourceItemGetDefinitionXML ( string apiKey, string resourceName, string itemID );
   /* public string ResourceItemGetXML ( string apiKey, string resourceName, string itemID ); */
  $pdf_resource_params = array(
			       "apiKey" => $this->apikey,
			       "resourceName" => "PDFExportSettings",
			       "itemID" => CHILI_PRINT
			       );
  $settingsXML = $this->client->ResourceItemGetDefinitionXML($pdf_resource_params);

  /*generate pdf with api */
  /* public string DocumentCreatePDF ( string apiKey, string itemID, string settingsXML, int taskPriority ); */
  $chili_id = $this->getChiliId($job_id);
  $soap_params = array(
		       "apiKey" => $this->apikey,
		       "itemID" => $chili_id,
		       "settingsXML" => $settingsXML->ResourceItemGetDefinitionXMLResult,
		       "taskPriority" => 4
		       );
  $taskXML = $this->client->DocumentCreatePDF($soap_params);
  /*
 *** generate text file
 probably in YAML (see php yaml_emit_file)
 **** details
 + print pdf cross-reference
 + quantity
 + cost_price
 + handling_cost
 + price 
 + delivery address
 - addressee
 - location
 - street_number . street
 - town
 - postcode
 + date generated 
 + THEMIS code
 + comments 
  */
  $job_name = $this->getTemplateName($job_id);
  $stationery_type = $this->getTemplateNameFromJob($job_id);
  /* db query needed here */
  $textfilename = FILESTORE . $job_name . ".txt";
  $pdffilename = FILESTORE . $job_name . ".pdf";
  $zipfilename = FILESTORE . $job_name . ".zip";
  $csvfilename = FILESTORE . $job_name . ".csv";
  /* get task id for the pdf creation*/
  $dom = new DOMDocument();
  $dom->loadXML($taskXML->DocumentCreatePDFResult);
  $task_id = $dom->getElementsByTagName("task")->item(0)->getAttribute("id");

  // check task status until task is finished, then get URL
  $task_params = array(
		       "apiKey" => $this->apikey,
		       "taskID" => $task_id
		       );
  $status = "";
  try{
    do {
      $task_statusXML =  $this->client->TaskGetStatus($task_params);
      $dom = new DOMDocument();
      $dom->loadXML($task_statusXML->TaskGetStatusResult);
      $status = $dom->getElementsByTagName("task")->item(0)->getAttribute("finished");
    } while ($status != "True");
    $result = $dom->getElementsByTagName("task")->item(0)->getAttribute("result");
    $dom2 = new DOMDocument();
    $dom2->loadXML($result);
    $relativeURL = $dom2->getElementsByTagName("result")->item(0)->getAttribute("relativeURL");
    $pdfurl = CHILI_APP . $relativeURL; 
  }
  catch (Exception $e) {
    $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
    $pdfurl = "";
  }
  /* get base_price and handling fee for printers only
   */
  $category_id = $this->getCategoryFromJob($job_id);
  $full_price_list = $this->getListFromDB(
					  'template_price', 
					  array('category_id' => $category_id,
						'quantity' => $quantity));
  if (! empty($full_price_list)) {
    $full_price = $full_price_list[0];
    $handling_cost = number_format($full_price->handling_cost, 2);
    $cost_price = number_format($full_price->price_AUD, 2);
  }
  else {
    $handling_cost = '';
    $cost_price = '';
  }
  $ordernumber = substr($job_name, 0, strpos($job_name, '-'));
  $yaml_array =  array(
      'order number' => $ordernumber,
      'url' => $pdfurl,
      'quantity' => $quantity,
      /*'base cost' => $cost_price,
        'handling cost' => $handling_cost,*/
      'sell price' => $price,
      'date ordered' => $today,
      'THEMIS code' => $_REQUEST["themis"],
      'comments' => $instructions,
      'job information' => $job_name . "-print.pdf"
  );
    
  $file = fopen($textfilename,'w');
  if ($file === FALSE) {
    $this->error = "Can’t open file! " . $textfilename;
  }
  foreach($yaml_array as $key=>$value){
    fwrite($file, $key . ": " . $value . PHP_EOL);
  }
  fwrite($file, "DELIVERY ADDRESS" . PHP_EOL);
  foreach($address_info as $key=>$value) {
    fwrite($file, $key . ": " . $value . PHP_EOL);
  }
  fclose($file);
  /* generate csv file */
  $userprofile = $this->getProfile($_SESSION["username"]);
  $csv_headers = array(
      'order number',
      'date ordered',
      'item',
      'quantity',
      'name or title',
      'order by',
      'order by email',
      'themis code',
      'themis approver',
      'sell price',
      'comments',
      'adressee',
      'location',
      'street number',
      'street',
      'town',
      'postcode'
  );
  $csv_data = array(
      $ordernumber,
      $today,
      $stationery_type,
      $quantity,
      $stationery_title,
      $userprofile->given_name . " " . $userprofile->family_name,
      $userprofile->email,
      $_REQUEST["themis"],
      $_REQUEST["approver"],
      $price,
      $instructions 
  );
  foreach($address_info as $key=>$value) {
      $csv_data[] = $value;
  }
  $csvunified = array($csv_headers, $csv_data);
  if (count($csv_headers) == count($csv_data)) {
      $file = fopen($csvfilename,'w');
      if ($file === FALSE) {
          $this->error = "Can’t open file! " . $csvfilename;
      }
      else {
          foreach ($csvunified as $fields) {
              fputcsv($file, $fields);
          }
          fclose($file);
      }
  }
  
  /* copy the pdf to the output folder */
  $ch = curl_init();
  $timeout = 0;
  curl_setopt ($ch, CURLOPT_URL, $pdfurl);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

  // Getting binary data
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);

  $pdf = curl_exec($ch);
  curl_close($ch);
  file_put_contents( $pdffilename, $pdf );
  /*
 *** zip text, csv and print pdf
 remove the original files, keep only the zip if possible
  */
  $zip = new ZipArchive;
  $res = $zip->open($zipfilename, ZipArchive::CREATE);
  if ($res === TRUE){
    try {
        //$zip->addFile($textfilename, str_replace(FILESTORE, '', $textfilename));
      $zip->addFile($csvfilename, str_replace(FILESTORE, '', $csvfilename));
      $zip->addFile($pdffilename, str_replace(FILESTORE, '', $pdffilename));
      $zip->close();
      /* finally, remove the textfile and pdffile */
      unlink($textfilename);
      unlink($pdffilename);
      unlink($csvfilename);
    } catch(Exception $e) {
      $this->error .= 'zip creation failed because of' . $e->getMessage();
    }
  }
  
  
  
  /* email client and admin with details about order
   * send admin url of zip file in output folder;
   * send client details of order*/
   $recipient = $userprofile->email;
   $subject = "University stationery order $ordernumber";
   $zipurl = FILEURL . $job_name . '.zip';
   $email_array = array(
			'address_details' => $address_info,
			'userprofile' => $userprofile,
			'stationery_type' => $stationery_type,
			'quantity' => $quantity,
			'price' => $price,
			'order_date' => $today,
			'ordernumber' => $ordernumber,
			'zipurl' => $zipurl,
			'comments' => $instructions,
            'themis' => $_REQUEST["themis"]
			);
   $t2 = 'email.txt';
   $t2 = $this->twig->loadTemplate($t2);
   $message_text = $t2->render($email_array);
   $message_text = wordwrap($message_text, 70);
   $t3 = 'admin_email.html';
   $t3 = $this->twig->loadTemplate($t3);
   $message_text2 = $t3->render($email_array);
   $headers = $this->email_headers(array());
   $headers2 = $this->email_headers(
				    array(
					  "MIME-Version" => "1.0",
					  "Content-type" => "text/html; charset=utf-8"
					  )
				    );
   $emailsuccess = mail($recipient, $subject, $message_text, $headers);
   $emailsuccess2 = mail(ADMIN_EMAIL, $subject, $message_text2, $headers2);
   if(!$emailsuccess2) {
     $this->error .="<pre>admin email failed</pre>";
   }
   /* screen output, after all that */
 
   $t = 'final.html';
   $t = $this->twig->loadTemplate($t);
   $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error,
			     'address_details' => $address_info,
			     'userprofile' => $userprofile,
			     'stationery_type' => $stationery_type,
			     'quantity' => $quantity,
			     'price' => $price,
			     'order_date' => $today,
			     'ordernumber' => $ordernumber
			     ));
  return $output;
}
/* returns formatted email headers
 * $extra_headers_array are additional mail headers, eg.
 * "cc" => "bob.sackamento@bob.com"
 */
private function email_headers($extra_headers_array) {
  $header_array = array('From' => ADMIN_EMAIL);
  if (is_array($extra_headers_array)) {
    foreach($extra_headers_array as $label=>$value) {
      $header_array[$label] = $value;
    }
  }

  $expanded_headers = array();

  foreach($header_array as $label=>$value)
    {
      $expanded_headers[] = $label . ': ' . $value;
    }
  $headers = implode("\r\n", $expanded_headers);
  return $headers;
}
/* Admin functions */
/* $table is the name of the table
 * $conditions is an array of context-specific WHERE clause conditions eg.
 * ('id' => 1, username = 'godzilla') or
 * ('id' => array(1,2,3,4)) for a WHERE...IN clause
 * $ordering a string or array of column names for ORDER BY...
 * returns an array of objects
 */
private function getListFromDB($table, $conditions = null, $ordering = null) {
  $entity_list = array();
  $sql = "SELECT * from $table";
  if(is_array($conditions) and !empty($conditions)){
    $sql .= ' WHERE ' . $this->makeConstraintSQL($conditions);
  }
  if (!is_null($ordering)){
    $sql .= ' ' . $this->makeOrderingSQL($ordering);
  }
  //print_r($sql);
  try {

    $stmt = $this->conn->prepare($sql);
    $stmt->execute(array());
    while($row = $stmt->fetch(PDO::FETCH_OBJ)) {
      array_push($entity_list, $row);
    }
  }
  catch(Exception $e) {
    $this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';
  }
  return $entity_list;
}
/* $conditions is an array (column=>value)
 * if value is an array it is made into an IN clause
 * if $conditions is an array of arrays
 * then each sub-array is evaluated **recursively** 
 * as an OR clause
 */
private function makeConstraintSQL($conditions) {
  $keytext = "";
  $all_keytext = array();
  $key_conditions = array();
  if (is_array($conditions)) {
    if ($this->is_assoc($conditions)){
      foreach ($conditions as $column_name => $value) {
	$final_value = "";
	$operator = ' = ';
	if (is_array($value)) {
	  $operator = ' IN ';
	  $final_value .= '(';
	  foreach ($value as $item)
	    {
	      if (is_string($item)) {
		$item = $this->conn->quote($item);
	      }
	      $final_value .= $item . ', ';
	    }
	  $final_value = rtrim($final_value, ', ');
	  $final_value .= ')';
	}
	else {
	  if (is_string($value)) {
	    $value = $this->conn->quote($value);
	  }
	  $final_value = $value;
	}
	$key_conditions[] = strtolower($column_name) . $operator . $final_value;
      }
    }
    else {
      foreach ($conditions as $cond) {
	if (is_array($cond)) {
	  /* create OR clauses for multiple 
	   * arrays of column=>value */
	  $all_keytext[] = $this->makeConstraintSQL($cond);
	}
      }
    }
    
  }
  $keytext = implode(" AND ", $key_conditions);
  if(count($all_keytext) > 0) {
    $keytext = implode(" OR ", $all_keytext);
  }
  return $keytext;
}
/**
 * adds an ordering clause to a SQL statement
 * @param array(string) or simple string $ordering, a list of columns to order the query by
 * For convenience, a single term need not be enclosed in an array
 * @return string
 */
private function makeOrderingSQL($order_by) {
  $all_columns = "";
  if (is_array($order_by))
    {
      foreach ($order_by as $column_name)
	{
	  $all_columns .= $column_name . ', ';
	}
      $all_columns = rtrim($all_columns, ', ');
    }
  else
    {
      $all_columns = $order_by;
    }
  return 'ORDER BY ' . $all_columns;
}
/* a generic overview of some entity (default = template) */
function modifyTemplate() {
  if (!$this->isAdmin()) {
    return $this->showStart();
  }
  $entity = 'Template';
  if (isset($_REQUEST['entity'])) {
    $entity = strtolower($_REQUEST['entity']);
    
  }
  $plural = $this->pluralise($entity);

  if (isset($_REQUEST['parent_entity']) && isset($_REQUEST['parent_id'])){
    $parent_entity = $_REQUEST['parent_entity'];
    $parent_id = $_REQUEST['parent_id'];
    $conditions = array(strtolower($parent_entity) . '_id' => $parent_id);
    $edit_addition = 'parent_entity='.$parent_entity.'&parent_id='.$parent_id;
    $add_addition = '&' . $edit_addition;
  }
  else {
    $conditions = null;
    $edit_addition = "id=";
    $add_addition = "";
  }

  try {
    $template_list = $this->getListFromDB(strtolower($entity . '_view'), $conditions, null);
    /* make sure unimelb templates are visible in view */
    $properties = array();
    if(count($template_list) > 0 ){
      $properties1 = array_keys(get_object_vars($template_list[0]));
      $properties = str_replace('_', ' ', $properties1);
      
      
    }
  }
  catch(Exception $e) {
    return $this->handle_errors($e);
  }
  $editurl = $this->action . "?mode=update_item&entity=$entity&" . $edit_addition ;
  $deleteurl = $this->action . "?mode=delete&entity=$entity" . $add_addition;
  $addurl = $this->action . "?mode=add_item&entity=$entity" . $add_addition;
  /* screen output*/
  $t = 'admin-list.html';
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error,
			     'entity' => $entity,
			     'properties' => $properties,
			     'columns' =>$properties1,
			     'item_list' => $template_list,
			     'addurl' => $addurl,
			     'editurl' => $editurl,
			     'action' => $deleteurl,
			     'plural' => $plural
			     ));
  return $output;
}
/* shows the administrator guide */
function showGuide() {
  if (!$this->isAdmin()) {
    return $this->showStart();
  }
  /* screen output*/
  $t = 'admin-guide.html';
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error
			     ));
  return $output;
}

function modifyDepartment() {
  $_REQUEST['entity'] = 'Department';
  return $this->modifyTemplate();
}
function modifyCategory() {
   $_REQUEST['entity'] = 'Category';
   return $this->modifyTemplate();
}
function modifyPrinter() {
   $_REQUEST['entity'] = 'Printer';
   return $this->modifyTemplate();
}
private function getAdminUsernames() {
  $admin_usernames = array();
  $admin_usernames_list = $this->getListFromDB('user_group', array('group_id' => 1));
  $admin_usernames = array();
  foreach ($admin_usernames_list as $record) {
    $admin_usernames[] = $record->username;
  }
  return $admin_usernames;
}
function modifyAdmin() {
  if (!$this->isAdmin()) {
    return $this->showStart();
  }
  /* similar to modifyTemplate but
   * - no Edit column
   * - action is slightly different
   * don't want to add users, just add or subtract membership from group 1
   * (administrators)
   * two lists:
   * 1. current administrators check box is admin (checked)
   * 2. other users, check box to make them administrators (unchecked by default)
   */
  /* screen output*/
  $entity = 'administrator';
  $admin_usernames = $this->getAdminUsernames();
  //print_r($_REQUEST);
  if(isset($_REQUEST['submitted'])) {
    /* get the list of checked items 
    * add usernames in the list to admin
    * remove usernames in the existing admin list but NOT in the request list
    * admin must have at least one member at the end otherwise error
    * see also update profile for a similar function
    */
 
    $new_names = array();
    $pattern = 'markadministrator_';
    $offset = strlen($pattern);
    foreach($_REQUEST as $req=>$value) {
      $needle = strpos($req, $pattern);
      if ($needle !== false) {
	$username = substr($req, $needle + $offset);
	$new_names[] = $username;
      }
    }
    $common_names = array_intersect($admin_usernames, $new_names);
    $to_add = array_diff($new_names, $common_names, $admin_usernames );
    $to_remove = array_diff($admin_usernames, $common_names, $new_names);
    $admin_count = count($admin_usernames) + count($to_add) - count($to_remove);
    if ($admin_count > 0) {
    /* add $to_add */
      foreach($to_add as $adduser) {
	$this->addThing('user_group', array('username' => $adduser, 'group_id' => 1)); 
      }
    /* remove $to_remove */
      foreach($to_remove as $removeuser) {
	$this->deleteThings('user_group', array('username' => $removeuser));
      }
      /* refresh username list for display */
      $admin_usernames = $this->getAdminUsernames();
    }
    else {
      $this->error .= '<p>Not updated: there must be at least one administrator.</p>';
    }
  }

  

  $all_users = $this->getListFromDB('user', null, array('family_name', 'given_name'));
  $admin_users = array();
  $non_admins = array();
  foreach($all_users as $user) {
    if (in_array($user->username, $admin_usernames)) {
      $admin_users[] = $user;
    }
    else {
      $non_admins[] = $user;
    }
  }

  $plural = $this->pluralise($entity);
  $action = $this->action . "?mode=administrator_admin";
  $t = 'administrator.html'; //maybe some changes for this one
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error,
			     'entity' => $entity,
			     'admin_users' => $admin_users,
			     'non_admins' => $non_admins,
			     'plural' => $plural,
			     'action' => $action
			     ));
  return $output;

}

/* give the user confirmation before deletion 
 * needs: 
 * the Entity type to delete, (also gives where to return to on submit or cancel)
 * the id number of the thing(s) to delete
 * submit (deletes) or cancel (return to origin)
 */
function confirmDelete() {
  //print_r($_REQUEST);
  $action = $this->action;
  $entity = strtolower($_REQUEST['entity']);
  $to_delete = array();
  $needle = 'markdelete' . ($entity);
  $needle2 = '---'; 
 foreach($_REQUEST as $key => $value) {
    if(strpos($key, $needle) !== false) {
      if(strpos($value, $needle2) !== false) {
	$id_properties = explode($needle2, $value);
	//print_r ($id_properties);
	$final_value = array();
	foreach($id_properties as $prop) {
	  //print_r('\n' . $prop);
	  /* convert 'id[x]=y'
	   * to x => y */
	  $first = strpos($prop, ':');
	  $firstly = substr($prop, 0, $first);
	  $secondly = substr($prop, $first +1);
	  $final_value[$firstly] = $secondly;
	} 
	$value = $final_value;
      }
      $to_delete[] = $value;
    }
  }
 //print_r($to_delete);

  if (isset($to_delete[0])) {
    if(is_array($to_delete[0])){
      /* no primary key */
      $conditions = $to_delete;
      $delete_conditions = $to_delete;
    }
    else {
      /* primary key, an integer */
      $conditions = array('id' => $to_delete);
      $delete_conditions = array($entity . '_id' => $to_delete);
    }
  }
  else {
    $conditions = array('id' => -1);
    $delete_conditions = array($entity . '_id' => -1);
  }
  //print_r($conditions);
  //print_r($delete_conditions);
  if(isset($_REQUEST['submitted_confirm'])) {
    /* delete listed things */
    $this->deleteThings($entity, $delete_conditions);
    //return $this->modifyTemplate();
  }
  $returnurl = $this->action . '?mode=' . $entity .'_admin';
  $confirmurl = $this->action . '?mode=delete&entity=' . $entity;
if (isset($_REQUEST['parent_entity']) && isset($_REQUEST['parent_id'])){
    $parent_entity = $_REQUEST['parent_entity'];
    $parent_id = $_REQUEST['parent_id'];
    $conditions = array(strtolower($parent_entity) . '_id' => $parent_id);
    $extra = '&parent_entity='.$parent_entity.'&parent_id='.$parent_id;
    $returnurl .= '&entity='. $entity . $extra;
    $confirmurl .= $extra;
}
else {
  $parent_entity = null;
  $parent_id = null;
}

  $item_list = $this->getListFromDB(strtolower($entity . '_view'), $conditions, null);
  /* make sure unimelb templates are visible in view */
  $properties = array();
  if(count($item_list) > 0 ){
    $properties1 = array_keys(get_object_vars($item_list[0]));
    $properties = str_replace('_', ' ', $properties1);
  }
  /* screen output*/
  $t = 'admin-delete.html';
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error,
			     'entity' => $entity,
			     'action' => $confirmurl,
			     'returnurl' => $returnurl,
			     'properties' => $properties,
			     'item_list' => $item_list,
			     'id_list' => $to_delete,
			     'parent_entity' => $parent_entity,
			     'parent_id' => $parent_id
			     ));
  return $output; 

}
/* a generic function to add or edit entity details
 * gets all fields from the specified Entity
 * when submitted, adds the Entity, returns to the appropriate list page
 */
function addItem() {
  parse_str($_SERVER['QUERY_STRING'], $query);
  //print_r($query);
  //print_r($_REQUEST);
  if (isset($_REQUEST['entity'])) {
    $entity = strtolower($_REQUEST['entity']);
  }
  else {
    return $this->showStart();
  }
  if (isset($query['parent_entity']) && isset($query['parent_id'])){
    $parent_entity = $query['parent_entity'];
    $parent_id = $query['parent_id'];
    $conditions = array($entity . '_' . strtolower($parent_entity) . '_id' => $parent_id);
    //print_r($conditions);
    $edit_addition = 'parent_entity='.$parent_entity.'&parent_id='.$parent_id;
    $return_addition = '&entity='. $entity . '&' . $edit_addition;
  }
  else {
    //print_r("parent entity and id not set");
    $return_addition = "";
    $conditions = array();
  }
  $destination='add_item';
  if (isset($_REQUEST["submitted"])) {
    $this->error = "<pre>submitted</pre>";
    /* create the information from the form into the database
     * return editItem screen with the new object on success
     * or blank addItem screen with message on failure
     * plus the message '(entity) created successfully'
     * (entity)_column_1=xyz
     */
    $insert_values = array();
    $entity_prefix = $entity . '_';
     foreach($_REQUEST as $key=>$value){
      $hyphen = strpos($key, $entity_prefix);
      if ($hyphen !== false) {
	$column = substr($key, $hyphen + strlen($entity_prefix));
	$insert_values[$column] = $value;
      }
     }
     $insert_values = array_merge($insert_values, $conditions);
     //print_r($insert_values);
    $id = $this->addThing($entity, $insert_values);
    //print_r($id);
    if (! is_numeric($id)) {
      $this->error .="<pre>There was a problem with addThing</pre>";
    }
    else {
      if ((int)$id > 0){
	$_REQUEST['id'] = $id;
      }
      else {
	$_REQUEST['id'] = $insert_values;
      }
      $_REQUEST['mode'] = 'update_item';
      return $this->updateItem();
    }
  }
    $returnurl = $this->action . '?mode=' . $entity .'_admin' . $return_addition;
    $action = $this->action . '?mode=' . $destination;
    $properties = $this->getPropertyList($entity);
    foreach($properties as $property) {
      if (is_array($property)){
	$subtype1 = array_keys($property);
	$subtype = $subtype1[0];
	$working_array = $property[$subtype];
	foreach($working_array as $subthing) {
	  $subthing->id = reset($subthing);
	  $subthing->description = next($subthing);
	}
      }
    }
    /* screen output*/
    $t = 'admin-add.html';
    $t = $this->twig->loadTemplate($t);
    $output = $t->render(array(
			       'modes' => $this->user_visible_modes,
			       'error' => $this->error,
			       'entity' => $entity,
			       'properties' => $properties,
			       'returnurl' => $returnurl,
			       'action' => $action,
			       'parent_entity' => $parent_entity,
			       'parent_id' => $parent_id
			       ));
    return $output;
  }
/* an extremely lightweight and fragile pluralise function,
 * suitable only for entity names at this stage
 * $singular is the thing to pluralise
 */
private function pluralise($singular) {

  $last_letter = strtolower($singular[strlen($singular)-1]);
  switch($last_letter) {
  case 'y':
    return substr($singular,0,-1).'ies';
  case 's':
    return $singular.'es';
  default:
    return $singular.'s';
  }

}
/* Takes an entity name and returns a list of properties for that database
 * calls describe :entity;
 * adds field name to array: 
 * if primary key (Key = 'PRI'), changes name to id
 * if foreign key, adds a new property list to the array, 
 * entity based on the field name
 * returns the array
 * which will have the following example structure:
 * id, field1, field2, (field3 => (id, field1a, field2a))
 */
private function getPropertyList($entity) {
  $property_list = array();
  $item_fields = array();

  try {
  $item_fields = $this->getListFromDB('information_schema.columns', array('table_name' => $entity ), null);
  /*$statement = "select column_name, column_key from information_schema.columns where table_name = ':entity'"; */
  /* find fields */
  /* Key = column_key; Field = column_name in DESCRIBE entity equivalent*/
    foreach ($item_fields as $field) {
      if ($field->COLUMN_KEY == 'PRI') {
	$property_list[] = 'id';
      }
      else if ($field->COLUMN_KEY == 'MUL') {
	$id_field = $field->COLUMN_NAME;
	$subentity = str_replace('_id', '', $id_field);
	/* get all subentities */

	$subentity_list = $this->getListFromDB($subentity);
	$property_list[] = array($subentity => $subentity_list);
      }
      else {
	$property_list[] = $field->COLUMN_NAME;
      }
    }
  }
  catch(Exception $e){
    return $this->handle_errors($e);
    /*$this->error = '<pre>ERROR: ' . $e->getMessage() . '</pre>';*/
  }

  return $property_list;
}

/* a generic function to edit entity details
 * gets all fields from the specified Entity
 * filled in with values if a id number specified ie EDIT)
 * when submitted, updates the Entity
 */
function updateItem() {
  //print_r($query);*/
  //print_r($_REQUEST);
  if (isset($_REQUEST['entity'])) {
    $entity = strtolower($_REQUEST['entity']);
  }
  else {
    return $this->showStart();
  }
  if (isset($_REQUEST['id'])) {
    $id = $_REQUEST['id'];
    /* if submitted, update the details
     * get entity details by id 
     * print the details
     */
    if (isset($_REQUEST["submitted"])) {
      $this->error .= "<pre>submitted</pre>";
      /*
       * (entity)_column_1=xyz
       */
      $insert_values = array();
      $remove_entity = $entity . '_';
      foreach($_REQUEST as $key=>$value){
	$hyphen = strpos($key, $remove_entity);
	if ($hyphen !== false) {
	  $column = substr($key, $hyphen + strlen($remove_entity));
	  $insert_values[$column] = $value;
	}
      }
      $id = $this->updateThing($entity, $id, $insert_values);
      //$_REQUEST['parent_id'] = $parent_id;
      //$_REQUEST['parent_entity'] = $parent_entity;
    }
    if (!is_array($id)) {
      $id_array = array($entity . '_id' => $id);
    }
    else {
      $id_array = $id;
    }
    $itemlist = $this->getListFromDB($entity, $id_array);
    $item_vars = array();
    if (isset($itemlist) and count($itemlist) > 0) {
      $item = $itemlist[0];
      $item_props = get_object_vars($item);
      $item_vars = array_values($item_props);
    }


    
  }
  
  else {
    $_REQUEST['entity'] = $entity;
    return $this->modifyTemplate();
  }
  $destination='update_item';
  $special = new StdClass();
  $special->active = false;
  /* would be nice to have a more generic solution here*/
  if (strtolower($entity) == 'category') {
    $special->active = true;
    $special->entity = 'template_price';
    $special->destination = 'template_admin';
    $special->action = $this->action . '?mode=' . $special->destination . '&entity=' . $special->entity . '&parent_entity=' . $entity . '&parent_id='. $id;
    }
  $returnurl = $this->action . '?mode=' . $entity .'_admin';
  if (isset($_REQUEST['parent_entity']) && isset($_REQUEST['parent_id'])){
    $parent_entity = $_REQUEST['parent_entity'];
    $parent_id = $_REQUEST['parent_id'];
    $conditions = array(strtolower($parent_entity) . '_id' => $parent_id);
    $returnurl .= '&entity='. $entity .'&parent_entity='.$parent_entity.'&parent_id='.$parent_id;
    
}
//&id='. $id;
$add_id = http_build_query(array('id' => $id));
			   $action = $this->action . '?mode=' . $destination . '&entity=' . $entity .'&'. $add_id;
  $properties = $this->getPropertyList($entity);
  foreach($properties as $property) {
    if (is_array($property)){
      $subtype1 = array_keys($property);
      $subtype = $subtype1[0];
      $working_array = $property[$subtype];
      foreach($working_array as $subthing) {
	$subthing->id = reset($subthing);
	$subthing->description = next($subthing);
      }
    }
      
  }
  

  /* screen output*/
  $t = 'admin-update.html';
  $t = $this->twig->loadTemplate($t);
  $output = $t->render(array(
			     'modes' => $this->user_visible_modes,
			     'error' => $this->error,
			     'entity' => $entity,
			     'properties' => $properties,
			     'returnurl' => $returnurl,
			     'action' => $action,
			     'item' => $item_vars,
			     'special' => $special,
			     'parent_entity' => $parent_entity,
			     'parent_id' => $parent_id
			     ));
  return $output; 
}

}

?>
