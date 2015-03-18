<?php
/**
 * Sat Feb 27 21:45:53 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

interface IBaseService {

	function getBaseCost();
	// возможно - убрать
	public function getOrderID();
	public function getName();
	public function getCost($cust_type, $cust_id, $options=null);
	public function available($cust_type, $cust_id);
	public function expiredNext();
}

interface IServiceAPI {

	public function getServicePack($cust_type, $cust_id, $nocache=false);

	public function getConnectedServices($cust_type, $cust_id, $partner_id, $nocache=false);
	public function enableService($partner_id, $service_id);
	public function disableService($partner_id, $service_id);

}

class ServiceAPI implements IServiceAPI {
	private $billing_offine = false;
	private $wsdl_host = 'http://billing.SiteName.ru/wsdl';
	private $wsdl_options;

	private $cache;
	private $serial_false;

	public function __construct() {
		global $bapi;

		if(defined('BILLING_OFFLINE') && BILLING_OFFLINE) {
			$this->billing_offine = true;
		}

		$this->wsdl_options = BAPIConfig::getWSDLOptions();
		$this->cache = BAPIConfig::getCacheInstance();
		$this->serial_false = serialize(false);
	}

	/**
	 * Get connected services based on a PARTNER_ID now.
	 *
	 * @param CustomerType $cust_type
	 * @param int $cust_id
	 * @param int $partner_id
	 * @param boolean $nocache
	 * @return OperationResult::ERROR || int[]
	 */
	public function getConnectedServices($cust_type, $cust_id, $partner_id, $nocache=false) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		if($cust_type !== CustomerType::FORUM)
			return OperationResult::ERROR;

		$partner_id = (int)$partner_id;
		if(!$partner_id)
			return 	OperationResult::ERROR;

		$key = 'sapi-cs-' . $partner_id;
		$o = $nocache ? $this->serial_false : $this->cache->get($key);
		$o = @unserialize($o);

		if(is_array($o))
			return $o;

		try
		{
			$sc = new SoapClient($this->wsdl_host.'/service_control.wsdl', $this->wsdl_options);
			$o = $sc->getConnectedServices($partner_id);
			if(!is_array($o))
				throw new Exception("WS_servicecontrol->getConnectedServices($partner_id) returned not an array");

			$os = serialize($o);
			$this->cache->save($key, $os, 3600, false);

		} catch (Exception $e) {
			log_exception($e);
			return OperationResult::ERROR;
		}

		return $o;
	}

	public function enableService($partner_id, $service_id) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		$partner_id = (int)$partner_id;
		$service_id = (int)$service_id;

		if(!$partner_id || !$service_id)
			return OperationResult::ERROR;

		$key = 'sapi-cs-' . $partner_id;

		try
		{
			$sc = new SoapClient($this->wsdl_host.'/service_control.wsdl', $this->wsdl_options);
			$result = $sc->enableService($partner_id, $service_id);

			if($result != OperationResult::ERROR && $result != OperationResult::OK)
				throw new Exception("WS_servicecontrol->enableService($partner_id, $service_id) returned unknown (null?) result");

			$this->cache->delete($key);
			return $result;

		} catch (Exception $e) {
			log_exception($e);
			return OperationResult::ERROR;
		}

	}

	public function disableService($partner_id, $service_id) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		$partner_id = (int)$partner_id;
		$service_id = (int)$service_id;

		if(!$partner_id || !$service_id)
			return OperationResult::ERROR;

		$key = 'sapi-cs-' . $partner_id;

		try
		{
			$sc = new SoapClient($this->wsdl_host.'/service_control.wsdl', $this->wsdl_options);
			$result = $sc->disableService($partner_id, $service_id);

			if($result != OperationResult::ERROR && $result != OperationResult::OK)
				throw new Exception("WS_servicecontrol->disableService($partner_id, $service_id) returned unknown (null?) result");

			$this->cache->delete($key);
			return $result;

		} catch (Exception $e) {
			log_exception($e);
			return OperationResult::ERROR;
		}

	}

	public function getServicePack($cust_type, $cust_id, $nocache=false) {
		if($this->billing_offine)
			return OperationResult::ERROR;

		if($cust_type !== CustomerType::FORUM)
			return OperationResult::ERROR;

		$key = 'sapi-st-'.$cust_type.'-'.$cust_id;
		if($nocache) {
			$o = serialize(false);
		}
		else {
			$o = $this->cache->get($key);
			if($o == OperationResult::OK)
				return $o;
		}

		$o = @unserialize($o);
		if(is_array($o)) {
			return $o;
		}
		else {
			// TODO - переделать на SOAP
			try {
				$conn = MySQLConnector::getConnection(DbNamedConnections::get("services"));
//				$conn->set_option('trace', true);
				$conn->query(sprintf("select * from services_active where cust_id = %d", $cust_id));
				$o = array();
				while ($row = $conn->fetch(DBConst::KEY_ASSOC)) {
					$o[$row['service_id']] = $row;
				}

				if(!$nocache) {
					if(count($o)) {
						$os = serialize($o);
						$this->cache->save($key, $os, 3600, false);
					}
					else {
						$o = OperationResult::OK;
						$this->cache->save($key, $o, 3600, false);
	//					return $o;
					}
				}

			} catch (Exception $e) {
				log_exception($e);
				return OperationResult::ERROR;
			}

			return $o;
		}

		return OperationResult::ERROR;
	}
}

