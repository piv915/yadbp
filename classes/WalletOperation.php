<?php

interface IWalletOperation {

	public function getAmountUnits();
	public function perform($transaction_id, $acc_id, $time_stamp);

}

abstract class WalletOperation implements IWalletOperation {}

abstract class ChargeWalletOperation extends WalletOperation {}
abstract class PutWalletOperation extends WalletOperation {}

class MyProjectServiceWalletOperation extends ChargeWalletOperation {
	private $actor;
	private $actor_id;
	private $service_id;
	private $amount_units;

	public function __construct($amount, $actor, $actor_id, $service_id) {

		settype($amount, 'float');
		settype($actor, 'string');
		settype($actor_id, 'integer');
		settype($service_id, 'integer');

		$this->amount_units = $amount;
		$this->actor = $actor;
		$this->actor_id = $actor_id;
		$this->service_id = $service_id;

	}

	public function perform($transaction_id, $acc_id, $time_stamp) {
		$db = DBConnection::get();
		$db->query(
			sprintf("insert into wallet_history
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %.2f)",
			$transaction_id, $acc_id, $time_stamp, 'charge', $this->actor, $this->actor_id, $this->service_id, CURRENCY_UNIT,
			$this->amount_units, $this->amount_units
			)
		);
		error_log("TRANSACTION: *$transaction_id CHARGE from $acc_id [sum={$this->amount_units}, actor={$this->actor}, actor_id={$this->actor_id}, service={$this->service_id}]");
	}

	public function getAmountUnits() {
		return $this->amount_units;
	}
}

class SMSWalletOperation extends PutWalletOperation {
	private $actor_id;
	private $currency;
	private $amount;
	private $amount_units;
	private $donator = false;

	public function __construct($actor_id, $currency, $amount, $amount_units, $donator = false) {

		settype($actor_id, 'integer');
		settype($currency, 'integer');
		settype($amount, 'float');
		settype($amount_units, 'float');

		$this->actor_id = $actor_id;
		$this->currency = $currency;
		$this->amount = $amount;
		$this->amount_units = $amount_units;

		if($donator && is_int($donator)) {
			$this->donator = $donator;
		}

	}

	public function perform($transaction_id, $acc_id, $time_stamp) {
		$db = DBConnection::get();
		$db->query(
			sprintf("insert into wallet_history
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units, donator)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %.2f, %d, %d)",
			$transaction_id, $acc_id, $time_stamp, 'put', 'sms', $this->actor_id, 0, $this->currency,
			$this->amount, $this->amount_units, $this->donator
			)
		);
		error_log("TRANSACTION: *$transaction_id PUT via SMS on $acc_id [sum={$this->amount_units}, actor_id={$this->actor_id}".
		($this->donator ? ", donator={$this->donator}" : "")
		."]");
	}

	public function getAmountUnits() {
		return $this->amount_units;
	}
}

class ModerWalletOperation extends PutWalletOperation {
	private $actor_id;
	private $amount_units;
	private $donator;

	public function __construct($amount, $actor_id, $donator=0) {

		settype($amount, 'float');
		settype($actor_id, 'integer');

		$this->amount_units = $amount;
		$this->actor_id = $actor_id;
		$this->donator  = $donator;
	}

	public function perform($transaction_id, $acc_id, $time_stamp) {
		$db = DBConnection::get();
		$db->query(
			sprintf("insert into wallet_history
			(op_id, acc_id, op_tm, op_type, op_actor, op_actor_id, service_id, currency_id, amount_currency, amount_units, donator)
			values (%d, %d, %d, '%s', '%s', %d, %d, %d, %d, %d, %d)",
			$transaction_id, $acc_id, $time_stamp, 'put', 'moder', $this->actor_id, '0', CURRENCY_UNIT,
			$this->amount_units, $this->amount_units, $this->donator
			)
		);
		error_log("TRANSACTION: *$transaction_id MODER PUT on $acc_id [sum={$this->amount_units}, actor_id={$this->actor_id}, donator={$this->donator}]");
	}

	public function getAmountUnits() {
		return $this->amount_units;
	}
}

?>
