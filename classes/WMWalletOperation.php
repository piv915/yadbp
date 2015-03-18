<?php

class WMWalletOperation extends PutWalletOperation {

	private $actor_id;
	private $currency;
	private $amount;
	private $amount_units;
	private $wm_params;
		
	public function __construct($actor_id, $currency, $amount, $amount_units, $wm_params) {
	
		settype($actor_id, 'integer');
		settype($amount, 'float');
		settype($amount_units, 'float');

		$this->actor_id = $actor_id;
		$this->amount = $amount;
		$this->amount_units = $amount_units;
		$this->currency = $currency;
		
		if(!($wm_params instanceof DataObject)) {
			throw new TypeMismatchException("Invalid wm_params type (expected DataObject) ");
		}
		
		$this->wm_params = $wm_params;
	}
	
	public function perform($transaction_id, $acc_id, $time_stamp) {
		
		$wmParams =& $this->wm_params;
		
		$db = DBConnection::get();
		$db->query(sprintf(
		 
		 "insert into ps_webmoney_merchant(op_id, ip_from, LMI_PAYEE_PURSE, LMI_PAYMENT_AMOUNT, 
		 	LMI_PAYMENT_NO, LMI_MODE, LMI_SYS_INVS_NO, LMI_SYS_TRANS_NO, LMI_PAYER_PURSE, 
		 	LMI_PAYER_WM, LMI_HASH, LMI_SYS_TRANS_DATE, user_id)
		 values(%d, inet_aton('%s'), '%s', %.2f, %d, '%s', %d, %d, '%s', '%s', '%s', '%s', %d)",
		 
		$transaction_id, 
		$wmParams->remote_addr,
		$wmParams->LMI_PAYEE_PURSE,
		$this->amount,
		$wmParams->LMI_PAYMENT_NO,
		$wmParams->LMI_MODE,
		$wmParams->LMI_SYS_INVS_NO,
		$wmParams->LMI_SYS_TRANS_NO,
		$wmParams->LMI_PAYER_PURSE,
		$wmParams->LMI_PAYER_WM,
		$wmParams->LMI_HASH,
		$wmParams->LMI_SYS_TRANS_DATE,
		(!is_null($wmParams->user_id) ? (int)$wmParams->user_id : 0)
		
		));
		
		$db->query(
			sprintf("insert into wallet_history 
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units, donator)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f, %d)",
			$transaction_id, $acc_id, $time_stamp, 'put', 'webmoney', $this->actor_id, 0, $this->currency, 
			$this->amount, $this->amount_units,
			(!is_null($wmParams->user_id) ? (int)$wmParams->user_id : 0)
			)
		);
	}
	
	public function getAmountUnits() {
		return $this->amount_units;	
	}
}

?>