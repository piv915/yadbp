<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_ps_wmmerchant extends Controller_report {
	
	public function __construct() {
		parent::__construct();
	
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.wmmerchant', 'index');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
				
		$view->addNode('content', 'report-header', 'report.ps.wmmerchant.card-header');
		$view->addNode('content', 'card', 'report.ps.wmmerchant.card');
		$node = $view->node('card');
		
		$db->query(
			sprintf(
				"select LMI_PAYEE_PURSE as 'name', count(*) as 'count' , sum(LMI_PAYMENT_AMOUNT) as 'amount_currency', sum(t1.amount_units) as 'amount' 
				from ps_webmoney_merchant as t2 inner join wallet_history as t1 on t2.op_id=t1.op_id 
				where t1.op_tm >= %d and t1.op_tm <= %d group by LMI_PAYEE_PURSE order by amount desc",
				$start_date, $end_date
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.wmmerchant.card-row'); 
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
		
		$_SESSION['lastPage'] = array('report.ps.wmmerchant', 'transactions');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_webmoney_merchant as t3 on t1.op_id=t3.op_id
				where t1.op_tm >= %d and t1.op_tm <= %d",
				$start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.wmmerchant.header');
		$node = $view->node('report-header');
		
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.name as 'currency', t3.LMI_PAYER_WM, t3.LMI_PAYER_PURSE, t1.amount_currency, t1.amount_units 
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_webmoney_merchant as t3 on t1.op_id=t3.op_id
				where t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d",
				$start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.wmmerchant.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.wmmerchant.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('currency', $row['currency']);
				$node->addText('wmid', $row['LMI_PAYER_WM']);
				$node->addText('purse', $row['LMI_PAYER_PURSE']);
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

		$_SESSION['lastPage'] = array('report.ps.wmmerchant', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.ps.wmmerchant.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.ps.wmmerchant.accID'])) {
			$_SESSION['report.ps.wmmerchant.accID'] = 0;
		}

//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.wmmerchant.prefs-accid');
		$node = $node->node('prefs');

		$accID = isset($_SESSION['report.ps.wmmerchant.accID']) ? (int)$_SESSION['report.ps.wmmerchant.accID'] : '';
		$node->addText('accID', $accID);

//	preferences ------------------------------------------------
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'putsum'
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_webmoney_merchant as t3 on t1.op_id=t3.op_id
				where t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d", 
				$_SESSION['report.ps.wmmerchant.accID'], $start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.wmmerchant.header-byaccid');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.ps.wmmerchant.accID']);
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t2.name as 'currency', t3.LMI_PAYER_WM, t3.LMI_PAYER_PURSE, t1.amount_currency, t1.amount_units 
				from wallet_history as t1 inner join ps_currencies as t2 on t1.currency_id=t2.id inner join ps_webmoney_merchant as t3 on t1.op_id=t3.op_id
				where t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d", 
				$_SESSION['report.ps.wmmerchant.accID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.wmmerchant.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.wmmerchant.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('currency', $row['currency']);
				$node->addText('wmid', $row['LMI_PAYER_WM']);
				$node->addText('purse', $row['LMI_PAYER_PURSE']);
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
			$_SESSION['report.ps.wmmerchant.accID'] = (int)$_POST['accID'];
			
		$_SESSION['report.ps.wmmerchant.showSucc'] = isset($_POST['Succ']) ? (bool)$_POST['Succ'] : false;
		$_SESSION['report.ps.wmmerchant.showMistake'] = isset($_POST['Mistake']) ? (bool)$_POST['Mistake'] : false;
		
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}
}
