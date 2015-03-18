<?php
/**
 * Sun Mar 21 00:48:18 MSK 2010
 * Author: Vasiliy.Ivanovich@gmail.com
 *
 * $Id$
 *
 */


require_once(FSPATH . '/controllers/.parent/all.php');
class Controller_control_balance extends Controller_report {

	public function __construct() {
		parent::__construct();

	}

	public function index() {
		$view = new Template($this->ap->getTemplate());
		parent::__shared_paramsView($view);

		$acc_id = 0;
		if (isset($_GET['accID']))
			/*$_SESSION['report.account.accID'] =*/ $acc_id = (int)$_GET['accID'];



		$view->addNode('content', 'plus-form', 'control.balance.form');
		$node = $view->node('plus-form');

		if($acc_id > 0) {
			$node->addText('accID', $acc_id);
		}

		if(isset($_SESSION['control.balance.error'])) {
			$node->addText('error', $_SESSION['control.balance.error']);
			unset($_SESSION['control.balance.error']);
		}



		$view->display();
	}

	public function post() {
//		var_dump($_POST);
		$params = $this->putMoney($_POST);
//		var_dump($params);
//		if($params['result'] == 'error')
			$_SESSION['control.balance.error'] = $params['text'];

		parent::redirect('control.balance', 'index', null);

	}

	private function putMoney($_req) {

		$accID = isset($_req['accID']) ? (int)$_req['accID'] : 0;
		$amount = isset($_req['amount']) ? sprintf("%.2f", $_req['amount']) : 0;
		$moderID = 1;//(int)$this->session['uid'];
		$donator = isset($_req['donator']) ? intval($_req['donator']) : 0;

		if ($accID && $amount) {
			$sc = new SoapClient(PFX_WSDL_PATH.'/wallet.wsdl',
				array('connection_timeout', 3));

			try {
				$sc->grantFunds($accID, $moderID, $amount, true, $donator);

				$params['result'] = 'success';
				$params['text'] = "Счет $accID пополнен на $amount кредитов";

			} catch (Exception $e) {
				$params['result'] = 'error';
				$params['text'] = 'Системная ошибка: ' . $e->getMessage();
			}
		} else {
				$params['result'] = 'error';
				$params['text'] = 'Не указан номер счета или сумма';
		}

		return $params;
	}
}

?>
