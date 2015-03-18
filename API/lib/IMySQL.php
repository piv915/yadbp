<?php
/**
 * Thu Feb 18 01:06:51 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id: IMySQL.php,v 1.3 2010/03/07 00:21:58 username Exp $
 *
 */
class MySQLServerRole {
	const MASTER = 2;
	const SLAVE  = 4;
}

class MySQLUseMode {
	const SHARED = 2;
	const EXCLUSIVE = 4;
}

class DBConst {
	const KEY_ASSOC = 2;
	const KEY_NUM = 4;
}

class DbNamedConnections {
	private static $connections =
	array(
		"bs" => array(
			"master" => array("10.0.50.1", "3306"),
			"encoding" => "cp1251",
			"dbname"   => "bs",
			"user"	   => "bs",
			"password" => "WmBejQP2r9VuauiJ"
		),
		"dispatch" => array(
			"master" => array("10.0.42.1", "3306"),
			"slaves" => array("10.0.50.1", "3306", /*"10.0.44.1", "3306"*/),
			"encoding" => "cp1251",
			"dbname" => "dispatch",
			"user"   => "SiteName",
			"password" => "Bd4JRacpjrUxWXuG"
		),
		"s4" => array(
			"master" => array("10.0.46.1", "3306"),
			"encoding" => "cp1251",
			"user"   => "SiteName",
			"password" => "Bd4JRacpjrUxWXuG"
		),
		"services" => array(
			"master" => array("10.0.42.1", "3306"),
			"dbname" => "services",
			"encoding" => "cp1251",
			"user" => "srvs",
			"password" => "Or8h92nFK897h"
		)
	);

	public static function get($name, $conn_role=null, $encoding=null) {
		if(!isset(self::$connections[$name]))
			throw new Exception("Connection named '$name' not exists.");
		else
			$opt = self::$connections[$name];

		if(is_null($conn_role) || $conn_role == MySQLServerRole::MASTER) {

			$string = 'role=master;user='.$opt['user'].';password='.$opt['password'].';host='.$opt['master'][0].';port='.$opt['master'][1];
			if(isset($opt['dbname']))
				$string .= ';db='.$opt['dbname'];
			if(!is_null($encoding))
				$string .= ';charset='.$encoding;

		}
		elseif ($conn_role == MySQLServerRole::SLAVE) {
			if(isset($opt['slaves']) && is_array($opt['slaves']) &&
				(($pairs = count($opt['slaves'])/2) > 0)
			) {

				$i = 2 * rand(1, $pairs);
				$string = 'role=slave;named='.$name.';slave_id='.($i>>1).';user='.$opt['user'].';password='.$opt['password'].';host='.$opt['slaves'][$i-2].';port='.$opt['slaves'][$i-1];

				if(isset($opt['dbname']))
					$string .= ';db='.$opt['dbname'];
			}
			else {

				$string = 'role=master;user='.$opt['user'].';password='.$opt['password'].';host='.$opt['master'][0].';port='.$opt['master'][1];
				if(isset($opt['dbname']))
					$string .= ';db='.$opt['dbname'];
			}

			if(!is_null($encoding))
				$string .= ';charset='.$encoding;
		}
		else
			throw new InvalidArgumentException("conn_role must be MySQLServerRole::MASTER or MySQLServerRole::SLAVE; data=[$conn_role]");

		return $string;
	}
}

/**
 * Enter description here...
 * @example
 * $conn = MySQLConnector::getConnection(DbNamedConnections::get("bs", MySQLServerRole::SLAVE), MySQLUseMode::SHARED);
 * $conn->query("select * from banners");
 */
interface IMySQLPool {
	/**
	 * Get real connection handle to mysql db
	 *
	 * @param unknown_type $conn_string
	 * @throws
	 *  - invalid conn string
	 *  - underlying driver
	 *  - unknown conn. type
	 */
	static public function getConnection($conn_string, $use_mode=null /* default - shared */);
}

interface IMySQLDriver {
	public function query($sql, $log=false, $retries=null, $throws=false);

	public function fetch($key_type);
	public function num_rows();
	public function affected_rows();
	public function insert_id();
	public function escape($string);
	public function close($force=false);

	public function get_saved_queries();
	public function get_num_queries();
	public function clear_queries();

	public function free_result();
	/**
	 * Get last query error (cached in the instance).
	 *
	 */
	public function error();
	public function errno();

	public function mysql_thread_id();

	public function commit();
	public function rollback();

