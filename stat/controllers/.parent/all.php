<?php

class Controller_auth extends Controller {
	
	public function __construct() {
		parent::__construct();
		
		if (!$this->ap->loggedIn()) {
			parent::redirect('login', 'index');
		}
	}
	
	public function redirect($controller, $action, $args=null) {
		parent::redirect($controller, $action, $args);
	}
	
	public function __shared_paramsView($view) {
		$view->addNode("sidebar", "shared", "layout.grey-shared");
		$node = $view->node("shared");
		
		if (isset($_SESSION['period'])) {
			$node->addText('startdate', date("d/m/Y", $_SESSION['period']['start']));
			$node->addText('enddate', date("d/m/Y", $_SESSION['period']['end']));
		} else {
			$node->addText('startdate', date("d/m/Y", mktime(0,0,0, date('m'), 1, date('Y'))));
			$node->addText('enddate', date("d/m/Y", SYSTIME));
		}
		
	}
}

class Controller_report extends Controller_auth {
	
	protected $start_date;
	protected $end_date;
	
	public function __construct() {
		parent::__construct();	
		
		$this->start_date = isset($_SESSION['period']['start']) ? (int)$_SESSION['period']['start'] : mktime(0,0,0, date('m'), 1, date('Y'));
		$this->end_date   = isset($_SESSION['period']['end']) ? (int)$_SESSION['period']['end'] : SYSTIME;
	}
	
	protected function startRow($all, $page) {
		$onPage = isset($_SESSION['rowsOnPage']) ? (int)$_SESSION['rowsOnPage'] : ROWSONPAGE;
		
		if ($all < $onPage || $all == 0) {
			return array(0, $onPage);
		}
		
		$start = ($page - 1) * $onPage;
		return array($start, $onPage);
	}
	
	protected function navigation($all, $page, $format, $view) {
		
		$onPage = isset($_SESSION['rowsOnPage']) ? (int)$_SESSION['rowsOnPage'] : ROWSONPAGE;
		
		if ($all < $onPage || $all == 0) {
			return 0;
		}
		
		$pages = ceil($all / $onPage);
		if ($page < 1 || $page > $pages) {
			$page = 1;
		}
		
		$page_block = ceil($page / 10);
		$blocks = ceil($pages / 10);
		
		$page_start = ($page_block * 10) - 9;
		
		$page_end = min($pages, $page_block * 10);
		
		$naviText = '<div id="navigation">';
		
		if($page_block > 1)
			$naviText .= sprintf(' <a href="%s">&laquo;&laquo;&laquo;</a>', sprintf($format, $page_start - 1));
		
		for ($i = $page_start; $i <= $page_end; $i++) {
			$naviText .= ($page == $i)
				? sprintf(' <a href="%s" class="active">%d</a>', sprintf($format, $i), $i)
				: sprintf(' <a href="%s">%d</a>', sprintf($format, $i), $i);
		}

		if($page_block < $blocks)
			$naviText .= sprintf(' <a href="%s">&raquo;&raquo;&raquo;</a>', sprintf($format, $page_end + 1));
		
		$naviText .= '</div>';
		
		$view->addText('content', $naviText);
	}
}

?>