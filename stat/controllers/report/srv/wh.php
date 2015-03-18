<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_srv_wh extends Controller_report {
	
	private $servRegisty;
	
	public function __construct() {
		parent::__construct();
		$this->servRegistry = array(
			0 => 'пополнение',
			1001 => 'откл.рекламы',
			1002 => 'рег.домена',
			1003 => 'продл.домена',
			1004 => 'поля профиля',
			1005 => 'рез.копии',
			1006 => 'рассылки',
			1007 => 'лидер',
			1008 => 'смена номера',
			1009 => 'дизайн профайла',
			1010 => 'невидимость',
			1011 => 'реклама',
			1012 => 'перевод кредитов',
			1013 => 'досрочный форум',
			1014 => 'форум без фото',
			1015 => 'выход из бана',
			1016 => 'доступ анонимам',
			1017 => 'исключение из поиска',
		);
	}
	
	public function index() {
		parent::redirect('report.srv.wh', 'transactions');
	}
	

	public function transactions() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.srv.wh', 'transactions');
		
		if (isset($_GET['srvID'])) {
			 $_SESSION['report.srv.wh.srvID'] = (int)$_GET['srvID'];
		}
		if(!isset($_SESSION['report.srv.wh.srvID']))
			$_SESSION['report.srv.wh.srvID'] = -1;
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 

		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'chargesum' from wallet_history as t1
				where op_type='charge' and service_id = %d and t1.op_tm >= %d and t1.op_tm <= %d",
				$_SESSION['report.srv.wh.srvID'], $start_date, $end_date
			)
		);
		
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$chargeSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.srv.wh.header');
		$node = $view->node('report-header');
		$node->addText('serviceName', $this->servRegistry[$_SESSION['report.srv.wh.srvID']]);
		
		$node->addText('putSum', '0.00');
		$node->addText('chargeSum', $chargeSum);
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t1.service_id, t1.amount_units, t1.op_actor as 'actor', t1.op_actor_id, t1.op_type
				from wallet_history as t1
				where op_type='charge' and service_id = %d and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d",
				$_SESSION['report.srv.wh.srvID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.srv.wh.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.srv.wh.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				
				
//				$node->addText('service_name', $this->servRegistry[$row['service_id']]);
				$node->addText('service_name', 
					($row['actor'] == 'remit') 
					? (
						($row['op_type']=='put') 
							? ($this->servRegistry[1012] . ' от # ' . $row['op_actor_id'])
							: ($this->servRegistry[$row['service_id']] . ' для # '. $row['op_actor_id'])
					  )
					:
					(isset($this->servRegistry[$row['service_id']]) 
					? $this->servRegistry[$row['service_id']] 
					: '&mdash;')
				);
				
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

		$_SESSION['lastPage'] = array('report.srv.wh', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.srv.wh.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.srv.wh.accID'])) {
			$_SESSION['report.srv.wh.accID'] = 0;
		}
		
		if (isset($_GET['srvID'])) {
			 $_SESSION['report.srv.wh.srvID'] = (int)$_GET['srvID'];
		}
		if(!isset($_SESSION['report.srv.wh.srvID']))
			$_SESSION['report.srv.wh.srvID'] = -1;
//	preferences ------------------------------------------------

		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.srv.wh.prefs-accid');
		$node = $node->node('prefs');

		$accID = isset($_SESSION['report.srv.wh.accID']) ? (int)$_SESSION['report.srv.wh.accID'] : '';
		$node->addText('accID', $accID);

//	preferences ------------------------------------------------
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$db->query(
			sprintf(
				"select count(*), sum(t1.amount_units) as 'chargesum'
				from wallet_history as t1
				where op_type='charge' and t1.acc_id = %d and service_id = %d and t1.op_tm >= %d and t1.op_tm <= %d",
				$_SESSION['report.srv.wh.accID'], $_SESSION['report.srv.wh.srvID'], $start_date, $end_date
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$chargeSum 	= sprintf("%.2f", $row[1]);
		$view->addNode('content', 'report-header', 'report.srv.wh.header-byaccid');
		$node = $view->node('report-header');
		$node->addText('serviceName', $this->servRegistry[$_SESSION['report.srv.wh.srvID']]);
		$node->addText('accountID', $_SESSION['report.srv.wh.accID']);
		
		$node->addText('putSum', '0.00');
		$node->addText('chargeSum', $chargeSum);
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
			$db->query(
				sprintf(
				"select t1.op_id, t1.acc_id, t1.op_tm, t1.service_id, t1.amount_units, t1.op_actor as 'actor', t1.op_actor_id, t1.op_type 
				from wallet_history as t1
				where op_type='charge' and t1.acc_id = %d and service_id = %d and t1.op_tm >= %d and t1.op_tm <= %d order by t1.op_tm desc limit %d, %d",
				$_SESSION['report.srv.wh.accID'], $_SESSION['report.srv.wh.srvID'], $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.srv.wh.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {
				
				$table->addNode('tabledata', 'row_' . ++$c, 'report.srv.wh.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				
				
//				$node->addText('service_name', $this->servRegistry[$row['service_id']]);
				$node->addText('service_name', 
					($row['actor'] == 'remit') 
					? (
						($row['op_type']=='put') 
							? ($this->servRegistry[1012] . ' от # ' . $row['op_actor_id'])
							: ($this->servRegistry[$row['service_id']] . ' для # '. $row['op_actor_id'])
					  )
					:
					(isset($this->servRegistry[$row['service_id']]) 
					? $this->servRegistry[$row['service_id']] 
					: '&mdash;')
				);
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
			$_SESSION['report.srv.wh.accID'] = (int)$_POST['accID'];
			
	
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}
}
