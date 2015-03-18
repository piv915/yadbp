<pre>
<?php
/**
 * Thu Mar 04 05:39:58 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require('../impl/DataObject.php');
require('../out/bapi.php');
require('../../classes/IMySQL.php');

function log_exception($e) {
error_log('EXCEPTION: "' . $e->getMessage() . ' : ' . $e->getTraceAsString());
}

$bapi = new BillingAPI();
$sapi = new ServiceAPI();

$ss = $sapi->getServicePack(CustomerType::FORUM, 5148);
//var_dump($sapi);
print "ss: "; var_dump($ss);

$forum_sum = $bapi->getAvailableSum(AccountType::FORUM, 419854);
if(is_null($forum_sum)) {
	print "error while fetch forum account sum";
}

var_dump($forum_sum);
print $forum_sum . "\n";

$last_payments = $bapi->getLastPayments(5148, 10);
if($last_payments == OperationResult::ERROR) {
	print "error while get last payments";
}
else {
//	var_dump($last_payments);
	print "last-payments\n";
	foreach ($last_payments as $row) {
		print join(',',$row) . "\n";
	}
}

$top_payers = $bapi->getTopPayers(5148, 10);
if($top_payers == OperationResult::ERROR) {
	print "error while get top payers";
}
else {
	print "top-payers\n";
	foreach ($top_payers as $row) {
		print join(',',$row) . "\n";
	}

}

print "OK";
?>
</pre>
