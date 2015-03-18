<?php

class Application {

	private $template = null;
	private $userID = 0;
	private static $entryP = '';

	public function __construct() {
//		session_set_cookie_params(900);
		session_start();

		if(isset($_SESSION['SID']) && $_SESSION['SID'] == session_id()) {

			$this->userID = (int)$_SESSION['userID'];

		} elseif(isset($_COOKIE['ASID']) && USE_SOAP) {

			try {

				$sc = new MyProjectSoapClient(AS_WSDL_PATH . '/auth.wsdl');
				$userID = $sc->checkSID((string)$_COOKIE['ASID']);
//				var_dump($userID);

				if($userID > 0) {

					$_SESSION['SID'] = session_id();
					$_SESSION['userID'] = $userID;
					$this->userID = $userID;
				}

			} catch (Exception $e) {
				info_log($e->getMessage());
			}
		}

		self::$entryP = $_SERVER['SCRIPT_NAME'];
	}

	public function setTemplate($template) {
		$this->template = (string)$template;
	}

	public function getTemplate() {
		return $this->template;
	}

	public function getUrlSchema() {
		return URL_SCHEMA;
	}

	public function loggedIn() {
		return ($this->userID > 0) ? true : false;
	}

	public function whoami() {
//		var_dump($this->userID);
		return $this->userID;
	}

	public function setUserID($UserID) {
		$this->userID = $UserID;
	}

	public static function entryPoint() {
		return self::$entryP;
	}

}

?>
