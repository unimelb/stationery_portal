<?php
/**
 * User is directed here when they manually log out
 * 
 * @package utilities
 * @subpackage authentication
 * @copyright University of Melbourne, 2010
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */
 
require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
// set up the template
Twig_Autoloader::register();
/*prepare Twig environment */
$loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/tpl"); 
$twig = new Twig_Environment($loader, array(
					    "auto_reload" => true
					    
));
/* define variables to be used with the template */
/* Load the actual template here */
$extra_content = "";
$template = $twig->loadTemplate('logout.html');
echo $template->render(array(
			     'notice' => $extra_content,
));
?>
