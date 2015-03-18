<?php
 /*
  * (C) 2008 SiteName.ru, Vasiliy Ivanovich
  * (C) 2004-2008 SiteName.ru
  *
  *  $Id: MemCacheClient.php,v 1.2 2010/09/08 18:10:25 username Exp $
  */

interface IMemCache
{
	public function set($key, $value, $expire=0);
	public function get($key);
	public function delete($key);
}

interface IMemCache2
{
	public function set($key, $value, $expire=0);
	public function get($key);
	public function delete($key);
}

class MemCacheClient implements IMemCache  {

	public static function getSingleton($server=null) {
		if (is_null(self::$singletonInstance)) {
			self::$singletonInstance = new MemCacheClient($server);
		}
		return self::$singletonInstance;
	}

	public function set($key, $value, $expire=0) {
		return $this->mmc->set($key, $value, null, $expire);
	}

	public function get($key) {
		return $this->mmc->get($key);
	}

	public function delete($key) {
		return memcache_delete($this->mmc, $key, 0);
	}

	private function __construct($server=null) {
		$this->mmc = new Memcache;
		if(!$this->mmc->pconnect(is_null($server) ? '10.0.20.44' : $server, 11211)) {
			return false;
		}
	}

	private $mmc;
	private static $singletonInstance = null;

}

class MemCacheClient2 implements IMemCache2  {

	public static function getSingleton($server=null) {
		if (is_null(self::$singletonInstance)) {
			self::$singletonInstance = new MemCacheClient2($server);
		}
		return self::$singletonInstance;
	}

	public function set($key, $value, $expire=0) {
		return $this->mmc->set($key, $value, null, $expire);
	}

	public function get($key) {
		return $this->mmc->get($key);
	}

	public function delete($key) {
		return $this->mmc->delete($key);
	}

	private function __construct() {
		$this->mmc = new Memcache;
		if(!$this->mmc->pconnect('10.0.50.1', 11211)) {
			return false;
		}
	}

	private $mmc;
	private static $singletonInstance = null;

}
