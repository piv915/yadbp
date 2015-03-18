<?php
/*
 * Sat Feb 13 19:31:07 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

/**
 * Реализация надежного кэша на основе memcached-libmemcached.
 *
 * Проблемы:
 * - нельзя сохранить значение типа null, если отключена опция "создавать исключение для ненайденного объекта"
 *
 * @example mmc-libmemcached01.php Использование кэша
 * @deprecated broken now
 *
 */
class MMCReliableCache01 implements IReliableCache {

	/**
	 * @access private
	 * @var string
	 */
	private $driver;
	/**
	 * @access private
	 * @var boolean
	 */
	private $catch_not_found = false;

	/**
	 * Конструктор
	 *
	 * @param string $driver - всегда должен быть "memcached"
	 * @param array $options
	 * @link http://ru.php.net/manual/en/memcached.constants.php Опции драйвера
	 */
	public function __construct($driver, $options) {

		if($driver=="memcached") {
			$this->driver = new Memcache;
		}
		else
			throw new DriverNotFoundException("[driver = $driver]");

		if (!is_array($options)) {
			throw new InvalidArgumentException("ReliableCache->construct(\$options) not an array.");
		}

		$this->catch_not_found  = isset($options['catch_not_found']) ? (boolean)$options['catch_not_found'] : false;

		$this->driver->setOption(Memcached::OPT_DISTRIBUTION, Memcached::DISTRIBUTION_CONSISTENT);
		$this->driver->addServers($pool);
	}

	public function save($key, $object, $ttl=0, $throws=true){

		if(!is_string($key))
			throw new InvalidArgumentException("key is not a string: ".gettype($key));

		$o = base64_encode(serialize($object));
		$h = md5($o);
		$ttl = (int)$ttl; if($ttl < 0) $ttl = 0;
		if($ttl > 0 && $ttl < 2592000) { // relative (30 days and less, as mmc driver specifies)
			$ttl1 = time()+$ttl;
		}
		else $ttl1 = $ttl;
		$o = $h.';'.$ttl1.';'.$o;

		$this->driver->set($key, $o, $ttl1);
		$rs = $this->driver->getResultCode();
		if($rs !== Memcached::RES_SUCCESS)
			throw new DriverException(Memcached::getResultMessage());
	}

	public function  get($key){
		if(!is_string($key))
			throw new InvalidArgumentException("key is not a string: ".gettype($key));

		$o = $this->driver->get($key);

		$rs = $this->driver->getResultCode();
		if($rs === Memcached::RES_NOTFOUND) {
			if ($this->catch_not_found) {
				throw new ObjectNotFoundException("key=[$key]");
			}
			else {
				return null;
			}
		}
		if($rs !== Memcached::RES_SUCCESS)
			throw new DriverException(Memcached::getResultMessage());

		list ($hash,$ttl,$o) = explode(";", $o, 3);
		if(is_null($ttl))
			throw new IntegrityViolationException("key = [$key]");
		$ttl = (int)$ttl;

		if (!($ttl >= 0 && time() < $ttl)) {
			if ($this->catch_not_found) {
				throw new ObjectNotFoundException("key=[$key]");
			}
			else {
				return null;
			}
		}

		$h = md5($o);
		if($hash != $h)
			throw new IntegrityViolationException("key = [$key]");

		$o = base64_decode($o);
		if($o === false) throw new IntegrityViolationException("key = [$key]");
		$sf = serialize(false);
		if($o == $sf)
			return false; // real false saved

		$o = @unserialize($o);
		if ($o === false)
			throw new IntegrityViolationException("key = [$key]");
		return $o;
	}



	public function getMulti($key){
		throw new Exception("getMulti not implemented in MMCReliableCache01");
	}

	public function delete($key) {
		throw new Exception("not implemented");
	}
}

