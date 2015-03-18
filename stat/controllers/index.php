<?php

require_once(FSPATH . '/controllers/.parent/all.php');

class Controller_index extends Controller_auth {
	
	public function __construct() {
		parent::__construct();	
	}

	public function index() {
		
		$ap = $this->ap;
		
		$view = new Template($ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$view->addText("content", 'Приветствуем, ' . $ap->whoami());
//		$view->addText("sidebar", 'preved');
		
		$view->display();
	}
}