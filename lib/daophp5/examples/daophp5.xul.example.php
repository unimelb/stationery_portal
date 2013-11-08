<?php header( "Content-type: application/vnd.mozilla.xul+xml" );
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
?>
<?xml version="1.0"?>
<?xml-stylesheet href="chrome://global/skin" type="text/css"?>
<window xmlns = "http://www.mozilla.org/keymaster/gatekeeper/there.is.only.xul">
<tree flex="1" enableColumnDrag="true">
	<treecols>
		<treecol id="idCol" label="Id" fixed="true" primary="true"/>
		<splitter class="tree-splitter"/>
		<treecol id="userCol" label="Name" flex="2" />
		<splitter class="tree-splitter"/>
		<treecol id="emailCol" label="Email" flex="2" />
	</treecols>
	<treechildren>
	
		<?php
		// loop through the list, display the data
		foreach($userList as $userObj) {
            print "<treeitem container=\"true\" open=\"true\">\n";
            print "<treerow>\n";
            print "<treecell label=\"".$userObj->getID()."\" />\n";
            print "<treecell label=\"".$userObj->getName()."\" />\n";
            print "<treecell label=\"".$userObj->getEmail()."\" flex=\"1\" />\n";
            print "</treerow>\n";
            print "</treeitem>\n";
		}
		?>
		
	</treechildren>
</tree>
</window>
