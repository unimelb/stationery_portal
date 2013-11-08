<?php
/*
 * $Id: ConnectionFactory.class.php,v 1.2 2005/03/25 03:07:15 au5lander Exp $
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

require_once("ConnFactoryException.class.php"); 
require_once("ConnectionCache.class.php");
require_once("DataAccess.class.php");

/**
 * class: ConnectionFactory
 * 
 * connection factory handles the creation of database connections
 * and manages them via a connection cache singleton
 * 
 * see:
 * 
 *  	ConnectionCache
 * 		ConnFactoryException
 * 		DataAccess
 * 
 */
class ConnectionFactory {

	// group: methods

	/**
	 * method: makeConnection
	 * 
	 * creates an instance of the connection cache
	 * and returns either an existing DataAccess object
	 * or creates and returns a new DataAccess object
	 * if it cannot create a new DataAccess object, throws
	 * a ConnFactoryException
	 * 
	 * parameters:
	 * 
	 * 		connStr - database connection string
	 * 
	 * returns:
	 * 
	 * 		DataAccess object
	 */
	public static function makeConnection($connStr) {

		$cache = connectionCache::getInstance();
		
		if($cache->isEntry($connStr)) {
			return $cache->getEntry($connStr);
		} else {

			$pattern = "/^([a-zA-Z]+):\/\/([a-zA-Z0-9_]+):([\!#a-zA-Z0-9_\-]+)@([a-zA-Z0-9]+|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\/([a-zA-Z0-9_\-]+)$/";

			if(!preg_match($pattern, $connStr, $matches)) {
				throw new ConnFactoryException('Connection string: '.$connStr.' not in correct format (dbtype://user:pass@host/dbname))');
			}

			list($match, $dbtype, $user, $pass, $host, $db) = $matches;

			switch(strtolower($dbtype)) {
				case "mysql":
					require_once("DataAccessMysql.class.php");
					$da = new DataAccessMysql($host, $user, $pass, $db);
					$cache->setEntry($connStr, $da);
					return $da;
					break;
				case "mysqli":
					require_once("DataAccessMysqli.class.php");
					$da = new DataAccessMysqli($host, $user, $pass, $db);
					$cache->setEntry($connStr, $da);
					return $da;
					break;
				case "mssql":
					require_once("DataAccessMssql.class.php");
					$da = new DataAccessMssql($host, $user, $pass, $db);
					$cache->setEntry($connStr, $da);
					return $da;
					break;
     				case "pgsql":
	     				require_once("DataAccessPgsql.class.php");
					$da = new DataAccessPgsql($host, $user, $pass, $db);
					$cache->setEntry($connStr, $da);
					return $da;
					break;
     				case "odbc":
	     				require_once("DataAccessOdbc.class.php");
					$da = new DataAccessOdbc($host, $user, $pass, $db);
					$cache->setEntry($connStr, $da);
					return $da;
					break;
				default:
					throw new ConnFactoryException('Unknown DB Type: '.$dbtype);
					break;
			}
		}
	}
}
?>