<?php
/**
 * Sat Feb 13 19:43:03 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require('../bapi.php');
function log_exception() {}

$bapi = new BillingAPI();

try {
	$sc = new SoapClient('http://billing.SiteName.ru/wsdl/wallet.wsdl', array(
			'connection_timeout' => 3,
			'exceptions' => true,
			'cache_wsdl' => WSDL_CACHE_MEMORY,
		));
	$sc->createAccount(5148);
//	$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);

} catch (SoapFault $e) {
	print $e->detail;
}
