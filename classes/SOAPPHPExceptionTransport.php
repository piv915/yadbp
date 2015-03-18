<?php

class SOAPPHPExceptionTransport {

	public $message;
	public $code;
	public $class;
	public $trace;
	public $exception;
	
	public function __construct($e) {
		
		$this->message = $e->getMessage();
		$this->code = $e->getCode();
		$this->class = get_class($e);
		$this->trace = $e->getTraceAsString();
		$this->exception = $e;
	}
	
}

?>