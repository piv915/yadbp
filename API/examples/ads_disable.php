<pre>
<?php
/**
 * Tue Mar 02 03:02:58 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */
exit();

//$conn = MySQLConnector::getConnection(DbNamedConnections::get("bs", MySQLServerRole::SLAVE), MySQLUseMode::SHARED)
ini_set('display_errors', 'On');
error_reporting(E_ALL);

//require('../bapi.php');
function log_exception() {}
class DBException extends Exception {}

require('../exceptions.php');
require('../enums.php');

require('../lib/logger.php');
require('../lib/date_util.php');
require('../lib/IMySQL.php');



require('../IReliableCache.php');
require('../impl/MMCReliable.php');
require('../IBillingAPI.php');
require('../impl/BillingAPI.php');
require('../impl/DataObject.php');
require('../impl/ServiceAPI.php');

$conn = MySQLConnector::getConnection(DbNamedConnections::get("services"));

$service = ServiceFactory::getService(1001, array('partner_id' => 1));
print_r($service);

$av = $service->available(CustomerType::FORUM, 19);
var_dump($av);

$cust_id  = 803534;

$cost = $service->getCost(CustomerType::FORUM, $cust_id, array('proportional' => true));
var_dump($cost);
print_r($cost);

print "OK";

?>
</pre>