	public function select_db($db_name);
	public function set_option($name, $value);
	public function reset_options();

}

class MySQLConnector implements IMySQLPool,IMySQLDriver {

	static $debug = 0;

	static public function getConnection($conn_string, $use_mode=null /* default - shared */) {

		$opt = self::parse_conn_string($conn_string, $use_mode);
		$ident = $opt['ident'];

		if(isset(self::$instances[$ident]) && (self::$instances[$ident] instanceof MySQLConnector)) {
			return self::$instances[$ident];
		}
		else {
			if(self::$debug)
				print "init new conn, $conn_string\n";
			$inst = new MySQLConnector($conn_string, $use_mode);
			if($inst instanceof MySQLConnector) {
				if($ident !== false )
					self::$instances[$ident]  = $inst;
			}
			else
				throw new Exception("Connector unable to initialize instance.");
		}

//		var_dump($inst);
		return $inst;
	}


	private $last_result;
	private $result_exists;

	public function query($sql, $log=false, $retries=null, $throws=true) {

		if($this->result_exists) {
			@mysql_free_result($last_result);
		}
		if($this->trace) $log = true;

		if($log) {
			$s = microtime(1);
		}
		$rs = mysql_query($sql, $this->link);
		if($rs===false) {
			$this->_mysql_check_error(false);
			if($throws) {
				throw new DBException($this->mysql_errno .' '. $this->mysql_error . ' on '.$sql);
			}
			$return = false;
		}
		$return = true;

		$this->result_exists = is_resource($rs) ? true : false;
		$this->last_result = is_resource($rs) ? $rs : false;


		if($log) {
			$e = microtime(1);
			$this->log_message(sprintf('[%.6f] ', 1000*($e-$s)) . $sql);
		}

		return $return;
	}

	public function fetch($key_type) {
		if($key_type == DBConst::KEY_ASSOC) {
			$row = mysql_fetch_assoc($this->last_result);
		}
		else if($key_type == DBConst::KEY_NUM) {
			$row = mysql_fetch_row($this->last_result);
		}
		if($row===false) {
			$this->_mysql_check_error();
			$this->result_exists = false;
			$this->last_result = null;
		}
		return $row;
	}

	public function num_rows() {
		if($this->result_exists) {
			return mysql_num_rows($this->last_result);
		}
		else {
			return false;
		}
	}

	public function affected_rows() {
		return mysql_affected_rows($this->link);
	}

	public function insert_id() {
		return mysql_insert_id($this->link);
	}

	public function escape($string) {
		return mysql_real_escape_string($string, $this->link);
	}

	public function close($force=false) {
		mysql_close($this->link);
		$this->link = null;
	}

	public function get_saved_queries() {

	}

	public function get_num_queries() {

	}

	public function clear_queries() {

	}

	public function free_result() {
		if($this->result_exists) {
			@mysql_free_result($this->last_result);
			$this->last_result = null;
			$this->result_exists = null;
		}
	}

	/**
	 * Get last query error (cached in the instance).
	 *
	 */
	public function error() {
		if($this->mysql_error) {
			$error = $this->mysql_error;
			$this->mysql_error = false;
			return $error;
		}
		else return null;
	}

	public function errno() {
		if($this->mysql_errno) {
			$errno = $this->mysql_errno;
			$this->mysql_errno = false;
			return $errno;
		}
		else return null;

	}

	public function mysql_thread_id() {
		return mysql_thread_id($this->link);
	}

	public function commit() {

	}

	public function rollback() {
		$this->query("ROLLBACK", false, null, false);
	}

	public function select_db($db_name) {
		if($this->db_name_locked)
			throw new Exception("db_name changes locked in this connection; db={$opt['db']}, host={$opt['host']}"); // +++++++ !!

		if(false===(@mysql_select_db($db_name, $this->link))) {
			$this->_mysql_check_error();
		}

	}

	public function set_option($name, $value) {
		if($name == 'trace') {
			$this->trace = (bool)$value;
		}
	}

	public function reset_options() {

	}

	private function _connect() {
		$opt = $this->conn_params;
		if(!$link = mysql_connect($opt['host'], $opt['user'], $opt['password'])) {
			$this->_mysql_check_error();
		}
		if(isset($opt['db'])) {
			if(!mysql_select_db($opt['db'])) {
				$this->_mysql_check_error();
			}
		}
		$this->link = $link;
	}

