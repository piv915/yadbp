<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_ps_robox extends Controller_report {
	
	private $states;
	
	public function __construct() {
		parent::__construct();
		
		$this->states = array(
			0 => 'выписан',
			5 => 'инициирован',
			10 => 'отменен',
			50 => 'получены ср-ва',
			60 => 'ср-ва возвращены',
			80 => 'приостановлено',
			100 => 'завершен'
			
		);
		
	}
	
	public function index() {

		parent::redirect('report.ps.robox', 'transactions');
		
	}
	

	public function transactions() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.robox', 'transactions');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1, ps_rbx_operations as t3, ps_rbx_invoices as t2 where t1.op_id=t3.op_id and t3.inv_id=t2.inv_id
				and t1.op_tm >= %d and t1.op_tm <= %d", 
				$start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.robox.header');
		$node = $view->node('report-header');
		
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.inv_id, t2.op_state, t1.amount_currency, t2.in_curr_mark, t1.amount_units 
				from wallet_history as t1, ps_rbx_operations as t3, ps_rbx_invoices as t2 where t1.op_id=t3.op_id and t3.inv_id=t2.inv_id
				and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d", 
				$start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.robox.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.robox.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('inv_id', $row['inv_id']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('op_state', $this->states[$row['op_state']]);
				$node->addText('mark', $row['in_curr_mark']);
				$node->addText('amount_wmz', sprintf("%.2f", $row['amount_currency']));
				$node->addText('amount_units', sprintf("%.2f", $row['amount_units']));
				
			}

			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}

		$view->display();
	}
	
	public function byAccID() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);

		$_SESSION['lastPage'] = array('report.ps.robox', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.ps.robox.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.ps.robox.accID'])) {
			$_SESSION['report.ps.robox.accID'] = 0;
		}

//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.robox.prefs-accid');
		$node = $node->node('prefs');

		$accID = isset($_SESSION['report.ps.robox.accID']) ? (int)$_SESSION['report.ps.robox.accID'] : '';
		$node->addText('accID', $accID);

//	preferences ------------------------------------------------
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1, ps_rbx_operations as t3, ps_rbx_invoices as t2 where t1.op_id=t3.op_id and t3.inv_id=t2.inv_id
				and t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d",
				$_SESSION['report.ps.robox.accID'], $start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.robox.header-byaccid');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.ps.robox.accID']);
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.inv_id, t2.op_state, t1.amount_currency, t2.in_curr_mark, t1.amount_units 
				from wallet_history as t1, ps_rbx_operations as t3, ps_rbx_invoices as t2 where t1.op_id=t3.op_id and t3.inv_id=t2.inv_id
				and t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d",  
				$_SESSION['report.ps.robox.accID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.robox.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.robox.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('inv_id', $row['inv_id']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('op_state', $this->states[$row['op_state']]);
				$node->addText('mark', $row['in_curr_mark']);
				$node->addText('amount_wmz', sprintf("%.2f", $row['amount_currency']));
				$node->addText('amount_units', sprintf("%.2f", $row['amount_units']));

			}

			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}
		
		
		$view->display();
	}
	
	public function setprefs() {
		if (isset($_POST['accID'])) 
			$_SESSION['report.ps.robox.accID'] = (int)$_POST['accID'];
			
		$_SESSION['report.ps.robox.showSucc'] = isset($_POST['Succ']) ? (bool)$_POST['Succ'] : false;
		$_SESSION['report.ps.robox.showMistake'] = isset($_POST['Mistake']) ? (bool)$_POST['Mistake'] : false;
		
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}
}
