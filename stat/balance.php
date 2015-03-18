<?php

//	phpinfo();

	ini_set('display_errors', 'On');

	define('LOCAL_PATH', $_SERVER['DOCUMENT_ROOT']);
	$__LOCAL_PATH = LOCAL_PATH;

	require_once('../conf/mainconf.php');
	require_once('../system/functions.php');
	require_once('../controllers/Controller.control.php');

	sys_preInit();

	$lom = new UserLogin();
	$session = $lom->get_session();


class Controller_control_balance extends Controller_control {

	public function __construct($lang) {
		$this->name = 'пополнение счета';
		$this->role = 'PFXBALANCE';
		$this->self = '/control/balance.php';
		parent::__construct($lang);
	}

	public function viewForm() {
		$db = $this->db;
		parent::_set_shared_params();

		$this->view->addText('handlerURL', $this->self);
		$this->view->addNode('workspaceContent', 'balanceForm', 'control.balance.form');

		$this->view->display();
	}

	public function putMoney($_req) {

		$accID = isset($_req['accID']) ? (int)$_req['accID'] : 0;
		$amount = isset($_req['amount']) ? (int)$_req['amount'] : 0;
		$moderID = (int)$this->session['uid'];

		if ($accID && $amount) {
			$sc = new MyProjectSoapClient(WALLET_WSDL_URI,
				array('local_cert' => WALLET_SOAP_CERTIFICATE, 'passphrase' => WALLET_SOAP_CERT_PASS,
						'connection_timeout', SOAP_TIMEOUT));
			try {
				$sc->grantFunds($accID, $moderID, $amount, true);

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

		parent::viewRedirect($params);
	}
}

try {

	$ctl = new Controller_control_balance($lang);

	if(isset($_POST['putMoney'])) {
		$ctl->putMoney($_POST);
	} else {
		$ctl->viewForm();
	}


} catch (Exception $e) {
	print $e->getMessage();
}

?>
