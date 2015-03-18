<?php

require_once(FSPATH . '/controllers/.parent/all.php');

class Controller_report_charge extends Controller_report {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$_SESSION['lastPage'] = array('report.charge', 'index');

		$start_date = $this->start_date;
		$end_date   = $this->end_date; 

		$threshold = isset($_SESSION['report.charge.threshold']) ? (int)$_SESSION['report.charge.threshold'] : 50;
		$liveOnly = isset($_SESSION['report.charge.liveOnly']) ? (bool)$_SESSION['report.charge.liveOnly'] : true;
		$liveOnly_condition = $liveOnly ? "(t1.have_owner = 'y')" : "(1)";
		
		$db->query(
			sprintf(
				"select count(*) from (select sum(t2.amount_units) as 'amount' 
				from wallet_accounts as t1 
				inner join wallet_history as t2 on t1.acc_id = t2.acc_id 
				where t2.op_type = 'charge' and t2.op_tm > %d and t2.op_tm < %d and %s and t2.op_actor <> 'remit' 
				group by t1.acc_id 
				having amount > %d) as tview",
				$start_date, $end_date, $liveOnly_condition, $threshold
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.charge.prefs');
		$node = $node->node('prefs');
		$node->addText('minBalance', $threshold);
		if (!$liveOnly) {
			$node->addText('liveOnlyflag', 'checked="checked"');
		}
		
		
		$view->addNode('content', 'report-header', 'report.charge.header');
		$node = $view->node('report-header');
		$node->addText('threshold', $threshold);
			
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
					"select t1.acc_id, t1.balance, t1.reserve, t1.have_owner, sum(t2.amount_units) as 'amount' 
					from wallet_accounts as t1 inner join wallet_history as t2 
					on t1.acc_id = t2.acc_id 
					where t2.op_type = 'charge' and t2.op_tm > %d and t2.op_tm < %d and %s  and t2.op_actor <> 'remit' 
					group by t1.acc_id 
					having amount > %d order by amount desc limit %d, %d",
					$start_date, $end_date, $liveOnly_condition, $threshold, $start, $len
				)
			);

			
			$view->addNode('content', 'table', 'report.charge.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {
				$table->addNode('tabledata', 'row_' . ++$c, 'report.charge.table-row');
				$node = $table->node('row_' . $c);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('balance', sprintf("%.2f", $row['balance']));
				$node->addText('reserve', sprintf("%.2f", $row['reserve']));
				$node->addText('amount', sprintf("%.2f", $row['amount']));
				if($row['have_owner'] == 'n')
					$node->addText('style', 'class="deleted"');
			}
			
			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}
		
		$view->addViewAsText('content', 'report.charge.footer');

		$view->display();
	}
	
	public function setprefs() {
		
		if (isset($_POST['minBalance'])) {
			$_SESSION['report.charge.threshold'] = (int)$_POST['minBalance'];
		}
		
		$_SESSION['report.charge.liveOnly'] = isset($_POST['liveOnly']) ? false : true;
			 
		$this->redirect('report.charge', 'index');
	}
}

?>