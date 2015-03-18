<?php
/*
 * Sat Feb 13 19:36:18 MSK 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

$cache = new MMCReliableCache01(
	"memcached",
	array(
		"pool" => array(
			array('127.0.0.1', 11211, 1),
//			array('mem2.domain.com', 11211, 1)
		),
		"catch_not_found" => true
	)
);

var_dump($cache);
/*
$o = array('some' => 'data');

$cache->save("key0", $o);
$o2 = array('another' => 'data');

try {
	$o = $cache->get("key0");
} catch (ObjectNotFoundException $e) {
	$o = null;
}

$cache->save("key1", $o2);
try {
	$c = $cache->getMulti("key0", "key1");
	$o = $c["key0"];
	$o2 = $c["key1"];
} catch (ObjectNotFoundException $e) {

}
*/
