<?php
/*
 * $Id: DataResultMysql.class.php,v 1.4 2005/03/25 03:07:17 au5lander Exp $
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
 * class: DataResultPgsql
 * 
 * DataResultPgsql handles retrieving row data from the Pgsql database
 * 
 * see:
 * 
 * 		DataResult
 */
/**
 * class added by Patrick Maslen 21 November 2006
 *
 */
class DataResultPgsql extends DataResult {

	// properties
	
	/**
	 * integer: format
	 * 
	 * holds the format of the result set, NUM, ASSOC or BOTH
	 * @access private
	 * @var int row format return type
	 */
	private $format;

	// constants
	
	const DATA_RESULT_FORMAT_NUM = PGSQL_NUM;
	const DATA_RESULT_FORMAT_ASSOC = PGSQL_ASSOC;
	const DATA_RESULT_FORMAT_BOTH = PGSQL_BOTH;
	 
	// methods
	
	/**
	 * constructor: __construct
	 * 
	 * DataResultPgsql constructor
	 * 
	 * parameters:
	 * 
	 * 		result - Pgsql database result resource
	 */
	public function __construct($result) {
		$this->result = $result;
		$this->format = self::DATA_RESULT_FORMAT_NUM;
		$this->currentRow = new ArrayObject();
		$this->rowNum = -1;
		$this->valid = false;
	}

	/**
	 * method: getFormat
	 * 
	 * gets the result array format
	 *
	 * returns:
	 * 
	 * 		format - format integer value
	 */
	public function getFormat() {
		return $this->format;
	}
	
	/**
	 * method: setFormat
	 * 
	 * sets the format of the result array to either 0-indexed (default),
	 * associative (with column names) or both
	 *
	 * parameters:
	 * 
	 * 		format - string indicating format to set
	 */
	public function setFormat($format) {
		switch($format) {
			case "ASSOC":
				$this->format = self::DATA_RESULT_FORMAT_ASSOC;
				break;
			case "BOTH":
				$this->format = self::DATA_RESULT_FORMAT_BOTH;
				break;
			case "NUM":
			default:
				$this->format = self::DATA_RESULT_FORMAT_NUM;
				break;
		}
	}

	/**
	 * method: getRow
	 * 
	 * returns a row of data from the Pgsql database
	 * result resource. if no more rows in resource,
	 * returns false
	 * 
	 * returns:
	 * 
	 * 		ArrayObject or false
	 */
	public function getRow() {		
		if($row = pg_fetch_array($this->result, null, $this->format)) {
			return new ArrayObject($row);
		}
		
		return false;
	}

	/**
	 * method: dataSeek
	 * 
	 * resets cursor to beginning of dataset
	 *
	 * returns:
	 * 
	 * 		boolean indicating seek was succesful
	 */
	protected function dataSeek($row_num = 0) {
		return pg_result_seek($this->result, $row_num);
	}
	
	/**
	 * method: getNumRows
	 * 
	 * returns number of rows from resulting from select
	 * query
	 *
	 * returns:
	 * 
	 * 		integer indicating number of rows in result set
	 */
	public function getNumRows() {
		return pg_num_rows($this->result);
	}
	
	/**
	 * method: getNumFields
	 * 
	 * returns number of fields in result set
	 *
	 * returns:
	 * 
	 * 		integer indicating number of fields in result set
	 */
	public function getNumFields() {
	 	return pg_num_fields($this->result);
	}
}
?>