<?php
/**
 * Wed Feb 03 23:09:39 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

try {
	$bapi = new BillingAPI();
	if(OperationResult::NO === $bapi->accountExists(AccountType::FORUM, 5148)) {
		$oprs = $bapi->createAccount(AccountType::FORUM, 5148);

		if (!($oprs === OperationResult::OK || $oprs === OperationResult::ACCCOUNT_EXISTS)) {
			log_message("BillingAPI->createAccount returns an error; OP_RESULT = " . $oprs, __FILE__, __LINE__);
			print "неизвестная системная ошибка";
		}
	}
} catch(OutOfRangeException $e) {
	log_exception($e);
	print "системная ошибка";
}
