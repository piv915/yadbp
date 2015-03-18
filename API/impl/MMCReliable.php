<?php
/*
 * Sat Feb 13 19:33:36 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

class MMCReliable implements IReliableCache {

	private $catch_not_found = false;
	private $driver;

	public function __construct($options) {
		if (!is_array($options)) {
			throw new InvalidArgumentException("MMCReliable->construct(\$options) not an array.");
		}

		$this->catch_not_found  = isset($options['catch_not_found']) ? (boolean)$options['catch_not_found'] : false;
		if(!isset($options['pool'])) {
			throw new InvalidArgumentException("MMCReliable->construct(\$options) required a Pool arg.");
		}

		$this->driver = new Memcache;
		$pool = $options['pool'];
		if(count($pool) == 1) {
			$this->driver->connect($pool[0][0], $pool[0][1], 1);
		}
		else foreach ($pool as $entry) {
			$this->driver->addServer($entry[0], $entry[1], false, $entry[2], 1, -1);
		}
	}

	public function save($key, $object, $ttl=0, $throws=true) {
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

		$rs = $this->driver->set($key, $o, 0, $ttl1);
		if($throws && ($rs === false))
			throw new DriverException("Memcache not store item");
	}

	public function  get($key) {
		if(!is_string($key))
			throw new InvalidArgumentException("key is not a string: ".gettype($key));

		$o = $this->driver->get($key);
		if($o === false) {
			if ($this->catch_not_found) {
				throw new ObjectNotFoundException("key=[$key]");
			}
			else {
				return null;
			}
		}

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

	public function getMulti($key) {
		throw new Exception("getMulti not implemented in MMCReliable");
	}

	public function delete($key) {
//		error_log('INFO: try delete key '.$key);
		$this->driver->delete($key, 0);
	}

}
