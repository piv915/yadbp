<?php

class WalletManager {

	private function __construct() {}

	/**
	* create account in wallets database
	*
	* @access public
	* @throws OutOfRangeException, DBException, AccountExistsException
	*/
	public static function createAccount($account_number, $partner_id=1) {

		settype($account_number, 'integer');
		if($account_number <= 0)
			throw new OutOfRangeException('Invalid account number');

		$now = time();
		try {
			$db = DBConnection::get();
			$db->query(sprintf("insert into wallet_accounts (acc_id, partner_id, balance, create_tm, change_tm, have_owner) values(%d, %d, 0.0, %d, %d, 'y')",
					$account_number, $partner_id, $now, $now));
		} catch (DBException $e) {
			if($e->getCode() == MYSQL_DUPLICATE_KEY_ERROR)
				throw new AccountExistsException("Account No $account_number already exists", $account_number);
			else
				throw $e;
		}
	}

	/**
	* mark account ownerless
	*
	* @access public
	* @throws OutOfRangeException, DBException
	*/
	public static function markAccountOwnerless($account_number) {

		settype($account_number, 'integer');
		if($account_number <= 0)
			throw new OutOfRangeException('Invalid account number');

		$now = time();
		try {
			$db = DBConnection::get();
			$db->query(sprintf("update wallet_accounts set have_owner='n', change_tm = %d where acc_id = %d",
					$now, $account_number));
			if(0 === $db->affected_rows())
				throw new OutOfRangeException("Invalid account number: $account_number");

		} catch (DBException $e) {
			throw $e;
		}
	}

	public static function markListOwnerless($accounts_list) {

		if(!is_array($accounts_list))
			throw new TypeMismatchException('Invalid parameter type $accounts_list (expected Array)');

		$now = time();
		$db = DBConnection::get();
		$db->query("start transaction");

		foreach ($accounts_list as $user_id) {
			$db->query(sprintf("update wallet_accounts  set have_owner='n', change_tm = %d where acc_id = %d",
						$now, $user_id));
		}

		$db->query("commit");
	}
}

?>