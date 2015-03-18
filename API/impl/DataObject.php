<?php

class DataObject {
	
	public $__data;
	
	public function __construct() {
		$this->__data = array();
	}
	
	public function __get($name){
		return (isset($this->__data[$name])) ? $this->__data[$name] : null;
	}
	
	public function __set($name, $value){
		$this->__data[$name] = $value;
	}
}

?>