<?php

    class MySQLDBDriver extends DBDriver
    {
	private $sqllink = null;
	private $dbname = null;
	private $query = null;
	private $res = null;
	
	private function raiseError($p_errno) {
	    if($p_errno != 0) {
		throw new DBException($this->query . ' = ' . $p_errno . ': ' . mysql_error(), $p_errno );
	    }
	}
	
	public function connect($p_host='localhost',$p_login='',$p_password='',
				$p_persistent=false){
	    if(!isset($sqllink)) {
		if($p_persistent == true) {
		    $this->sqllink = mysql_pconnect($p_host,$p_login,$p_password);
		} else {
		    $this->sqllink = mysql_connect($p_host,$p_login,$p_password);
		}
		$this->raiseError(mysql_errno());
	    }
	    
	    if(!is_resource($this->sqllink))
	    	throw new Exception("MySQL (p)connect failed");
	}
	
	public function prepare($p_query){
	    if(is_resource($this->res)) {
		mysql_free_result($this->res);
	    }
	    $this->query = $p_query; //mysql_real_escape_string($p_query);
	}

	public function selectdb($p_dbname){
	    mysql_select_db($p_dbname,$this->sqllink);
	    $this->raiseError(mysql_errno());
	    $this->dbname = $p_dbname;
	}
	
	public function usedb($p_dbname) {
		$this->selectdb($p_dbname);
	}
	
	public function query($p_query) {
		$this->prepare($p_query);
		$this->execute();
	}
	
	public function execute() {
	    if(isset($this->query)) {
	    	$mytime = microtime(true);
		$this->res = mysql_query($this->query,$this->sqllink);
			$executed = microtime(true) - $mytime;
//			error_log('SQL: [' . 1000*$executed . '] "' . $this->query . '"');
//			$GLOBALS['sqlc']++;
		$this->raiseError(mysql_errno());
	    }
	}
	
	public function fetch($p_array_type) {
	    if(!isset($this->res) || ($this->res == false)) {
		//mysql_free_result($this->res);
		$this->raiseError('MySQLDBDriver: No data available');
	    } else {
		return mysql_fetch_array($this->res,$p_array_type);
	    }
	
	}
	
	public function disconnect(){
	    if(is_resource($this->sqllink)) { 
		mysql_close($this->sqllink);
		$this->sqllink = null;
	    }
	}
	
	public function info() {
	  if(isset($this->sqllink)) {
	    $info = sprintf ("MySQL client info: %s\n", mysql_get_client_info());
	    $info .= sprintf ("MySQL host info: %s\n", mysql_get_host_info($this->sqllink));
	    $info .= sprintf ("MySQL protocol version: %s\n", mysql_get_proto_info($this->sqllink));
	    $info .= sprintf ("MySQL server version: %s\n", mysql_get_server_info($this->sqllink));
	    $info .= sprintf ("MySQL thread id: %s\n", mysql_thread_id($this->sqllink));
	    return $info;
	  } else {
	    throw new Exception("Not connected: Cann't get info from mysql server");
	  }
	}
	
	public function affected_rows() {
		return mysql_affected_rows($this->sqllink);
	}
	
	public function escape($string) {
		
		$rs = mysql_real_escape_string($string);
		if($rs === false) {
			$this->raiseError(mysql_errno());
		} else 
			return $rs;
	
	}
	
    }
?>