<?php
 /*
  * (C) 2009
  *  Author: Vasiliy.Ivanovich@gmail.com
  *
  *  $Id: DBMutex.php,v 1.1 2009/06/19 15:02:11 username Exp $
  */

interface IMutex
{
	public static function get($mutex_name, $db_host, $db_user, $db_password, $db_name);
	public function release();
}

class DBMutex implements IMutex
{
	private $db_link = null;

	public static function get($mutex_name, $db_host, $db_user, $db_password, $db_name)
	{
		try {

			$i = new DBMutex($db_host, $db_user, $db_password, $db_name);
			$i->__get_mutex($mutex_name);
			return $i;

		} catch (Exception $e)
		{
			trigger_error($e->getMessage(), E_USER_WARNING);
//			print $e->getMessage() . ' code= ' . $e->getCode();
			$i = null;
			return false;
		}

	}

	private function __construct($db_host, $db_user, $db_password, $db_name)
	{
		if (!defined('MYSQL_LOCK_WAIT'))
			define('MYSQL_LOCK_WAIT', 1205);

		if(false===($db_link = mysql_connect($db_host,$db_user,$db_password, true)))
		 throw new Exception('Cant connect to mutex db');

		if(false===mysql_select_db($db_name, $db_link))
		 throw new Exception('Cant select mutex db');

		$this->db_link = $db_link;

	}

	private function __get_mutex($mutex_name) {
		$db_link = $this->db_link;


		if(false === mysql_query('set session wait_timeout = 100', $db_link))
			throw new Exception(mysql_error($db_link), mysql_errno($db_link));

		if(false === mysql_query('start transaction', $db_link))
			throw new Exception(mysql_error($db_link), mysql_errno($db_link));

		if(false === ($result = mysql_query(sprintf('select * from db_mutex where mutex_name = \'%s\' FOR UPDATE', mysql_real_escape_string($mutex_name, $db_link)))))
			throw new Exception(mysql_error($db_link), mysql_errno($db_link));

		if(!(false !== ($row = mysql_fetch_assoc($result)) && $row['mutex_name'] == $mutex_name))
			throw new Exception('DBMutex: '. $mutex_name . ' not registered in the table.');
	}

	public function release()
	{
		$db_link = $this->db_link;

		mysql_query('commit', $db_link);
		mysql_close($db_link);
	}

	public function __destruct()
	{
		if ($this->db_link) {
			$this->release();
		}
	}
}
