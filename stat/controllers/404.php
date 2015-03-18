<?php

class Controller_404 extends Controller {

	private $callName;
	
	public function __construct($name, $action) {
		parent::__construct();
		$this->callName = $name;
	}

	public function __call($action, $args) {
		
		$view = new Template($this->ap->getTemplate());
		
		$view->addText("title", 'Страница не найдена');
		$view->addText("foot", "страница не найдена");

		$view->addNode("content", "message", "errors.404");

		$view->message->addText("path", $_SERVER['REQUEST_URI']);
		$view->message->addText("action", $action);
		$view->message->addText("controller", $this->callName);
				
		$view->display();
	}
}	

?>