<?php

class RbxWalletOperation extends PutWalletOperation {

	private $actor_id;
	private $amount;
	private $amount_units;
	private $currency;
	private $rbx_params;
	private $forced;
	
	public function __construct($actor_id, $amount, $amount_units, $rbx_params, $forced = false) {
		
		settype($actor_id, 'integer');
		settype($amount, 'float');
		settype($amount_units, 'float');

		$this->actor_id = $actor_id;
		$this->amount = $amount;
		$this->amount_units = $amount_units;
		$this->currency = 30;
		$this->forced = $forced;
		
		if(!($rbx_params instanceof DataObject)) {
			throw new TypeMismatchException("Invalid rbx_params type (expected DataObject) ");
		}
		
		$this->rbx_params = $rbx_params;
	}
	
	public function perform($transaction_id, $acc_id, $time_stamp) {
		
		$invID = $this->rbx_params->invID;
		$rbx_params =& $this->rbx_params;
		$db = DBConnection::get();
		
		$db->query(sprintf("select op_state from ps_rbx_invoices where inv_id = %d", $invID));
		$row = $db->fetch(ROW_ASSOC);
		if(!$row)
			throw new Exception(sprintf("RBX invoice [%d] not found", $invID));
		
		if(!$this->forced && $row['op_state'] != RBX_OPSTATE_COMPLETED) 
			throw new Exception(sprintf("RBX invoice [%d] not have been payed else", $invID));
			
		$db->query(
			sprintf(
				"insert into ps_rbx_operations (op_id, inv_id, forced) values(%d, %d, %d)",
				$transaction_id, $this->rbx_params->invID, $this->forced
			)
		);
		
		$db->query(
			sprintf(
			"insert into wallet_history 
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f)",
			$transaction_id, $acc_id, $time_stamp, 'put', 'robox', $this->actor_id, 0, $this->currency, 
			$this->amount, $this->amount_units
			)
		);
		
	}
	
	public function getAmountUnits() {
		return $this->amount_units;	
	}
}

?>