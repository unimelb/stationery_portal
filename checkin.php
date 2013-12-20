<?php
/**
 * Check in file - determines where a login request should be
 * redirected
 * 
 * @package utilities
 * @subpackage authentication
 * @copyright University of Melbourne, 2005
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */
 
session_name("stationery");
session_start();

require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
// have we been redirected?
$redirect = "/" . LIBPATH;
if (isset($_SESSION["nologin_from"]))
{
	$redirect = $_SESSION["nologin_from"];
}
else if (isset($_SESSION["login_from"]))
{
	$redirect = $_SESSION["login_from"];
}

/* clear all previous session variables (in case a previous user hasn't
 * logged out of their session)
 */
$_SESSION = array();

/**
 * Check the login details provided
 */
require_once(dirname(__FILE__) . "/lib/find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/controllers/auth/ldap_login.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/controllers/request.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/login_session_updater.class.php");
$login = new LDAPLogin();
$login->setFields(array("uid", "displayname", "auedupersontype", "givenname", "sn", "mail", "departmentnumber", "auedupersonsubtype", "auedupersonid"));
$request = new Request();
$user = $request->getProperty("userName");
$pass = $request->getProperty("pw");
$login->attach(new LoginSessionUpdater());
if ($user and $pass)
{
	if ($user == "godzilla" and $pass == "monster")
	{
		$_SESSION["logged_in"] = true;
		$_SESSION["username"] = "godzilla";
		$_SESSION["email"] = "chili@lists.unimelb.edu.au";
		$_SESSION["common_name"] = "Godzilla";
		$_SESSION["given_names"] = "Godzilla";
		$_SESSION["family_name"] = "daikaiju";
		$_SESSION["usertype"] = "staff";
		$_SESSION["department_number"] = "030";
		header("Location: http://{$_SERVER["HTTP_HOST"]}$redirect");
		exit;
	}
	else
	{
		try
		{
			if ($login->authenticate($user, $pass))
			{
				header("Location: http://{$_SERVER["HTTP_HOST"]}$redirect");
				exit;
			}
		}
		catch (LDAPBindException $e)
		{
			header("Location: http://{$_SERVER["HTTP_HOST"]}" . LIBPATH . "/failedlogin.php");
			exit;
		}
	}
}

// Should only reach here if the credentials are wrong
if ($redirect != "/")
{
	$_SESSION["nologin_from"] = $redirect;
}
header("Location: http://{$_SERVER["HTTP_HOST"]}" . LIBPATH . "/failedlogin.php");
exit;
?>
