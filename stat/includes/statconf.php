<?php

	define('SYSTIME', time());
	define('USE_SOAP', false);//class_exists('SoapClient'));
	define('CLASSES_PATH', FSPATH . '/classes');
	define('AS_WSDL_PATH', 'http://SiteName.ru/webservice/wsdl');
	define('PFX_WSDL_PATH', 'http://billing.SiteName.ru/wsdl');

	define('DB_SERVER','MySQL');
	define('DB_HOST', 'localhost');
	define('DB_USER', 'armwallet');
	define('DB_PASSWORD', 'kmXsz82j');
	define('DB_NAME_MAIN', 'billing');
	define('DB_PERSISTENT_CONNECTIONS', false);

	define('MYSQL_DUPLICATE_KEY_ERROR', 1062);
	define('MYSQL_FOREIGN_KEY_ERROR', 1452);

	define('ROW_NUM', MYSQL_NUM);
    define('ROW_ASSOC', MYSQL_ASSOC);
    define('ROW_BOTH', MYSQL_BOTH);

	define('OK', 0);
	define('ERROR', -1);


	define('LAYOUT_BASIC', 'layout.grey');
	define('URL_SCHEMA', 'https://');
	define('ROWSONPAGE', 50);


	function __autoload($class_name) {
    	$file = CLASSES_PATH . "/{$class_name}.php";
		if(file_exists($file))
			require_once $file;
	}


?>
