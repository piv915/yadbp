<?php

	define('FSPATH', dirname(__FILE__));
	define('INFO_LOG', 'statsys.log');
	
	ini_set('include_path', '.:' . FSPATH . '/includes/');
	
	require_once('statconf.php');
	require_once('functions.php');
	
	__autoload('_Exception');
	
	$ap = new Application();
	$router = new Router();
	$router->delegate();
	
	
?>