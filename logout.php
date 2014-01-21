<?php
/**
 * Ensure the session is destroyed on logout
 * 
 * @package utilities
 * @subpackage authentication
 * @copyright University of Melbourne, 2005
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */
 
session_name("stationery");
session_start();
session_unset();
session_destroy();
require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
header("Location: http://" . $_SERVER["HTTP_HOST"] . LIBPATH . "/loggedout.php");
exit
?>
