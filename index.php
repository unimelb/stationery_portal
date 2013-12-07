<?php
/**
 * The instance script to run a stationery app
 *
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 * @author Patrick Maslen <pmaslen@unimelb.edu.au>
 */
//session_name('stationery');
//session_start();
require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
//include_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/passport.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/cgiapps/stationery.class.php");
// require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/dbconnect.inc.php");
$template_path = $_SERVER["DOCUMENT_ROOT"] . LIBPATH . '/tpl/';
// template_params must at minimum include 'filename' keyword
$title = "University Stationery";
//$title_attributes = "";
$template_params = array(
		'filename' => 'base.html',
	  	'title' => $title,
	  	'extra_style' => array(
			/*array('url' => '/css/qset.css', 'media' => 'screen')*/
			),
	  	'extra_script' => array(
			array(
				)
			)
	);
$params = array(
	'template_path' => $template_path,
	'template_params' => $template_params
	 );
$webapp = new Stationery(array('PARAMS' => $params));
$webapp->run(); 
?>
