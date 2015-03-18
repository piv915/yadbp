<?php

interface IWallet {
	
	public function __construct($account_number);
	public function getAvailableFunds();
	public function getBalance();
	public function getReservedFunds();
	public function reserveFunds($amount);
	public function chargeImmediately($op);
	public function chargeFromReserve($amount);
	public function putFunds($op);
	public function getHistoryPage($page_number, $op_type=null);
}
	
class Wallet implements IWallet {
	
	private $db;
	private $account_number;
	
	/**
	* constructor 
	*
	* @access public
	* @throws DBException, OutOfRangeException
	* @param integer $account_number
	*/
	public function __construct($account_number) {
	
		settype($account_number, 'integer');
		if($account_number <= 0)
			throw new OutOfRangeException('Invalid account number');
		
		$this->db = DBConnection::get();
		$db =& $this->db;
		$db->query(sprintf('select acc_id from wallet_accounts where acc_id  = %d', 
				$account_number));

		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");
			
		$this->account_number = $account_number;
	}
	
	/** 
	* returns amount of available funds on account
	*
	* @access public
	* @throws DBException, OutOfRangeException
	*
	* @return float
	*/
	public function getAvailableFunds() {
		
		$db =& $this->db;
		$db->query(sprintf("select balance-reserve as 'funds' from wallet_accounts where acc_id = %d", 
					$this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");
		
		return sprintf("%.2f", $row['funds']);
	}
	
	/** 
	* returns balance amount on account
	*
	* @access public
	* @throws DBException, OutOfRangeException
	*
	* @return float
	*/	
	public function getBalance() {

		$db =& $this->db;
		$db->query(sprintf("select balance as 'funds' from wallet_accounts where acc_id = %d", 
					$this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");
		
		return sprintf("%.2f", $row['funds']);		
		
	}

	/** 
	* returns amount of reserved funds on account
	*
	* @access public
	* @throws DBException, OutOfRangeException
	*
	* @return float
	*/
	public function getReservedFunds() {

		$db =& $this->db;
		$db->query(sprintf("select reserve as 'funds' from wallet_accounts where acc_id = %d", 
					$this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");
		
		return sprintf("%.2f", $row['funds']);	
		
	}
	
	public function getSums() {
		$db =& $this->db;
		
		$db->query(sprintf("select balance, reserve from wallet_accounts where acc_id = %d",
					$this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC)))
			throw new OutOfRangeException("Invalid account number: $account_number");
		
		$do = new DataObject();
		
		$do->balance 	= $row['balance'];
		$do->reserve 	= $row['reserve'];
		$do->available 	= $row['balance'] - $row['reserve'];
		
		return $do;
		
	}
	
	
	/** 
	* reserve funds for further charge
	*
	* @access public
	* @throws DBException, OutOfRangeException
	*
	* @param float $amount
	* @return OK, ERROR
	*/	
	public function reserveFunds($amount) {
		
//		settype($amount, 'float');
		$amount = sprintf("%.2f", $amount);
		
		if($amount == 0)
			throw new OutOfRangeException("Invalid funds amount");
		
		$db =& $this->db;
		$db->query("start transaction");
		$db->query(sprintf("select balance, reserve from wallet_accounts where acc_id = %d", $this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC))) {
			$db->query("commit");
			throw new OutOfRangeException("Invalid account number: $account_number");
		}
		
		
		
		$new_reserve = sprintf("%.2f", $row['reserve'] + $amount);
		
		error_log("INFO: new_reserve $new_reserve for " . $this->account_number);
		
		if($new_reserve >= 0 && $new_reserve <= $row['balance']) {
			$db->query(sprintf("update wallet_accounts set reserve = %.2f where acc_id = %d", 
				$new_reserve, $this->account_number));
			$ret_code = OK;
		} else {
			$ret_code = ERROR;
		}
		$db->query("commit");
		
		error_log("INFO: Wallet->reserveFunds($amount) : [$ret_code]");
		
		return $ret_code;
	}
	
	/**
	 * Return reserved funds to account.
	 *
	 * @access public
	 * @throws DBException, OutOfRangeException
	 * 
	 * @param float $amount
	 * @return OK, ERROR
	 */
//	public function returnReserve($amount) {
//		
//		$amount = sprintf("%.2f", $amount);
//		
//		if($amount == 0)
//			throw new OutOfRangeException("Invalid funds amount");
//		
//		$db =& $this->db;
//		$db->query("start transaction");
//		$db->query(sprintf("select balance, reserve from wallet_accounts where acc_id = %d", $this->account_number));
//		if(false === ($row = $db->fetch(ROW_ASSOC))) {
//			$db->query("commit");
//			throw new OutOfRangeException("Invalid account number: $account_number");
//		}
//		
//		
//		
//		$new_reserve = sprintf("%.2f", $row['reserve'] + $amount);
//		
//		if($new_reserve >= 0 && $new_reserve <= $row['reserve']) {
//			$db->query(sprintf("update wallet_accounts set reserve = %.2f where acc_id = %d", 
//				$new_reserve, $this->account_number));
//			$ret_code = OK;
//		} else {
//			$ret_code = ERROR;
//		}
//		$db->query("commit");
//		
//		error_log("INFO: Wallet->reserveFunds($amount) : [$ret_code]");
//		
//		return $ret_code;
//	}
	
	
	/**
	*
	* @access public
	* @param ChargeWalletOperation $op
	* @throws DBException, TypeMismatchException, OutOfRangeException
	* @return integer operation result
	*
	*/
	public function chargeImmediately($op) {
		if(!($op instanceof ChargeWalletOperation))
			throw new TypeMismatchException("Invalid operation class");
			
		$db =& $this->db;
		
		$db->query("start transaction");
		$db->query(sprintf("select balance-reserve as 'funds' from wallet_accounts where acc_id = %d", $this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC))) {
			$db->query("commit");
			throw new OutOfRangeException("Invalid account number: $account_number");
		}
		
		$amount = $op->getAmountUnits();
		
		try {
			
			$now = time();
			
			$transaction_id = $this->__get_seq_id();
			
			$op->perform($transaction_id, $this->account_number, $now);
			
		} catch (Exception $e) {
			$db->query("rollback");
			throw $e;
		}
		
		if($amount <= $row['funds']) {
			$db->query(sprintf("update wallet_accounts set balance = balance - %.2f, change_tm = %d, last_trans_id = %d where acc_id = %d", 
					$amount, $now, $transaction_id, $this->account_number));
			$ret_code = OK;
		} else {
			$db->query("rollback");
			$ret_code = ERROR;
		}
		$db->query("commit");
		
		return $ret_code;		
	}

	
	public function chargeFromReserve($op) {
	
//		throw new Exception("Method not implemented yet");
		
		if(!($op instanceof ChargeWalletOperation))
			throw new TypeMismatchException("Invalid operation class");
			
		$db =& $this->db;
		
		$db->query("start transaction");
		$db->query(sprintf("select reserve as 'funds' from wallet_accounts where acc_id = %d", $this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC))) {
			$db->query("commit");
			throw new OutOfRangeException("Invalid account number: $account_number");
		}
		
		$amount = $op->getAmountUnits();
		
		try {
			
			$now = time();
			
			$transaction_id = $this->__get_seq_id();
			
			$op->perform($transaction_id, $this->account_number, $now);
			
		} catch (Exception $e) {
			$db->query("rollback");
			throw $e;
		}
		
		if($amount <= $row['funds']) {
			$db->query(sprintf("update wallet_accounts set balance = balance - %.2f, reserve = reserve - %.2f, 
					change_tm = %d, last_trans_id = %d where acc_id = %d", 
					$amount, $amount, $now, $transaction_id, $this->account_number));
			$ret_code = OK;
		} else {
			$db->query("rollback");
			$ret_code = ERROR;
		}
		$db->query("commit");
		
		return $ret_code;		
		
	}
	

	public function putFunds($op) {
		if(!($op instanceof PutWalletOperation))
			throw new TypeMismatchException("Invalid operation class");

		$db =& $this->db;
		$db->query("start transaction");
		$db->query(sprintf("select have_owner from wallet_accounts where acc_id = %d", $this->account_number));
		if(false === ($row = $db->fetch(ROW_ASSOC))) 
			throw new OutOfRangeException("Invalid account number: {$this->account_number}");
		
		if($row['have_owner'] == 'n')
			throw new OutOfRangeException("Account No: {$this->account_number} have not owner");
		
		$amount = $op->getAmountUnits();
		
		try {
			
			$now = time();
			$transaction_id = $this->__get_seq_id();
			
			$op->perform($transaction_id, $this->account_number, $now);
			
			$db->query(sprintf("update wallet_accounts set balance = balance + %.2f, change_tm = %d, last_trans_id = %d where acc_id = %d", 
					$amount, $now, $transaction_id, $this->account_number));
		
		} catch (Exception $e) {
			$db->query("rollback");
			throw $e;
		}
		
		$db->query("commit");
		
		return $transaction_id;
	}

	public function getTopPayers($length) {
		$db =& $this->db;
		
		if($length < 1 || $length > 100) {
			throw new Exception("getTopPayers page length out of range ($length)");
		}
		
		$db->query(sprintf("select donator, sum(amount_units) from wallet_history where donator > 1 and acc_id = %d group by donator order by sum(amount_units) desc limit %d", $this->account_number, $length));
		
		$page = array();
		while ($row = $db->fetch(ROW_ASSOC))
			$page[] = $row;

		return $page;
		
	}
	
	public function getHistoryPage($page_number, $op_type=null, $length=null) {
		
		$op_type = is_null($op_type) ? '%' : $op_type;
		
		$db =& $this->db;
		$db->query(sprintf("select * from wallet_history where op_type='%s' and acc_id = %d order by op_id desc limit %d, %d",
			$op_type, $this->account_number, 
			$page_number * ($length > 0 ? $length : WALLET_HISTORY_PAGE_RECN), 
			($length > 0 ? $length : WALLET_HISTORY_PAGE_RECN)));
		
		$page = array();
		while ($row = $db->fetch(ROW_ASSOC))
			$page[] = $row;

		return $page;
	
	}

	private function __get_seq_id() {

			$db =& $this->db;
			$db->query("insert into _seq_wallet_transaction_id () values ()");
			$db->query("select last_insert_id()");
			$row = $db->fetch(ROW_NUM);
			$id = (int)$row[0];
			$db->query(sprintf("delete from _seq_wallet_transaction_id where id < %d", $id));
			return $id;
		
	}
	
}

?>