class BaseService {
	protected $context;
	private $wsdl_host = 'http://billing.SiteName.ru/wsdl';
	protected $service_id;

	// возможно перевести в protected
	public function getOrderID() {

		try {
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::MASTER), MySQLUseMode::EXCLUSIVE);
			$conn->query("insert into order_id_seq values(NULL)");
			$order_id = $conn->insert_id();
			if($order_id <= 0)
				$order_id  = null;
			$conn->query(sprintf("delete from order_id_seq where order_id < %d", $order_id));

		} catch (Exception $e) {
			log_exception($e);
			$order_id = null;
		}

		return $order_id;
	}

	public function expiredNext($cust_type, $cust_id) {
		return null;
	}

	public function __construct($service_id, $context=null) {
		if(is_null($context) || is_array($context))
			$this->context = $context;
		else
			throw new Exception("context not null nor array");
		$this->service_id = (int)$service_id;
	}

	public function getCost($cust_type, $cust_id, $options=null) {
//		$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
//		$conn->query("")
		return null;
	}

	public function available($cust_type, $cust_id) {
		if(
			isset($this->context['partner_id']) &&
			in_array($this->context['partner_id'], array(1,3,4))
		)
		{
			return true;
		}
		else {
			$partner_id = isset($this->context['partner_id']) ? (int)$this->context['partner_id'] : 0;
			if(!$partner_id) {
				return false;
			}

			try {
				$sc = new SoapClient($this->wsdl_host.'/service_control.wsdl',
					BAPIConfig::getWSDLOptions());
				$result = $sc->getConnectedServices($partner_id);
				if(!is_array($result))
					throw new Exception("WS_servicecontrol->getConnectedServices call returns invalid result");

				$service_id = $this->service_id;
				return (bool)in_array($service_id, $result);

			} catch (Exception $e) {
				log_exception($e);
				return null;
			}

		}
	}

}

class ServiceFactory {
	public static function getService($service_id, $context=null) {
		$service_id = (int)$service_id;
		$class_name = 'Service_'.$service_id;
		if (class_exists($class_name)) {
			return new $class_name($service_id, $context);
		}
		else {
			return new BaseService($service_id, $context);
		}
	}
}

/**
 * DISABLE_ADS_SERVICE
 *
 */
class Service_1001 extends BaseService {

	public function __construct($service_id, $context=null) {
		parent::__construct(1001, $context);
	}

