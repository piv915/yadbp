<?php
/**
 * Thu Apr 15 21:35:54 MSD 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

function __var_desc($var_dump) {
	$_s = array('/\s{0,1}({|=>)\n\s*/', '/\s*\n\s+(\[)/', '/$\s*(})/m');
	$_r = array(' $1 ', ', $1', ' $1');
	return substr(preg_replace($_s,$_r,$var_dump), 0, -1);
}

function __print_var(&$var) {
	ob_start(); ob_start("__var_desc"); var_dump($var); ob_end_flush();
	return ob_get_clean();
}

function log_var(&$var, $name = null)
{
	$message = 'VARIABLE: ' . ($name  ? '['.$name.'] ' : '') . __print_var($var);
	error_log($message);
}

function log_message($text)
{
	error_log($text);
}

?>
