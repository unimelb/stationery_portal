<?php
/*
 * $Id: DataAccessPgsql.class.php,v 1.12 2005/03/25 03:39:53 au5lander Exp $
 * 
 * This program is free software; you can redistribute it and/or modify  it
 * under the terms of the GNU General Public License as published by  the Free
 * Software Foundation; either version 2 of the License, or  (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,  but WITHOUT
 * ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the  GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License  along with
 * this program; if not, write to the Free Software  Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA  02111-1307  USA
 */
/**
 * This pgsql DataAccess class added by Patrick Maslen 29 Aug 2007
 */
require_once("DAOException.class.php");
require_once("DataAccessError.class.php");
require_once("DataResultPgsql.class.php");
require_once("ConnectionCache.class.php");

/**
 * class: DataAccessPgsql
 * 
 * DataAccessPgsql handles connecting to and querying database
 * using php Pgsql_* functions
 */
class DataAccessPgsql implements DataAccess {

	// properties

	/**
	 * handle: conn
	 * 
	 * database connection resource
	 */
	protected $conn;
	
	/**
	 * string: db
	 * 
	 * database name
	 */
	protected $db;
	
	/**
	 * object: error
	 * 
	 * DataAccessError
	 */
	private $error;

	// methods

	/**
	 * constructor: __construct
	 * 
	 * DataAccessPgsql class constructor
	 * 
	 * parameters:
	 * 
	 * 		host - hostname string
	 * 		user - username string
	 * 		pass - password string
	 * 		db - database name
	 */
	public function __construct($host, $user, $pass, $db) {
		$this->connect($host, $user, $pass, $db);
	}

	
	/**
	 * method: connect
	 * 
	 * given a hostname, username, password and database name
	 * makes a connection to the database and selects the database
	 * if the connection cannot be made or the database cannot
	 * be selected, throws DAOExcpetion
	 * 
	 * parameters:
	 * 
	 * 		host - hostname string
	 * 		user - username string
	 * 		pass - password string
	 * 		db - database name
	 * 
	 * returns:
	 * 
	 * 		boolean
	 */
	public function connect($host, $user, $pass, $db) {

		if(!$this->conn = pg_connect("host=$host user=$user password=$pass dbname=$db")) {
			$this->error = new DataAccessError("CONNECT", 0, pg_last_error());
			throw new DAOException('Could not connect to database, reason: '.$this->error->getErrorStr());
		}
		$this->db = $db;
		return true;
	}

	/**
	 * method: query
	 * 
	 * given an SQL string, queries the database and returns
	 * the result or throws DAOException
	 * 
	 * parameters:
	 * 
	 * 		sql - sql query string
	 * 
	 * returns:
	 * 
	 * 		DataResultPgsql object
	 */
	public function query($sql) {
		if(!$result = @pg_query($this->conn, $sql)) {
			$this->error = new DataAccessError("QUERY", 0, pg_last_error());
			throw new DAOException('Could not execute query, reason: '.$this->error->getErrorStr());
		}

		return new DataResultPgsql($result);
	}

	/**
	 * method: escape
	 * 
	 * escape a value before using it in a query string
	 * 
	 * parameters:
	 * 
	 * 		val - value to escape
	 * 
	 * returns:
	 * 
	 * 		escaped value
	 */
	public function escape($val) {
		// seems to work better when this function does nothing.
		//return pg_escape_string($val);
		return $val;
	}

	/**
	 * method: escapeContext
	 *
	 * escape a value with contextual use of quote marks
	 * 
	 * 
	 * parameters:
	 * 
	 * 		val - value to escape
	 * 
	 * returns:
	 * 
	 * 		escaped value
	 */
	public function escapeContext($val) {
		// a list of pgsl functions to be passed in without quotes
		$special_functions = array('localtimestamp');
		if (is_numeric($val))
		{
			$value = $this->escape($val);
		}
		else if (is_bool($val))
		{
			$value = "'".$this->escape((int)$val)."'";
		}
		else if (is_null($val))
		{
			$value = 'NULL';
		}
		else if (in_array($val, $special_functions))
		{
			$value = $this->escape($val);
		}
		else
		{
			$value = "'".$this->escape($val)."'";
		}
		return $value;
	}
	/**
	 * method: getAffectedRows
	 * 
	 * returns the number of rows affected by an insert, 
	 * update or delete query
	 * 
	 * returns:
	 * 
	 * 		integer indicating number of row affected
	 */
	public function getAffectedRows() {
		return pg_num_rows($this->conn);
	}
	
	/**
	 * method: disconnect
	 * 
	 * disconnect database connection
	 * 
	 * returns:
	 * 
	 * 		boolean indicating if disconnect was successful
	 */
	public function disconnect() {
		if(!@pg_close($this->conn)) {
			$this->error = new DataAccessError("CLOSE", 0, pg_last_error());
			throw new DAOException('Could not close connection, reason: '.$this->error->getErrorStr());
		}

		return ConnectionCache::getInstance()->removeEntry($this);
	}
}
?>