<?php

require_once(FSPATH . '/controllers/.parent/all.php');

class Controller_report_ps_ifree_log extends Controller_report {

	private $price;
	
	public function __construct() {
		parent::__construct();
		
		$this->paytable = array(
			'ru' => array(
				7733 => 150,
				4161 => 85,
				4449 => 65,
				4448 => 47,
				4447 => 21,
				4446 => 16,
				4445 => 10,
				4444 => 5,
				4443 => 2
			),
			'ua' => array(
				4161 => 47,
				4449 => 25,
				4448 => 19,
				4447 => 12,
				4446 => 12,
				4445 => 5,
				4444 => 3,
				4443 => 1
			),
			'kz' => array(
				4161 => 70,
				4446 => 15,
				4444 => 6,
				4449 => 47,
			)
		);
	}
	
	public function index() {
		
		$db = DBConnection::get();
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		$this->__fill_prefs($view);
		
		$_SESSION['lastPage'] = array('report.ps.ifree.log', 'index');

		$start_date = $this->start_date;
		$end_date   = $this->end_date;
		
		$succ = isset($_SESSION['report.ps.ifree.log.showSucc']) ? (bool)$_SESSION['report.ps.ifree.log.showSucc'] : true;
		$mistake = isset($_SESSION['report.ps.ifree.log.showMistake']) ? (bool)$_SESSION['report.ps.ifree.log.showMistake'] : true;

		$abExact = isset($_SESSION['report.ps.ifree.log.abExact']) ? (bool)$_SESSION['report.ps.ifree.log.abExact'] : false;
		$smsExact = isset($_SESSION['report.ps.ifree.log.smsExact']) ? (bool)$_SESSION['report.ps.ifree.log.smsExact'] : false;
		
		$abonent = isset($_SESSION['report.ps.ifree.log.abonent']) ? $_SESSION['report.ps.ifree.log.abonent'] : false;
		$smsText = isset($_SESSION['report.ps.ifree.log.smsText']) ? $_SESSION['report.ps.ifree.log.smsText'] : false;
		
		$prefix = isset($_SESSION['report.ps.ifree.log.prefix']) ? $_SESSION['report.ps.ifree.log.prefix'] : false;
		$country = isset($_SESSION['report.ps.ifree.log.country']) ? $_SESSION['report.ps.ifree.log.country'] : false;
		
		$condition = '1 ';
		
		if (strlen($abonent)) {
			$abonent = $db->escape($abonent);
			$condition .= (
				($abExact) 
				? "and phone = '$abonent' "
				: "and phone like '%$abonent%' "
			);
		}

		if (strlen($smsText)) {
			$smsText = $db->escape($smsText);
			$condition .= (
				($smsExact) 
				? "and smsText = '$smsText' "
				: "and smsText like '%$smsText%' "
			);
		}

//		if (strlen($prefix)) {
//			$prefix = $db->escape($prefix);
//			$condition .=  "and keyword='$prefix' ";
//		}
		
		if (strlen($country)) {
			$country = $db->escape($country);
			$condition .=  "and country='$country' ";
		}
		
		if ($succ && !$mistake) {
			$condition .= 'and commited = 1 ';
		}
		if ($mistake && !$succ) {
			$condition .= 'and commited = 0 ';
		}
		if(!$succ && !$mistake) {
			$condition .= 'and 0';	
		}
		
		$db->query(
			sprintf(
			"select country, serviceNumber, count(*) from  ps_sms_ifree as t1 
			where (%s) and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d group by country, serviceNumber",
			$condition, $start_date, $end_date
			)
		);
		
		$all = 0; $putSum = 0;
		while ($row = $db->fetch(ROW_NUM)) {
			$all += $row[2];
			$sums = isset($this->paytable[$row[0]]) ? $this->paytable[$row[0]] : $this->paytable['default'];
			$putSum += $sums[$row[1]] * $row[2];
		} 

		$view->addNode('content', 'report-header', 'report.ps.ifree.log.header');
		$node = $view->node('report-header');
		
		$node->addText('putSum', sprintf("%.2f", $putSum));
		$node->addText('chargeSum', '0.00');
		
		
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
	
			$db->query(
				sprintf(
				"select t1.op_id, t1.evtID, t1.me_recv_tm, t1.serviceNumber, '!' as 'keyword', t1.phone, t1.operator, 
				t1.smsText, t1.commited, t1.country
				from  ps_sms_ifree as t1 
				where (%s) and t1.me_recv_tm >= %d and t1.me_recv_tm <= %d order by t1.me_recv_tm desc limit %d, %d", 
				$condition, $start_date, $end_date, $start, $len
				)
			);
			
			$view->addNode('content', 'table', 'report.ps.ifree.log.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {

				$table->addNode('tabledata', 'row_' . ++$c, 'report.ps.ifree.log.table-row');
				$node = $table->node('row_' . $c);

				$node->addText('op_id', $row['op_id']);
				$node->addText('evtID', $row['evtID']);
				$node->addText('op_tm', date('d/m/y H:i:s' , $row['me_recv_tm']));
				$node->addText('serviceNumber', $row['serviceNumber']);
				$node->addText('country', $row['country']);
				$node->addText('keyword', $row['keyword']);
				$node->addText('phone', $row['phone']);
				$node->addText('operator', $row['operator']);
				$node->addText('smsText', '<a href="/stat/index.php/report.account/?accID=' . 
					(int)$row['smsText'] . '">' . htmlspecialchars($row['smsText']) . '</a>');
				
				if($row['commited'] == '0')
					$node->addText('style', 'class="mistake"');
					
			}

			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}
		
		
		$view->display();
	}
	
	public function setprefs() {
		
		$_SESSION['report.ps.ifree.log.showSucc'] = isset($_POST['Succ']) ? (bool)$_POST['Succ'] : false;
		$_SESSION['report.ps.ifree.log.showMistake'] = isset($_POST['Mistake']) ? (bool)$_POST['Mistake'] : false;
		
		$_SESSION['report.ps.ifree.log.abExact'] = isset($_POST['abExact']) ? (bool)$_POST['abExact'] : false;
		$_SESSION['report.ps.ifree.log.smsExact'] = isset($_POST['smsExact']) ? (bool)$_POST['smsExact'] : false;
		
		$_SESSION['report.ps.ifree.log.prefix'] = isset($_POST['prefix']) ? trim((string)$_POST['prefix']) : '';
		$_SESSION['report.ps.ifree.log.country'] = isset($_POST['country']) ? trim((string)$_POST['country']) : '';
		
		$_SESSION['report.ps.ifree.log.abonent'] = isset($_POST['abonent']) ? trim((string)$_POST['abonent']) : '';
		$_SESSION['report.ps.ifree.log.smsText'] = isset($_POST['smsText']) ? trim((string)$_POST['smsText']) : '';
		
		parent::redirect('report.ps.ifree.log', 'index');
		
	}
	
	private function __fill_prefs($view) {
		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.ps.ifree.log.prefs');
		$node = $node->node('prefs');
		
		
		$succ = isset($_SESSION['report.ps.ifree.log.showSucc']) ? (bool)$_SESSION['report.ps.ifree.log.showSucc'] : true;
		$mistake = isset($_SESSION['report.ps.ifree.log.showMistake']) ? (bool)$_SESSION['report.ps.ifree.log.showMistake'] : true;
		
		if ($mistake) 
			$node->addText('Mistakeflag', 'checked="checked"');
		
		if ($succ)
			$node->addText('Succflag', 'checked="checked"');
			
		$abExact = isset($_SESSION['report.ps.ifree.log.abExact']) ? (bool)$_SESSION['report.ps.ifree.log.abExact'] : false;
		$smsExact = isset($_SESSION['report.ps.ifree.log.smsExact']) ? (bool)$_SESSION['report.ps.ifree.log.smsExact'] : false;
		
		if ($abExact) 
			$node->addText('abExactflag', 'checked="checked"');
		
		if ($smsExact)
			$node->addText('smsExactflag', 'checked="checked"');			
		
			
		$abonent = isset($_SESSION['report.ps.ifree.log.abonent']) ? $_SESSION['report.ps.ifree.log.abonent'] : '';
		$smsText = isset($_SESSION['report.ps.ifree.log.smsText']) ? $_SESSION['report.ps.ifree.log.smsText'] : '';
		
		$country = isset($_SESSION['report.ps.ifree.log.country']) ? $_SESSION['report.ps.ifree.log.country'] : false;
		if ($country)
			$node->addText('select_' . $country, 'selected="selected"');
			
		$node->addText('abonent', htmlspecialchars($abonent));
		$node->addText('smsText', htmlspecialchars($smsText));
	}
}