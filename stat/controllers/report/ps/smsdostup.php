<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_ps_smsdostup extends Controller_report {
	
	private $paytable;
	
	public function __construct() {
		parent::__construct();
		
		$this->paytable = array(
			'ru' => array(
				3858 => 150,
				2858 => 85,
				9151 => 65,
				8151 => 47,
				7151 => 17,
				6151 => 10,
				3151 => 5,
				2151 => 1
			),
			'ua' => array(
				7654 => 80,
				7373 => 47,
				4113 => 40,
				1033 => 22,
				7900 => 16,
				5900 => 7,
				7500 => 3,
			),
			'kz' => array(
				9915 => 45,
				9916 => 26,
				9917 => 18,
				9912 => 8,
			),
			'by' => array(
				5014 => 31,
				5013 => 4
			)
		);
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.smsdostup', 'index');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$succ = isset($_SESSION['report.ps.smsdostup.showSucc']) ? (bool)$_SESSION['report.ps.smsdostup.showSucc'] : true;
		$mistake = isset($_SESSION['report.ps.smsdostup.showMistake']) ? (bool)$_SESSION['report.ps.smsdostup.showMistake'] : true;
		
		if($succ && $mistake)
			$commit_condition = 1;
		elseif ($succ && !$mistake)
			$commit_condition = 'commited = 1';
		elseif($mistake) 
			$commit_condition = 'commited = 0';
		 else 
		 	$commit_condition = '0';
				
		$view->addNode('content', 'report-header', 'report.ps.smsdostup.card-header');
		
		
		$view->addNode('content', 'card-ru', 'report.ps.smsdostup.card-table');
		$node = $view->node('card-ru');
		$node->addText('country', 'Россия');
		
		$db->query(
			sprintf(
				"select serviceNumber as 'name', commited, count(*) as 'count' from ps_sms_smsdostup as t1
				where country='ru' and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d and (%s) group by commited, serviceNumber order by commited desc, serviceNumber",
				$start_date, $end_date, $commit_condition
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.smsdostup.card-row'); 
			$nodeP = $node->node('numbersRow_' . $c);
			
			$nodeP->addText('name', $row['name'] . ' [' . $this->paytable['ru'][$row['name']] . ']');
			$nodeP->addText('count', $row['count']);
			
			if($row['commited'] == 0) 
				$nodeP->addText('style', 'class="mistake"');
			
			$amount = (int)$row['count'] * $this->paytable['ru'][$row['name']];
			
			$nodeP->addText('amount', $amount);
			$total += $amount;
		}

		$node->addText('numbersTotal', sprintf("%.2f", $total));
		
		
		$view->addNode('content', 'card-kz', 'report.ps.smsdostup.card-table');
		$node = $view->node('card-kz');
		$node->addText('country', 'Казахстан');
		
		$db->query(
			sprintf(
				"select serviceNumber as 'name', commited, count(*) as 'count' from ps_sms_smsdostup as t1
				where country='kz' and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d and (%s) group by commited, serviceNumber order by commited desc, serviceNumber",
				$start_date, $end_date, $commit_condition
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.smsdostup.card-row'); 
			$nodeP = $node->node('numbersRow_' . $c);
			
			$nodeP->addText('name', $row['name'] . ' [' . $this->paytable['kz'][$row['name']] . ']');
			$nodeP->addText('count', $row['count']);
			
			if($row['commited'] == 0) 
				$nodeP->addText('style', 'class="mistake"');
			
			$amount = (int)$row['count'] * $this->paytable['kz'][$row['name']];
			
			$nodeP->addText('amount', $amount);
			$total += $amount;
		}

		$node->addText('numbersTotal', sprintf("%.2f", $total));
				
		$view->addNode('content', 'card-ua', 'report.ps.smsdostup.card-table');
		$node = $view->node('card-ua');
		$node->addText('country', 'Украина');
		
		$db->query(
			sprintf(
				"select serviceNumber as 'name', commited, count(*) as 'count' from ps_sms_smsdostup as t1
				where country='ua' and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d and (%s) group by commited, serviceNumber order by commited desc, serviceNumber",
				$start_date, $end_date, $commit_condition
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.smsdostup.card-row'); 
			$nodeP = $node->node('numbersRow_' . $c);
			
			$nodeP->addText('name', $row['name'] . ' [' . $this->paytable['ua'][$row['name']] . ']');
			$nodeP->addText('count', $row['count']);
			
			if($row['commited'] == 0) 
				$nodeP->addText('style', 'class="mistake"');
			
			$amount = (int)$row['count'] * $this->paytable['ua'][$row['name']];
			
			$nodeP->addText('amount', $amount);
			$total += $amount;
		}

		$node->addText('numbersTotal', sprintf("%.2f", $total));

		
		$view->addNode('content', 'card-by', 'report.ps.smsdostup.card-table');
		$node = $view->node('card-by');
		$node->addText('country', 'Беларусь');
		
		$db->query(
			sprintf(
				"select serviceNumber as 'name', commited, count(*) as 'count' from ps_sms_smsdostup as t1
				where country='by' and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d and (%s) group by commited, serviceNumber order by commited desc, serviceNumber",
				$start_date, $end_date, $commit_condition
			)
		);
		
		$c = 0;
		$total = 0;
		while ($row = $db->fetch(ROW_ASSOC)) {

			$node->addNode('numbersData', 'numbersRow_' . ++$c, 'report.ps.smsdostup.card-row'); 
			$nodeP = $node->node('numbersRow_' . $c);
			
			$nodeP->addText('name', $row['name'] . ' [' . $this->paytable['by'][$row['name']] . ']');
			$nodeP->addText('count', $row['count']);
			
			if($row['commited'] == 0) 
				$nodeP->addText('style', 'class="mistake"');
			
			$amount = (int)$row['count'] * $this->paytable['by'][$row['name']];
			
			$nodeP->addText('amount', $amount);
			$total += $amount;
		}

		$node->addText('numbersTotal', sprintf("%.2f", $total));
		
		$view->addNode('content', 'card', 'report.ps.smsdostup.card');
		
		
//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.smsdostup.prefs');
		$node = $node->node('prefs');

		$succ = isset($_SESSION['report.ps.smsdostup.showSucc']) ? (bool)$_SESSION['report.ps.smsdostup.showSucc'] : true;
		$mistake = isset($_SESSION['report.ps.smsdostup.showMistake']) ? (bool)$_SESSION['report.ps.smsdostup.showMistake'] : true;
		
		if ($mistake) 
			$node->addText('Mistakeflag', 'checked="checked"');
		
		if ($succ)
			$node->addText('Succflag', 'checked="checked"');

//	preferences ------------------------------------------------	
		
		$view->display();
	}
	

	public function transactions() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.ps.smsdostup', 'transactions');
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t2.amount_units) as 'putsum'
				from  ps_sms_smsdostup as t1 inner join wallet_history as t2 on t1.op_id = t2.op_id
				where t2.op_tm >= %d and t2.op_tm <= %d", 
				$start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.smsdostup.header');
		$node = $view->node('report-header');
		
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t2.acc_id, t1.evtId, t2.op_tm, t1.serviceNumber, '!' as 'keyword', t1.phone, 
				t1.operator, t2.amount_units, t1.country
				from  ps_sms_smsdostup as t1 inner join wallet_history as t2 on t1.op_id = t2.op_id
				where t2.op_tm >= %d and t2.op_tm <= %d order by t2.op_tm desc limit %d, %d", 
				$start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.smsdostup.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.smsdostup.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('evtId', $row['evtId']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('country', $row['country']);
				$node->addText('serviceNumber', $row['serviceNumber']);
				$node->addText('keyword', $row['keyword']);
				$node->addText('phone', $row['phone']);
				$node->addText('operator', $row['operator']);
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

		$_SESSION['lastPage'] = array('report.ps.smsdostup', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.ps.smsdostup.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.ps.smsdostup.accID'])) {
			$_SESSION['report.ps.smsdostup.accID'] = 0;
		}

//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.smsdostup.prefs-accid');
		$node = $node->node('prefs');

		$accID = isset($_SESSION['report.ps.smsdostup.accID']) ? (int)$_SESSION['report.ps.smsdostup.accID'] : '';
		$node->addText('accID', $accID);

//	preferences ------------------------------------------------
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t2.amount_units) as 'putsum'
				from  ps_sms_smsdostup as t1 inner join wallet_history as t2 on t1.op_id = t2.op_id
				where t2.acc_id = %d and t2.op_tm >= %d and t2.op_tm <= %d", 
				$_SESSION['report.ps.smsdostup.accID'], $start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.ps.smsdostup.header-byaccid');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.ps.smsdostup.accID']);
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', '0.00');
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t2.acc_id, t1.evtID, t2.op_tm, t1.serviceNumber, '!' as 'keyword', t1.phone, 
				t1.operator, t1.country, t2.amount_units
				from  ps_sms_smsdostup as t1 inner join wallet_history as t2 on t1.op_id = t2.op_id
				where  t2.acc_id = %d and t2.op_tm >= %d and t2.op_tm <= %d order by t2.op_tm desc limit %d, %d", 
				$_SESSION['report.ps.smsdostup.accID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.smsdostup.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.smsdostup.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('evtID', $row['evtID']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('country', $row['country']);
				$node->addText('serviceNumber', $row['serviceNumber']);
				$node->addText('keyword', $row['keyword']);
				$node->addText('phone', $row['phone']);
				$node->addText('operator', $row['operator']);
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
			$_SESSION['report.ps.smsdostup.accID'] = (int)$_POST['accID'];

		if (!isset($_POST['notouchflags'])) {
			$_SESSION['report.ps.smsdostup.showSucc'] = isset($_POST['Succ']) ? (bool)$_POST['Succ'] : false;
			$_SESSION['report.ps.smsdostup.showMistake'] = isset($_POST['Mistake']) ? (bool)$_POST['Mistake'] : false;
		}
		
		$_SESSION['report.ps.smsdostup.country'] = isset($_POST['country']) ? trim((string)$_POST['country']) : '';
		
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}
	


	
	private function __fill_country($view) {
		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.smsdostup.prefs-country');
		$node = $node->node('prefs');

		$country = isset($_SESSION['report.ps.smsdostup.log.country']) ? $_SESSION['report.ps.smsdostup.log.country'] : false;
		if ($country)
			$node->addText('select_' . $country, 'selected="selected"');
			
	}
	
}
