<?php

class Controller_login extends Controller {

	public function __construct() {
		parent::__construct();

	}

	public function index() {

		$view = new Template($this->ap->getTemplate());

		$view->addNode("content", "form", "login.form");
		if(isset($_GET['state']) && $_GET['state'] == 'fail')
			$view->form->addViewAsText("error", "login.auth-error");

		$view->form->addViewAsText("tip", "login.tip");

		if (isset($_GET['login'])) {
			$view->form->addText("login", $_GET['login']);
			$view->form->addText("focuspwd", 'true');
		} else
			$view->form->addText("focuspwd", 'false');

		$view->addText("foot", "вход в систему");

		$view->display();
	}

	public function check() {

		$login = (isset($_POST['login'])) ? (string)$_POST['login'] : '';
		$password = (isset($_POST['password'])) ? (string)$_POST['password'] : '';

//		$sc = new MyProjectSoapClient(AS_WSDL_PATH . '/auth.wsdl');
//		$uid = $sc->AuthUser($login, $password, 'PFXSTAT');
		$uid = ($login=='admin' && $password=='ZeleBOBA99') ? 1 : 0;

		if($uid > 0) {

			if(isset($_POST['sp']) && $_POST['sp']) {
				session_set_cookie_params(86400*7);
			}
			session_regenerate_id(true);
			$_SESSION['SID'] = session_id();
			$_SESSION['userID'] = $uid;
			$this->ap->setUserID($uid);

			parent::redirect('index', 'index');

		} else {
			$args['state'] = 'fail';
			$args['login'] = $login;
			parent::redirect('login', 'index', $args);
		}

	}

	public function logout() {

		$_SESSION = array();

		if (isset($_COOKIE[session_name()]))
    		setcookie(session_name(), '', time()-42000, '/');

		session_destroy();

		parent::redirect('index', 'index');
	}
}


?>
