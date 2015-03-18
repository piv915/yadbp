<?php

class Controller {
	protected $ap;
//	protected $name;
	
	public function __construct() {
		global $ap;
		$this->ap = $ap;
//		$this->name = $name;
		$this->ap->setTemplate(LAYOUT_BASIC);
	}
	
	public function redirect($controller, $action, $args=null) {
		
		if(headers_sent($file, $line))
			throw new Exception("Headers already sent in file $file at line $line");
		
		$schema = $this->ap->getUrlSchema();
		$redirect  = $schema . $_SERVER['HTTP_HOST'] . Application::entryPoint() . '/' . $controller . '/' . $action . '/';
		$args['rnd'] = SYSTIME;
		
		if(count($args)) {
			$redirect .= '?';
			$pairs = array();
			foreach ($args as $name => $value) {
				$pairs[] = urlencode($name) . '=' . urlencode($value);
			}
			$redirect .= join('&', $pairs);
		}
		
		header('Location: ' . $redirect);
	}
	
//	public function __call($m, $a) {
////		if(!is_callable(array($this, 'index'))) {
////			include_once(FSPATH . '/controllers/404.php');
////			$controller = new Controller_404($this->controller_name, $this->action_name);
////		}
////		$this->index();	
//		print $this->name . ':::' . $m;
//		
//	}
}


?>