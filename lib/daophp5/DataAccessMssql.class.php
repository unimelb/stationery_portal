<?php
/*
 * $Id: DataAccessMssql.class.php,v 1.12 2005/03/25 03:39:53 au5lander Exp $
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
 * This ms-sql DataAccess class added by Patrick Maslen 17 Nov 2006
 */
require_once("DAOException.class.php");
require_once("DataAccessError.class.php");
require_once("DataResultMssql.class.php");
require_once("ConnectionCache.class.php");

/**
 * class: DataAccessMssql
 * 
 * DataAccessMssql handles connecting to and querying database
 * using php Mssql_* functions
 */
class DataAccessMssql implements DataAccess {

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
	 * DataAccessMssql class constructor
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

		if(!$this->conn = @mssql_connect($host, $user, $pass)) {
			$this->error = new DataAccessError("CONNECT", 0, mssql_get_last_message());
			throw new DAOException('Could not connect to database, reason: '.$this->error->getErrorStr());
		}

		if(!mssql_select_db($db)) {
			$this->error = new DataAccessError("SELECTDB", 0, mssql_get_last_message());
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
	 * 		DataResultMssql object
	 */
	public function query($sql) {
		if(!$result = @mssql_query($sql, $this->conn)) {
			$this->error = new DataAccessError("QUERY", 0, mssql_get_last_message());
			throw new DAOException('Could not execute query, reason: '. $this->error->getErrorStr() . "with sql of: $sql");
		}

		return new DataResultMssql($result);
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
        /** De MagicQuotes
         * This function was taken from the php documentation site 
         * at http://au.php.net/function.mssql-query
         * by vollmer at ampache dot org
         */
        
        	$fix_str = stripslashes($val);
    		$fix_str = str_replace("'","''",$val);
    		$fix_str = str_replace("\0","[NULL]",$fix_str);
    		return $fix_str;
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
		if (is_int($val) or is_float($val))
		{
			$value = $this->escape($val);
		}
		else if (is_bool($val))
		{
			$value = $this->escape((int)$val);
		}
		else if (is_null($val))
		{
			$value = 'NULL';
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
		return mssql_num_rows($this->conn);
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
		if(!@mssql_close($this->conn)) {
			$this->error = new DataAccessError("CLOSE", 0, mssql_get_last_message());
			throw new DAOException('Could not close connection, reason: '.$this->error->getErrorStr());
		}

		return ConnectionCache::getInstance()->removeEntry($this);
	}
}
?>