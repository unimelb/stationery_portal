<?php
/*
 * $Id: DataAccess.class.php,v 1.8 2005/03/25 03:22:51 au5lander Exp $
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
 * interface: DataAccess
 * 
 * interface which all DataAccess class should implement
 */
interface DataAccess {
	 
	// group: methods
	
	/**
	 * method: connect
	 * 
	 * create a connect to the database
	 *
	 * parameters:
	 * 
	 * 		host - string hostname
	 * 		user - string username
	 * 		pass - string password
	 * 		db - database name
	 */
	public function connect($host, $user, $pass, $db);
	
	/**
	 * method: query
	 *
	 * send query to the database
	 * 
	 * parameters:
	 * 
	 * 		sql - query string
	 */
	public function query($sql);
	
	/**
	 * method: escape
	 * 
	 * escape value used in query string
	 * 
	 * parameters:
	 * 
	 * 		val - value to escape
	 */
	public function escape($val);
	
	/**
	 * method: disconnect
	 *
	 * disconnect from the database
	 */
	public function disconnect();
}
?>