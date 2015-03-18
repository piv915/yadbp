<?php
/**
 * Wed Sep 08 13:37:59 MSD 2010
 * Author: Vasiliy.Ivanovich@gmail.com
 *
 * $Id$
 *
 */

class WhoisConnector {

	private $relay_host = null;
	private $relay_port = 43;

	private $socket = null;
	private $connect_timeout = 1;
	private $connect_retries = 3;
	private $stream_timeout  = 5;
	private $packet_length = 1500;

	public function __construct() {
//		$this->relay_host = "whois.SiteName.ru";
		$this->relay_host = "10.0.20.8";
	}

	/*
	 * Returns a string, with new-lines (\n) converted to non-breaking spaces (&lt;BR&gt;),
	 * with details for the domain specified by $domain.
	 * @access public
	 * @param string $domain the domain to lookup, excluding http:// and www
	 * @return string the results of the whois
	 */
	public function lookup($domain)
	{
		$result = "";
		$parts  = array();
		$host   = "";

//		 .tv don't allow access to their whois
//		if (strstr($domain,".tv"))
//		{
//			$result = "'.tv' domain names require you to have an account to do whois searches.";
//		 New domains fix (half work, half don't)
//		} elseif (strstr($domain,".name") || strstr($domain,".pro") >0){
//			$result = ".name,.pro require you to have an account to do whois searches.";
//		} else{
			if (empty($this->relay_host))
			{
				$parts    = explode(".",$domain);
				$testhost = $parts[sizeof($parts)-1];
				$whoisserver   = $testhost . ".whois-servers.net";
				$this->relay_host     = gethostbyname($whoisserver);
				$this->relay_host     = gethostbyaddr($this->host);

				if ($this->relay_host == $testhost)
				{
					$this->relay_host = "whois.internic.net";
				}
				flush();
			}

			$errno = $errstr = null;
			$tv_sec = floor($this->stream_timeout);
			$tv_usec = intval(1000000 * ($this->stream_timeout - $tv_sec));

			$retry = 0;
			$socket = null;

			do {
				$socket = stream_socket_client($this->relay_host.':'.$this->relay_port, $errno, $errstr, $this->connect_timeout);
				$retry++;
			}
			while (!is_resource($socket)&& $retry < $this->connect_retries);

			if(is_resource($socket))
			{
				stream_set_timeout($socket, $tv_sec, $tv_usec);
				stream_set_blocking  ( $socket  , 0  );

				$this->socket =& $socket;

				$start = time();
				fputs($socket, $domain."\015\012");
				while (!feof($socket))
				{
					$result .= fgets($socket,128);
					$info = stream_get_meta_data($socket);
					if($info['timed_out']) {
						trigger_error("socket timed out", E_USER_WARNING);
						break;
					}
					if(time()-$start >= $this->stream_timeout) {
						trigger_error("socket timed out", E_USER_WARNING);
						break;
					}
				}
				fclose($socket);

			}
			else
			{
				trigger_error($errstr.' ('.$errno.')', E_USER_WARNING);
			}

//			$whoisSocket = fsockopen($this->relay_host,43, $errno, $errstr, $this->connect_timeout);
//
//			if ($whoisSocket)
//			{
//
//				fputs($whoisSocket, $domain."\015\012");
//				while (!feof($whoisSocket))
//				{
//					$result .= fgets($whoisSocket,128) . "<br>";
//				}
//				fclose($whoisSocket);
//			}
//			else {
//				print "$errno: $errstr";
//			}
//		}
		return $result;
	}

}
