<?php
/*
 * $Id: DataAccessMysqli.class.php,v 1.11 2005/03/25 03:39:53 au5lander Exp $
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

require_once("DAOException.class.php");
require_once("DataAccessError.class.php");
require_once("DataResultMysqli.class.php");
require_once("ConnectionCache.class.php");
 
/**
 * class: DataAccessMysqli
 * 
 * DataAccessMysqli handles connecting to and querying
 * database using php mysqli_* functions
 */
class DataAccessMysqli implements DataAccess {

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
	 * DataAccessMysql class constructor
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

		//verify connection string here

		if(!$this->conn = @mysqli_connect($host, $user, $pass, $db)) {
			$this->error = new DataAccessError("CONNECT", mysqli_connect_errno(), mysqli_connect_error());
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
	 * 		DataResultMysql object
	 */
	public function query($sql) {
		if(!$result = @mysqli_query($this->conn, $sql)) {
			$this->error = new DataAccessError("QUERY", mysqli_errno($this->conn), mysqli_error($this->conn));
			throw new DAOException('Could not execute query, reason: '.$this->error->getErrorStr());
		}

		return new DataResultMysqli($result);
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
		return mysqli_real_escape_string($val);
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
		return mysqli_affected_rows($this->conn);
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
		if(!@mysqli_close($this->conn)) {
			$this->error = new DataAccessError("CLOSE", mysqli_errno(), mysqli_error());
			throw new DAOException('Could not close connection, reason: '.$this->error->getErrorStr());
		}

		return ConnectionCache::getInstance()->removeEntry($this);
	}
}
?>