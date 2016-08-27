<?php

require_once(__DIR__ . '/includes.php');

abstract class CacheHandler {
	protected $dataSource = null;
	protected $timestamp = null;

	abstract public function saveToCache($venue, $data, $price);
	abstract public function getFromCache($venue, &$changed, &$data, &$price);
	abstract public function updateCache($venue, $data, $price);
	abstract public function deleteCache($venue);
	abstract public function queryCache($venueKeyword, $dataKeyword);
	abstract public function get_stats();
}
