<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_redirect_srv extends Controller_report {
	
	public function __construct() {
		parent::__construct();

	}
	
	public function index() {
		$controller = '404';
		
		$action = isset($_GET['type']) ? $_GET['type'] : 'transactions';
		$params['accID']  = isset($_GET['accID']) ? (int)$_GET['accID'] : 0;
		$params['srvID'] = isset($_GET['srvID']) ? (int)$_GET['srvID'] : 0;
		
		parent::redirect('report.srv.wh', $action, $params);
			
	}

}

?>