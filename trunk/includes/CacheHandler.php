<?php

require_once(__DIR__ . '/includes.php');

abstract class CacheHandler {
	protected $dataSource = null;
	protected $timestamp = null;

	abstract public function saveToCache(&$date, &$price, &$data);
	abstract public function getFromCache(&$date, &$price, &$data);
	abstract public function queryCache($dataSourceKeyword, $dataKeyword);
	abstract public function deleteFromCache($timestamp, $dataSource);
}
