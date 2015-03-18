<?php

require_once(FSPATH . '/controllers/.parent/all.php');

class Controller_dispatch extends Controller_auth {

	public function __construct() {
		parent::__construct();	
	}

	public function timeout() {
//		print "timeout"; 
		$start_date = (isset($_POST['start_date'])) 	? (string)$_POST['start_date'] 	: 0;
		$end_date = (isset($_POST['end_date'])) 		? (string)$_POST['end_date'] 	: 0;
		
		if(isset($_POST['reset']))// && isset($_SESSION['period']))
			unset($_SESSION['period']);
			
		elseif (isset($_POST['all'])) {
			
			$_SESSION['period'] = array('start' => 946674000, 'end' => SYSTIME);
		}			
		elseif (isset($_POST['today'])) {
			
			$_SESSION['period'] = array('start' => mktime(0,0,0, date('m'), date('d'), date('Y')), 'end' => SYSTIME);
			
		}
		elseif (isset($_POST['backmonth'])) {
			
			$e = mktime(0,0,0, date('m'), 1, date('Y')) - 1;
			$s = mktime(0,0,0, date('m', $e), 1, date('Y', $e));
			
			$_SESSION['period'] = array(
				'start' => $s,
				'end' => $e);
			
		}
		elseif (isset($_POST['yesterday'])) {
			
			$_SESSION['period'] = array(
				'start' 	=> mktime(0,0,0, date('m', time()-86400), date('d', time()-86400), date('Y', time()-86400)), 
				'end' 		=> mktime(23,59,59, date('m', time()-86400), date('d', time()-86400), date('Y', time()-86400)) );
			
		}
		else
		{
			$period = array();
			
			if ($start_date == 0) {
				$period['start'] = 0;
			} else {
				list($day, $month, $year) = split("[./]", $start_date, 3);
				
				if (checkdate($month, $day, $year)) {
					$period['start'] = mktime(0,0,0, $month, $day, $year);
				} else 
					$period['start'] = 0;
			}
			
			if ($end_date == 0) {
				$period['end'] = SYSTIME;
			} else {
				list($day, $month, $year) = split("[./]", $end_date, 3);
				
				if (checkdate($month, $day, $year)) {
					$period['end'] = mktime(23,59,59, $month, $day, $year);
				} else 
					$period['end'] = SYSTIME;
			}
			
			if ($period['start'] > 0 || $period['end'] > 0) {
				$_SESSION['period'] = $period;
			}
			
//			print_r($period);
		}
		if(isset($_SESSION['lastPage']))
			parent::redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
		else 
			parent::redirect("index", "index");
		
	}
	
//	public function __call($m, $a) {
////		print_r($m); var_dump($m);
////		print_r($a); var_dump($a);
//		
//		$path = FSPATH . '/controllers/' . str_replace('.', '/', $m) . '.php';
//		if(is_file($path)) {
//			require_once($path);
//			$class = 'Controller_' . str_replace('.', '_', $m);
//			$handler = new $class();
//			$handler->_action();
//		} else {
//			require_once( FSPATH . '/controllers/404.php');
//			$handler = new Controller_404($m, 'event');
//			$handler->index();
//		}
//	}
	
}

?>