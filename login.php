<?php
/**
 * User is usually directed here for login once they've requested a
 * protected page
 * 
 * @package utilities
 * @subpackage authentication
 * @see passport.php
 * @copyright University of Melbourne, 2005-2013
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 */
 
session_name("stationery");
session_start();

require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
Twig_Autoloader::register();
/*prepare Twig environment */
$loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/tpl"); 
$twig = new Twig_Environment($loader, array(
					    "auto_reload" => true
					    
));
/* define variables to be used with the template */
/* Load the actual template here */
$extra_content = "";
$template = $twig->loadTemplate('login.html');
echo $template->render(array(
			     'notice' => ""
));
?>
