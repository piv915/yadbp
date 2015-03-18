<?php
    abstract class DBDriver
    {
		abstract public function connect($p_host='localhost',$p_login='',
		$p_password='',$p_persistent=false);
		abstract public function selectdb($p_dbname);
		abstract public function usedb($p_dbname);
		abstract public function query($p_query);
		abstract public function prepare($p_query);
		abstract public function execute();
		abstract public function fetch($p_array_type);
		abstract public function affected_rows();
		abstract public function disconnect();
		abstract public function info();
    }
?>