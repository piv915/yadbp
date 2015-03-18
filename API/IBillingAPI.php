<?php
/**
 * Wed Feb 03 22:07:01 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */


interface IBillingAPI {

	public function apiVersion();

	/**
	 * Создать новый счет.
	 *
	 * {@example examples/createAccount.php 9}
	 * @param AccountType $acc_type
	 * @param integer $acc_id
	 * @param integer $partner_id
	 * @throws OutOfRangeException
	 * @return OperationResult OperationResult::OK,ERROR,ACCCOUNT_EXISTS
	 *
	 */
	public function createAccount($acc_type, $acc_id, $partner_id=1);

	/**
	 * Проверяем, существует ли счет в системе.
	 *
	 * @param AccountType $acc_type
	 * @param integer $acc_id
	 * @throws OutOfRangeException
	 * @return OperationResult OperationResult::YES or OperationResult::NO
	 */
	public function accountExists($acc_type, $acc_id);

	public function getAvailableSum($acc_type, $acc_id, $no_cache=false);

	public function getHistoryPage($acc_type, $acc_id/*, $length, $op_type_flag*/);

	public function getLastPayments($acc_id, $length=null);
	public function getTopPayers($acc_id, $length=null);

	public function markAccountOwnerless($acc_type, $acc_id);

	public function putFunds($acc_type, $acc_id, $amount, $notify);

	public function chargeAccount($acc_type, $acc_id, $amount, $sub_account, $actor, $actor_id, $service_id, $notify);

	public function reserveFunds($acc_type, $acc_id, $amount, $sub_account, $notify);

}

?>
