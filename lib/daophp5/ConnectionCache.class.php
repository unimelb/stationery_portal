<?php
/*
 * $Id: ConnectionCache.class.php,v 1.7 2005/03/25 03:07:16 au5lander Exp $
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

/**
 * class: ConnectionCache
 * 
 * singleton which is used to manage DataAccess objects
 * 
 * see:
 * 
 * 		ConnectionFactory
 * 		DataAccess
 */
 class ConnectionCache {

	// group: properties
	
	/**
	 * object: cache
	 * 
	 * ArrayObject which holds the DataAccess objects
	 */
	private $cache;
	
	/**
	 * object: instance
	 * 
	 * ConnectionCache instance
	 */
	private static $instance = null;

	// group: methods

	/**
	 * constructor: __construct
	 * 
	 * initializes cache
	 */ 
	private function __construct() {
		$this->cache = new ArrayObject();
	}

	/**
	 * method: getInstance
	 * 
	 * creates and returns instance of ConnectionCache if instance does not exist, otherwise returns instance if
	 * instance already exists
	 * 
	 *  returns:
	 * 
	 * 		instance	 of ConnectionCache
	 */ 
	public static function getInstance() {
		if(self::$instance == null) {
			self::$instance = new connectionCache();
		}
		return self::$instance;
	}

	/**
	 * method: setEntry
	 * 
	 * creates/overwrites a DataAccess entry in the cache
	 * 
	 * parameters:
	 * 
	 * 		key - string defining which entry to set
	 * 		conn - DataAccess object to set
	 * 
	 * see:
	 * 
	 * 		<getEntry>
	 */
	public function setEntry($key, $conn) {
		$this->cache->offsetSet(md5($key), $conn);
	}
	
	/**
	 * method: removeEntry
	 * 
	 * removes an entry from the cache
	 * 
	 * parameters:
	 * 
	 * 		conn - DataAccess object to remove 
	 *
	 * returns:
	 * 
	 * 		boolean indicating if removal was successful
	 */
	public function removeEntry($conn) {
		$i = $this->cache->getIterator();
		while($i->valid()) {
			if($conn === $i->current()) {
				$this->cache->offsetUnset($i->key());
				return true;
			}
			$i->next();
		}
		
		// offset not found
		return false;
	}

	/**
	 * method: getEntry
	 * 
	 * creates/overwrites a DataAccess entry in the cache
	 * 
	 * parameters:
	 * 
	 * 		key - string defining which entry to get
	 * 
	 * returns:
	 *
	 * 		DataAccess object
	 * 
	 * see:
	 * 
	 * 		<setEntry>
	 */ 
	public function getEntry($key) {
		return $this->cache->offsetGet(md5($key));
	}

	/**
	 * method: isEntry
	 * 
	 * verifies if an entry is valid based on its key
	 * 
	 * parameters:
	 * 
	 * 		key - string defining which entry to verify
	 * 
	 * returns:
	 * 
	 * 		boolean indicating if key is in cache
	 */
	public function isEntry($key) {
		$i = $this->cache->getIterator();
		while($i->valid()) {
			if($key === $i->current()) {
				return true;
			}
			$i->next();
		}
		
		// entry not found		
		return false;
	}
}
?>