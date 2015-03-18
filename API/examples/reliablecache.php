<?php
/**
 * Sat Feb 06 03:13:24 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */
ini_set('display_errors', 'On');
error_reporting(E_ALL);

require("../IReliableCache.php");
require("../impl/MMCReliable.php");
require("../exceptions.php");
require("../enums.php");


print "<pre>";

$cache = new MMCReliable(
	array(
		"pool" => array(
			array('localhost', 11211, 1),
//			array('mem2.domain.com', 11211, 1)
		),
		"catch_not_found" => true
	)
);

var_dump($cache);

//$o = $cache->get('KUKU');
var_dump($o);

$s = array(
	'balance' => 476.28,
	'services' => array(
		1001 => array(
			'enabled' => true,
			'expires' => 1266290254
		),
	)
);
$cache->save("KUKU2", $s, 10, true);

$g = $cache->get("KUKU2");
var_dump($g);
