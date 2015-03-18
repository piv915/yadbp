<?php
/*
 * (C) 2009
 *  Author: Vasiliy.Ivanovich@gmail.com
 *
 *  $Id: RemittanceWalletOperation.php,v 1.2 2009/06/07 07:52:57 username Exp $
 */

class RemittanceWalletOperation //extends WalletOperation
{
	private $amount_units;
	private $actor_id;
	private $agent_id;

	public function __construct($actor_id, $agent_id, $amount) {

		settype($amount, 'float');
		settype($actor_id, 'integer');
		settype($agent_id, 'integer');

		$this->amount_units = $amount;
		$this->actor_id = $actor_id;
		$this->agent_id = $agent_id;
	}

	public function perform($transaction_id, $acc_id, $time_stamp) {
		$db = DBConnection::get();

		$db->query(
			sprintf("select balance-reserve as funds, have_owner from wallet_accounts where acc_id = %d", $acc_id
			)
		);
		$row = $db->fetch(ROW_ASSOC);
		if(!$row)
			throw new AccountExistsException("Account #$acc_id not exists while try to remit funds from $acc_id to {$this->agent_id}");
		if($row['have_owner']!='y')
			throw new AccountExistsException("Account #$acc_id mark ownerless while try to remit funds from $acc_id to {$this->agent_id}");
		$max_funds = sprintf("%.2f", $row['funds']);
		settype($max_funds, 'float');
		if($this->amount_units > $max_funds)
			throw new OutOfRangeException("Not enought money [available=$max_funds], while try to remit funds from $acc_id to {$this->agent_id}");
		if($this->amount_units <= 0)
			throw new OutOfRangeException("Negative or null transfer attempt, while try to remit funds from $acc_id to {$this->agent_id}");
		$db->query(
			sprintf("select have_owner from wallet_accounts where acc_id = %d", $this->agent_id
			)
		);
		$row = $db->fetch(ROW_ASSOC);
		if(!$row or ($row && $row['have_owner']!='y'))
			throw new AccountExistsException("Account #{$this->agent_id} not exists while try to remit funds from $acc_id to {$this->agent_id}");

		$db->query(
			sprintf("insert into wallet_history
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f)",
			$transaction_id[0], $acc_id, $time_stamp, 'charge', 'remit', $this->agent_id, 1012, CURRENCY_UNIT,
			$this->amount_units, $this->amount_units
			)
		);

		$db->query(
			sprintf("insert into wallet_history
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f)",
			$transaction_id[1], $this->agent_id, $time_stamp, 'put', 'remit', $this->actor_id, 0, CURRENCY_UNIT,
			$this->amount_units, $this->amount_units
			)
		);

		$db->query(sprintf("update wallet_accounts set balance = balance - %.2f, change_tm = %d, last_trans_id = %d where acc_id = %d",
		$this->amount_units, $time_stamp, $transaction_id[0], $this->actor_id));

		$db->query(sprintf("update wallet_accounts set balance = balance + %.2f, change_tm = %d, last_trans_id = %d where acc_id = %d",
		$this->amount_units, $time_stamp, $transaction_id[1], $this->agent_id));

		error_log("TRANSACTION: *{$transaction_id[0]},{$transaction_id[1]} REMIT from $acc_id [sum={$this->amount_units}, agent_id={$this->agent_id}]");
	}

	public function getAmountUnits() {
		return $this->amount_units;
	}
}

?>
