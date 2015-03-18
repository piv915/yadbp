<?php
/**
 * Sun Feb 14 19:24:31 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

ini_set('display_errors', 'On');
error_reporting(E_ALL);

require('../bapi.php');
function log_exception() {}

//require('../exceptions.php');
//require('../enums.php');
//
//require('../IReliableCache.php');
//require('../impl/MMCReliable.php');
//require('../IBillingAPI.php');
//require('../impl/BillingAPI.php');
//require('../impl/DataObject.php');

$bapi = new BillingAPI();

$forum_account = $bapi->getSums(AccountType::FORUM, 5148);
var_dump($forum_account);
print sprintf("%.2f", $forum_account);
