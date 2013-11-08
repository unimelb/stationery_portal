<?php
$start = microtime(true);

// User.class.php contains UserDAO and User classes
require_once("UserDAO.class.php");
require_once("User.class.php");

/*
 * get/display a list of "users"
 * 
 * create table user_info (
 * id int auto_increment not null primary_key,
 * name varchar(64) not null,
 * email varchar(128));
 * 
 * insert into user_info (`id`, `name, `email`) values ('', 'foo', 'foo@here.com');
 * insert into user_info (`id`, `name`, `email`) values ('', 'bar', 'bar@here.com');
 */

define("DBTYPE", "mysql");
define("DBUSER", "daophp5");
define("DBPASS", "daophp5");
define("DBHOST", "localhost");
define("DBNAME", "test");

// create the DSN
$dsn = DBTYPE."://".DBUSER.":".DBPASS."@".DBHOST."/".DBNAME;

// create a new UserDAO (user data access object)
$userDAO = new UserDAO($dsn);

// use the user data access object to retrieve a list (SPL ArrayObject) of users
$userList = $userDAO->getUserList();
$userDAO->close();

if(php_sapi_name() != "cli") {
	header("Content-Type: text/plain");
}

// loop through the list, display the data
foreach($userList as $userObj) {
	echo "ID:   ".$userObj->getID()."\n";
	echo "NAME: ".$userObj->getName()."\n";
	echo "EMAIL ".$userObj->getEmail()."\n";				
	echo "\n";
}

echo "Time: ".(microtime(true) - $start)."\n\n";
?>
