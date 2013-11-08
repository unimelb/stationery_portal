<?php
/*
 * $Id: DataAccessError.class.php,v 1.4 2005/03/25 03:06:46 au5lander Exp $
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
 * class: DataAccessError
 * 
 * general purpose error class used
 * by daophp5 classes
 */
class DataAccessError {
	
	// properties
	
	/**
	 * string: errorNum
	 * 
	 * error number
	 */
	private $errorNum;
	
	/**
	 * string: errorMsg
	 * 
	 * error message
	 */
	private $errorMsg;
	
	/**
	 * string: errorType
	 * 
	 * error type
	 */
	private $errorType;
	
	// methods
	
	/**
	 * constructor: __construct
	 * 
	 * initializes errorType, errorNum and ErrorMsg
	 * 
	 * parameters:
	 * 
	 * 		errType - error type
	 *  	errNum - error number
	 * 		errMsg - error message
	 */
	public function __construct($errType = 0, $errNum = 0, $errMsg = "") {
		$this->errorType = $errType;
		$this->errorNum = $errNum;
		$this->errorMsg = $errMsg;
	}
	
	/**
	 * method: getErrorType
	 * 
	 * retrieves error message
	 *
	 * returns:
	 * 
	 * 		error type string
	 */
	public function getErrorType() {
		return $this->errorType;
	}

	/**
	 * method: getErrorNum
	 * 
	 * retrieves error number
	 *
	 * returns:
	 * 
	 * 		error number string
	 */
	public function getErrorNum() {
		return $this->errorNum;
	}
	
	/**
	 * method: getErrorMsg
	 * 
	 * retrieves error message string
	 *
	 * returns:
	 * 
	 * 		error message string
	 */
	public function getErrorMsg() {
		return $this->errorMsg;
	}
	
	/**
	 * method: getErrorStr
	 * 
	 * retrieves error message string
	 *
	 * returns:
	 * 
	 * 		error message string
	 */
	 public function getErrorStr() {
	 	return $this->errorType.", ".$this->errorNum.": ".$this->errorMsg;
	 }
}
?>