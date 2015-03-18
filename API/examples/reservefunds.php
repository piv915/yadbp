<pre>
<?php
/**
 * Mon Sep 13 15:38:49 MSD 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require("../out/bapi.php");
require("../impl/DataObject.php");

function log_exception($e) {
	print $e->getMessage() . ' at ' . $e->getTraceAsString();
}

$acc_type = AccountType::FORUM;
$acc_id = 5148;

$amount1 = 10;
$amount2 = 10;

try {
	$bapi = new BillingAPI();

	$rs = $bapi->reserveFunds($acc_type, $acc_id, $amount1, null, 1);
	if($rs == OperationResult::OK) {
		print "Reserve OK ($amount1 credits reserved)\n";
	}
	else {
		print "system can't reserve $amount1 credits from $acc_id.\n";
	}

	$rs = $bapi->reserveFunds($acc_type, $acc_id, -100, null, 1);
	print_r($rs);

	$rv = $bapi->getAvailableSum($acc_type, $acc_id, true);
	var_dump($rv);

//	$rs = $bapi->chargeAccountFromReserve($acc_type, $acc_id, $amount2, null,
//		'user', 2, 1002, true);
//
//	if($rs != OperationResult::OK) {
//		print "error occured during charge from reserve ($amount2)\n";
//	}
//	else {
//		print "Charge OK\n";
//	}



} catch(Exception $e) {
	log_exception($e);
	print "Ошибка системы\n";
	print $e->getMessage();
}

?>
</pre>
