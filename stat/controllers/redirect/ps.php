<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_redirect_ps extends Controller_report {
	
	public function __construct() {
		parent::__construct();

	}
	
	public function index() {
		$controller = '404';
		
		$action = isset($_GET['type']) ? $_GET['type'] : 'transactions';
		$params['accID']  = isset($_GET['accID']) ? (int)$_GET['accID'] : 0;
		
		if (isset($_GET['id']) && $_GET['id'] == '1001') {
			parent::redirect('report.ps.nikitarf', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1002') {
			parent::redirect('report.ps.nikitaam', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1003') {
			parent::redirect('report.ps.nikitaua', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1004') {
			parent::redirect('report.ps.nikitage', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1201') {
			parent::redirect('report.ps.smsonline', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1101') {
			parent::redirect('report.ps.ifree', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '1301') {
			parent::redirect('report.ps.smsdostup', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '2001') {
			parent::redirect('report.ps.wmmerchant', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '3001') {
			parent::redirect('report.ps.robox', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == '5001') {
			parent::redirect('report.ps.platron', $action, $params);
			
		}
		elseif (isset($_GET['id']) && $_GET['id'] == 'moder') {
			parent::redirect('report.ps.moder', $action, $params);
		}
		elseif (isset($_GET['id']) && ($_GET['id'] == 'remit' || $_GET['id']=='4001')) {
			parent::redirect('report.ps.remit', $action, $params);
		}
		else {
		
			parent::redirect('404', 'index');
		}
//		var_dump($_GET);
	}

}

?>