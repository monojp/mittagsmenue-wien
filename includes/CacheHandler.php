<?php

require_once(__DIR__ . '/includes.php');

abstract class CacheHandler {
	protected $dataSource = null;
	protected $timestamp = null;

	abstract public function saveToCache($dataSource, $date, $price, $data);
	abstract public function getFromCache($dataSource, &$date, &$price, &$data);
	abstract public function updateCache($dataSource, $date, $price, $data);
	abstract public function queryCache($dataSourceKeyword, $dataKeyword);
	abstract public function get_stats();
}
