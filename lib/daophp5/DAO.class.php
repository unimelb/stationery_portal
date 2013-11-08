<?php
/*
 * $Id: DAO.class.php,v 1.3 2005/04/01 01:08:43 au5lander Exp $
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
 
require_once("DataAccess.class.php");
require_once("DataResult.class.php");
require_once("ConnectionFactory.class.php");

/**
 * class: DAO
 * 
 * general purpose data access object meant to handle all communcations
 * with the database through DataAccess and DataResult objects
 * 
 * see:
 * 
 * 		DAOException
 */
abstract class DAO {

	// properties
	
	/**
	 * object: da
	 * 
	 * DataAccess object
	 */
	protected $da;

	// methods

	/**
	 * constructor: __construct
	 * 
	 * creates a connection to the database
	 * 
	 * parameters:
	 * 
	 * 		connStr - database connection string
	 */
	public function __construct($connStr) {
		$this->da = ConnectionFactory::makeConnection($connStr);
	}

	/**
	 * method: close
	 * 
	 * closes database connection and unsets DataAccess object
	 */
	public function close() {
		$this->da->disconnect();
		$this->da = null;
	}

	/**
	 * method: retrieve
	 * 
	 * runs database select query and returns DataResult object
	 * 
	 * parameters:
	 * 
	 * 		sql - sql string
	 * 
	 * returns:
	 * 
	 * 		DataResult object
	 */
	public function retrieve($sql) {
		if(!$this->da) {
			throw new DAOException('Cannot execute query, '.$sql.', reason: connection object no longer exists.');
		}

		return $this->da->query($sql);
	}

	/**
	 * method: update
	 * 
	 * runs database insert/update/dete/ query and returns DataResult object
	 *
	 * parameters:
	 * 
	 * 		sql - sql string
	 * 
	 * returns:
	 * 
	 * 		DataResult object
	 */
	public function update($sql) {
		if(!$this->da) {
			throw new DAOException('Cannot execute query,  '.$sql.', reason: connection object no longer exists');
		}

		return $this->da->query($sql);
	}
}
?>