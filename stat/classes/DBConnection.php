<?php

class DBConnection {
	
	private static $connection;
	
	private function __construct(){}
	
	public static function get($p_driver='MySQLDBDriver') {

    	if(!isset(self::$connection)) {
	    	self::$connection = new $p_driver;
	    	self::$connection->connect(DB_HOST,DB_USER,DB_PASSWORD, DB_PERSISTENT_CONNECTIONS);
	    	self::$connection->selectdb(DB_NAME_MAIN);
    	}
    
    	return self::$connection;
    }

}

?>