	public function expiredNext($cust_type, $cust_id) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$conn->query(sprintf("select expired from services_active where cust_id = %d and service_id = 1001 and expired > %d", $cust_id, $_now));
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if($row) {
				$expired = (int)$row['expired'];
				return $expired;
			}
			else {
				$conn->query("select period from services_dict where service_id = 1001");
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if(!$row) {
					throw new Exception("Service 1001 not found in services.services_dict");
				}
				$period = $row['period'];
				if(!function_exists('date_plus'))
					throw new Exception("Function date_plus not defined in namespace;");

				$expired = date_plus($period);
				return (int)$expired;
			}
		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function getCost($cust_type, $cust_id, $options=null) {
		try {

			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$conn->query(sprintf("select hits,days from hitstat_month where forum_id = %d", $cust_id));
			$row = $conn->fetch(DBConst::KEY_ASSOC);

			// минимальная или со статистикой ?
			if($row) {
				$cost = $this->_getCost($row['hits'], $row['days']);
			}
			else {
				$cost = $this->get_ads_cost(0);
			}

			if(is_array($options) && isset($options['proportional']) && $options['proportional']) {
				// пересчет нужен
				$conn->query(sprintf("select ordered,expired from services_active where cust_id = %d and service_id = 1001", $cust_id));
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if($row && (int)$row['expired'] > time()) {

					// заканчивается когда?
					$expired = (int)$row['expired'];
					$started = (int)$row['ordered'];

					// считаем когда началась?
					$conn->query("select period from services_dict where service_id = 1001");
					$row = $conn->fetch(DBConst::KEY_ASSOC);
					if(!$row) {
						throw new Exception("Service 1001 not found in services.services_dict");
					}
					$period = $row['period'];

//					if(!function_exists('date_minus'))
//						throw new Exception("Function date_minus not defined in namespace;");
//					$started 		= date_minus($period, $expired);

					$service_length = ($expired - $started);
					$time_left 		= ($expired - time());

					if($time_left <= 0)
						throw new Exception("time_left negative or null");

					$_now = time();
					$period_ut = date_plus($period, $_now) - $_now;

					$koef 			= floatval(sprintf("%.2f", $time_left/$period_ut));
//					$koef 			= floatval(sprintf("%.2f", $time_left/$service_length));

					log_message("DEBUG: koef for cust_id $cust_id = $koef");
					if($koef==0) {
						$koef = 0.01;
					}
					if(/*$koef > 1 ||*/ $koef <= 0) {
						throw new Exception("error in proportional cost: (koef = $koef)");
					}

					$cost[1] = ceil($cost[1] * $koef);
					$cost[2] = ceil($cost[2] * $koef);
					$cost[4] = ceil($cost[4] * $koef);
					$cost[8] = ceil($cost[8] * $koef);

					if($cost[2] > 140) $cost[2] = 140;
					if($cost[4] > 140) $cost[4] = 140;
					if($cost[8] > 70) $cost[8] = 70;

					$cost[0] = $cost[1] + $cost[2] + $cost[4] + $cost[8];

					if (isset($options['partner_id']) && in_array($options['partner_id'], array(1,3,4))){
						foreach ($cost as $k => $v){
							$cost[$k] = round($v*0.8, 2);
						}
					}
					return $cost;
				}
				else {
					// услуга не подключена - без пересчета
					if (isset($options['partner_id']) && in_array($options['partner_id'], array(1,3,4))){
						foreach ($cost as $k => $v){
							$cost[$k] = round($v*0.8, 2);
						}
					}
					return $cost;
				}

			}
			else {
				if (isset($options['partner_id']) && in_array($options['partner_id'], array(1,3,4))){
					foreach ($cost as $k => $v){
						$cost[$k] = round($v*0.8, 2);
					}
				}
				return $cost;
			}

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

//	public function available($cust_type, $cust_id) {
//		return parent::available($cust_type, $cust_id);
//	}

	private function _getCost($hits,$days) {
		$nhits = (30*$hits/$days);
		$result = $this->get_ads_cost($nhits);

		if($result[2] > 140) $result[2] = 140;
		if($result[4] > 140) $result[4] = 140;
		if($result[8] > 70)  $result[8] = 70;

		return $result;
	}

	private function get_ads_cost($hits) {
	//	минималку считаем как 5 тыс хитов в месяц
	//	цену округляем до тысячи хитов в большую сторону.
	//	сплеш: с руссобитом приносит 40р за тысячу показов... но он показывается уникальным юзерам,
	//	то есть 1 показ на 13 хитов. себестоимость 3р - выставляем цену: 6р (кредитов) за тысячу хитов
	//	верх: 3р за тысячу хитов
	//	низ: 3р за тысячу хитов
	//	копирайт: 1р за тысячу хитов
	//
	//	итого, минималка:
	//	сплеш: 30кр.
	//	баннер верх: 15кр.
	//	баннер низ: 15кр.
	//	копирайт: 5кр.
	//	отключить всю рекламу: 65кр.

		$hits = (int)$hits;
		if($hits < 5000) $hits = 5000;
		$khits = ceil($hits/1000);

	// скидки - сюда
		if($khits > 100) $khits = ceil($khits*0.8);

		$result = array();
		$result[1] = $khits * 4; // splash
		$result[2] = $khits * 2; // top
		$result[4] = $khits * 2; // bottom
		$result[8] = $khits * 1; // copyright
//		if($result[8] < 4) $result[8] = 4;

		$result[0]	= $result[1] + $result[2] + $result[4] + $result[8]; // total
		return $result;

	}

	public function order($cust_type, $cust_id, $period = 1, $options = null)
	{

		// проверка данных


		$order_id = $this->getOrderID();
		$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::MASTER), MySQLUseMode::EXCLUSIVE);

	}

}

/**
 * DOMAIN REGISTRATION (MASTERNAME)
 *
 */
class Service_1002 extends BaseService {
	public function __construct($service_id, $context=null) {
		parent::__construct(1002, $context);
	}

