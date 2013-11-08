<?php
require_once("../DAO.class.php");
require_once("User.class.php");

class UserDAO extends DAO {

	public function __construct($dsn) {
		parent::__construct($dsn);
	}

	public function getUserByID($id = 0) {
		
		// validate id
		if(!is_int($id) && $id <= 0) {
			throw new InvalidArgumentException($id." is not a valid argument");
		}
		
		// query database
		$sql = "select * from user_info where id = '".$this->da->escape($id)."'";
		$res = $this->retrieve($sql);
		$res->setFormat("ASSOC");

		// loop through results, create user object
		if($res->getNumRows() == 1) {
			$row = $res->getRow();
			$user = new User();
			$user->setID($row->offsetGet("id"));
			$user->setName($row->offsetGet("name"));
			$user->setEmail($row->offsetGet("email"));
			return $user;
		} else {
			return false;
		}
	}

	public function getUserList() {

		// query database
		$sql = "select * from user_info";
		$res = $this->retrieve($sql);
		$res->setFormat("ASSOC");

		$users = new ArrayObject();
		
		// retrieve row and create user object
		if($res->getNumRows() >= 1) {
			
			foreach($res as $row) {
				$user = new User();
				$user->setID($row->offsetGet("id"));
				$user->setName($row->offsetGet("name"));
				$user->setEmail($row->offsetGet("email"));
				$users->append($user);
			}
			return $users;
		} else {
			return false;
		}
	}
}
?>