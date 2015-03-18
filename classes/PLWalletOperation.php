<?php

class PLWalletOperation extends PutWalletOperation {

	private $actor_id;
	private $currency;
	private $amount;
	private $amount_units;
	private $pl_params;
		
	public function __construct($actor_id, $currency, $amount, $amount_units, $pl_params) {
	
		settype($actor_id, 'integer');
		settype($amount, 'float');
		settype($amount_units, 'float');

		$this->actor_id = $actor_id;
		$this->amount = $amount;
		$this->amount_units = $amount_units;
		$this->currency = $currency;
		
		if(!($pl_params instanceof DataObject)) {
			throw new TypeMismatchException("Invalid pl_params type (expected DataObject) ");
		}
		
		$this->pl_params = $pl_params;
	}
	
	public function perform($transaction_id, $acc_id, $time_stamp) {
		
		$plParams =& $this->pl_params;
		
		$db = DBConnection::get();
		$db->query(sprintf(
		 
		 "insert into ps_platron(op_id, ip_from, pg_salt, pg_order_id, pg_payment_id, pg_amount,
		 						pg_currency, pg_net_amount, pg_ps_amount, pg_ps_full_amount, pg_ps_currency,
		 						pg_payment_system, pg_description, pg_result, pg_payment_date, pg_can_reject,
		 						pg_user_phone, acc_id, forum_id, user_id, user_ip, pg_sig)
		 values(%d, inet_aton('%s'), '%s', %d, %d, %.2f, '%s', %.2f, %.2f, %.2f, '%s', '%s', '%s', %d, '%s', %d, '%s', %d, %d, %d, '%s', '%s')",
		 
		$transaction_id, 
		$plParams->remote_addr,
		$plParams->pg_salt,
		$plParams->pg_order_id,
		$plParams->pg_payment_id,
		$plParams->pg_amount,
		$plParams->pg_currency,
		$plParams->pg_net_amount,
		$plParams->pg_ps_amount,
		$plParams->pg_ps_full_amount,
		$plParams->pg_ps_currency,
		$plParams->pg_payment_system,
		$plParams->pg_description,
		$plParams->pg_result,
		$plParams->pg_payment_date,
		$plParams->pg_can_reject,
		$plParams->pg_user_phone,
		$plParams->acc_id,
		$plParams->forum_id,
		
		(!is_null($plParams->user_id) ? (int)$plParams->user_id : 0),
		
		$plParams->user_ip,
		$plParams->pg_sig
		
		));
		
		$db->query(
			sprintf("insert into wallet_history 
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units, donator)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f, %d)",
			$transaction_id, $acc_id, $time_stamp, 'put', 'platron', $this->actor_id, 0, $this->currency, 
			$this->amount, $this->amount_units,
			(!is_null($plParams->user_id) ? (int)$plParams->user_id : 0)
			)
		);
	}
	
	public function getAmountUnits() {
		return $this->amount_units;
	}
}

?>