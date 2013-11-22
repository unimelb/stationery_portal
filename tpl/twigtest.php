<?php
/* a test script for Giles to run the Twig templates in this folder */
require_once(dirname(__FILE__) . "/../lib/find_path.inc.php");
require_once ($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/addons/Twig/lib/Twig/Autoloader.php");
Twig_Autoloader::register();
/*prepare Twig environment */
$loader = new Twig_Loader_Filesystem($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/tpl"); 
$twig = new Twig_Environment($loader, array(
					    "auto_reload" => true
					    
));
/* define variables to be used with the template */
/* Load the actual template here */

$template = $twig->loadTemplate('base.html');
echo $template->render(array(
));
?>
