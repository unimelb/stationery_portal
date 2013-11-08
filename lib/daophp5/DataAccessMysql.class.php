<?php
/*
 * $Id: DataAccessMysql.class.php,v 1.12 2005/03/25 03:39:53 au5lander Exp $
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
require_once("DataResultMysql.class.php");
require_once("ConnectionCache.class.php");

/**
 * class: DataAccessMysql
 * 
 * DataAccessMysql handles connecting to and querying database
 * using php mysql_* functions
 */
class DataAccessMysql implements DataAccess {

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

		if(!$this->conn = @mysql_connect($host, $user, $pass)) {
			$this->error = new DataAccessError("CONNECT", mysql_errno(), mysql_error());
			throw new DAOException('Could not connect to database, reason: '.$this->error->getErrorStr());
		}

		if(!mysql_select_db($db)) {
			$this->error = new DataAccessError("SELECTDB", mysql_errno(), mysql_error());
			throw new DAOException('Could not select database \''.$db.'\', reason: '.$this->error->getErrorStr());
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
		if(!$result = @mysql_query($sql, $this->conn)) {
			$this->error = new DataAccessError("QUERY", mysql_errno(), mysql_error());
			throw new DAOException('Could not execute query, reason: '.$this->error->getErrorStr());
		}

		return new DataResultMysql($result);
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
		return mysql_real_escape_string($val);
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
		return mysql_affected_rows($this->conn);
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
		if(!@mysql_close($this->conn)) {
			$this->error = new DataAccessError("CLOSE", mysql_errno(), mysql_error());
			throw new DAOException('Could not close connection, reason: '.$this->error->getErrorStr());
		}

		return ConnectionCache::getInstance()->removeEntry($this);
	}
}
?>