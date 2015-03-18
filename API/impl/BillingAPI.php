<?php
/**
 * Sat Feb 13 19:45:53 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

class BAPIConfig {
	public static function getWSDLOptions() {
		return array(
			'connection_timeout' => 3,
			'exceptions' => true,
			'cache_wsdl' => WSDL_CACHE_MEMORY,
		);
	}

	public static function getCacheInstance() {
		if(self::$cache instanceof MMCReliable) {
			return self::$cache;
		}
		else {
			self::$cache = new MMCReliable(array(
				"pool" => array(
					array('10.0.42.41', 11211, 1),
				),
				"catch_not_found" => false
			));
			return self::$cache;
		}
	}

	private static $cache;
}

class BillingAPI implements IBillingAPI {

	private $billing_offine = false;
	private $wsdl_host = 'http://billing.SiteName.ru/wsdl';
	private $wsdl_options;

	private $cache;
	private $serial_false;

	public function __construct() {
		if(defined('BILLING_OFFLINE') && BILLING_OFFLINE) {
			$this->billing_offine = true;
		}
//		$this->cache = new MMCReliable(array(
//			"pool" => array(
//				array('10.0.42.41', 11211, 1),
//			),
//			"catch_not_found" => false
//		));
//		$this->wsdl_options = array(
//			'connection_timeout' => 3,
//			'exceptions' => true,
//			'cache_wsdl' => WSDL_CACHE_MEMORY,
//		);
		$this->wsdl_options = BAPIConfig::getWSDLOptions();
		$this->cache = BAPIConfig::getCacheInstance();
		$this->serial_false = serialize(false);
	}

	public function _get_cache_handle() {
		return $this->cache;
	}

	public function apiVersion() {
		return trim(substr('$Revision$', 10), " :$");
	}

	public function createAccount($acc_type, $acc_id, $partner_id=1) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;
			if($acc_id <= 0)
				throw new OutOfRangeException("Account ID <= 0");

			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$sc->createAccount($acc_id, $partner_id);
		}
		catch (SoapFault $e) {
			log_exception($e);
			if($e->detail == 'AccountExistsException') {
				return OperationResult::ACCCOUNT_EXISTS;
			}
			else {
				return OperationResult::ERROR;
			}
		}
		catch (Exception $e) {
			log_exception($e);
			return OperationResult::ERROR;
		}
	}

	public function accountExists($acc_type, $acc_id) {
		throw new Exception("not implemented");
	}

	public function getHistoryPage($acc_type, $acc_id/*, $length, $op_type_flag*/) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;
			if($acc_id <= 0)
				throw new OutOfRangeException("Account ID <= 0");

			$key = 'bapi-hp-'.$acc_type.'-'.$acc_id;
			$o = $this->cache->get($key);

			if($o instanceof DataObject) {
				return $o;
			}
			else {
				if($o===false)
					return OperationResult::ERROR;

				$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
				$o = $sc->getHistoryPage($acc_id);
				$o = @unserialize($o);

				if($o instanceof DataObject) {
					// кэшируем и возврат
					$this->cache->save($key, $o, 3600, false);
					return $o;
				}
				else {
					// кэшируем false на 5 мин
					$this->cache->save($key, false, 300, false);
					return OperationResult::ERROR;
				}
			}
		}
		catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;
	}

	public function markAccountOwnerless($acc_type, $acc_id) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;

			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$sc->markAccountOwnerless($acc_id);

			return OperationResult::OK;

		} catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;

	}

	public function chargeAccount($acc_type, $acc_id, $amount, $sub_account, $actor, $actor_id, $service_id, $notify) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;
			if($acc_id <= 0)
				throw new OutOfRangeException("Account ID <= 0");

			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$rs = $sc->chargeAccount($acc_id, $amount, 1/*ACC_TYPE_MAIN*/, 'user', $actor_id, $service_id, $notify);
			if($rs == -1)
				throw new Exception("WS_wallet->chargeAccount($acc_id) reports error;");
			if($rs == 0)
				return OperationResult::OK;
//			chargeAccount($account_number, $amount, $acc_type, $actor, $actor_id, $service_id, $notify)
		}
		catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;

	}

	public function chargeAccountFromReserve($acc_type, $acc_id, $amount, $sub_account, $actor, $actor_id, $service_id, $notify) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;
			if($acc_id <= 0)
				throw new OutOfRangeException("Account ID <= 0");

			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$rs = $sc->chargeAccount($acc_id, $amount, 2/*ACC_TYPE_RESERVE*/, 'user', $actor_id, $service_id, $notify);
			if($rs == -1)
				throw new Exception("WS_wallet->chargeAccount($acc_id) reports error;");
			if($rs == 0)
				return OperationResult::OK;
//			chargeAccount($account_number, $amount, $acc_type, $actor, $actor_id, $service_id, $notify)
		}
		catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;

	}

	public function reserveFunds($acc_type, $acc_id, $amount, $sub_account, $notify) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");
			$acc_id = (int)$acc_id;
			if($acc_id <= 0)
				throw new OutOfRangeException("Account ID <= 0");

			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);

			$rs = $sc->reserveFunds($acc_id, $amount, $notify);
			if($rs == -1)
				throw new Exception("WS_wallet->reserveFunds($acc_id) reports error;");
			if($rs == 0)
				return OperationResult::OK;
		}
		catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;
	}

	public function getAvailableSum($acc_type, $acc_id, $no_cache=false) {

		if($this->billing_offine)
			return null;

		try {
//			ini_set('default_socket_timeout', 1);

			if($acc_type != AccountType::FORUM)
				throw new Exception("AccountType other than FORUM, not implemented yet. [acc_type= $acc_type]");

			$key = 'bapi-sums-'.$acc_type.'-'.$acc_id;
			$o = $this->cache->get($key);

			$available = null;
			if ($o instanceof DataObject) {
				$available = !is_null($o->available) ? $o->available : null;
			}
			if($no_cache)
				$available =null;
			if(is_null($available))
			{
				$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
				$o = $sc->getWalletSums($acc_id);
				$o = @unserialize($o);
				if ($o instanceof DataObject) {
					$available = !is_null($o->available) ? $o->available : null;
					$this->cache->save($key, $o, 3600, false);
				}
			}

			if(!is_null($available)) {
				return sprintf("%.2f", $available);
			}

		} catch (SoapFault $e) {
//			print $e->getMessage() . ' <br />' . $e->getTraceAsString();
			log_exception($e);
		} catch (Exception $e) {
			log_exception($e);
		}

		return null;
	}

	public function putFunds($acc_type, $acc_id, $amount, $notify) {
		throw new Exception("not implemented");
	}

	public function getLastPayments($acc_id, $length=null) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if(is_null($length)) $length = 20;
			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$o = $sc->getLastPayments($acc_id, $length);
			$o = @unserialize($o);

			if(is_array($o)) {
				return $o;
			}

		} catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;
	}

	public function getTopPayers($acc_id, $length=null) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		try {
			if(is_null($length)) $length = 20;
			$sc = new SoapClient($this->wsdl_host.'/wallet.wsdl', $this->wsdl_options);
			$o = $sc->getTopPayers($acc_id, $length);
			$o = @unserialize($o);

			if(is_array($o)) {
				return $o;
			}

		} catch (Exception $e) {
			log_exception($e);
		}

		return OperationResult::ERROR;
	}
}
