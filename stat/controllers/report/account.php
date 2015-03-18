<?php

require_once(FSPATH . '/controllers/.parent/all.php');


class Controller_report_account extends Controller_report {
	
	private $servRegisty;
	
	public function __construct() {
		parent::__construct();
		
		$this->servRegistry = array(
			0 => 'пополнение',
			1001 => 'откл.рекламы',
			1002 => 'рег.домена',
			1003 => 'продл.домена',
			1004 => 'поля проф.',
			1005 => 'рез.копии',
			1006 => 'рассылки',
			1007 => 'лидер',
			1008 => 'смена номера',
			1009 => 'дизайн профайла',
			1010 => 'невидимость',
			1011 => 'реклама',
			1012 => 'перевод',
			1013 => 'досрочный форум',
			1014 => 'форум без фото',
			1015 => 'выход из бана',
			1016 => 'доступ анонимам',
			1017 => 'исключение из поиска',
		);
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.account', 'index');
		if (isset($_GET['accID'])) 
			$_SESSION['report.account.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.account.accID'])) {
			$_SESSION['report.account.accID'] = 0;
		}

		$start_date = $this->start_date;
		$end_date   = $this->end_date; 
		
		$view->addNode('content', 'report-header', 'report.account.card-header');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.account.accID']);

		$db->query(
			sprintf("select * from wallet_accounts where acc_id = %d", $_SESSION['report.account.accID'])
		);
		
		$row = $db->fetch(ROW_ASSOC);
		if(!$row) {
			$view->addViewAsText('content', 'report.nodata');
		} else {
			$view->addNode('content', 'card', 'report.account.card');
			$node = $view->node('card');
			
			$node->addText('createTm', date('d/m/Y H:i:s', $row['create_tm']));

			$node->addText('lastOp', ($row['last_trans_id'] > 0)
				? '#' . $row['last_trans_id'] . ', ' . date('d/m/Y H:i:s', $row['change_tm'])
				: 'нет'
			);

			$node->addText('balance', sprintf("%.2f", $row['balance']));
			$node->addText('reserve', sprintf("%.2f", $row['reserve']));
			$node->addText('accState', ($row['have_owner'] == 'y') ? '<span class="put">Активен</span>' : '<span class="red">Удален</span>');

			$db->query(
				sprintf("select t1.service_id as 'name', count(*) as 'count', sum(t1.amount_units) as 'amount' from wallet_history as t1
						where t1.op_type='charge' and t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d
						group by t1.service_id order by `count` desc", 
						$_SESSION['report.account.accID'], $start_date, $end_date
				)
			);

			$c = 0;
			$total = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$node->addNode('servData', 'servRow_' . ++$c, 'report.account.card-row'); 
				$nodeP = $node->node('servRow_' . $c);
				
				$name = isset($this->servRegistry[$row['name']]) ? $this->servRegistry[$row['name']] : $row['name'];
				
				$nodeP->addText('name', '<a href="/stat/index.php/redirect.srv/?type=byAccID&accID=' . 
					$_SESSION['report.account.accID'] . '&srvID=' . $row['name'] . '">' .$name .'</a>');
					
				$nodeP->addText('count', $row['count']);
				$nodeP->addText('amount', sprintf("%.2f", $row['amount']));
				$total += $row['amount'];
			}

			$node->addText('servTotal', sprintf("%.2f", $total));
			
			$db->query(
				sprintf("select t2.name, t1.op_actor_id, t2.ps_type, count(*) as 'count', sum(t1.amount_units) as 'amount' from wallet_history as t1, ps_actor_list as t2 
						where ((t1.op_actor_id = t2.id) or (t1.op_actor = t2.ps_type and t2.ps_type='remit')) 
						and t1.op_type='put' and t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d
						group by t2.name order by t2.name", 
						$_SESSION['report.account.accID'], $start_date, $end_date
				)
			);

			$c = 0;
			$total = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$node->addNode('payData', 'payRow_' . ++$c, 'report.account.card-row'); 
				$nodeP = $node->node('payRow_' . $c);
				
				$name = isset($this->servRegistry[$row['name']]) ? $this->servRegistry[$row['name']] : $row['name'];
				
				$nodeP->addText('name', 
					'<a href="/stat/index.php/redirect.ps/?type=byAccId&id=' . ($row['ps_type']=='remit' ? 4001 : $row['op_actor_id']) . '&accID=' . 
					$_SESSION['report.account.accID'] . '">' . $name . '</a>');
				$nodeP->addText('count', $row['count']);
				$nodeP->addText('amount', sprintf("%.2f", $row['amount']));
				/*if($row['ps_type']!='remit') */$total += $row['amount'];
			}
			
			
			$db->query(
				sprintf("select count(*) as 'count', sum(t1.amount_units) as 'amount' from wallet_history as t1
					where t1.op_actor='moder' and t1.op_type='put' and t1.acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d",
					$_SESSION['report.account.accID'], $start_date, $end_date
				)
			);
			
			$row = $db->fetch(ROW_ASSOC);
			if($row['count'] > 0) {
				
				$node->addNode('payData', 'payRow_moder', 'report.account.card-row'); 
				$nodeP = $node->node('payRow_moder');
				
				$nodeP->addText('name', '<a href="/stat/index.php/redirect.ps/?type=byAccId&id=moder&accID=' .
					$_SESSION['report.account.accID'] . '">модераторы</a>');
				$nodeP->addText('count', $row['count']);
				$nodeP->addText('amount', sprintf("%.2f", $row['amount']));
				$total += $row['amount'];
			}
			
			$node->addText('payTotal', sprintf("%.2f", $total));
		}
		
		$this->__fill_prefs($view);
		$view->display();
	}
	
	public function setprefs() {
		
		if (isset($_POST['accID'])) 
			$_SESSION['report.account.accID'] = (int)$_POST['accID'];
		
		$_SESSION['report.account.showPut'] = isset($_POST['put']) ? (bool)$_POST['put'] : false;
		$_SESSION['report.account.showCharge'] = isset($_POST['charge']) ? (bool)$_POST['charge'] : false;
		
		$this->redirect($_SESSION['lastPage'][0], $_SESSION['lastPage'][1]);
	}

	public function byAccID() {
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$_SESSION['lastPage'] = array('report.account', 'byAccID');
		if (isset($_GET['accID'])) 
			$_SESSION['report.account.accID'] = (int)$_GET['accID'];
		
		if (!isset($_SESSION['report.account.accID'])) {
			$_SESSION['report.account.accID'] = 0;
		}
		
		$charges = isset($_SESSION['report.account.showCharge']) ? (bool)$_SESSION['report.account.showCharge'] : true;
		$puts = isset($_SESSION['report.account.showPut']) ? (bool)$_SESSION['report.account.showPut'] : true;
		
		$op_types = array();
		if($charges) $op_types[] = "'charge'";
		if($puts) $op_types[] = "'put'";
		
		$op_types_condition  = count($op_types) ? '(op_type in(' . join(', ', $op_types) . '))' : '(0)';
		
		$start_date = $this->start_date;
		$end_date   = $this->end_date; 

		$db->query(
			sprintf(
				"select count(*), sum(if(op_type='put', amount_units, 0)) as 'putsum',
				sum(if(op_type='charge', amount_units, 0)) as 'chargesum' from wallet_history where acc_id = %d and op_tm >= %d and op_tm <= %d and %s", 
				$_SESSION['report.account.accID'], $start_date, $end_date, $op_types_condition
			)
		);	
		
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		$putSum 	= sprintf("%.2f", $row[1]);
		$chargeSum 	= sprintf("%.2f", $row[2]);
		
		$view->addNode('content', 'report-header', 'report.account.header');
		$node = $view->node('report-header');
		$node->addText('accountID', $_SESSION['report.account.accID']);
		$node->addText('putSum', $putSum);
		$node->addText('chargeSum', $chargeSum);
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
			
			$db->query(
				sprintf(
					"select t1.op_id, t1.op_tm, t1.op_type, t1.op_actor as 'actor', t1.op_actor_id, t1.service_id, t3.name as 'currency', t1.amount_currency, 
					t1.amount_units from wallet_history as t1, ps_currencies as t3 where t1.currency_id = t3.id and 
					acc_id = %d and t1.op_tm >= %d and t1.op_tm <= %d and %s order by op_tm desc limit %d, %d", 
					$_SESSION['report.account.accID'], $start_date, $end_date, $op_types_condition, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.account.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.account.table-row');
				$node = $table->node('row_' . $c);
				$node->addText('op_id', $row['op_id']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['op_tm']));
				$node->addText('op_type', ($row['op_type'] == 'put') ? '+' : '&ndash;');
				$node->addText('style', 'class="' . $row['op_type'] . '"');
				$node->addText('actor', $row['actor']);
				$node->addText('currency', $row['currency']);
				$node->addText('amount', sprintf("%.2f", $row['amount_currency']));
				$node->addText('amount_units', sprintf("%.2f", $row['amount_units']));
				
				$node->addText('remark',
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
			}

			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}		
		
		$this->__fill_prefs($view);
		$view->display();
		
	}
	
	private function __fill_prefs($view) {
		
		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.account.prefs');
		$node = $node->node('prefs');

		$charges = isset($_SESSION['report.account.showCharge']) ? (bool)$_SESSION['report.account.showCharge'] : true;
		$puts = isset($_SESSION['report.account.showPut']) ? (bool)$_SESSION['report.account.showPut'] : true;
		$accID = isset($_SESSION['report.account.accID']) ? (int)$_SESSION['report.account.accID'] : '';
		
		if ($charges) 
			$node->addText('chargeflag', 'checked="checked"');
		
		if ($puts) 
			$node->addText('putflag', 'checked="checked"');
			
		$node->addText('accID', $accID);
	}
}

?>