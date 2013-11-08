<?php
/*
 * $Id: DataResult.class.php,v 1.4 2005/03/25 03:07:16 au5lander Exp $
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
 * class:  DataResult
 * 
 * abstract class representing the result object returned from the DataAccess object 
 */
abstract class DataResult implements Iterator {

	// properties

	/**
	 * object: result
	 * 
	 * DataResult
	 */
	protected $result;
	
	/**
	 * object: currentRow
	 * 
	 * ArrayObject holding the current row data
	 */
	protected $currentRow;
	
	/**
	 * integer: rowNum
	 * 
	 * current row number
	 */ 
	protected $rowNum;
	
	/**
	 * boolean: valid
	 * 
	 * indicates if there are any more rows in result set
	 */
	protected $valid;

	// methods
	
	/**
	 * method: getRow
	 *
	 * returns an ArrayObject containing row data or false if no (more) rows
	 */
	abstract protected function getRow();
	
	/**
	 * method: getRow
	 *
	 * resets data pointer to first row of data set (0 indexed)
	 * 
	 * parameters:
	 * 
	 * 		rown_num - row number to seek to (default 0)
	 */
	abstract protected function dataSeek($row_num = 0);

	/**
	 * method: getNumRows
	 * 
	 * returns number of rows in result set
	 */
	abstract public function getNumRows();
	
	/**
	 * method: getNumFields
	 */
	abstract public function getNumFields();
	
	/**
	 * method: valid
	 * 
	 * returns value of valid (overrides Iterator::valid())
	 * 
	 * returns:
	 * 
	 * 		boolean
	 */
	 public function valid() {
		return $this->valid;
	}
	
	/**
	 * method: current
	 * 
	 * returns current row from dataset (overrides Iterator:: current())
	 * 
	 * returns:
	 * 
	 * 		ArrayObject
	 */
	 public function current() {
		return $this->currentRow;
	}
	
	/**
	 * method: key
	 * 
	 * returns current row number (overrides Iterator::key())
	 * 
	 * returns:
	 * 
	 * 		integer
	 */
	public function key() {
		return $this->rowNum;
	}
	
	/**
	 * method: rewind
	 * 
	 * repositions cursor to beginning of dataset and resets row number (overrides Iterator::rewind())
	 * 
	 * returns:
	 * 
	 * 		boolean
	 */
	public function rewind() {
		$this->rowNum = -1;
		if($this->dataSeek(0) !== false) {
        	$this->next();
        	return true;
		}
		
		return false;
		
	}
	
	/**
	 * method: next
	 * 
	 * retrieves next row from dataset, places it into currentRow and
	 * increments row number (overrides Iterator:: next())
	 * 
	 * returns:
	 * 
	 * 		boolean
	 */
	public function next() {
		if(($this->currentRow = $this->getRow()) !== false) {
			$this->rowNum++;
			$this->valid = true;
			return true;
		}
		
		$this->valid = false;
		return false;
	}		
}
?>