	private function __construct($conn_string, $use_mode) {

		if(is_null($use_mode) || $use_mode == MySQLUseMode::SHARED) {
			$this->use_mode = MySQLUseMode::SHARED;
		}
		elseif($use_mode == MySQLUseMode::EXCLUSIVE) {
			$this->use_mode = MySQLUseMode::EXCLUSIVE;
		}
		else {
			throw new InvalidArgumentException("use_mode must be MySQLUseMode::SHARED or MySQLUseMode::EXCLUSIVE; data=[$use_mode]");
		}

//		$this->conn_params = $this->parse_conn_string($conn_string);
		$options = self::parse_conn_string($conn_string, $use_mode);
//
		if($options['role'] == 'master')
			$this->server_role = MySQLServerRole::MASTER;
		elseif ($options['role'] == 'slave')
			$this->server_role = MySQLServerRole::SLAVE;
		else {
			throw new InvalidArgumentException("conn_string contains invalid server role.");
		}

		if(isset($options['db'])) {
			$this->db_name_locked = true;
		}

		$this->conn_params = $options;
		$this->_connect();
//
	}
	private $trace = false;
	private $conn_params;
	private $use_mode;
	private $server_role;
	private $slave_id;

	private $link = null;
	private $db_name_locked;

	/*public*/ private static function pool_ident($opt, $use_mode) {
//		if($this->use_mode == MySQLUseMode::EXCLUSIVE)
		if($use_mode == MySQLUseMode::EXCLUSIVE)
			return false;

		$ident = false;
//		$opt = $this->conn_params;
//		if($this->server_role == MySQLServerRole::MASTER) {
		if($opt['role'] == 'master') {
			$ident = 'master'.$opt['host'].':'.$opt['port'].':'.$opt['user'].':'.$opt['password']/*.':'.$this->server_role*/;
//			if($this->db_name_locked) {
			if(isset($opt['db'])) {
				$ident .= ':'.$opt['db'];
			}
		}
//		elseif($this->server_role == MySQLServerRole::SLAVE) {
		elseif($opt['role'] == 'slave') {
			if(isset($opt['named']) && isset($opt['db'])) {
				$ident = 'namedslave'.$opt['named'].':'.$opt['db'];
			}
			elseif(isset($opt['named'])) {
				$ident = 'namedslave'.$opt['named'];
			}
			else {
				$ident = 'slave'.$opt['host'].':'.$opt['port'].':'.$opt['user'].':'.$opt['password']/*.':'.$this->server_role*/;
//				if($this->db_name_locked) {
				if(isset($opt['db'])) {
					$ident .= ':'.$opt['db'];
				}
			}
		}
//		print 'IDENT='.$ident;
//		var_dump($opt);
		return sha1($ident);
	}

	private static function parse_conn_string($conn_string, $use_mode) {
		$options = array();
		$pairs = explode(';', $conn_string);
		foreach ($pairs as $pair) {
			list($k,$v) = explode('=', $pair);
			$options[$k] = $v;
		}

		foreach (array('host','port','user','password') as $opt) {
			if(!isset($options[$opt])) {
				throw new InvalidArgumentException("conn_string option $opt is absent.");
			}
		}

		if(!isset($options['role'])) {
			$options['role'] = 'master';
//			$this->conn_params['role'] = 'master';
		}

		if(!($options['role'] == 'master' || $options['role'] == 'slave'))
			throw new InvalidArgumentException("conn_string contains invalid server role.");

		$options['ident'] = self::pool_ident($options, $use_mode);

		return $options;
	}

	private static function log_message($text) {
		error_log($text);
	}

	private static function log_exception($e) {
		error_log(get_class($e) . ' [' . $e->getCode() . '] '. $e->getMessage() . ' ' . $e->getTraceAsString());
	}

	private function _mysql_check_error($throws=true) {
		$errno = mysql_errno($this->link);
		if($errno) {
			$this->mysql_errno = $errno;
			$this->mysql_error = mysql_error($this->link);
			if($throws)
				throw new DBException($errno . ' ' . $this->mysql_error);
			else {
				try {
					throw new DBException($errno . ' ' . $this->mysql_error);
				} catch (DBException $e) {
					$this->log_exception($e);
				}
			}
		}
	}

	public function getRole() {
		if($this->server_role == MySQLServerRole::MASTER) {
			print 'MASTER';
		}
		elseif ($this->server_role == MySQLServerRole::SLAVE)
		{
			print 'SLAVE';
		}

	}

	private $mysql_errno;
	private $mysql_error;

	private static $instances;
}
