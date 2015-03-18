<?php

class Curl {

	private $url;
	private $method;
	private $ch;
	private $result;
	private $cacert;
	private $verify = false;
	
	public function __construct() {
		$this->ch = curl_init($this->url);
		if(!is_resource($this->ch))
			throw new Exception("curl_init failed");
	}
	
	private function doRequest($method, $url, $vars=null) {
		
		if(!preg_match("|^http(s)?://|", $url))
			throw new Exception("Invalid URL: [$url]");
			
		$ssl = (substr($url, 0, 5) == 'https') ? true : false;

		// не выдавать заголовки
		curl_setopt($this->ch, CURLOPT_HEADER, 0);
		// не ходить по перенаправлениям
		curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, 0);
		// При установке этого параметра в ненулевое значение, получение HTTP кода более 300 считается ошибкой
		curl_setopt($this->ch, CURLOPT_FAILONERROR, 1);
		// не выдавать сообщений от библиотеки
//		curl_setopt($this->ch, CURLOPT_MUTE, 1);
		// тайм-аут
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 20);
		
		curl_setopt($this->ch, CURLOPT_URL, $url);
		
		if($method == 'post') {
			if(!is_array($vars)) {
				$body = (string)$vars;
			} else {
				$pairs = array();
				foreach ($vars as $name => $value)
					$pairs[] = urlencode($name) . '=' . urlencode($value);
				$body = join('&', $pairs);
			}
			curl_setopt($this->ch, CURLOPT_POST, 1);
			curl_setopt($this->ch, CURLOPT_POSTFIELDS, $body);
		}
		
		if($this->verify && $ssl) {
			$cacert =& $this->cacert;
			if(file_exists($cacert) && is_readable($cacert)) {
				curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, 1);
				curl_setopt($this->ch, CURLOPT_CAINFO, $cacert);
			} else 
				throw new Exception("CA cert [$cacert] not found or not readable");
		}
		
		$this->result = '';
		ob_start();
		curl_exec($this->ch);
		$this->result = ob_get_clean();
		
		if(curl_errno($this->ch) != 0) {

			throw new Exception("curl_exec: " . curl_error($this->ch));
		}
	}
	
	public function get($url) {
		$this->doRequest('get', $url);
	}
	
	public function post($url, $params) {
		$this->doRequest('post', $url, $params);
	}
	
	public function result() {
		return (string)$this->result;
	}
	
	public function verifyPeer($verify, $caCert=null) {
		$this->verify = (bool)$verify;
		if($verify) {
			$this->cacert = $caCert;
		}
	}
	
	public function __destruct() {
		if(is_resource($this->ch))
			curl_close($this->ch);
	}
	
}

?>