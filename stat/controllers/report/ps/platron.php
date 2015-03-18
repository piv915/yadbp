<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_ps_platron extends Controller_report {
	
	public function __construct() {
		parent::__construct();
	
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.platron', 'index');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
				
		$view->addNode('content', 'report-header', 'report.ps.platron.card-header');
		$view->addNode('content', 'card', 'report.ps.platron.card');
		$node = $view->node('card');
		
		$db->query(
			sprintf(
				"select pg_payment_system as 'name', count(*) as 'count' , sum(pg_amount) as 'amount_currency', sum(t1.amount_units) as 'amount' 
				from ps_platron as t2 inner join wallet_history as t1 on t2.op_id=t1.op_id 
				where t1.op_tm >= %d and t1.op_tm <= %d group by pg_payment_system order by amount desc",
				$start_date, $end_date
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.platron.card-row'); 
			$nodeP = $node->node('numbersRow_' . $c);
			
			$nodeP->addText('name', $row['name']);
			$nodeP->addText('count', $row['count']);
			$nodeP->addText('amount_currency', sprintf("%.2f", $row['amount_currency']));
			$nodeP->addText('amount', sprintf("%.2f", $row['amount']));
			$total += $row['amount'];
		}

		$node->addText('numbersTotal', sprintf("%.2f", $total));
		
		$view->display();
	}
	

	public function transactions() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.platron', 'transactions');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_platron as t3 on t1.op_id=t3.op_id
				where t1.op_tm >= %d and t1.op_tm <= %d",
				$start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.platron.header');
		$node = $view->node('report-header');
		
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.name as 'currency', t3.pg_user_phone, t3.pg_payment_system, t1.amount_currency, t1.amount_units 
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_platron as t3 on t1.op_id=t3.op_id
				where t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d",
				$start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.platron.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.platron.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('currency', $row['currency']);
				$node->addText('user_phone', $row['pg_user_phone']);
				$node->addText('payment_system', $row['pg_payment_system']);
				$node->addText('amount_currency', sprintf("%.2f", $row['amount_currency']));
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

		$_SESSION['lastPage'] = array('report.ps.platron', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.ps.platron.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.ps.platron.accID'])) {
			$_SESSION['report.ps.platron.accID'] = 0;
		}

//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.platron.prefs-accid');
		$node = $node->node('prefs');

		$accID = isset($_SESSION['report.ps.platron.accID']) ? (int)$_SESSION['report.ps.platron.accID'] : '';
		$node->addText('accID', $accID);

//	preferences ------------------------------------------------
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_platron as t3 on t1.op_id=t3.op_id
				where t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d", 
				$_SESSION['report.ps.platron.accID'], $start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.platron.header-byaccid');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.ps.platron.accID']);
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.name as 'currency', t3.pg_user_phone, t3.pg_payment_system, t1.amount_currency, t1.amount_units 
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_platron as t3 on t1.op_id=t3.op_id
				where t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d", 
				$_SESSION['report.ps.platron.accID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.platron.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.platron.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('currency', $row['currency']);
				$node->addText('user_phone', $row['pg_user_phone']);
				$node->addText('payment_system', $row['pg_payment_system']);
				$node->addText('amount_currency', sprintf("%.2f", $row['amount_currency']));
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
			$_SESSION['report.ps.platron.accID'] = (int)$_POST['accID'];
			
		$_SESSION['report.ps.platron.showSucc'] = isset($_POST['Succ']) ? (bool)$_POST['Succ'] : false;
		$_SESSION['report.ps.platron.showMistake'] = isset($_POST['Mistake']) ? (bool)$_POST['Mistake'] : false;
		
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}
}
