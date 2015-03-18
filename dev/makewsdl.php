<?php
/**
 * Fri Oct 22 17:36:25 MSD 2010
 * Author: Vasiliy.Ivanovich@gmail.com
 *
 * $Id$
 *
 */


@header("Content-Type: text/html");

ini_set('include_path', '.:' . $_SERVER['DOCUMENT_ROOT'] . '/include/');
ini_set('display_errors', 'Off');
error_reporting(E_ALL|E_NOTICE);

require_once('config.php');
require_once('tools/logger.php');
__autoload('IMySQL');

define('DEVCONTEXT', 1);

if(!empty($_POST)) {
	// создание WSDL
	$result = array();

	$class = preg_replace('#[^_a-z0-9]#i', '', $_POST['class']);
	$wsdl_file = preg_replace('#[^_a-z0-9]#i', '', $_POST['wsdl_file']);

	$file = $_SERVER['DOCUMENT_ROOT'] . '/webservice/' . $class . '.php';
	if(!file_exists($file)) {
		$result[] = "File $file not found.";
	}
	else {
		try {
			//print $file;
			$included = include_once($file);
			if(!$included) {
				$result[] = "Can't include $file";
			}
			else {

				$wsdl = new WSDLDocument($class, 'http://billing.SiteName.ru/webservice/' . $class . '.php');
				$doc = $wsdl->saveXML();

				$file_url = '/wsdl/'.$wsdl_file.'.wsdl';
				$saved = file_put_contents($_SERVER['DOCUMENT_ROOT'].$file_url, $doc);
				if(!$saved) {
					$result[] = "Can't save $file_url";
				}
				else {
					$result[] = 'Document saved: <iframe src="'.$file_url.'" style="width:50%;height:400px;"></iframe>';

					$sc = new SoapClient($_SERVER['DOCUMENT_ROOT'].$file_url);
					$func = $sc->__getFunctions();
					ob_start();
					print_r($func);
					$c = ob_get_clean();
					$result[] = 'Loaded functions: <pre>'.$c.'</pre>';
				}

			}

		} catch (WSDLDocumentException $e) {
			$result[] = 'exception';//$e->getMessage();
		}
	}

	$result = join("<br/>", $result);
}
?>
<html>
<head>
<meta http-equiv="Content-Language" content="en" />
<meta name="GENERATOR" content="Zend Studio" />
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<title>Make WSDL</title>
</head>
<body bgcolor="#FFFFFF" text="#000000" link="#FF9966" vlink="#FF9966" alink="#FFCC99">
<form method="post">
	Class: <input type="text" name="class" value="WS_" /> <br />
	WSDL File: <input type="text" name="wsdl_file" value="" /> <br />
	<input type="submit" value="Make!" />
</form>
<?php echo isset($result) ? $result : ""; ?>
</body>
</html>
