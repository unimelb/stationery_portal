<?php
/**
 * Error page for users with wrong credentials
 * 
 * @package utilities
 * @subpackage authentication
 * @copyright University of Melbourne, 2010-2013
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 */
 
require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
Twig_Autoloader::register();
$loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/tpl"); 
$twig = new Twig_Environment($loader, array(
					    "auto_reload" => true
));
// set up the template
$template = $twig->loadTemplate('login.html');
$content = <<<EOHTML
<p class="warning">Your login attempt failed for some reason.</p>
<h3>Please try again</h3>
EOHTML;
echo $template->render(array(
			     'notice' => $content 
			     ));
?>
