<?php
/**
 * Mon Sep 20 12:02:13 MSD 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require('../impl/DataObject.php');
require('../out/bapi.php');

function log_exception($e) {
	print ('EXCEPTION: "' . $e->getMessage() . ' : ' . $e->getTraceAsString());
//	error_log('EXCEPTION: "' . $e->getMessage() . ' : ' . $e->getTraceAsString());
}

$bapi = new BillingAPI();
$result = $bapi->reserveFunds(AccountType::FORUM, 5148, 1, null, true);

var_dump($result);

print '<pre>class OperationResult {
	const OK = 2;
	const ERROR = 4;
	const YES = 8;
	const NO = 16;
	const ACCCOUNT_EXISTS = 32;
}</pre>';

print 'type = '.AccountType::FORUM;

//$r1 = $bapi->getAvailableSum(AccountType::FORUM, 5148, true);
//var_dump($r1);

$r2 = $bapi->getAvailableSum(AccountType::FORUM, 5148, false);
var_dump($r2);

//
//$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
//
//$rs = $sc->reserveFunds($acc_id, $amount, $notify);
//if($rs == -1)
//	throw new Exception("WS_wallet->reserveFunds($acc_id) reports error;");
//if($rs == 0)
//	return OperationResult::OK;
//

