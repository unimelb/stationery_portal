<?php
/**
 * LDAPLogin class
 *
 * @package controllers
 * @subpackage authentication
 * @copyright University of Melbourne, 2007
 * @author Damian Sweeney <dsweeney@unimelb.edu.au>
 */

/**
 */
require_once(dirname(__FILE__) . "/../../find_path.inc.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/lib/controllers/auth/login.class.php");
require_once($_SERVER["DOCUMENT_ROOT"] . LIBPATH . "/includes/ldapconnect.inc.php");

/**
 * Exception for when username or password is null
 *
 * @package controllers
 * @subpackage authentication
 */
class LDAPCredentialsException extends Exception {}

/**
 * Exception for when ldap_bind fails
 *
 * @package controllers
 * @subpackage authentication
 */
class LDAPBindException extends Exception {}

/**
 * LDAPLogin, a class to authenticate users using an LDAP server
 *
 * @package controllers
 * @subpackage authentication
 */
class LDAPLogin extends Login
{
	/**
	 * @var connection container
	 */
	private $ldapconn;
	/**
	 * @var string ldap distinguished name (minus uid)
	 */
	private $dn;
	/**
	 * @var string ldap filter
	 */
	private $filter;
	/**
	 * @var array fields to collect from the LDAP server
	 */
	private $fields;

	/**
	 * connect to an LDAP server
	 */
	public function connect()
	{
		$this->ldapconn = @ldap_connect(LDAP_CONNECTION);
	}
	/**
	 * disconnect from an LDAP server
	 */
	public function disconnect()
	{
		ldap_unbind($this->ldapconn);
	}
	/**
	 * authenticate the user using their username and password
	 */
	public function authenticate($user, $pass)
	{
		if ($user == null or $pass == null)
		{
			throw new LDAPCredentialsException("User name or password is blank");
		}
		else
		{
			$this->connect();
			$this->dn = "uid=$user," . LDAP_DN;
			if (@ldap_bind($this->ldapconn, $this->dn, $pass))
			{
				$this->filter = "(uid=$user)";
				$this->populateFields();
				$this->authenticated = true;
				$this->notify();
				$this->disconnect();
			}
			else
			{
				$this->notify();
				$this->disconnect();
				throw new LDAPBindException("Couldn't log in");
			}
		}
		return $this->authenticated;
	}
	/**
	 * Grab the fields from the ldap server
	 */
	private function populateFields()
	{
		if (!empty($this->fields))
		{
			$search = ldap_search($this->ldapconn, $this->dn, $this->filter, $this->fields);
			$results = ldap_get_entries($this->ldapconn, $search);
			$temp_fields = array();
			foreach ($this->fields as $field)
			{
				if (array_key_exists($field, $results[0]))
				{
					$temp_fields[$field] = $results[0][$field][0];
				}
			}
			$this->fields = $temp_fields;
		}
	}
	/**
	 * sets an array of fields to retrieve from the LDAP server
	 * @param array fields to retrieve
	 */
	public function setFields(array $fields)
	{
		$this->fields = $fields;
	}
	/**
	 * gets an array of fields to retrieve from the LDAP server
	 * @return array fields to retrieve
	 */
	public function getFields()
	{
		return $this->fields;
	}
}
?>
