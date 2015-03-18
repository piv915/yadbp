<?php

class Router {

	private $controller_name = 'index';
	private $action_name = 'index';
	
	public function __construct() {

		$ep = Application::entryPoint();
		$ep_length = strlen($ep);
		
		if(@substr_compare($_SERVER['REQUEST_URI'], $ep, 0, $ep_length, true) === 0) {
			$app_path = substr_replace($_SERVER['REQUEST_URI'], '', 0, $ep_length);
			$qsign = strpos($app_path, '?');
			if($qsign !== false)
				$app_path = substr_replace($app_path, '', $qsign);
			$app_path = trim($app_path, '/');
			
			$parts = explode('/', $app_path, 3);
			
			if(isset($parts[0]) && preg_match("/^[a-z0-9.]+$/i", $parts[0]))
				$this->controller_name = $parts[0];
				
			if(isset($parts[1]) && preg_match("/^[a-z0-9:.]+$/i", $parts[1]))
				$this->action_name = $parts[1];

		}
	}
	
	public function getController(&$class) {
//		print $this->controller_name . '<br>' . $this->action_name;
		$path = FSPATH . '/controllers/' . str_replace('.', '/', $this->controller_name) . '.php';
		if(is_file($path)) {
			$class = 'Controller_' . str_replace('.', '_', $this->controller_name);
			return $path;
		} else {
			$class = 'Controller_404';
			return (FSPATH . '/controllers/404.php');
		}
	}
	
	public function delegate() {
		
		$file = $this->getController($class);
		include_once($file);
		
		$controller = new $class($this->controller_name, $this->action_name);
		
		if(!is_callable(array($controller, $this->action_name))) {
			include_once(FSPATH . '/controllers/404.php');
			$controller = new Controller_404($this->controller_name, $this->action_name);
		}
		
		$action = $this->action_name;
		$controller->$action();
	}
}