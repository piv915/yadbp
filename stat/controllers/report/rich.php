<?php

require_once(FSPATH . '/controllers/.parent/all.php');

class Controller_report_rich extends Controller_report {
	
	public function __construct() {
		parent::__construct();
	}
	
	public function index() {
		$db = DBConnection::get();
		
		$_SESSION['lastPage'] = array('report.rich', 'index');
		
		$threshold = isset($_SESSION['rich.threshold']) ? (int)$_SESSION['rich.threshold'] : 50;
		$liveOnly = isset($_SESSION['rich.liveOnly']) ? (bool)$_SESSION['rich.liveOnly'] : true;
		
		$db->query(
			(
				$liveOnly 
					? sprintf("select sum(balance) from wallet_accounts where have_owner='y' and balance >= %d", $threshold)
					: sprintf("select sum(balance) from wallet_accounts where balance >= %d", $threshold)
			)
		);
		$row = $db->fetch(ROW_NUM);
		$totalSum = sprintf("%.2f", $row[0]);
		
		$db->query(
			(
				$liveOnly 
					? sprintf("select count(*) from wallet_accounts where have_owner='y' and balance >= %d", $threshold)
					: sprintf("select count(*) from wallet_accounts where balance >= %d", $threshold)
			)
		);
		$row = $db->fetch(ROW_NUM); $all = $row[0];
		
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);
		
		$node = $view->node("shared");
		$node->addNode('repSettings', 'prefs', 'report.rich.prefs');
		$node = $node->node('prefs');
		$node->addText('minBalance', $threshold);
		if (!$liveOnly) {
			$node->addText('liveOnlyflag', 'checked="checked"');
		}
		
		
		$view->addNode('content', 'report-header', 'report.rich.header');
		$node = $view->node('report-header');
		$node->addText('threshold', $threshold);
		$node->addText('totalSum', $totalSum);
			
		if ($all) {
			$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
			list($start, $len) = parent::startRow($all, $page);
		
		
			$db->query(
				(
					$liveOnly 
					? sprintf("select acc_id, balance, reserve, have_owner from wallet_accounts 
							where have_owner = 'y' and balance >= %d order by balance desc limit %d, %d",
						$threshold, $start, $len)
						
					: sprintf("select acc_id, balance, reserve, have_owner from wallet_accounts 
							where balance >= %d order by balance desc limit %d, %d",
						$threshold, $start, $len)
				)
			);

			
			$view->addNode('content', 'table', 'report.rich.table');
			$table = $view->node('table');
			$table->addText('rowsfound', $all);
			
			$c = 0;
			while ($row = $db->fetch(ROW_ASSOC)) {
				$table->addNode('tabledata', 'row_' . ++$c, 'report.rich.table-row');
				$node = $table->node('row_' . $c);
				$node->addText('acc_id', $row['acc_id']);
				$node->addText('balance', sprintf("%.2f", $row['balance']));
				$node->addText('reserve', sprintf("%.2f", $row['reserve']));
				if($row['have_owner'] == 'n')
					$node->addText('style', 'class="deleted"');
			}
			
			parent::navigation($all, $page, "?page=%d", $view);
			
		} else {
			$view->addViewAsText('content', 'report.nodata');
		}
		
		$view->addViewAsText('content', 'report.rich.footer');

		$view->display();
	}
	
	public function setprefs() {
		
		if (isset($_POST['minBalance'])) {
			$_SESSION['rich.threshold'] = (int)$_POST['minBalance'];
		}
		
		$_SESSION['rich.liveOnly'] = isset($_POST['liveOnly']) ? false : true;
			 
		$this->redirect('report.rich', 'index');
	}
}

?>