	public function expiredNext($cust_type, $cust_id) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);

			$conn->query("select period from services_dict where service_id = 1002");
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if(!$row) {
				throw new Exception("Service 1002 not found in services.services_dict");
			}
			$period = $row['period'];
			if(!function_exists('date_plus'))
				throw new Exception("Function date_plus not defined in namespace;");

			$expired = date_plus($period);
			return (int)$expired;

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function getCost($cust_type, $cust_id, $options=null) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services",
				MySQLServerRole::SLAVE), MySQLUseMode::SHARED);

			$conn->query("select basecost from services_dict where service_id = 1002");
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if(!$row) {
				throw new Exception("Service 1002 not found in services.services_dict");
			}

			return (int)$row['basecost'];

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function get_dnstmplbyhost($host) {

		$known_hosts = array(
			'SiteName' 	=> 'ext128',
			'2bb' 	=> 'ext132',
			'3bb' 	=> 'ext133',
			'4bb' 	=> 'ext134',
			'5bb' 	=> 'ext135',
			'my' 	=> 'ext136',
			'co' 	=> 'ext137',
		);

		return (isset($known_hosts[$host])) ? $known_hosts[$host] : "";
	}

	public function completeOrder($order_id) {
		try {
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE),
				MySQLUseMode::SHARED);
			$conn->query(sprintf("select * from service_orders where order_id = %d", $order_id));

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}

	}

	public function revertOrder($order_id) {

	}

}


/**
 * DOMAIN PROLONGATION (MASTERNAME)
 */
class Service_1003 extends BaseService {

    public function expiredNext($cust_type, $cust_id) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE),
                                                  MySQLUseMode::SHARED);

			$conn->query("select period from services_dict where service_id = 1003");
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if(!$row) {
				throw new Exception("Service 1003 not found in services.services_dict");
			}
			$period = $row['period'];

            $conn->query(sprintf("select paid_till from domain_registry where domain_name = '%s'",
                         $conn->escape($this->context['checked_domain'])));
            $row = $conn->fetch(DBConst::KEY_ASSOC);
            if(!$row) {
                throw new Exception("domain_name ".$this->context['checked_domain'].
                                    " not found in services.domain_registry");
            }
            $paid_till = $row['paid_till'];

			if(!function_exists('date_plus'))
				throw new Exception("Function date_plus not defined in namespace;");

			$expired = date_plus($period, $paid_till);
			return (int)$expired;

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

    public function getCost($cust_type, $cust_id, $options=null) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services",
				MySQLServerRole::SLAVE), MySQLUseMode::SHARED);

			$conn->query("select basecost from services_dict where service_id = 1003");
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if(!$row) {
				throw new Exception("Service 1003 not found in services.services_dict");
			}

			return (int)$row['basecost'];

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function get_dnstmplbyhost($host) {

		$known_hosts = array(
			'SiteName' 	=> 'ext128',
			'2bb' 	=> 'ext132',
			'3bb' 	=> 'ext133',
			'4bb' 	=> 'ext134',
			'5bb' 	=> 'ext135',
			'my' 	=> 'ext136',
			'co' 	=> 'ext137',
		);

		return (isset($known_hosts[$host])) ? $known_hosts[$host] : "";
	}

}
/**
 * PROFILE_FIELDS
 *
 */
class Service_1004 extends BaseService {

	public function __construct($service_id, $context=null) {
		parent::__construct(1004, $context);
	}

