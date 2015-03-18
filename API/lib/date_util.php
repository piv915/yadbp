<?php
/**
 * Thu Apr 15 21:33:48 MSD 2010
 * Author: Vasiliy Ivanovich
 *
 * $Id$
 *
 */

function date_plus($period, $timestamp=null) {

	if(is_null($timestamp))
		$timestamp = time();

	$period = strtolower(trim($period));
	if(!preg_match('#^(\d+)(d|w|m|y|h|i|s)$#', $period, $m)) {
		throw new Exception("Invalid period: [$period]");
	}

	$units = array(
		'd' => 'day',
		'w' => 'week',
		'm' => 'month',
		'y' => 'year',
		'h' => 'hour',
		'i' => 'min',
		's' => 'sec'
	);
	$reltime = '+'.$m[1].' '.$units[$m[2]];
	$result = strtotime($reltime, $timestamp);
	if(false === $result) {
		throw new Exception("strtotime can't process args ['$reltime', $timestamp]");
	}
	return $result;
}

function date_minus($period, $timestamp=null) {

	if(is_null($timestamp))
		$timestamp = time();

	$period = strtolower(trim($period));
	if(!preg_match('#^(\d+)(d|w|m|y|h|i|s)$#', $period, $m)) {
		throw new Exception("Invalid period: [$period]");
	}

	$units = array(
		'd' => 'day',
		'w' => 'week',
		'm' => 'month',
		'y' => 'year',
		'h' => 'hour',
		'i' => 'min',
		's' => 'sec'
	);
	$reltime = '-'.$m[1].' '.$units[$m[2]];
	$result = strtotime($reltime, $timestamp);
	if(false === $result) {
		throw new Exception("strtotime can't process args ['$reltime', $timestamp]");
	}
	return $result;
}

function date_length($period) {
	$now = time();
	$future = date_plus($period);
	return ($future-$now);
}


?>
