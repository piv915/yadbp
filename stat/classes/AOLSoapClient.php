<?php

class MyProjectSoapClient extends SoapClient {

	public function __construct($wsdl, $options=array()) {
		parent::SoapClient($wsdl, $options);
	}

	public function __call($function_name, $arguments) {
		try {
			return parent::__call($function_name, $arguments);
		} catch (SoapFault $e) {
			if('SOAP-ENV' == substr($e->faultcode, 0, 8)) {
				$class_name = $e->detail;
				throw new $class_name($e->getMessage());
			} else
				throw $e;
		}
	}
}

?>