	public function expiredNext($cust_type, $cust_id) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$conn->query(sprintf("select expired from services_active where cust_id = %d and service_id = 1004 and expired > %d", $cust_id, $_now));
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if($row) {
				$expired = (int)$row['expired'];
				return $expired;
			}
			else {
				$conn->query("select period from services_dict where service_id = 1004");
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if(!$row) {
					throw new Exception("Service 1004 not found in services.services_dict");
				}
				$period = $row['period'];
				if(!function_exists('date_plus'))
					throw new Exception("Function date_plus not defined in namespace;");

				$expired = date_plus($period);
				return (int)$expired;
			}
		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function getCost($cust_type, $cust_id, $options=null) {
		try {

			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$cost = $this->_getCost();

			if(is_array($options) && isset($options['proportional']) && $options['proportional']) {
				// пересчет нужен
				$conn->query(sprintf("select ordered,expired from services_active where cust_id = %d and service_id = 1004", $cust_id));
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if($row && (int)$row['expired'] > time()) {

					// заканчивается когда?
					$expired = (int)$row['expired'];
					$started = (int)$row['ordered'];

					// считаем когда началась?
					$conn->query("select period from services_dict where service_id = 1004");
					$row = $conn->fetch(DBConst::KEY_ASSOC);
					if(!$row) {
						throw new Exception("Service 1004 not found in services.services_dict");
					}
					$period = $row['period'];

					$service_length = ($expired - $started);
					$time_left 		= ($expired - time());

					if($time_left <= 0)
						throw new Exception("time_left negative or null");

					$_now = time();
					$period_ut = date_plus($period, $_now) - $_now;

					$koef 			= floatval(sprintf("%.2f", $time_left/$period_ut));

					log_message("DEBUG: koef for cust_id $cust_id = $koef");
					if($koef==0) {
						$koef = 0.01;
					}
					if(/*$koef > 1 ||*/ $koef <= 0) {
						throw new Exception("error in proportional cost: (koef = $koef)");
					}

					$cost[1] = ceil($cost[1] * $koef);
					$cost[2] = ceil($cost[2] * $koef);
					$cost[3] = ceil($cost[3] * $koef);
					$cost[4] = ceil($cost[4] * $koef);
					$cost[5] = ceil($cost[5] * $koef);

					return $cost;
				}
				else {
					// услуга не подключена - без пересчета
					return $cost;
				}

			}
			else {
				return $cost;
			}

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	private function _getCost() {

		return array(1=>20,2=>50,3=>80,4=>100,5=>120);
	}

	public function order($cust_type, $cust_id, $period = 1, $options = null)
	{

		// проверка данных


		$order_id = $this->getOrderID();
		$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::MASTER), MySQLUseMode::EXCLUSIVE);

	}

}

/**
 * FORUM_BACKUPS
 *
 */
class Service_1005 extends BaseService {

	public function __construct($service_id, $context=null) {
		parent::__construct(1005, $context);
	}

	public function expiredNext($cust_type, $cust_id) {
		try {
			$_now = time();
			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$conn->query(sprintf("select expired from services_active where cust_id = %d and service_id = 1005 and expired > %d", $cust_id, $_now));
			$row = $conn->fetch(DBConst::KEY_ASSOC);
			if($row) {
				$expired = (int)$row['expired'];
				return $expired;
			}
			else {
				$conn->query("select period from services_dict where service_id = 1005");
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if(!$row) {
					throw new Exception("Service 1005 not found in services.services_dict");
				}
				$period = $row['period'];
				if(!function_exists('date_plus'))
					throw new Exception("Function date_plus not defined in namespace;");

				$expired = date_plus($period);
				return (int)$expired;
			}
		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	public function getCost($cust_type, $cust_id, $options=null) {
		try {

			$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
			$cost = $this->_getCost();

			if(is_array($options) && isset($options['proportional']) && $options['proportional']) {
				// пересчет нужен
				$conn->query(sprintf("select ordered,expired from services_active where cust_id = %d and service_id = 1005", $cust_id));
				$row = $conn->fetch(DBConst::KEY_ASSOC);
				if($row && (int)$row['expired'] > time()) {

					// заканчивается когда?
					$expired = (int)$row['expired'];
					$started = (int)$row['ordered'];

					// считаем когда началась?
					$conn->query("select period from services_dict where service_id = 1005");
					$row = $conn->fetch(DBConst::KEY_ASSOC);
					if(!$row) {
						throw new Exception("Service 1005 not found in services.services_dict");
					}
					$period = $row['period'];

					$service_length = ($expired - $started);
					$time_left 		= ($expired - time());

					if($time_left <= 0)
						throw new Exception("time_left negative or null");

					$_now = time();
					$period_ut = date_plus($period, $_now) - $_now;

					$koef 			= floatval(sprintf("%.2f", $time_left/$period_ut));

					log_message("DEBUG: koef for cust_id $cust_id = $koef");
					if($koef==0) {
						$koef = 0.01;
					}
					if(/*$koef > 1 ||*/ $koef <= 0) {
						throw new Exception("error in proportional cost: (koef = $koef)");
					}

					$cost[1] = ceil($cost[1] * $koef);
					$cost[2] = ceil($cost[2] * $koef);
					$cost[3] = ceil($cost[3] * $koef);

					return $cost;
				}
				else {
					// услуга не подключена - без пересчета
					return $cost;
				}

			}
			else {
				return $cost;
			}

		} catch (Exception $e) {
			log_exception($e);
			return null;
		}
	}

	private function _getCost() {

		return array(1=>90,2=>95,3=>100);
	}

	public function order($cust_type, $cust_id, $period = 1, $options = null)
	{

		// проверка данных


		$order_id = $this->getOrderID();
		$conn = MySQLConnector::getConnection(DbNamedConnections::get("services", MySQLServerRole::MASTER), MySQLUseMode::EXCLUSIVE);

	}

}

