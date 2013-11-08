<?php
class User {

	private $id;
	private $name;	
	private $email;

	public function __construct() {
		$this->id = 0;
		$this->name = "";
		$this->email = "";
	}

	public function getID() {
		return $this->id;
	}

	public function getName() {
		return $this->name;
	}

	public function getEmail() {
		return $this->email;
	}
	
	public function setID($id) {
		$this->id = $id;
	}

	public function setName($name) {
		$this->name = $name;
	}

	public function setEmail($email) {
		$this->email = $email;
	}
}
?>